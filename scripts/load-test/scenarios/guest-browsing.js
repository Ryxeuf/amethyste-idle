// Scenario k6 — navigation anonyme (guest).
//
// Objectif : simuler des visiteurs non authentifies qui parcourent les pages
// publiques (home, login, register, demo) et les endpoints de monitoring
// (/health, /metrics). Aucun credential requis, aucune donnee persistee.
//
// Utilisation :
//   k6 run scripts/load-test/scenarios/guest-browsing.js
//   BASE_URL=https://amethyste.ryxeuf.fr VUS=100 DURATION=5m \
//     k6 run scripts/load-test/scenarios/guest-browsing.js
//
// Ce scenario vise a identifier les goulots d'etranglement sur le rendu Twig
// de la home, l'assetmapper, et la collecte Prometheus (/metrics fait des
// requetes Doctrine sur Player/Fight/Mob). Il ne couvre pas les routes /game
// ni /api/ qui necessitent une session.

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import {
    BASE_URL,
    THINK_TIME_MAX,
    THINK_TIME_MIN,
    rampingOptions,
} from '../config.js';

export const options = rampingOptions({
    tags: { scenario: 'guest-browsing' },
});

// Metriques custom pour separer les pages Twig des endpoints de monitoring.
const htmlPageFailRate = new Rate('html_page_fail');
const metricsLatency = new Trend('metrics_endpoint_latency', true);

const htmlHeaders = {
    Accept: 'text/html,application/xhtml+xml',
};

function visitHtml(name, path) {
    const res = http.get(`${BASE_URL}${path}`, {
        headers: htmlHeaders,
        tags: { endpoint: name },
    });
    const ok = check(res, {
        [`${name} status 200`]: (r) => r.status === 200,
        [`${name} returns HTML`]: (r) =>
            (r.headers['Content-Type'] || '').includes('text/html'),
        [`${name} body not empty`]: (r) => r.body && r.body.length > 500,
    });
    htmlPageFailRate.add(!ok);
    return res;
}

function randomThink() {
    const range = Math.max(0, THINK_TIME_MAX - THINK_TIME_MIN);
    const delay = THINK_TIME_MIN + Math.random() * range;
    sleep(delay);
}

export default function () {
    group('landing', () => {
        visitHtml('home', '/');
        randomThink();
    });

    group('auth-pages', () => {
        visitHtml('login', '/login');
        randomThink();

        visitHtml('register', '/register');
        randomThink();
    });

    group('demo', () => {
        visitHtml('demo', '/demo');
        randomThink();
    });

    group('monitoring', () => {
        const health = http.get(`${BASE_URL}/health`, {
            tags: { endpoint: 'health' },
        });
        check(health, {
            'health status 200 or 503': (r) =>
                r.status === 200 || r.status === 503,
            'health returns JSON': (r) =>
                (r.headers['Content-Type'] || '').includes('application/json'),
        });

        const metrics = http.get(`${BASE_URL}/metrics`, {
            tags: { endpoint: 'metrics' },
        });
        metricsLatency.add(metrics.timings.duration);
        check(metrics, {
            'metrics status 200': (r) => r.status === 200,
            'metrics prometheus body': (r) =>
                r.body && r.body.indexOf('# HELP') !== -1,
        });
    });
}

export function handleSummary(data) {
    // Resume lisible sur stdout + export JSON pour les pipelines CI.
    // Le chemin du JSON peut etre override via la variable K6_SUMMARY_EXPORT.
    const exportPath =
        __ENV.K6_SUMMARY_EXPORT || 'scripts/load-test/last-summary.json';
    return {
        stdout: buildTextSummary(data),
        [exportPath]: JSON.stringify(data, null, 2),
    };
}

function buildTextSummary(data) {
    const metrics = data.metrics || {};
    const duration = metrics.http_req_duration && metrics.http_req_duration.values;
    const failed = metrics.http_req_failed && metrics.http_req_failed.values;
    const checksRate = metrics.checks && metrics.checks.values;

    const line = (label, value) => `  ${label.padEnd(32)} ${value}\n`;

    let out = '\nguest-browsing scenario summary\n';
    out += '------------------------------------------------------------\n';
    if (duration) {
        out += line('http_req_duration avg', `${duration.avg.toFixed(1)} ms`);
        out += line('http_req_duration p95', `${duration['p(95)'].toFixed(1)} ms`);
        out += line('http_req_duration p99', `${duration['p(99)'].toFixed(1)} ms`);
    }
    if (failed) {
        out += line('http_req_failed rate', `${(failed.rate * 100).toFixed(2)} %`);
    }
    if (checksRate) {
        out += line('checks pass rate', `${(checksRate.rate * 100).toFixed(2)} %`);
    }
    out += '------------------------------------------------------------\n';
    return out;
}
