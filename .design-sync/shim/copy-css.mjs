/**
 * Pulls the canonical KlassApp stylesheet into the shim package.
 *
 * `cfg.cssEntry` is resolved relative to — and bounded by — the DS package
 * directory, so it cannot point at `public/css/` up in the repo root. Rather
 * than keep a second copy of the stylesheet under version control (which
 * would silently rot), the build copies the real file in on every run.
 *
 * Source of truth stays `public/css/dashboard-refresh.css`. The copy lands in
 * `dist/`, which is gitignored.
 */
import { copyFileSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const src = resolve(here, '../../public/css/dashboard-refresh.css');
const dest = resolve(here, 'dist/dashboard-refresh.css');

mkdirSync(dirname(dest), { recursive: true });
copyFileSync(src, dest);

console.log(`copied ${src} → ${dest}`);
