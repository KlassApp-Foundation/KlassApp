# Running KlassApp's tests in a Claude Code cloud environment

> Research proposal, 2026-10-05. Nothing here changes the app. Measured on a fresh Ubuntu 24.04 cloud
> session (4 vCPU, 15 GB RAM), repo at `main` @ `d34863c` for the full-suite run and a fresh clone of the
> then-current `main` for the setup-script run. No tokens were used or needed.

## TL;DR

| Question | Answer |
|---|---|
| Can the full suite run in a cloud session? | **Yes.** 2186 tests, 0 failures, 4 skipped, 7 min 16 s on PHP 8.4.26 + SQLite, under `scripts/test-guard.sh`. |
| Extra network domains needed? | **None.** The script below works with only hosts that were already reachable. A faster PHP route (Option B) needs one extra domain. |
| Why did `composer install` fail before? | Three separate causes: PHP 8.3 only, `api.github.com` denied (and rate-limited without a token), and `ext-redis` 5.3.7 conflicting with `symfony/cache` v8.1.1. |
| Is PHP 8.4 actually required to run the tests? | The lock requires it. The suite also passed on PHP 8.3.6 with platform checks ignored (2186 tests, 0 failures, 7 min 23 s), so 8.3 is an acceptable stop-gap but not CI parity (CI uses 8.4). |

## 1. What blocks `composer install` today

1. **PHP version.** Ubuntu 24.04's archive only has PHP 8.3.6. `composer check-platform-reqs --lock` fails only on
   PHP: `spatie/laravel-model-states` needs `^8.4` and ten `symfony/*` v8 packages need `>=8.4.1`.
2. **Extensions.** All required extensions are present in the preinstalled 8.3 (`php -m`). The set that
   `composer.lock` requires is: ctype, curl, dom, exif, fileinfo, filter, gd, hash, iconv, intl, json, libxml,
   mbstring, openssl, pcre, phar, session, simplexml, sodium, tokenizer, xml, xmlreader, xmlwriter, zip, zlib.
   Tests additionally need `pdo_sqlite` (and `sqlite3`). `composer.json` itself only pins `php: ^8.2`.
3. **`ext-redis` conflict.** The preinstalled `ext-redis` is 5.3.7; `symfony/cache` v8.1.1 declares
   `conflict: ext-redis <6.1`, so `composer install` aborts ("cannot be modified by Composer") even with the PHP
   check ignored. Fix: don't install `ext-redis` (the app uses Predis, and the 8.4 build below has none), or use
   `>=6.1`.
4. **GitHub auth.** All 239 locked packages have `dist` URLs of the form
   `https://api.github.com/repos/<o>/<r>/zipball/<sha>`. That host answers **403** from the egress policy, and where it
   is allowed it is capped at 60 unauthenticated requests per hour, which is the usual cause of Composer's "could not
   authenticate against github.com" prompt. `--prefer-source` avoids it but took ~10 min and still failed on one
   package. `codeload.github.com` and `github.com/.../archive` are also 403.

What does work: `git` over `https://github.com/<o>/<r>.git` (smart HTTP) including `git fetch --depth 1 <exact sha>`.
That is the basis of the script: fetch each locked commit over git, `git archive` it to a zip, and install from a
temporary copy of the lock whose dist URLs point at those local zips. Cold, 239 packages fetch in ~28 s and
`composer install` takes ~10 s. The repo's `composer.lock` is restored afterwards (the existing
`.claude/hooks/session-start.sh` uses the same idea but installs `--prefer-source` and still needs PHP 8.4 and a
compatible `ext-redis`).

## 2. Setup script (tested)

