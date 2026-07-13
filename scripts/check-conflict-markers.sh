#!/bin/bash
# SPDX-License-Identifier: MIT
# check-conflict-markers.sh
#
# Scans the codebase for unresolved git merge/stash conflict markers:
#   <<<<<<< , ======= , >>>>>>>
#
# Exit codes:
#   0 — no markers found
#   1 — markers found (prints file list and paths)
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
EXIT_CODE=0

# Directories to scan (relative to project root)
SCAN_DIRS=("app" "resources" "routes" "config" "database" "tests")

# Patterns to search for (full-line conflict markers only, not legitimate === operators)
PATTERNS=(
  '<<<<<<< '
  '||||||| '
  '>>>>>>> '
)

echo "=== Conflict Marker Check ==="
echo "Scanning directories: ${SCAN_DIRS[*]}"
echo ""

for dir in "${SCAN_DIRS[@]}"; do
  TARGET="$ROOT_DIR/$dir"
  if [ ! -d "$TARGET" ]; then
    continue
  fi

  for pattern in "${PATTERNS[@]}"; do
    # Use grep with word-boundary-style anchoring — conflict markers always start at beginning of line
    RESULTS=$(grep -rln "$pattern" \
      --include='*.php' \
      --include='*.blade.php' \
      --include='*.js' \
      --include='*.ts' \
      --include='*.vue' \
      --include='*.css' \
      --include='*.scss' \
      --include='*.json' \
      --include='*.yml' \
      --include='*.yaml' \
      --include='*.md' \
      --include='*.sh' \
      --include='*.env*' \
      --include='*.txt' \
      --include='*.xml' \
      --include='*.html' \
      --include='*.neon' \
      "$TARGET" 2>/dev/null || true)

    if [ -n "$RESULTS" ]; then
      echo "FAILED: Found '$pattern' markers in:"
      echo "$RESULTS" | while IFS= read -r file; do
        echo "  - $file"
      done
      EXIT_CODE=1
    fi
  done
done

echo ""
if [ "$EXIT_CODE" -eq 0 ]; then
  echo "Result: PASS — no conflict markers found."
else
  echo "Result: FAIL — conflict markers detected. Resolve them before merging or deploying."
fi
exit "$EXIT_CODE"
