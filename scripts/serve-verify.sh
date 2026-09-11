#!/bin/bash
# Fixed serve-verify.sh — see .ai/rules/walkthrough-verify-d61ca86.md.
# The Aug 10 leak: a buggy version never `cd`'d into the worktree, so
# `php artisan serve` bootstrapped the main repo's .env (klassapp_local)
# instead of the isolated verify DB. Laravel resolves .env by cwd, and
# Dotenv is immutable — pre-existing env vars win over the .env file.
# Therefore BOTH the cd AND the env-strip+pin below are load-bearing:
# remove either and the leak reappears. Do not "simplify" this script.
set -euo pipefail

VW="${VERIFY_WORKTREE:-/Users/mac/projects/KlassApp-walk-d61ca86}"
PORT="${PORT:-8012}"

# 1. Strip any DB_* leaked from the caller's shell/environment.
unset DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD \
      MYSQL_DATABASE MYSQL_HOST MYSQL_PORT REDIS_HOST REDIS_PORT 2>/dev/null || true

# 2. cd into the worktree so .env resolves to ITS file, not the main repo's.
cd "$VW"

# 3. Pin the isolated DB explicitly (immutable Dotenv: exported vars win).
export DB_CONNECTION=mysql
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_DATABASE="${VERIFY_DB:-klassapp_verify_d61ca86}"
export DB_USERNAME=root
export DB_PASSWORD=
export APP_URL="http://127.0.0.1:${PORT}"

echo "[serve-verify] worktree=${VW}"
echo "[serve-verify] cwd=$(pwd)"
echo "[serve-verify] DB_DATABASE=${DB_DATABASE} (isolated verify DB)"
echo "[serve-verify] serving http://127.0.0.1:${PORT}"

exec php artisan serve --host=127.0.0.1 --port="${PORT}"