Run as root from the repo root. Idempotent. **PHP 8.4 is built from source** from `php-src` over git, because
`ppa.launchpadcontent.net` and `getcomposer.org` are denied. The build takes about 8 minutes on 4 vCPU and should
live in the environment's setup script so it is paid once and cached (confirm the caching behaviour in the
[cloud environments docs](https://code.claude.com/docs/en/cloud-environments)). Re-runs skip it.

What I ran and measured:

- PHP 8.4.26 build (same `configure` flags as below): 8 min 17 s. `php -m` then includes every extension listed above plus
  `pdo_sqlite`, `sqlite3`, `bcmath`, `pcntl`, `posix`.
- Everything after the PHP build (Composer, fetch, install, `.env`) on a fresh clone of `main`, cold zip cache: **44 s**,
  `git status` clean afterwards. `tests/Feature/Navigation`: 27 tests, 127 assertions, OK.
- Full guarded suite on that PHP: 2186 tests, 0 failures, 0 errors, 4 skipped, 7 min 16 s.

```bash
#!/usr/bin/env bash
# KlassApp cloud-session setup: PHP 8.4 + Composer deps + SQLite test env.
# Run as root from the repo root. Idempotent. Needs no tokens and no domains beyond the
# Trusted defaults (github.com git smart-HTTP, repo.packagist.org, archive.ubuntu.com).
set -euo pipefail
export COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1 DEBIAN_FRONTEND=noninteractive
PHP_PREFIX="${PHP_PREFIX:-/opt/php84}"
PHP_TAG="${PHP_TAG:-php-8.4.26}"          # any 8.4.x >= 8.4.1 (symfony/* v8 in composer.lock needs >=8.4.1)
ZIP_CACHE="${ZIP_CACHE:-$HOME/.cache/klassapp-dist}"

# ---- 1. PHP 8.4 (Ubuntu 24.04 ships only 8.3) -------------------------------------------------
if ! "$PHP_PREFIX/bin/php" -r 'exit(PHP_VERSION_ID >= 80401 ? 0 : 1);' 2>/dev/null; then
  apt-get update -qq
  apt-get install -y -qq build-essential autoconf bison re2c pkg-config git unzip \
    libxml2-dev libsqlite3-dev libssl-dev libcurl4-openssl-dev libonig-dev libzip-dev \
    libpng-dev libjpeg-dev libwebp-dev libfreetype-dev libicu-dev libsodium-dev zlib1g-dev
  src="$(mktemp -d)"
  git clone -q --depth 1 --branch "$PHP_TAG" https://github.com/php/php-src.git "$src"
  ( cd "$src" && ./buildconf --force >/dev/null 2>&1 &&
    ./configure --prefix="$PHP_PREFIX" --with-config-file-path="$PHP_PREFIX/etc" \
      --with-config-file-scan-dir="$PHP_PREFIX/etc/conf.d" --disable-all --enable-cli --disable-cgi --disable-phpdbg \
      --enable-ctype --enable-dom --enable-exif --enable-fileinfo --enable-filter --enable-mbstring \
      --enable-pcntl --enable-phar --enable-posix --enable-session --enable-simplexml --enable-tokenizer \
      --enable-xml --enable-xmlreader --enable-xmlwriter --enable-intl --enable-bcmath --enable-sockets \
      --enable-calendar --enable-opcache --enable-gd --with-jpeg --with-webp --with-freetype \
      --with-iconv --with-openssl --with-curl --with-zlib --with-zip --with-sodium --with-libxml \
      --with-mhash --enable-pdo --with-pdo-sqlite --with-sqlite3 >/dev/null &&
    make -j"$(nproc)" >/dev/null && make install >/dev/null )
  rm -rf "$src"
  mkdir -p "$PHP_PREFIX/etc/conf.d"
  printf 'memory_limit=-1\n' > "$PHP_PREFIX/etc/conf.d/99-ci.ini"
fi
export PATH="$PHP_PREFIX/bin:$PATH"
ln -sf "$PHP_PREFIX/bin/php" /usr/local/bin/php84

# ---- 2. Composer (reuse the preinstalled one; else GitHub release asset, NOT getcomposer.org) ----
command -v composer >/dev/null || {
  curl -fsSL https://github.com/composer/composer/releases/download/2.8.12/composer.phar -o /usr/local/bin/composer
  chmod +x /usr/local/bin/composer; }

# ---- 3. composer install without api.github.com ---------------------------------------------
# composer.lock points every dist at api.github.com/.../zipball/<sha>. That host is denied here and,
# unauthenticated, is limited to 60 requests/hour even where allowed, which is the "GitHub
# authentication" prompt. So fetch each locked commit over plain git, zip it, and install from a
# lock copy whose dist URLs are local files. The repo's composer.lock is never modified.
mkdir -p "$ZIP_CACHE"
tmp_lock="$(mktemp)"
php -r '
  [$_, $cache, $out] = $argv;
  $l = json_decode(file_get_contents("composer.lock"), true); $jobs = "";
  foreach (["packages", "packages-dev"] as $k) foreach ($l[$k] as &$p) {
    if (!preg_match("#^https://api\.github\.com/repos/([^/]+/[^/]+)/zipball/([0-9a-f]{40})$#", $p["dist"]["url"] ?? "", $m)) continue;
    $zip = "$cache/" . str_replace("/", "-", $m[1]) . "-{$m[2]}.zip";
    $jobs .= "{$m[1]}\t{$m[2]}\t$zip\n";
    $p["dist"] = ["type" => "zip", "url" => $zip, "reference" => $m[2], "shasum" => ""];
  }
  file_put_contents("$cache/jobs.tsv", $jobs);
  file_put_contents($out, json_encode($l, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
' "$ZIP_CACHE" "$tmp_lock"
fetch_one() {  # owner/repo sha zip
  [ -s "$3" ] && return 0
  local t; t="$(mktemp -d)"
  git init -q "$t"
  for i in 1 2 3; do git -C "$t" fetch -q --depth 1 "https://github.com/$1.git" "$2" 2>/dev/null && break; sleep $((i * 2)); done
  git -C "$t" archive --format=zip -o "$3.tmp" FETCH_HEAD && mv "$3.tmp" "$3" || echo "FAILED $1 $2" >&2
  rm -rf "$t"
}
export -f fetch_one
cut -f1-3 "$ZIP_CACHE/jobs.tsv" | xargs -P 12 -L1 bash -c 'fetch_one "$@"' _
[ "$(cut -f3 "$ZIP_CACHE/jobs.tsv" | xargs -r ls 2>/dev/null | wc -l)" = "$(wc -l < "$ZIP_CACHE/jobs.tsv")" ] \
  || { echo "some package archives failed to fetch" >&2; exit 1; }

# Composer reads composer.lock next to composer.json: swap in the local-dist copy only for the install.
cp composer.lock "$ZIP_CACHE/composer.lock.orig"; trap 'cp "$ZIP_CACHE/composer.lock.orig" composer.lock' EXIT
cp "$tmp_lock" composer.lock
composer install --prefer-dist --no-progress
cp "$ZIP_CACHE/composer.lock.orig" composer.lock; trap - EXIT

# ---- 4. SQLite test environment ---------------------------------------------------------------
[ -f .env ] || { cp .env.example .env; php artisan key:generate --force; }
# phpunit.xml already forces DB_CONNECTION=sqlite / DB_DATABASE=:memory: and every other behaviour-relevant var.
echo "ready: bash scripts/test-guard.sh   (or: php vendor/bin/phpunit --filter ...)"
```

Run the suite with `bash scripts/test-guard.sh` (same command CI runs). `phpunit.xml` already forces
`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` and the other behaviour-relevant variables, so no database
service is needed. If the 8.4 prefix is not first on `PATH`, call `/opt/php84/bin/php vendor/bin/phpunit`.

### Option B: PHP 8.4 from the Ondrej PPA (faster, **untested** because the host is denied)

Replaces step 1 of the script. Needs the one extra domain in section 3. Uses a manual `sources.list` entry, so
`add-apt-repository` (which needs `api.launchpad.net`) is not used. The key fingerprint below was verified from
`keyserver.ubuntu.com` in this session.

```bash
install -d -m 0755 /etc/apt/keyrings
curl -fsSL "https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x71DAEAAB4AD4CAB6" | gpg --dearmor -o /etc/apt/keyrings/ondrej-php.gpg
# expected fingerprint: B8DC7E53946656EFBCE4C1DD71DAEAAB4AD4CAB6
echo "deb [signed-by=/etc/apt/keyrings/ondrej-php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ondrej-php.list
apt-get update -qq
apt-get install -y -qq php8.4-cli php8.4-common php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-intl php8.4-sqlite3 php8.4-bcmath
update-alternatives --set php /usr/bin/php8.4
```

Do not install `php8.4-redis` unless it is 6.1 or newer (the PPA ships 6.x, but nothing in the tests needs it). I did not
verify the PPA package names or versions because the host is blocked. Steps 2 to 4 of the script are unchanged.

## 3. Network allowlist

Reachability was probed from this session (HTTP status via the egress proxy; a "denied" host answers 403 to
`CONNECT`, recorded in the proxy's status endpoint). "Trusted defaults" below means "reachable with no changes to this
environment"; I could not read the actual Trusted list, only what is reachable.

**Needed by the tested script: nothing beyond what is already reachable.**

| Host | Used for | Status here |
|---|---|---|
| `archive.ubuntu.com`, `security.ubuntu.com` | `apt-get` build dependencies | reachable |
| `github.com` (git smart-HTTP) | `php-src` clone, per-package `git fetch`, `composer.phar` release redirect | reachable |
| `release-assets.githubusercontent.com` | composer.phar download (fallback only; Composer 2.8.12 is preinstalled) | reachable |
| `repo.packagist.org`, `packagist.org` | Composer metadata | reachable |
| `registry.npmjs.org` | `npm ci` (only for the frontend/Playwright side) | reachable |

**Extra domain for Option B only:** `ppa.launchpadcontent.net` (denied today). `keyserver.ubuntu.com` and `launchpad.net` are
already reachable.

**Do not bother allowing:**

- `api.github.com` / `codeload.github.com`: denied today; even if allowed, unauthenticated Composer hits the 60/hour API
  limit on a 239-package lock.
- `getcomposer.org`: denied, and not needed (Composer is preinstalled, with a GitHub-release fallback).
- `api.launchpad.net`: only needed by `add-apt-repository`, which Option B avoids.
- Docker Hub: the registry answers but layer storage (`production.cloudflare.docker.com`) is denied, so `docker pull php:8.4-cli`
  would not work either.

## 4. What I could verify with nothing changed

- **Environment facts:** Ubuntu 24.04, 4 vCPU, 15 GB RAM; PHP 8.3.6 with all required extensions; Composer 2.8.12; Node 22 and
  `npm ci` works (~19 s); `apt-get` works as root; Chromium for Playwright is present.
- **Without any setup**, `php -l` on changed files and static review (including checking icon names against a git clone of a
  package) are possible, but **not** PHPUnit, Blade rendering or the app: `vendor/` does not exist and `composer install` fails
  for the three reasons above.
- **With only a stop-gap and no new domains:** the same prefetch-and-install steps plus
  `composer install --prefer-dist --ignore-platform-req=php --ignore-platform-req=ext-redis` on the stock PHP 8.3.6 ran the
  full guarded suite green (2186 tests, 0 failures, 7 min 23 s). That is enough to catch regressions in everyday work, with
  the caveat that CI runs PHP 8.4.

## 5. Recommendation

1. Put the script in the environment's setup script as written (no allowlist change).
2. If the 8-minute PHP build is unacceptable and the platform does not cache the result, allow `ppa.launchpadcontent.net` and
   use Option B (and test it once; it is the only untested part).
3. Optional follow-up for the repo (not done here): make `.claude/hooks/session-start.sh` use this
   fetch-over-git approach with `--prefer-dist`, and pin the PHP prefix, so the hook and the setup script do not drift.
