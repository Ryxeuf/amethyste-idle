// Configuration centralisee pour les scenarios de charge k6.
//
// Les variables d'environnement suivantes peuvent etre surchargees au runtime :
//   - BASE_URL : URL racine du serveur a tester (defaut: http://localhost)
//   - VUS : nombre d'utilisateurs virtuels (defaut: 50)
//   - DURATION : duree totale du test (defaut: 1m)
//   - RAMP_UP : duree de montee en charge (defaut: 30s)
//   - RAMP_DOWN : duree de descente (defaut: 15s)
//   - THINK_TIME_MIN : attente min entre requetes en secondes (defaut: 1)
//   - THINK_TIME_MAX : attente max entre requetes en secondes (defaut: 4)
//
// Exemple :
//   BASE_URL=https://amethyste.ryxeuf.fr VUS=100 DURATION=5m \
//     k6 run scripts/load-test/scenarios/guest-browsing.js

export const BASE_URL = __ENV.BASE_URL || 'http://localhost';

const parsePositiveInt = (value, fallback) => {
    const parsed = parseInt(value, 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
};

export const VUS = parsePositiveInt(__ENV.VUS, 50);
export const DURATION = __ENV.DURATION || '1m';
export const RAMP_UP = __ENV.RAMP_UP || '30s';
export const RAMP_DOWN = __ENV.RAMP_DOWN || '15s';

export const THINK_TIME_MIN = parsePositiveInt(__ENV.THINK_TIME_MIN, 1);
export const THINK_TIME_MAX = parsePositiveInt(__ENV.THINK_TIME_MAX, 4);

// Seuils globaux (thresholds k6). Un test echoue si l'une de ces conditions
// n'est pas respectee. Adaptez ces valeurs au profil cible du serveur.
export const DEFAULT_THRESHOLDS = {
    // P95 sous 800ms, P99 sous 2s sur l'ensemble des requetes HTTP.
    http_req_duration: ['p(95)<800', 'p(99)<2000'],
    // Moins de 1% de requetes en erreur.
    http_req_failed: ['rate<0.01'],
    // Au moins 95% des checks applicatifs passent.
    checks: ['rate>0.95'],
};

// Options par defaut pour un test "ramp-up -> plateau -> ramp-down".
export function rampingOptions(extraOptions = {}) {
    return {
        scenarios: {
            default: {
                executor: 'ramping-vus',
                startVUs: 0,
                stages: [
                    { duration: RAMP_UP, target: VUS },
                    { duration: DURATION, target: VUS },
                    { duration: RAMP_DOWN, target: 0 },
                ],
                gracefulRampDown: '15s',
            },
        },
        thresholds: { ...DEFAULT_THRESHOLDS, ...(extraOptions.thresholds || {}) },
        summaryTrendStats: ['min', 'avg', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
        userAgent: 'amethyste-idle-k6/1.0',
        ...extraOptions,
    };
}
