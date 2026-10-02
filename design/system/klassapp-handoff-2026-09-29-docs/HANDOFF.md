# HANDOFF v2: one VitePress site for all KlassApp docs

This replaces Docsify (`docs/index.html`, `docs/community/`, `docs/dev/`) and retires GitBook.

**Repo:** KlassApp-Foundation/KlassApp · designed from `main` @ `9f3297546d8c` (confirm with `git rev-parse`). Before starting, run `git log --oneline 9f3297546d8c..main -- docs/ routes/web.php app/Http/Controllers/DocsController.php`.

**Files in this ZIP:**
- `vitepress/docs-site/`: config, theme, the Vue components and page templates. **It was written in Claude Design and has never been installed or built.** Expect small fixes on the first `npm run docs:build`.
- `MIGRATION.md`: every Docsify and GitBook page, where it goes, and the redirects needed.
- `design-system/`: tokens, `styles/docs.css` (the HTML reference for every component), the concepts and the voice guide.

**Not browser-measured:** all contrast ratios (calculated from hex), all performance budgets (estimates to verify), and the whole VitePress output.

## URL plan
| URL | What it is |
|---|---|
| `/docs/` | Docs home: two doors (Help for schools, Community for contributors), plus one line telling parents they don't need the docs to start |
| `/docs/help/…` | User manuals by role (`admins/`, `teachers/`, `bursars/`, `parents/`, `students/`), plus `concepts/`, `faq`, `troubleshooting`, `whats-new` |
| `/docs/community/…` | Getting started, architecture, contributing, connectors, `reference/…`, design system, roadmap, changelog, provenance, security, code of conduct, `archive/` |
| `/help` | 301 to `/docs/help/`. The quick-start QR codes now encode `https://klassapp.xyz/help`. |

**Section switch:** a two-button segmented control in the nav bar (`SectionSwitch.vue`, shown as "Help" / "Community" below 960px). Each section has its own sidebar, and search covers both.

## P1 · VitePress scaffold and theme
1. Add `docs-site/` at the repo root from `vitepress/docs-site/`, plus `vitepress` as a dev dependency with pinned exact versions (`package.json.snippet`).
2. Copy the latin-subset woff2 files into `docs-site/public/fonts/` (DM Sans 400/700, Sora 600, from `@fontsource`). Also copy `klassapp-icon.svg`, `whatsapp.svg` and a favicon into `docs-site/public/`. **There's no Google Fonts request.**
3. Build output: `base: '/docs/'` writes to `public/docs/`, which is git-ignored and built in CI and deploy (`npm run docs:build` after `npm run build`).
4. **Rewrite `DocsController`** to serve the static build from `public/docs/`:
   - resolve `{path}` to `{path}.html`, then `{path}/index.html`, else `404.html` with status 404;
   - keep the `..` guard;
   - drop the Docsify allowlist and the SPA fallback.
   The route `/docs/{path?}` stays. Alternatively, let the web server serve `public/docs` directly with `try_files $uri $uri.html $uri/index.html /docs/404.html`. That's faster; **your choice, depending on what Laravel Cloud allows.**
5. **Theme:** `theme/klassapp.css` maps the tokens onto VitePress variables:

   | VitePress variable | Value | Role |
   |---|---|---|
   | `--vp-c-bg` | #FAFAF5 | canvas |
   | `--vp-c-text-1` | #1E293B | body text |
   | `--vp-c-text-2`, `--vp-c-text-3` | #475569 | secondary text; VitePress's default text-3 is too light |
   | `--vp-c-brand-1` | #1D4ED8 | links |
   | `--vp-c-brand-3` | #15803D | buttons |
   | `--vp-custom-block-*` | tip, info, warning and danger tints, each with a 1px border | callouts |

   It also sets 44px targets and reduced motion. `appearance: false` gives one light theme.
6. **Breadcrumbs** (`Breadcrumbs.vue`, in the `doc-before` slot) are built from the sidebar config. Pages opt out with `breadcrumbs: false`.
7. **Feedback** (`Feedback.vue`, in the `doc-footer-before` slot). **No endpoint exists.** It logs to the console until `POST /api/docs-feedback` is built, which is out of scope. Never show "Thanks, sent" before it is.

**Accept:**
- `npm run docs:build` passes with no dead-link warnings.
- `/docs/` home and one page per section render at 375 and 1280.
- No horizontal scroll at 375.
- Every nav, search, switch, feedback and pager target is at least 44px, measured with `getBoundingClientRect`.
- axe shows no contrast failures.

