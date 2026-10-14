const path = require('path');
const { createRequire } = require('module');
const requireFromApp = createRequire('/Users/mac/projects/KlassApp/package.json');
const { chromium, webkit } = requireFromApp('playwright');

const root = path.resolve(__dirname, '../..');
const css = require('fs').readFileSync(path.join(root, 'packages/toshi-ui/resources/css/toshi-ui.css'), 'utf8');

function assert(cond, message) {
  if (!cond) throw new Error(message);
}

function pageHtml() {
  return `<!doctype html>
    <style>
      ${css}
      .hidden { display: none !important; }
      #res_sidebar.block { display: block; position: fixed; inset: 0 auto 0 0; width: 288px; background: #fff; z-index: 60; }
      #topbar { height: 64px; display: flex; gap: 8px; align-items: center; }
    </style>
    <body class="toshi-collapsed">
      <div id="sidebar-scrim" hidden></div>
      <div id="app">
        <header id="topbar">
          <button type="button" id="mobile-menu-trigger" aria-expanded="false" aria-controls="res_sidebar">Menu</button>
          <button type="button" id="term-switcher">Term</button>
          <button type="button" id="command-palette">Search</button>
          <button type="button" id="account-button">Account</button>
        </header>
        <main>
          <div id="app-sidebar-wrap">
            <div id="res_sidebar" class="hidden" role="dialog" aria-modal="true" aria-label="Main menu">
              <button type="button" id="drawer-close">Close</button>
              <a href="#students">Students</a>
              <select id="academic_year_drawer" aria-label="Academic year"><option>2026</option></select>
              <button type="button" id="drawer-last">Settings</button>
            </div>
          </div>
          <div class="dashboard-content-area"><button type="button" id="content-action">Open record</button></div>
        </main>
      </div>
      <div data-toshi-root>
        <div id="toshi-resize-handle" data-toshi-resize-handle tabindex="0" role="separator" aria-label="Resize Toshi panel (drag or arrow keys)"></div>
        <div id="toshi-panel"><button type="button" id="toshi-talk">Talk</button></div>
      </div>
    </body>`;
}

async function outsideInert(page, id) {
  return page.locator('#' + id).evaluate((el) => el.closest('[inert]') !== null || el.hasAttribute('inert'));
}

async function check(page, name) {
  await page.setViewportSize({ width: 375, height: 812 });
  await page.setContent(pageHtml());
  await page.addScriptTag({ path: path.join(root, 'public/js/jquery.min.js') });
  await page.addScriptTag({ path: path.join(root, 'public/js/custom.js') });
  const resizeDisplay = await page.locator('[data-toshi-resize-handle]').evaluate((el) => getComputedStyle(el).display);
  assert(resizeDisplay === 'none', name + ' resize control display ' + resizeDisplay);

  await page.locator('#mobile-menu-trigger').click();
  const dialog = await page.locator('#res_sidebar').evaluate((el) => ({
    role: el.getAttribute('role'),
    modal: el.getAttribute('aria-modal'),
    label: el.getAttribute('aria-label'),
  }));
  assert(dialog.role === 'dialog' && dialog.modal === 'true' && dialog.label === 'Main menu', name + ' dialog ' + JSON.stringify(dialog));
  for (const id of ['term-switcher', 'command-palette', 'account-button', 'content-action', 'toshi-talk']) {
    assert(await outsideInert(page, id), name + ' ' + id + ' is not inert');
  }

  await page.locator('#drawer-close').focus();
  await page.keyboard.press('Shift+Tab');
  const fromFirst = await page.evaluate(() => document.activeElement && document.activeElement.id);
  assert(fromFirst === 'drawer-last', name + ' Shift+Tab from first landed on ' + fromFirst);

  let left = false;
  for (let i = 0; i < 60; i++) {
    await page.keyboard.press('Tab');
    const inside = await page.evaluate(() => {
      const drawer = document.getElementById('res_sidebar');
      return drawer.contains(document.activeElement);
    });
    if (!inside) left = true;
  }
  assert(!left, name + ' Tab moved focus outside the drawer');

  await page.keyboard.press('Escape');
  const back = await page.evaluate(() => document.activeElement && document.activeElement.id);
  const closed = await page.locator('#res_sidebar').evaluate((el) => el.classList.contains('hidden'));
  assert(closed && back === 'mobile-menu-trigger', name + ' Escape focus ' + back + ' closed=' + closed);
}

async function run(browser, name) {
  const page = await browser.newPage();
  await check(page, name);
  await page.close();
}

(async () => {
  const chrome = await chromium.launch({ channel: 'chrome', headless: true });
  const webkitBrowser = await webkit.launch({ headless: true });
  await run(chrome, 'chromium');
  await run(webkitBrowser, 'webkit');
  await chrome.close();
  await webkitBrowser.close();
  console.log('phone-drawer pass');
})().catch((error) => {
  console.error(error.message || error);
  process.exit(1);
});
