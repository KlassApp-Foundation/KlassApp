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

echo "[1/7] Pulling latest code (includes compiled assets)..."
git pull origin "\$GIT_BRANCH" --ff-only

echo "[2/7] Installing/updating PHP dependencies inside container..."
docker exec "$CONTAINER" composer install --no-dev --optimize-autoloader --no-interaction
echo "[2/7] Dependencies synchronized."

echo "[3/7] Publishing toshi-ui assets (CSS, views)..."
docker exec "\$CONTAINER" php artisan vendor:publish --tag=toshi-ui-css --force
docker exec "\$CONTAINER" php artisan vendor:publish --tag=toshi-ui-views --force
echo "[3/7] Toshi UI assets published."

echo "[4/7] Running migrations..."
docker exec "\$CONTAINER" php artisan migrate --force

echo "[5/7] Clearing caches inside container..."
docker exec "\$CONTAINER" php artisan optimize:clear

echo "[6/7] Restarting FPM..."
docker exec "\$CONTAINER" sh -c "kill -USR2 1 2>/dev/null || php-fpm -t >/dev/null 2>&1" || true

echo "[7/7] Verifying app is serving..."
sleep 1
docker exec "\$CONTAINER" php -r "echo 'PHP OK: ' . phpversion() . PHP_EOL;"

echo ""
echo "========================================"
echo " Deploy complete!"
echo " https://klassapp.xyz"
echo "========================================"
REMOTE_SCRIPT
