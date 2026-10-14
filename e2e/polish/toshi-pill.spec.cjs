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

async function boot(page, width, height) {
  await page.setViewportSize({ width, height });
  await page.setContent(`<!doctype html>
    <style>${css}</style>
    <body class="toshi-collapsed" style="margin:0">
      <button type="button" id="toshi-toggle" class="toshi-toggle" aria-label="Open Toshi"><span class="toshi-handle-mark">K</span></button>
      <button type="button" id="toshi-entry-pill" class="toshi-entry-pill" data-testid="toshi-entry-pill" aria-label="Open Toshi">
        <span class="toshi-entry-mark">K</span><span class="toshi-entry-label">Meet Toshi</span>
      </button>
      <div data-people-bulk hidden style="position:fixed;left:0;right:0;bottom:0;height:56px;background:#0f172a;z-index:15">
        <button type="button" id="bulk-clear" style="height:44px">Clear selection</button>
      </div>
      <a id="corner-action" href="#go" style="position:fixed;right:24px;bottom:24px;display:inline-block;height:44px;padding:0 12px;background:#fff">Record payment</a>
    </body>`);
  await page.addScriptTag({ path: placeJs });
  await page.evaluate(() => {
    document.getElementById('toshi-entry-pill').addEventListener('click', () => {
      document.body.classList.remove('toshi-collapsed');
      document.documentElement.classList.remove('toshi-collapsed');
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

async function run(browser, name) {
  const page = await browser.newPage();
  const notes = {};
  await boot(page, 1280, 680);
  const pill = await box(page, '#toshi-entry-pill');
  const mark = await page.locator('.toshi-entry-mark').evaluate((el) => getComputedStyle(el).color);
  const label = await page.locator('.toshi-entry-label').evaluate((el) => {
    const s = getComputedStyle(el);
    return { text: el.textContent.trim(), color: s.color, size: s.fontSize, weight: s.fontWeight };
  });
  notes.preview = { pill, mark, label };
  assert(pill.display !== 'none' && pill.height === 44, name + ' pill not 44px at 1280: ' + JSON.stringify(pill));
  assert(label.text === 'Meet Toshi', name + ' label');
  assert(mark === 'rgb(21, 128, 61)', name + ' K colour ' + mark);
  const colors = await page.evaluate(() => {
    const bad = [];
    document.querySelectorAll('#toshi-entry-pill, #toshi-entry-pill *, #toshi-toggle, #toshi-toggle *').forEach((el) => {
      const s = getComputedStyle(el);
      if (s.display === 'none') return;
      ['color', 'backgroundColor', 'borderTopColor', 'outlineColor'].forEach((key) => {
        const value = s[key] || '';
        if (value.includes('201, 100, 66') || value.includes('197, 84, 56')) bad.push(el.id + ' ' + key + ':' + value);
      });
    });
    return bad;
  });
  assert(colors.length === 0, name + ' orange in the Toshi controls ' + colors.join(','));
  notes.orange = colors;

  const corner = await box(page, '#corner-action');
  assert(!overlaps(pill, corner), name + ' pill covers the corner action ' + JSON.stringify({ pill, corner }));

  await page.evaluate(() => { document.querySelector('[data-people-bulk]').hidden = false; });
  await page.evaluate(() => window.toshiPlaceEntryPill());
  const lifted = await box(page, '#toshi-entry-pill');
  const bar = await box(page, '[data-people-bulk]');
  const clear = await box(page, '#bulk-clear');
  assert(!overlaps(lifted, bar), name + ' pill intersects the bulk bar ' + JSON.stringify({ lifted, bar }));
  assert(!overlaps(lifted, clear), name + ' pill intersects a bulk button ' + JSON.stringify({ lifted, clear }));
  assert(!overlaps(lifted, await box(page, '#corner-action')), name + ' pill intersects the corner link after the bar');
  notes.lifted = { lifted, bar };

  await page.setViewportSize({ width: 1440, height: 900 });
  await page.evaluate(() => window.toshiPlaceEntryPill());
  const wide = await box(page, '#toshi-entry-pill');
  assert(!overlaps(wide, await box(page, '[data-people-bulk]')), name + ' 1440 intersects the bar');
  assert(!overlaps(wide, await box(page, '#corner-action')), name + ' 1440 intersects the corner link');
  notes.wide = wide;

  await page.setViewportSize({ width: 1279, height: 800 });
  const phone = await box(page, '#toshi-entry-pill');
  assert(phone.display === 'none' || phone.width === 0, name + ' pill visible below 1280 ' + JSON.stringify(phone));

  await page.setViewportSize({ width: 1280, height: 680 });
  await page.evaluate(() => { document.querySelector('[data-people-bulk]').hidden = true; });
  await page.locator('#toshi-entry-pill').click();
  const open = await page.evaluate(() => !document.body.classList.contains('toshi-collapsed'));
  const hidden = await box(page, '#toshi-entry-pill');
  assert(open, name + ' click did not open the panel');
  assert(hidden.display === 'none' || hidden.width === 0, name + ' pill stays visible while open');
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
