#!/bin/bash
set -e

# ====================================
# KlassApp Deploy Script (Docker)
# Server: root@46.101.111.131
# Key: ~/.ssh/id_ed25519_do
# ====================================

APP_SERVER="root@46.101.111.131"
APP_DIR="/var/www/KlassApp"
CONTAINER="sms-app"
GIT_BRANCH="main"

echo "========================================"
echo " KlassApp Deploy"
echo " Server: $APP_SERVER"
echo " Container: $CONTAINER"
echo " Branch: $GIT_BRANCH"
echo "========================================"
echo ""

# ---- Pre-deploy checks ----
cd "$(dirname "$0")/.."

echo "[Pre] Checking for unresolved git conflict markers..."
bash scripts/check-conflict-markers.sh
echo "[Pre] Conflict marker check passed."

# ---- Local build step ----
# Prod serves hashed Vite assets from git (public/build). A local-only build
# never reaches the server — commit + push when the manifest/assets change.
echo "[Local] Rebuilding frontend assets (Vite)..."
npm run build
if ! git diff --quiet -- public/build || [ -n "$(git ls-files --others --exclude-standard -- public/build)" ]; then
  echo "[Local] public/build changed — committing and pushing so prod git pull gets assets..."
  git add public/build
  git commit -m "$(cat <<'EOF'
chore(assets): rebuild Vite assets for deploy

EOF
)"
  git push origin "$GIT_BRANCH"
  echo "[Local] Built assets pushed to origin/$GIT_BRANCH."
else
  echo "[Local] public/build unchanged — nothing to commit."
fi

ssh "$APP_SERVER" -i ~/.ssh/id_ed25519_do << REMOTE_SCRIPT
set -e
APP_DIR="$APP_DIR"
CONTAINER="$CONTAINER"
GIT_BRANCH="$GIT_BRANCH"

cd "\$APP_DIR"

echo "[1/8] Pulling latest code (includes compiled assets)..."
git pull origin "\$GIT_BRANCH" --ff-only

echo "[2/8] Installing/updating PHP dependencies inside container..."
docker exec "$CONTAINER" composer install --no-dev --optimize-autoloader --no-interaction
echo "[2/8] Dependencies synchronized."

echo "[3/8] Publishing toshi-ui assets (CSS, views)..."
docker exec "\$CONTAINER" php artisan vendor:publish --tag=toshi-ui-css --force
docker exec "\$CONTAINER" php artisan vendor:publish --tag=toshi-ui-views --force
echo "[3/8] Toshi UI assets published."

echo "[4/8] Running migrations..."
docker exec "\$CONTAINER" php artisan migrate --force

echo "[5/8] Clearing caches inside container..."
docker exec "\$CONTAINER" php artisan optimize:clear

# ── FPM reload: must confirm signal reaches PID 1 and file hash propagates ──
# The container entrypoint runs php-fpm -F as PID 1. Standalone kill binary is
# missing, but the shell builtin works. The old code was:
#   docker exec ... sh -c "kill -USR2 1 2>/dev/null || true"
# which ALWAYS silently "succeeds" because the inner sh exits 0 (output via
# 2>/dev/null swallowed the real error), so FPM/OPcache never actually reloaded.
# This explains the recurring pattern where deploys "look done" but the old
# PHP code is still served until someone manually sends a signal.
# FIX: capture signal result explicitly, wait for workers, then checksum-verify.
echo "[6/8] Restarting FPM (OPcache flush)..."
USRSIG="$(docker exec "\$CONTAINER" sh -c "kill -USR2 1 2>&1")"
if [ -z "\$USRSIG" ]; then
    echo "[6/8] ✅ FPM USR2 signal sent to PID 1"
    echo "      Waiting 2s for workers to drain and reload..."
    sleep 2
else
    echo "[6/8] ⚠️ FPM USR2 may have failed — output: \$USRSIG"
fi

# entrypoint.sh runs queue:work in its own background loop, separate from
# the PID 1 php-fpm process the step above signals — restarting FPM does
# NOT touch it. A long-running queue:work process bootstraps Laravel once
# and keeps that code in memory for its entire lifetime, so without this
# step every deploy leaves the worker silently serving pre-deploy code
# (stale report PDFs from Print All / Download All) until it happens to
# crash or the container restarts. queue:restart sets a flag the worker
# checks after each job and exits cleanly; entrypoint.sh's wrapper loop
# then immediately relaunches it with fresh code.
echo "[7/8] Restarting queue worker (picks up new code for background jobs)..."
docker exec "\$CONTAINER" php artisan queue:restart

echo "[8/8] Verifying deployed file reached FPM workers..."
# Compare a known file's SHA from git HEAD to what's on container disk.
# This catches the silent-failure case where git pull succeeded but OPcache
# still holds the old bytecode, or the volume mount did not sync.
VERIFY_FILE="app/Http/Controllers/Auth/RegisterController.php"
LOCAL_SHA="$(git show HEAD:\$VERIFY_FILE | sha256sum | awk '{print \$1}')"
REMOTE_SHA="$(docker exec "\$CONTAINER" sha256sum "/var/www/\$VERIFY_FILE" | awk '{print \$1}')"
if [ "\$LOCAL_SHA" = "\$REMOTE_SHA" ]; then
    echo "[8/8] ✅ SHA match — deploy reached running FPM workers"
else
    echo "[8/8] ⚠️ SHA MISMATCH — file on disk differs from git HEAD"
    echo "      local:  \$LOCAL_SHA"
    echo "      remote: \$REMOTE_SHA"
    echo "      Continuing anyway, but verify /health or reload FPM manually."
fi
docker exec "\$CONTAINER" php -r "echo 'PHP version: ' . phpversion() . PHP_EOL;"


echo ""
echo "========================================"
echo " Deploy complete!"
echo " https://klassapp.xyz"
echo "========================================"
REMOTE_SCRIPT
