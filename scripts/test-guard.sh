#!/usr/bin/env bash
# Full-suite regression guard: every failing test must already be listed in
# tests/known-failures.txt. Any unlisted failure fails this script.
#
# Usage: bash scripts/test-guard.sh
# Requires: vendor/bin/phpunit, python3.
set -u

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
KNOWN="$ROOT/tests/known-failures.txt"
# Template ends in Xs: BSD mktemp (macOS) does not substitute Xs before a suffix.
JUNIT="$(mktemp "${TMPDIR:-/tmp}/test-guard-junit.XXXXXX")"
trap 'rm -f "$JUNIT"' EXIT

cd "$ROOT"
vendor/bin/phpunit --log-junit "$JUNIT" >/dev/null 2>&1 || true

if [ ! -s "$JUNIT" ]; then
    echo "[test-guard] ERROR: phpunit did not produce $JUNIT" >&2
    exit 2
fi

python3 - "$JUNIT" "$KNOWN" <<'PY'
import sys, xml.etree.ElementTree as ET

junit_path, known_path = sys.argv[1], sys.argv[2]
root = ET.parse(junit_path).getroot()

current = set()
for case in root.iter('testcase'):
    if case.find('failure') is not None or case.find('error') is not None:
        cls = (case.get('classname') or '').replace('.', '\\')
        meth = case.get('name') or ''
        current.add(f'{cls}::{meth}')

known = {
    line.strip()
    for line in open(known_path, encoding='utf-8')
    if line.strip() and not line.strip().startswith('#')
}

new = current - known
recovered = known - current

if new:
    print(f'[test-guard] FAIL: {len(new)} new failing test(s) not in tests/known-failures.txt:')
    for name in sorted(new):
        print(f'  {name}')
    print(f'[test-guard] baseline failing: {len(known)}, current failing: {len(current)}')
    sys.exit(1)

print(f'[test-guard] OK: {len(current)} failing test(s), all accounted for in tests/known-failures.txt '
      f'({len(recovered)} previously-known failures now passing).')
PY
