#!/usr/bin/env bash
# Full-suite regression guard: every failing test must already be listed in
# tests/known-failures.txt. Any unlisted failure fails this script.
#
# Harness hardening (2026-09-29):
#   - phpunit.xml force="true" pins every behaviour-relevant variable, so a
#     leaking shell environment cannot change test behaviour relative to CI.
#   - This script ABORTS if any phpunit.xml <env>/<server> entry WITHOUT
#     force="true" is present in the invoking environment: such a value would
#     override the harness and diverge from CI.
#   - A gitignored tests/known-failures.local.txt, when present locally, is
#     honoured as an extra ignore set; CI never sees that file.
#   - Stale entries FAIL: a test listed as known-failing that now passes must
#     be removed from the list in the same change (CI check).
#
# Usage: bash scripts/test-guard.sh
# Requires: vendor/bin/phpunit, python3.
set -u

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
KNOWN="$ROOT/tests/known-failures.txt"
KNOWN_LOCAL="$ROOT/tests/known-failures.local.txt"
# Template ends in Xs: BSD mktemp (macOS) does not substitute Xs before a suffix.
JUNIT="$(mktemp "${TMPDIR:-/tmp}/test-guard-junit.XXXXXX")"
trap 'rm -f "$JUNIT"' EXIT

cd "$ROOT"

# --- Preflight: refuse to run when unforced phpunit.xml vars are shadowed ---
UNFORCED="$(python3 - <<'PY'
import xml.etree.ElementTree as ET
try:
    root = ET.parse('phpunit.xml').getroot()
except Exception:
    raise SystemExit
names = []
for el in root.iter():
    if el.tag in ('env', 'server'):
        name = el.get('name')
        if name and el.get('force') != 'true' and name not in names:
            names.append(name)
print('\n'.join(names))
PY
)"
if [ -n "$UNFORCED" ]; then
    LEAKED="$(comm -12 <(printf '%s\n' "$UNFORCED" | sort -u) <(printenv | cut -d= -f1 | sort -u) || true)"
    if [ -n "$LEAKED" ]; then
        echo "[test-guard] ABORT: these phpunit.xml variables are NOT forced but ARE set in this shell;" >&2
        echo "[test-guard] they would override the harness and change behaviour vs CI. Unset them here" >&2
        echo "[test-guard] or pin them with force=\"true\" in phpunit.xml:" >&2
        printf '%s\n' "$LEAKED" | sed 's/^/  /' >&2
        exit 3
    fi
fi

vendor/bin/phpunit --log-junit "$JUNIT" >/dev/null 2>&1 || true

if [ ! -s "$JUNIT" ]; then
    echo "[test-guard] ERROR: phpunit did not produce $JUNIT" >&2
    exit 2
fi

python3 "$ROOT/scripts/test-guard-compare.py" "$JUNIT" "$KNOWN" "$KNOWN_LOCAL"
