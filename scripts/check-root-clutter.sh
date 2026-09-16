#!/usr/bin/env bash
# SPDX-License-Identifier: MIT
# check-root-clutter.sh
#
# Narrow PR gate: fail only when the diff *introduces* new root/repo clutter.
# Does not re-audit files already on the base branch.
#
# Checks newly added paths for:
#   1. Repo-root screenshots / session image dumps
#   2. One-off Playwright-style test scripts outside e2e/
#   3. Typo/duplicate KlassApp brand asset names (klassaplogo* / klassapplogo*)
#   4. New top-level directories outside the established allowlist
#
# Usage:
#   BASE_REF=origin/main bash scripts/check-root-clutter.sh
#   bash scripts/check-root-clutter.sh origin/main
#
# Exit codes:
#   0 — no new disallowed clutter
#   1 — new clutter detected
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

BASE_REF="${1:-${BASE_REF:-}}"
if [ -z "$BASE_REF" ]; then
  if git rev-parse --verify origin/main >/dev/null 2>&1; then
    BASE_REF="origin/main"
  else
    BASE_REF="main"
  fi
fi

if ! git rev-parse --verify "$BASE_REF" >/dev/null 2>&1; then
  echo "ERROR: base ref '$BASE_REF' not found. Fetch it first (e.g. git fetch origin main)."
  exit 1
fi

# Established tracked top-level directories (see AGENTS.md root AI-tool audit).
# Extend this list only when a new top-level dir is intentional and reviewed.
ALLOWED_ROOT_DIRS=".ai .cursor .design-sync .devin .github app bootstrap config database docs docs-site e2e lang packages public resources routes scripts storage tests"

is_allowed_root_dir() {
  case " $ALLOWED_ROOT_DIRS " in
    *" $1 "*) return 0 ;;
    *) return 1 ;;
  esac
}

base_has_dir() {
  # True if $1 is a tree (directory) on BASE_REF.
  git cat-file -t "${BASE_REF}:$1" 2>/dev/null | grep -qx tree
}

echo "=== Root Clutter Guard ==="
echo "Base: $BASE_REF"
echo "Head: $(git rev-parse --short HEAD)"
echo ""

ADDED="$(git diff --name-only --diff-filter=A "${BASE_REF}...HEAD" || true)"
ADDED="$(printf '%s\n' "$ADDED" | sed '/^$/d')"

if [ -z "$ADDED" ]; then
  echo "No newly added paths vs ${BASE_REF}."
  echo "Result: PASS"
  exit 0
fi

COUNT="$(printf '%s\n' "$ADDED" | wc -l | tr -d ' ')"
echo "Newly added paths: ${COUNT}"
EXIT_CODE=0
FAILURES=""
SEEN_NEW_ROOT_DIRS=" "

fail() {
  FAILURES="${FAILURES}
  - $1"
  EXIT_CODE=1
}

while IFS= read -r path; do
  [ -z "$path" ] && continue
  path="${path#./}"
  filename="${path##*/}"
  # Portable lowercase (macOS bash 3.2 has no ${var,,})
  lower_path="$(printf '%s' "$path" | tr '[:upper:]' '[:lower:]')"
  lower_file="$(printf '%s' "$filename" | tr '[:upper:]' '[:lower:]')"

  # --- 1) Repo-root screenshots / session dumps ---
  case "$path" in
    */*) ;;
    *)
      case "$lower_file" in
        *.png|*.jpg|*.jpeg|*.webp|*.gif)
          fail "root screenshot/image: $path (keep under e2e/screenshots/ or docs/; do not commit session dumps at repo root)"
          ;;
        *screenshot*)
          fail "root screenshot artifact: $path"
          ;;
      esac
      ;;
  esac

  # --- 2) One-off test scripts outside e2e/ ---
  case "$path" in
    e2e/*) ;;
    *)
      case "$lower_file" in
        *.cjs)
          fail "Playwright-style script outside e2e/: $path (put verify scripts under e2e/)"
          ;;
      esac
      case "$path" in
        */*) ;;
        *)
          case "$lower_file" in
            test-*.js|test-*.mjs|test-*.ts|e2e-*.js|e2e-*.mjs|e2e-*.ts|verify-*.js|verify-*.mjs|verify-*.ts|*-verify.js|*-verify.mjs|*-verify.ts)
              fail "one-off test script at repo root: $path (use e2e/ or tests/)"
              ;;
          esac
          ;;
      esac
      case "$lower_path" in
        tmp/*)
          case "$lower_file" in
            *.cjs|*.js|*.mjs)
              fail "test script under tmp/: $path (do not commit tmp/ runners)"
              ;;
          esac
          ;;
      esac
      ;;
  esac

  # --- 3) Typo / duplicate brand asset names ---
  case "$lower_file" in
    *klassaplogo*)
      fail "typo brand asset (klassaplogo*): $path (use klassapp-logo-*)"
      ;;
    *klassapplogo*)
      fail "typo brand asset (klassapplogo*): $path (use klassapp-logo-* with hyphen)"
      ;;
  esac

  # --- 4) New top-level directories ---
  case "$path" in
    */*)
      top="${path%%/*}"
      if ! is_allowed_root_dir "$top"; then
        if ! base_has_dir "$top"; then
          case "$SEEN_NEW_ROOT_DIRS" in
            *" $top "*) ;;
            *)
              SEEN_NEW_ROOT_DIRS="${SEEN_NEW_ROOT_DIRS}${top} "
              fail "new top-level directory not on allowlist: ${top}/ (from $path). Established: ${ALLOWED_ROOT_DIRS}"
              ;;
          esac
        fi
      fi
      ;;
  esac
done <<EOF
$ADDED
EOF

echo ""
if [ "$EXIT_CODE" -eq 0 ]; then
  echo "Result: PASS — no new disallowed root clutter."
else
  echo "Result: FAIL — new disallowed clutter introduced:${FAILURES}"
  echo ""
  echo "Fix: move screenshots to e2e/screenshots/ (or leave uncommitted), put Playwright verify scripts under e2e/, use klassapp-logo-* asset names, and do not add ad-hoc top-level directories. See CONTRIBUTING.md."
fi

exit "$EXIT_CODE"
