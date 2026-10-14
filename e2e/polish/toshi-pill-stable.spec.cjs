const path = require('path');
const { createRequire } = require('module');
const requireFromApp = createRequire('/Users/mac/projects/KlassApp/package.json');
const { chromium, webkit } = requireFromApp('playwright');

const root = path.resolve(__dirname, '../..');
const css = require('fs').readFileSync(path.join(root, 'packages/toshi-ui/resources/css/toshi-ui.css'), 'utf8');
const placeJs = path.join(root, 'public/js/toshi-entry-pill.js');

function assert(cond, message) {
  if (!cond) throw new Error(message);
}

function listPage(title) {
  const rows = Array.from({ length: 24 }, (_, i) => `
    <div class="row" style="display:flex;align-items:center;height:56px;border-bottom:1px solid #E2E8F0">
      <span>${title} ${i + 1}</span>
      <button type="button" class="status" style="margin-left:auto;height:32px">Active</button>
      <button type="button" class="menu" style="height:32px;width:32px">Menu</button>
    </div>`).join('');
  return `<!doctype html>
    <style>${css} :root { --ka-topbar-h: 64px; } body { margin: 0; }</style>
    <body class="toshi-collapsed">
      <script>
        (function () {
          var store = {};
          var memory = {
            getItem: function (k) { return Object.prototype.hasOwnProperty.call(store, k) ? store[k] : null; },
            setItem: function (k, v) { store[k] = String(v); },
            removeItem: function (k) { delete store[k]; }
          };
          try { window.localStorage.getItem('toshi_probe'); }
          catch (e) { Object.defineProperty(window, 'localStorage', { value: memory }); }
        })();
      </script>
      <div class="dashboard-content-area">
        <h1>${title}</h1>
        ${rows}
        <nav class="pager" style="height:48px">Pager</nav>
      </div>
      <div id="toshi-toggle-wrapper" data-toshi-mode="assistant" data-toshi-user="42" data-setup-done="2" data-setup-total="8">
        <button type="button" id="toshi-toggle" class="toshi-toggle" aria-label="Open Toshi"><span class="toshi-handle-mark">K</span></button>
        <button type="button" id="toshi-entry-pill" class="toshi-entry-pill" aria-label="Open Toshi">
          <span class="toshi-entry-ring"><span class="toshi-entry-mark">K</span></span>
          <span class="toshi-entry-label">Talk</span>
        </button>
      </div>
    </body>`;
}

async function bottomOf(page) {
  return page.locator('#toshi-entry-pill').evaluate((el) => getComputedStyle(el).bottom);
}

async function checkList(page, title, width, height) {
  await page.setViewportSize({ width, height });
  await page.setContent(listPage(title));
  await page.addScriptTag({ path: placeJs });
  await page.evaluate(() => window.toshiSyncLauncher && window.toshiSyncLauncher());
  const atTop = await bottomOf(page);
  const widthAtTop = await page.locator('#toshi-entry-pill').evaluate((el) => Math.round(el.getBoundingClientRect().width));
  assert(widthAtTop > 44, title + ' pill starts wider than the circle ' + widthAtTop);

  await page.evaluate(() => window.scrollTo(0, 300));
  await page.evaluate(() => window.dispatchEvent(new Event('scroll')));
  const at300 = await bottomOf(page);
  const widthAt300 = await page.locator('#toshi-entry-pill').evaluate((el) => Math.round(el.getBoundingClientRect().width));
  const labelDisplay = await page.locator('.toshi-entry-label').evaluate((el) => getComputedStyle(el).display);
  const aria = await page.locator('#toshi-entry-pill').getAttribute('aria-label');
  assert(at300 === atTop, title + ' bottom changed after scroll ' + atTop + ' -> ' + at300);
  assert(widthAt300 === 44, title + ' scrolled width ' + widthAt300);
  assert(labelDisplay === 'none', title + ' label still visible');
  assert(aria === 'Open Toshi', title + ' aria-label changed');

  await page.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight));
  await page.evaluate(() => window.dispatchEvent(new Event('scroll')));
  const atEnd = await bottomOf(page);
  assert(atEnd === atTop, title + ' bottom changed at the end ' + atTop + ' -> ' + atEnd);

  const clear = await page.evaluate(() => {
    const pill = document.getElementById('toshi-entry-pill').getBoundingClientRect();
    const last = document.querySelector('.row:last-of-type').getBoundingClientRect();
    const pager = document.querySelector('.pager').getBoundingClientRect();
    const hit = (a, b) => !(a.right <= b.left || a.left >= b.right || a.bottom <= b.top || a.top >= b.bottom);
    return { last: !hit(pill, last), pager: !hit(pill, pager) };
  });
  assert(clear.last && clear.pager, title + ' last row or pager intersects the pill ' + JSON.stringify(clear));

  await page.evaluate(() => window.scrollTo(0, 0));
  await page.evaluate(() => window.dispatchEvent(new Event('scroll')));
  const expanded = await page.locator('#toshi-entry-pill').evaluate((el) => Math.round(el.getBoundingClientRect().width));
  assert(expanded > 44, title + ' pill did not expand at the top ' + expanded);
}

async function run(browser, name) {
  const page = await browser.newPage();
  for (const title of ['Students', 'Teachers', 'Parents']) {
    for (const size of [[1280, 680], [1440, 900]]) {
      await checkList(page, name + ' ' + title, size[0], size[1]);
    }
  }
  await page.close();
}

(async () => {
  const chrome = await chromium.launch({ channel: 'chrome', headless: true });
  const webkitBrowser = await webkit.launch({ headless: true });
  await run(chrome, 'chromium');
  await run(webkitBrowser, 'webkit');
  await chrome.close();
  await webkitBrowser.close();
  console.log('pill-stable pass');
})().catch((error) => {
  console.error(error.message || error);
  process.exit(1);
});
