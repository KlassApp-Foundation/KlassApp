#!/bin/bash
# SessionStart hook for Claude Code on the web (cloud sessions only).
#
# Installs PHP + Node dependencies and a test-ready .env so PHPUnit, Pint,
# PHPStan and Playwright work without manual setup. Idempotent; the container
# is cached after this completes, so later sessions mostly no-op.
#
# Why --prefer-source: the cloud proxy blocks GitHub's HTTP archive endpoints
# (api.github.com zipballs, codeload, github.com/archive all 403), so Composer
# dist downloads fail. Plain `git` over the proxy works, so every package is
# installed from its git source instead. A few packages are dist-only in
# composer.lock (phpstan/phpstan, whose full repo is too big to mirror-clone
# within Composer's timeout): for those we shallow-fetch the exact locked
# commit, `git archive` it to a local zip, and point a temporary copy of the
# lock at that zip. The original composer.lock is always restored.
set -euo pipefail

if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

cd "$CLAUDE_PROJECT_DIR"
export COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1

# --- PHP dependencies -------------------------------------------------------
if [ ! -f vendor/autoload.php ] || ! cmp -s composer.lock vendor/.session-start-lock; then
  cp composer.lock /tmp/composer.lock.orig
  trap 'cp /tmp/composer.lock.orig composer.lock' EXIT

  php -r '
    $cache = getenv("HOME")."/.cache/klassapp-dist";
    @mkdir($cache, 0777, true);
    $l = json_decode(file_get_contents("composer.lock"), true);
    foreach (["packages", "packages-dev"] as $k) {
      foreach ($l[$k] as &$p) {
        if (! empty($p["source"]) || ! isset($p["dist"]["url"])
            || ! preg_match("#^https://api\.github\.com/repos/([^/]+/[^/]+)/zipball/([0-9a-f]{40})$#", $p["dist"]["url"], $m)) {
          continue;
        }
        $zip = "$cache/".str_replace("/", "-", $m[1])."-{$m[2]}.zip";
        if (! is_file($zip)) {
          $tmp = sys_get_temp_dir()."/ss-".bin2hex(random_bytes(4));
          $cmd = "git init -q ".escapeshellarg($tmp)
            ." && git -C ".escapeshellarg($tmp)." fetch -q --depth 1 https://github.com/{$m[1]}.git {$m[2]}"
            ." && git -C ".escapeshellarg($tmp)." archive --format=zip -o ".escapeshellarg($zip)." FETCH_HEAD";
          passthru($cmd, $rc);
          passthru("rm -rf ".escapeshellarg($tmp));
          if ($rc !== 0) { fwrite(STDERR, "session-start: could not fetch {$p["name"]}\n"); exit(1); }
        }
        $p["dist"] = ["type" => "zip", "url" => $zip, "reference" => $m[2], "shasum" => ""];
        fwrite(STDERR, "session-start: local dist for {$p["name"]} ({$m[2]})\n");
      }
    }
    file_put_contents("composer.lock", json_encode($l, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
  '

  composer install --prefer-source --no-progress --no-scripts
  cp /tmp/composer.lock.orig composer.lock
  trap - EXIT
  composer run-script post-autoload-dump --no-interaction >/dev/null 2>&1 || true
  cp composer.lock vendor/.session-start-lock
fi

# --- .env for local tests ---------------------------------------------------
if [ ! -f .env ]; then
  cp .env.example .env
  php artisan key:generate --force --no-interaction >/dev/null
fi
# .env.example points at S3 with no bucket; Storage::url() then throws and
# avatar/file-URL tests go falsely red. Use the local public disk here.
grep -q '^FILESYSTEM_DISK=' .env || echo 'FILESYSTEM_DISK=public' >> .env

# --- Node dependencies (Vite build, Playwright e2e) -------------------------
# npm ci: installs exactly package-lock.json and never rewrites it (npm install
# did, dirtying the tree).
# Chromium is preinstalled; PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD is set by the VM.
if [ ! -d node_modules ] || [ package-lock.json -nt node_modules/.package-lock.json ]; then
  npm ci --no-audit --no-fund
fi
