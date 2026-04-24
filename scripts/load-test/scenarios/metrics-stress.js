// Scenario k6 — stress de l'endpoint Prometheus `/metrics`.
//
// Objectif : isoler la charge sur la collecte Prometheus, qui declenche
// plusieurs `COUNT()` Doctrine sur les tables `player`, `fight`, `mob`
// (via les collectors de `App\Monitoring\*`). Contrairement a `guest-browsing`
// qui simule une navigation realiste avec think-time, ce scenario tape en
// continu sur `/metrics` pour mesurer la tenue en charge DB pure.
//
// Cas d'usage : detecter les regressions de requetes Doctrine (index manquant,
// N+1, join trop large), identifier la saturation du pool de connexions, et
// mesurer l'impact du volume de Fight/Mob sur la latence de collecte.
//
// Utilisation :
//   k6 run scripts/load-test/scenarios/metrics-stress.js
//   BASE_URL=https://staging.amethyste.best VUS=20 DURATION=3m \
//     k6 run scripts/load-test/scenarios/metrics-stress.js
//
// Ce scenario est intentionnellement agressif (zero think-time). Le limiter
// a un environnement de staging ou dev : ne pas lancer contre la production
// sans coordination avec l'infra.

import http from 'k6/http';
import { check } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import {
    BASE_URL,
    DEFAULT_THRESHOLDS,
    DURATION,
    RAMP_DOWN,
    RAMP_UP,
    VUS,
} from '../config.js';

// Thresholds specifiques : la collecte Prometheus doit rester rapide meme
// sous stress. Si la p95 depasse 500ms, c'est probablement un probleme DB
// (index manquant, COUNT sans WHERE, table qui grossit sans pagination).
const METRICS_THRESHOLDS = {
    ...DEFAULT_THRESHOLDS,
    http_req_duration: ['p(95)<500', 'p(99)<1500'],
    http_req_failed: ['rate<0.005'],
    'metrics_collect_latency': ['p(95)<500', 'p(99)<1500'],
    'metrics_payload_fail': ['rate<0.01'],
};

// VUs par defaut reduits (20 au lieu de 50) car la charge est soutenue
// sans think-time : 20 VUs en zero-wait equivalent a ~500+ VUs realistes.
const STRESS_VUS = Number.isFinite(parseInt(__ENV.VUS, 10))
    ? parseInt(__ENV.VUS, 10)
    : Math.min(20, VUS);

export const options = {
    scenarios: {
        default: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: RAMP_UP, target: STRESS_VUS },
                { duration: DURATION, target: STRESS_VUS },
                { duration: RAMP_DOWN, target: 0 },
            ],
            gracefulRampDown: '15s',
        },
    },
    thresholds: METRICS_THRESHOLDS,
    summaryTrendStats: ['min', 'avg', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
    userAgent: 'amethyste-idle-k6-metrics-stress/1.0',
    tags: { scenario: 'metrics-stress' },
};

const collectLatency = new Trend('metrics_collect_latency', true);
const payloadFailRate = new Rate('metrics_payload_fail');

export default function () {
    const res = http.get(`${BASE_URL}/metrics`, {
        tags: { endpoint: 'metrics' },
    });
    collectLatency.add(res.timings.duration);

    const ok = check(res, {
        'metrics status 200': (r) => r.status === 200,
        'metrics body non-empty': (r) => r.body && r.body.length > 0,
        'metrics prometheus format': (r) =>
            r.body && r.body.indexOf('# HELP') !== -1,
    });
    payloadFailRate.add(!ok);
}

export function handleSummary(data) {
    const exportPath =
        __ENV.K6_SUMMARY_EXPORT ||
        'scripts/load-test/last-summary-metrics-stress.json';
    return {
        stdout: buildTextSummary(data),
        [exportPath]: JSON.stringify(data, null, 2),
    };
}

function buildTextSummary(data) {
    const metrics = data.metrics || {};
    const duration = metrics.http_req_duration && metrics.http_req_duration.values;
    const collect = metrics.metrics_collect_latency && metrics.metrics_collect_latency.values;
    const failed = metrics.http_req_failed && metrics.http_req_failed.values;
    const payloadFail = metrics.metrics_payload_fail && metrics.metrics_payload_fail.values;
    const reqs = metrics.http_reqs && metrics.http_reqs.values;

    const line = (label, value) => `  ${label.padEnd(36)} ${value}\n`;

    let out = '\nmetrics-stress scenario summary\n';
    out += '------------------------------------------------------------\n';
    if (reqs) {
        out += line('http_reqs total', `${reqs.count}`);
        out += line('http_reqs rate', `${reqs.rate.toFixed(1)} /s`);
    }
    if (duration) {
        out += line('http_req_duration avg', `${duration.avg.toFixed(1)} ms`);
        out += line('http_req_duration p95', `${duration['p(95)'].toFixed(1)} ms`);
        out += line('http_req_duration p99', `${duration['p(99)'].toFixed(1)} ms`);
    }
    if (collect) {
        out += line('metrics_collect_latency p95', `${collect['p(95)'].toFixed(1)} ms`);
        out += line('metrics_collect_latency p99', `${collect['p(99)'].toFixed(1)} ms`);
    }
    if (failed) {
        out += line('http_req_failed rate', `${(failed.rate * 100).toFixed(2)} %`);
    }
    if (payloadFail) {
        out += line('metrics_payload_fail rate', `${(payloadFail.rate * 100).toFixed(2)} %`);
    }
    out += '------------------------------------------------------------\n';
    return out;
}
