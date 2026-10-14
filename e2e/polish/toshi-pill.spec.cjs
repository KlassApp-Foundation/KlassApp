const fs = require('fs');
const path = require('path');
const { createRequire } = require('module');
const requireFromApp = createRequire('/Users/mac/projects/KlassApp/package.json');
const { chromium, webkit } = requireFromApp('playwright');

const root = path.resolve(__dirname, '../..');
const css = fs.readFileSync(path.join(root, 'packages/toshi-ui/resources/css/toshi-ui.css'), 'utf8');
const placeJs = path.join(root, 'public/js/toshi-entry-pill.js');

function assert(cond, message) {
  if (!cond) throw new Error(message);
}

function overlaps(a, b) {
  return !(a.right <= b.left || a.left >= b.right || a.bottom <= b.top || a.top >= b.bottom);
}

function pageHtml(mode, label, chip) {
  return `<!doctype html>
    <style>${css} :root { --ka-topbar-h: 64px; }</style>
    <body class="toshi-collapsed" style="margin:0">
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
      <div class="dashboard-content-area" style="min-height:400px">
        <a id="corner-action" href="#go" style="position:fixed;right:80px;bottom:8px;display:inline-block;height:44px;padding:0 12px;background:#fff">Record payment</a>
      </div>
      <div data-sticky-bar id="select-bar" style="position:fixed;left:16px;right:16px;top:80px;height:48px;background:#0f172a">Select</div>
      <div data-sticky-bar id="form-bar" hidden style="position:fixed;left:0;right:0;bottom:0;height:56px;background:#fff;z-index:15">
        <button type="button" id="save" style="height:44px">Save</button>
      </div>
      <div id="toshi-toggle-wrapper" data-toshi-mode="${mode}" data-toshi-user="42" data-setup-done="2" data-setup-total="8">
        <button type="button" id="toshi-toggle" class="toshi-toggle" aria-label="Open Toshi"><span class="toshi-handle-mark">K</span></button>
        <button type="button" id="toshi-entry-pill" class="toshi-entry-pill" data-testid="toshi-entry-pill" aria-label="Open Toshi">
          <span class="toshi-entry-ring"><span class="toshi-entry-mark">K</span></span>
          <span class="toshi-entry-label">${label}</span>
          ${chip}
        </button>
      </div>
    </body>`;
}

async function boot(page, width, height, mode, label, chip) {
  await page.setViewportSize({ width, height });
  await page.setContent(pageHtml(mode, label, chip || ''));
  await page.addScriptTag({ path: placeJs });
  await page.evaluate(() => {
    document.getElementById('toshi-entry-pill').addEventListener('click', () => {
      document.body.classList.remove('toshi-collapsed');
      if (window.toshiSyncLauncher) window.toshiSyncLauncher();
    });
    document.getElementById('toshi-toggle').addEventListener('click', () => {
      document.body.classList.remove('toshi-collapsed');
      if (window.toshiSyncLauncher) window.toshiSyncLauncher();
    });
  });
}

async function box(page, selector) {
  return page.locator(selector).evaluate((el) => {
    const r = el.getBoundingClientRect();
    const s = getComputedStyle(el);
    return {
      left: Math.round(r.left), right: Math.round(r.right), top: Math.round(r.top), bottom: Math.round(r.bottom),
      width: Math.round(r.width), height: Math.round(r.height), display: s.display,
    };
  });
}

async function measure(page, name) {
  const pill = await box(page, '#toshi-entry-pill');
  const handle = await box(page, '#toshi-toggle');
  const mark = await page.locator('.toshi-entry-mark').evaluate((el) => getComputedStyle(el).color);
  const label = await page.locator('.toshi-entry-label').evaluate((el) => {
    const s = getComputedStyle(el);
    return { text: el.textContent.trim(), color: s.color, size: s.fontSize, weight: s.fontWeight };
  });
  const tab = await page.locator('#toshi-toggle').evaluate((el) => el.tabIndex);
  const pad = await page.locator('.dashboard-content-area').evaluate((el) => getComputedStyle(el).paddingBottom);
  assert(pill.display !== 'none' && pill.height === 44, name + ' pill ' + JSON.stringify(pill));
  assert(handle.width === 36 && handle.height === 96, name + ' handle ' + JSON.stringify(handle));
  assert(mark === 'rgb(21, 128, 61)', name + ' K colour ' + mark);
  assert(label.size === '14px' && label.weight === '700' && label.color === 'rgb(15, 23, 42)', name + ' label ' + JSON.stringify(label));
  assert(tab === -1, name + ' handle is in the tab order while the pill shows');
  assert(pad === '100px', name + ' page padding ' + pad);
  const mid = (handle.top + handle.bottom) / 2;
  const expected = 64 + (page.viewportSize().height - 64) / 2;
  assert(Math.abs(mid - expected) <= 2, name + ' handle centre ' + mid + ' expected ' + expected);
  const colors = await page.evaluate(() => {
    const bad = [];
    document.querySelectorAll('#toshi-entry-pill, #toshi-entry-pill *, #toshi-toggle, #toshi-toggle *').forEach((el) => {
      const s = getComputedStyle(el);
      if (s.display === 'none') return;
      ['color', 'backgroundColor', 'borderTopColor'].forEach((key) => {
        const value = s[key] || '';
        if (value.includes('201, 100, 66') || value.includes('197, 84, 56')) bad.push(key + ':' + value);
      });
    });
    return bad;
  });
  assert(colors.length === 0, name + ' orange ' + colors.join(','));
  assert(!overlaps(pill, await box(page, '#corner-action')), name + ' pill covers a link');
  assert(!overlaps(pill, await box(page, '#select-bar')), name + ' pill covers the Select bar');
  return { pill, handle, label };
}

