#!/usr/bin/env bash
# Starts the Northwind checklist demo at http://localhost:8000.
# The browser address is always http://localhost:8000. Google treats a
# numeric loopback host as a different origin. The Riverside preview
# stays on port 8126 and does not use Google sign-in.
#
# If port 8000 is already taken, this prints the listener and exits.
# It does not stop that process.
#
#   bash scripts/demo/poc-serve.sh
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

listener="$(lsof -nP -iTCP:8000 -sTCP:LISTEN || true)"
if [ -n "$listener" ]; then
  echo "Port 8000 is already in use. Left it running:"
  printf '%s\n' "$listener"
  exit 1
fi

export APP_URL=http://localhost:8000
export GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

php artisan config:clear >/dev/null

echo "Open http://localhost:8000"
exec php artisan serve --host=localhost --port=8000
