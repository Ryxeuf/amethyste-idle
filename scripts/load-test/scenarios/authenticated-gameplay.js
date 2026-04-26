// Scenario k6 — gameplay authentifie (login + boucle map/inventaire/HdV).
//
// Objectif : simuler des joueurs connectes qui parcourent les pages et API
// protegees (/game, /game/map, /game/inventory, /api/map/config,
// /api/map/cells, /api/map/entities). Couvre la chaine login (CSRF + session
// cookies) ainsi que la boucle classique de chargement de carte cote client.
//
// Utilisation :
//   TEST_USER_EMAIL=tester@amethyste.test TEST_USER_PASSWORD=password \
//     k6 run scripts/load-test/scenarios/authenticated-gameplay.js
//
//   # Pool de comptes (round-robin sur les VUs) :
//   TEST_CREDENTIALS_FILE=scripts/load-test/credentials.json \
//     k6 run scripts/load-test/scenarios/authenticated-gameplay.js
//
// Format du fichier JSON :
//   [{"email": "u1@test", "password": "pw1"}, {"email": "u2@test", "password": "pw2"}]
//
// Prerequis : un (ou plusieurs) compte(s) de test deja provisionnes sur la
// cible. Aucune ecriture de contenu n'est faite : seules des routes GET sont
// appelees apres login. La session est preservee tout au long de l'iteration
// VU (k6 maintient un cookie jar par VU).
//
// Ce scenario complete `guest-browsing.js` (anonyme) et `metrics-stress.js`
// (Prometheus). Il prepare la mesure de l'objectif Sprint 12 :
// 200 joueurs simultanes sans degradation.

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import {
    BASE_URL,
    THINK_TIME_MAX,
    THINK_TIME_MIN,
    rampingOptions,
} from '../config.js';