async function run(browser, name) {
  const notes = {};
  const page = await browser.newPage();
  await boot(page, 1280, 680, 'preview', 'Meet Toshi');
  notes.preview = await measure(page, name + ' preview 1280');
  assert(notes.preview.label.text === 'Meet Toshi', name + ' preview label');

  await page.setViewportSize({ width: 1440, height: 900 });
  await page.evaluate(() => window.toshiSyncLauncher());
  notes.wide = await measure(page, name + ' preview 1440');

  await page.evaluate(() => { document.getElementById('form-bar').hidden = false; window.toshiSyncLauncher(); });
  const lifted = await box(page, '#toshi-entry-pill');
  const bar = await box(page, '#form-bar');
  assert(!overlaps(lifted, bar), name + ' pill intersects the sticky bar');
  assert(!overlaps(lifted, await box(page, '#save')), name + ' pill intersects a bar button');
  assert(lifted.bottom <= bar.top - 16, name + ' pill is not 16px above the bar');
  notes.lifted = { bottom: lifted.bottom, barTop: bar.top };

  await page.setViewportSize({ width: 1279, height: 800 });
  await page.evaluate(() => window.toshiSyncLauncher());
  const phonePill = await box(page, '#toshi-entry-pill');
  const phoneHandle = await box(page, '#toshi-toggle');
  assert(phonePill.display === 'none' || phonePill.width === 0, name + ' pill below 1280');
  assert(phoneHandle.display === 'none' || phoneHandle.width === 0, name + ' handle below 1280');

  await page.setViewportSize({ width: 1280, height: 680 });
  await page.evaluate(() => { document.getElementById('form-bar').hidden = true; window.toshiSyncLauncher(); });
  await page.locator('#toshi-entry-pill').click();
  assert(await page.evaluate(() => !document.body.classList.contains('toshi-collapsed')), name + ' click did not open');
  const open = await box(page, '#toshi-entry-pill');
  assert(open.display === 'none' || open.width === 0, name + ' pill visible while open');

  await boot(page, 1280, 680, 'preview', 'Meet Toshi');
  await page.locator('#toshi-entry-pill').press('Enter');
  assert(await page.evaluate(() => !document.body.classList.contains('toshi-collapsed')), name + ' Enter did not open');

  await boot(page, 1280, 680, 'onboarding', 'Setup guide', '<span class="toshi-entry-chip">2/8</span>');
  const setup = await measure(page, name + ' setup');
  const chip = await page.locator('.toshi-entry-chip').innerText();
  assert(setup.label.text === 'Setup guide' && chip === '2/8', name + ' setup chip ' + chip);

  await boot(page, 1280, 680, 'assistant', 'Talk');
  const talk = await measure(page, name + ' talk');
  assert(talk.label.text === 'Talk', name + ' talk label');

  await boot(page, 1280, 680, 'preview', 'Meet Toshi');
  await page.evaluate(() => { localStorage.setItem('toshi_opened_42', '1'); window.toshiSyncLauncher(); });
  const after = await box(page, '#toshi-entry-pill');
  const handle = await box(page, '#toshi-toggle');
  assert(after.display === 'none' || after.width === 0, name + ' preview pill stays after the first open');
  assert(handle.width === 36, name + ' handle remains after the pill hides');
  await page.close();
  return notes;
}

(async () => {
  const chrome = await chromium.launch({ channel: 'chrome', headless: true });
  const webkitBrowser = await webkit.launch({ headless: true });
  const report = {
    chromium: await run(chrome, 'chromium'),
    webkit: await run(webkitBrowser, 'webkit'),
  };
  await chrome.close();
  await webkitBrowser.close();
  console.log(JSON.stringify(report));
})();
