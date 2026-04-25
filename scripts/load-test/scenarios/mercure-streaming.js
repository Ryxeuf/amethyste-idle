// Scenario k6 — capacite SSE Mercure (`map/move`).
//
// Objectif : mesurer la tenue en charge du hub Mercure integre a FrankenPHP
// (Caddy) en simulant N abonnes simultanes au topic `map/move`. Ce topic est
// le plus actif du jeu : chaque mouvement de joueur ou de mob declenche une
// publication, et chaque client carte (`map_pixi_controller`) maintient une
// `EventSource` ouverte en permanence.
//
// Comme k6 stock ne sait pas decouper un flux SSE evenement par evenement,
// chaque VU ouvre une requete HTTP vers `/.well-known/mercure?topic=map/move`
// avec un timeout egal a SUBSCRIBE_DURATION : le serveur tient la connexion
// ouverte (heartbeats SSE), k6 la coupe au timeout, puis le VU recommence.
// La p95 de connexion + le taux de succes mesurent la capacite reelle a
// accepter et conserver des abonnes concurrents.
//
// Utilisation :
//   k6 run scripts/load-test/scenarios/mercure-streaming.js
//   BASE_URL=https://staging.amethyste.best VUS=100 DURATION=2m \
//     SUBSCRIBE_DURATION=20 \
//     k6 run scripts/load-test/scenarios/mercure-streaming.js
//
// Goulots typiques detectes :
//   - Limite de file descriptors / connexions FrankenPHP
//   - Timeout proxy / load balancer trop court (coupe avant le heartbeat)
//   - Backpressure du hub Mercure quand le nombre d'abonnes monte
//
// Cible Sprint 12 : 200 VUs (= 200 EventSource ouverts) sans degradation.

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import {
    BASE_URL,
    DEFAULT_THRESHOLDS,
    DURATION,
    RAMP_DOWN,
    RAMP_UP,
    VUS,
} from '../config.js';

const parsePositiveInt = (value, fallback) => {
    const parsed = parseInt(value, 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
};

// Duree (en secondes) pendant laquelle chaque VU maintient son abonnement
// SSE ouvert avant de boucler. Defaut 30s : assez long pour qu'un heartbeat
// soit attendu (Mercure envoie un commentaire `:` toutes les ~30s par defaut)
// mais pas au point de masquer un probleme de coupure precoce.
const SUBSCRIBE_DURATION = parsePositiveInt(__ENV.SUBSCRIBE_DURATION, 30);

// Topic SSE cible. `map/move` est le plus actif ; `map/respawn`, `map/avatar`
// ou `event/announce` peuvent etre testes en surchargeant la variable.
const TOPIC = __ENV.MERCURE_TOPIC || 'map/move';

// Marge de tolerance ajoutee au timeout HTTP : evite que k6 ne coupe la
// connexion 1ms avant la fin "officielle" et fausse les checks de duree.
const TIMEOUT_GRACE_SEC = 2;

const STREAM_THRESHOLDS = {
    ...DEFAULT_THRESHOLDS,
    // Ouverture d'un EventSource doit rester rapide meme sous charge.
    'mercure_subscribe_latency': ['p(95)<1000', 'p(99)<3000'],
    'mercure_subscribe_fail': ['rate<0.02'],
    // Le hub doit tenir la connexion sur toute la duree demandee.
    'mercure_hold_duration': [`p(95)>=${(SUBSCRIBE_DURATION - 1) * 1000}`],
};

export const options = {
    scenarios: {
        default: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: RAMP_UP, target: VUS },
                { duration: DURATION, target: VUS },
                { duration: RAMP_DOWN, target: 0 },
            ],
            gracefulRampDown: '30s',
        },
    },
    thresholds: STREAM_THRESHOLDS,
    summaryTrendStats: ['min', 'avg', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
    userAgent: 'amethyste-idle-k6-mercure/1.0',
    tags: { scenario: 'mercure-streaming' },
};

const subscribeLatency = new Trend('mercure_subscribe_latency', true);
const holdDuration = new Trend('mercure_hold_duration', true);
const subscribeFailRate = new Rate('mercure_subscribe_fail');

const subscribeUrl = `${BASE_URL}/.well-known/mercure?topic=${encodeURIComponent(TOPIC)}`;