## P2 · Help section
**Components** (registered globally in `theme/index.ts`):
- `<Steps>`/`<Step title>`, `<Shot>`, `<Mark>`, `<Kbd>`, `<Btn>`, `<UiPath to="A > B">`, `<WhatsAppInstead say>`, `<Role r>`, `<RoleCards>` and `<ToshiCallout ask>`.
- **Callouts use VitePress's built-in containers:** `::: tip`, `::: info Note` and `::: warning`. There's no Vue component for them.

**Templates:** `help/_templates/*.md` (role landing, how-to, concept, FAQ, troubleshooting, what's new), excluded from the build by `srcExclude`.

**Content:**
- Write the 5 role landings, then migrate the Docsify community user pages (`MIGRATION.md` section 2).
- Then write the six how-tos from the v1 handoff: attendance, marks, payment, fee reminders, publishing results, linking a parent.
- Check every step against the running app, and name buttons exactly as the app does. Use the voice guide.

**Screenshots:** the pipeline is unchanged from v1 (Playwright, Demo Junior School and Demo Senior School seeders, WebP budgets), writing to `docs-site/public/img/`. Markers go in markup, never into the images.

**Accept:**
- A reviewer completes each how-to on a ≤ 2 GB Android phone in Chrome, using only the page.
- Lighthouse mobile ("Slow 4G"): LCP < 2.5s, CLS 0, total JS on a Help page ≤ 120 KB gzipped (an estimate; VitePress's runtime is the bulk of it).

## P3 · Community migration and redirects
1. **Pages:** create the Community pages from `community/_templates/` (getting started, architecture, contributing with the public-repo rule, connectors and MCP, reference, changelog). The sources for each are in `MIGRATION.md`.
2. **Single copies:** keep **one** copy of the Quick Start and contributing rules. `README.md` and `CONTRIBUTING.md` shrink to short pointers to the docs pages.
3. **Archive:** move `docs/dev/*` into `community/archive/` with the historical banner. Replace the real WhatsApp business number in its env examples with a placeholder.
4. **Old URLs:**
   - **Hash links:** the inline script in `config.mts` `head`, fed by `legacy-hash-map.ts`, redirects Docsify `#/…` URLs before hydration.
   - **Path URLs:** add the Laravel 301s from `MIGRATION.md` section 5, plus `Route::redirect('/help', '/docs/help/', 301)`.
5. **Clean-up:** delete `docs/index.html`, `docs/community/index.html`, `docs/dev/index.html`, `docs/shared/docsify-klassapp.css` and the `_sidebar.md` files. **Leave all internal docs in `docs/`**; they just aren't published.

**Accept:** a Playwright test hits every "Today" URL in `MIGRATION.md` sections 1–3 and asserts the final URL and a 200.

## P4 · GitBook content migration and retirement
1. Move only the GitBook pages marked Move or Merge in `MIGRATION.md` section 4; everything else is dropped.
2. **Unpublish or replace the GitBook space**, and turn off GitHub Sync. It currently publishes internal audits, `knowledge.md`, agent rules and the IDOR note. **Flag this to the owner now, independent of this PR.**
3. **Remove every GitBook link:** from `README.md`, `docs/README.md`, the Community README and anything else a `grep -ri gitbook` turns up.

**Accept:** `grep -ri gitbook` is empty outside `MIGRATION.md` and knowledge.md history.

## P5 · Search and final checks
1. **Search:** built-in local search (`search.provider: 'local'`). The index loads only when search opens. Check the index size: if it's over 150 KB gzipped, exclude `community/archive/**` from the index.
2. **"On this page":** VitePress's outline with `level: [2, 2]`. On mobile it becomes the built-in "On this page" dropdown.
3. **Final checks:**
   - Full-site link check: `lychee` or `vitepress build` dead-link detection.
   - The redirect test from P3.
   - axe on 10 pages.
   - Lighthouse mobile on `/docs/`, one Help how-to and one Community page.
   - A keyboard pass: Tab through the switch, search (Ctrl K), sidebar, outline, content, feedback and pager.
   - `prefers-reduced-motion`: no smooth scrolling.
4. **knowledge.md:** stamp with P1–P5, this file and the branch-point SHA.

## Rules for every PR
- Text contrast ≥ 4.5:1 using the given colours; no new colours.
- Targets ≥ 44px; sidebar links are 40px tall, stacked with no gap.
- `<ToshiCallout>` only for capabilities marked shipped in `docs/internal/role-capability-matrix.md`.
- The open-source line, exactly as written, in the site footer: "The source is public on GitHub; supported self-hosting opens after an independent security review."
- No real school, student, parent or phone number anywhere, including image files and alt text.