const CSRF_TOKEN_REGEX =
    /name=["']_csrf_token["']\s+value=["']([^"']+)["']|value=["']([^"']+)["']\s+name=["']_csrf_token["']/i;

const MAP_RADIUS = parseInt(__ENV.MAP_RADIUS || '15', 10);

// Custom metrics : separer chaque etape pour faciliter le diagnostic.
const loginLatency = new Trend('auth_login_latency', true);
const loginFail = new Rate('auth_login_fail');
const authedRequestFail = new Rate('authed_request_fail');
const mapApiLatency = new Trend('authed_map_api_latency', true);

const credentialsPool = loadCredentials();

export const options = rampingOptions({
    tags: { scenario: 'authenticated-gameplay' },
    thresholds: {
        // P95 plus large que guest-browsing : les routes /game et /api/map
        // declenchent plusieurs requetes Doctrine (Player, Map, Mob, Pnj).
        http_req_duration: ['p(95)<1500', 'p(99)<3000'],
        http_req_failed: ['rate<0.02'],
        auth_login_fail: ['rate<0.05'],
        authed_request_fail: ['rate<0.02'],
        auth_login_latency: ['p(95)<2000'],
    },
});

export function setup() {
    if (credentialsPool.length === 0) {
        throw new Error(
            'Aucun credential fourni. Definir TEST_USER_EMAIL + TEST_USER_PASSWORD ' +
                'ou TEST_CREDENTIALS_FILE (JSON array de {email, password}).',
        );
    }
    return { poolSize: credentialsPool.length };
}

export default function (data) {
    const credential = credentialsPool[__VU % credentialsPool.length];

    const loggedIn = group('login', () => doLogin(credential));
    if (!loggedIn) {
        // Inutile de continuer cette iteration : la session n'a pas ete etablie.
        return;
    }

    group('dashboard', () => visitAuthedHtml('dashboard', '/game'));
    randomThink();

    group('map-view', () => {
        visitAuthedHtml('map-view', '/game/map');
        randomThink();

        callMapApi('map-config', '/api/map/config');
        callMapApi('map-cells', `/api/map/cells?x=0&y=0&radius=${MAP_RADIUS}`);
        callMapApi('map-entities', `/api/map/entities?radius=${MAP_RADIUS}`);
    });
    randomThink();

    group('inventory', () => visitAuthedHtml('inventory', '/game/inventory'));
}

function loadCredentials() {
    const file = __ENV.TEST_CREDENTIALS_FILE;
    if (file && file.length > 0) {
        try {
            const raw = open(file);
            const parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) {
                throw new Error('Le fichier doit contenir un tableau JSON.');
            }
            return parsed
                .filter((entry) => entry && entry.email && entry.password)
                .map((entry) => ({
                    email: String(entry.email),
                    password: String(entry.password),
                }));
        } catch (err) {
            throw new Error(
                `Lecture de TEST_CREDENTIALS_FILE=${file} impossible : ${err.message}`,
            );
        }
    }

    const email = __ENV.TEST_USER_EMAIL;
    const password = __ENV.TEST_USER_PASSWORD;
    if (email && password) {
        return [{ email, password }];
    }

    return [];
}

function doLogin(credential) {
    const start = Date.now();
    const loginPage = http.get(`${BASE_URL}/login`, {
        headers: { Accept: 'text/html,application/xhtml+xml' },
        tags: { endpoint: 'login-form' },
        redirects: 0,
    });
    const csrfOk = check(loginPage, {
        'login form status 200': (r) => r.status === 200,
        'login form contains csrf': (r) => CSRF_TOKEN_REGEX.test(r.body || ''),
    });
    if (!csrfOk) {
        loginFail.add(true);
        return false;
    }

    const csrfToken = extractCsrfToken(loginPage.body);
    if (!csrfToken) {
        loginFail.add(true);
        return false;
    }

    const submit = http.post(
        `${BASE_URL}/login`,
        {
            email: credential.email,
            password: credential.password,
            _csrf_token: csrfToken,
        },
        {
            tags: { endpoint: 'login-submit' },
            // Bloquer la redirection : un 302 vers /game ou /game/character/select
            // signe l'authentification reussie. Un 200 (re-render du form) signe
            // l'echec (mauvais credentials, CSRF expire).
            redirects: 0,
        },
    );

    const latency = Date.now() - start;
    loginLatency.add(latency);

    const ok = check(submit, {
        'login redirects (302)': (r) => r.status === 302,
        'redirect targets game or character': (r) => {
            const loc = r.headers['Location'] || '';
            return (
                loc.indexOf('/game') !== -1 ||
                loc.indexOf('/character') !== -1
            );
        },
    });
    loginFail.add(!ok);
    return ok;
}

function extractCsrfToken(body) {
    if (!body) {
        return null;
    }
    const match = body.match(CSRF_TOKEN_REGEX);
    if (!match) {
        return null;
    }
    return match[1] || match[2] || null;
}

function visitAuthedHtml(name, path) {
    const res = http.get(`${BASE_URL}${path}`, {
        headers: { Accept: 'text/html,application/xhtml+xml' },
        tags: { endpoint: name },
    });
    const ok = check(res, {
        [`${name} status 200`]: (r) => r.status === 200,
        [`${name} returns HTML`]: (r) =>
            (r.headers['Content-Type'] || '').includes('text/html'),
        [`${name} not redirected to login`]: (r) =>
            !(r.body || '').includes('name="_csrf_token"'),
    });
    authedRequestFail.add(!ok);
    return res;
}

function callMapApi(name, path) {
    const res = http.get(`${BASE_URL}${path}`, {
        headers: { Accept: 'application/json' },
        tags: { endpoint: name },
    });
    mapApiLatency.add(res.timings.duration);
    const ok = check(res, {
        [`${name} status 200`]: (r) => r.status === 200,
        [`${name} returns JSON`]: (r) =>
            (r.headers['Content-Type'] || '').includes('application/json'),
    });
    authedRequestFail.add(!ok);
    return res;
}

function randomThink() {
    const range = Math.max(0, THINK_TIME_MAX - THINK_TIME_MIN);
    const delay = THINK_TIME_MIN + Math.random() * range;
    sleep(delay);
}

export function handleSummary(data) {
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
    const login = metrics.auth_login_latency && metrics.auth_login_latency.values;
    const loginFailRate = metrics.auth_login_fail && metrics.auth_login_fail.values;
    const mapApi = metrics.authed_map_api_latency && metrics.authed_map_api_latency.values;
    const authedFail = metrics.authed_request_fail && metrics.authed_request_fail.values;

    const line = (label, value) => `  ${label.padEnd(36)} ${value}\n`;

    let out = '\nauthenticated-gameplay scenario summary\n';
    out += '------------------------------------------------------------\n';
    if (duration) {
        out += line('http_req_duration p95', `${duration['p(95)'].toFixed(1)} ms`);
        out += line('http_req_duration p99', `${duration['p(99)'].toFixed(1)} ms`);
    }
    if (failed) {
        out += line('http_req_failed rate', `${(failed.rate * 100).toFixed(2)} %`);
    }
    if (checksRate) {
        out += line('checks pass rate', `${(checksRate.rate * 100).toFixed(2)} %`);
    }
    if (login) {
        out += line('auth_login_latency p95', `${login['p(95)'].toFixed(1)} ms`);
    }
    if (loginFailRate) {
        out += line('auth_login_fail rate', `${(loginFailRate.rate * 100).toFixed(2)} %`);
    }
    if (mapApi) {
        out += line('authed_map_api_latency p95', `${mapApi['p(95)'].toFixed(1)} ms`);
    }
    if (authedFail) {
        out += line('authed_request_fail rate', `${(authedFail.rate * 100).toFixed(2)} %`);
    }
    out += '------------------------------------------------------------\n';
    return out;
}
