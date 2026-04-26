#!/usr/bin/env bash
#
# Lance les 4 scenarios k6 sequentiellement et agrege les resultats.
#
# Utilisation :
#   ./scripts/load-test/run-all.sh                         # defauts (BASE_URL=http://localhost, VUS=50)
#   BASE_URL=https://staging.amethyste.best VUS=200 \
#     DURATION=5m RAMP_UP=1m RAMP_DOWN=30s \
#     TEST_CREDENTIALS_FILE=scripts/load-test/credentials.json \
#     ./scripts/load-test/run-all.sh                       # cible Sprint 12
#
# Pre-requis : k6 >= 0.50 dans le PATH (cf. scripts/load-test/README.md).
# Pour authenticated-gameplay : TEST_USER_EMAIL+TEST_USER_PASSWORD ou
# TEST_CREDENTIALS_FILE (sinon le scenario est SKIPPE avec un avertissement).
#
# Sortie : code 0 si tous les scenarios passent leurs thresholds, sinon
# code != 0 (= nombre de scenarios echoues, max 4).

set -u
set -o pipefail

cd "$(dirname "$0")"

SCENARIOS=(
    "guest-browsing.js"
    "metrics-stress.js"
    "mercure-streaming.js"
    "authenticated-gameplay.js"
)

REQUIRES_AUTH=("authenticated-gameplay.js")

if ! command -v k6 >/dev/null 2>&1; then
    echo "ERROR: k6 not found in PATH (install: https://grafana.com/docs/k6/latest/set-up/install-k6/)" >&2
    exit 127
fi

failed=0
skipped=0
passed=0
results=()

for scenario in "${SCENARIOS[@]}"; do
    name="${scenario%.js}"
    summary_file="last-summary-${name}.json"
    export K6_SUMMARY_EXPORT="${summary_file}"

    # Skip authenticated-gameplay si aucun credential n'est fourni.
    needs_auth=0
    for required in "${REQUIRES_AUTH[@]}"; do
        if [ "$scenario" = "$required" ]; then needs_auth=1; fi
    done
    if [ "$needs_auth" = "1" ] && [ -z "${TEST_USER_EMAIL:-}" ] && [ -z "${TEST_CREDENTIALS_FILE:-}" ]; then
        echo ""
        echo "=== SKIP: ${name} (TEST_USER_EMAIL ou TEST_CREDENTIALS_FILE requis) ==="
        skipped=$((skipped + 1))
        results+=("SKIP  ${name}")
        continue
    fi

    echo ""
    echo "=== RUN: ${name} ==="
    if k6 run "scenarios/${scenario}"; then
        passed=$((passed + 1))
        results+=("PASS  ${name}")
    else
        failed=$((failed + 1))
        results+=("FAIL  ${name}")
    fi
done

echo ""
echo "============================================================"
echo "Resultat global"
echo "============================================================"
for line in "${results[@]}"; do
    echo "  ${line}"
done
echo "------------------------------------------------------------"
echo "  PASS=${passed} | FAIL=${failed} | SKIP=${skipped}"
echo "============================================================"

exit "${failed}"