export default function () {
    const start = Date.now();
    // k6 lit le body en entier avant de rendre la main : on s'appuie sur le
    // timeout pour borner l'iteration. Le serveur repond avec status 200 +
    // Content-Type `text/event-stream` puis tient la connexion ouverte.
    const res = http.get(subscribeUrl, {
        headers: {
            Accept: 'text/event-stream',
            'Cache-Control': 'no-cache',
        },
        timeout: `${SUBSCRIBE_DURATION + TIMEOUT_GRACE_SEC}s`,
        tags: { endpoint: 'mercure_subscribe', topic: TOPIC },
    });
    const elapsedMs = Date.now() - start;
    holdDuration.add(elapsedMs);
    // `timings.waiting` = TTFB cote k6 = temps avant le premier byte SSE
    // (heartbeat ou evenement). C'est notre proxy pour la latence
    // d'etablissement de l'abonnement.
    if (res.timings && Number.isFinite(res.timings.waiting)) {
        subscribeLatency.add(res.timings.waiting);
    }

    // Un timeout est attendu : le hub maintient la connexion -> k6 finit par
    // la couper. On accepte status 200 (lecture partielle) ou 0 (timeout) tant
    // que la duree maintenue couvre bien SUBSCRIBE_DURATION.
    const ok = check(res, {
        'mercure status 0 (timeout) or 200': (r) =>
            r.status === 0 || r.status === 200,
        'mercure content-type event-stream': (r) => {
            const ct = (r.headers || {})['Content-Type'] || '';
            // Status 0 = timeout sans entete recue -> on ne peut pas asserter
            // le content-type, on tolere.
            return r.status === 0 || ct.indexOf('text/event-stream') !== -1;
        },
        'mercure held connection >= duration': () =>
            elapsedMs >= (SUBSCRIBE_DURATION - 1) * 1000,
    });
    subscribeFailRate.add(!ok);

    // Pause minimale pour eviter une boucle de reconnexion serree quand le
    // serveur rejette immediatement (5xx, 401). En regime nominal, l'iteration
    // dure deja SUBSCRIBE_DURATION secondes : ce sleep est un coussin.
    sleep(0.2);
}

export function handleSummary(data) {
    const exportPath =
        __ENV.K6_SUMMARY_EXPORT ||
        'scripts/load-test/last-summary-mercure-streaming.json';
    return {
        stdout: buildTextSummary(data),
        [exportPath]: JSON.stringify(data, null, 2),
    };
}

function buildTextSummary(data) {
    const metrics = data.metrics || {};
    const subscribe = metrics.mercure_subscribe_latency && metrics.mercure_subscribe_latency.values;
    const hold = metrics.mercure_hold_duration && metrics.mercure_hold_duration.values;
    const fail = metrics.mercure_subscribe_fail && metrics.mercure_subscribe_fail.values;
    const reqs = metrics.http_reqs && metrics.http_reqs.values;
    const httpFail = metrics.http_req_failed && metrics.http_req_failed.values;

    const line = (label, value) => `  ${label.padEnd(40)} ${value}\n`;

    let out = '\nmercure-streaming scenario summary\n';
    out += '------------------------------------------------------------\n';
    out += line('topic', TOPIC);
    out += line('subscribe duration target', `${SUBSCRIBE_DURATION}s`);
    if (reqs) {
        out += line('subscriptions opened', `${reqs.count}`);
    }
    if (subscribe) {
        out += line('mercure_subscribe_latency p95', `${subscribe['p(95)'].toFixed(1)} ms`);
        out += line('mercure_subscribe_latency p99', `${subscribe['p(99)'].toFixed(1)} ms`);
    }
    if (hold) {
        out += line('mercure_hold_duration avg', `${hold.avg.toFixed(0)} ms`);
        out += line('mercure_hold_duration p95', `${hold['p(95)'].toFixed(0)} ms`);
    }
    if (fail) {
        out += line('mercure_subscribe_fail rate', `${(fail.rate * 100).toFixed(2)} %`);
    }
    if (httpFail) {
        out += line('http_req_failed rate', `${(httpFail.rate * 100).toFixed(2)} %`);
    }
    out += '------------------------------------------------------------\n';
    return out;
}
