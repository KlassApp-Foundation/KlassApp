// Teacher and student shells share layouts/partials/navigation.blade.php.
// Below 768px the account card's name and email used to push Search over the
// menu button. This measures the real boxes: both targets stay at least 44px,
// they do not overlap, and a point on the menu hits the menu.
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '../..');

function stylesheet(rel) {
    return fs.readFileSync(path.join(ROOT, rel), 'utf8');
}

function tailwindCss() {
    const manifest = JSON.parse(fs.readFileSync(path.join(ROOT, 'public/build/manifest.json'), 'utf8'));
    const file = manifest['resources/css/tailwind.css'].file;
    return stylesheet(path.join('public/build', file));
}

function headerHtml({ school, name, initials, email }) {
    return `<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
${tailwindCss()}
${stylesheet('public/css/dashboard-refresh.css')}
body { margin: 0; background: #FAFAF5; }
</style>
</head>
<body>
<nav class="navbar dashboard-themed-header w-full flex lg:flex-row px-4 lg:px-8 py-2 justify-between items-center" style="background:#FAFAF5;border-bottom:1px solid #E2E8F0;">
  <div class="nav-brand flex items-center">
    <button type="button" class="mr-3 md:hidden" id="mobile-menu-trigger" aria-label="Toggle sidebar">
      <span class="navbar-toggler-icon">
        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M4 5h16a1 1 0 0 1 0 2H4a1 1 0 1 1 0-2zm0 6h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2zm0 6h16a1 1 0 0 1 0 2H4a1 1 0 0 1 0-2z" fill="currentColor"/></svg>
      </span>
    </button>
    <a class="nav-school-line" href="#">
      <span class="nav-school-name"><strong>${school}</strong></span>
      <span class="nav-school-term">Term 3, 2026</span>
    </a>
  </div>
  <div class="flex flex-row flex-wrap items-center gap-2 justify-end">
    <div class="flex items-center">
      <button type="button" id="command-palette-trigger" class="inline-flex items-center gap-2 mr-3 px-2.5 py-1.5 rounded-lg text-sm" aria-label="Open the command palette">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
        <span class="hidden sm:inline">Search</span>
        <kbd class="hidden sm:inline">Ctrl K</kbd>
      </button>
      <div>
        <a href="#" class="block no-underline -mt-1" aria-label="Notifications">
          <div class="relative flex" style="padding:10px 15px;">
            <svg class="w-8 h-8" viewBox="0 0 24 24" width="32" height="32"><path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22z"/></svg>
          </div>
        </a>
      </div>
      <div class="navbar-menu ml-5">
        <ul class="navbar-nav ml-auto flex items-center">
          <li>
            <div class="profile-click account-card">
              <button type="button" class="account-card__trigger" aria-label="${name}">
                <span style="display:inline-flex;width:36px;height:36px;border-radius:50%;background:#15803D;color:#fff;align-items:center;justify-content:center;flex-shrink:0;">${initials}</span>
                <span class="account-card__identity">
                  <span class="account-card__name">${name}</span>
                  <span class="account-card__email">${email}</span>
                </span>
                <svg class="account-card__chevron" width="16" height="16" viewBox="0 0 24 24"></svg>
              </button>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>
</body>
</html>`;
}

const SHELLS = [
    {
        role: 'teacher',
        school: 'Demo Junior School',
        name: 'Amina Nakato',
        initials: 'AN',
        email: 'phase4.class-teacher@klassapp.xyz',
    },
    {
        role: 'student',
        school: 'Demo Junior School',
        name: 'Mukasa David',
        initials: 'MD',
        email: 'phase4.student.mukasa@klassapp.xyz',
    },
];

function overlaps(a, b) {
    return a.left < b.right - 0.5 && a.right > b.left + 0.5 && a.top < b.bottom - 0.5 && a.bottom > b.top + 0.5;
}

async function measure(page) {
    return page.evaluate(() => {
        const menu = document.getElementById('mobile-menu-trigger');
        const search = document.getElementById('command-palette-trigger');
        const box = (el) => {
            const r = el.getBoundingClientRect();
            const s = getComputedStyle(el);
            return {
                left: r.left,
                top: r.top,
                right: r.right,
                bottom: r.bottom,
                width: r.width,
                height: r.height,
                display: s.display,
            };
        };
        const menuBox = box(menu);
        const searchBox = box(search);
        const hit = (b) => {
            const el = document.elementFromPoint(b.left + b.width / 2, b.top + b.height / 2);
            if (!el) return null;
            return el.id || (el.closest('#mobile-menu-trigger') ? 'mobile-menu-trigger' : null)
                || (el.closest('#command-palette-trigger') ? 'command-palette-trigger' : null)
                || el.tagName;
        };
        const label = search.querySelector('span');
        return {
            menu: menuBox,
            search: searchBox,
            menuHit: hit(menuBox),
            searchHit: hit(searchBox),
            searchLabelDisplay: label ? getComputedStyle(label).display : 'missing',
            viewport: window.innerWidth,
        };
    });
}

for (const shell of SHELLS) {
    for (const width of [360, 375, 414]) {
        test(`${shell.role} header at ${width}px keeps menu and search tappable`, async ({ page }) => {
            await page.setViewportSize({ width, height: 812 });
            await page.setContent(headerHtml(shell), { waitUntil: 'load' });
            const m = await measure(page);

            expect(m.menu.display).not.toBe('none');
            expect(m.menu.width).toBeGreaterThanOrEqual(44);
            expect(m.menu.height).toBeGreaterThanOrEqual(44);
            expect(m.search.width).toBeGreaterThanOrEqual(44);
            expect(m.search.height).toBeGreaterThanOrEqual(44);
            expect(m.menu.left).toBeGreaterThanOrEqual(0);
            expect(m.search.right).toBeLessThanOrEqual(width + 0.5);
            expect(overlaps(m.menu, m.search)).toBe(false);
            expect(m.menuHit).toBe('mobile-menu-trigger');
            expect(m.searchHit).toBe('command-palette-trigger');
        });
    }

    test(`${shell.role} header at 1280px still shows the desktop search label`, async ({ page }) => {
        await page.setViewportSize({ width: 1280, height: 800 });
        await page.setContent(headerHtml(shell), { waitUntil: 'load' });
        const m = await measure(page);

        expect(m.menu.display).toBe('none');
        expect(m.searchLabelDisplay).not.toBe('none');
        expect(m.search.width).toBeGreaterThan(44);
    });
}
