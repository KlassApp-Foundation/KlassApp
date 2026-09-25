/**
 * DIAGNOSTIC ONLY — Toshi header full-width thread.
 * No fix attempts here. Answers exactly two questions:
 *   1. Ancestor chain of .toshi-panel: does any ancestor between the panel
 *      and <html> have a computed transform / filter / contain / will-change /
 *      backdrop-filter that creates a containing block for position:fixed?
 *   2. Cascade: which stylesheet + rule actually wins for
 *      [data-toshi-root] position, and what is the computed value?
 * Also records the header (.toshi-header / .navbar) geometry for regression baselines.
 *
 * Usage:
 *   node e2e/toshi-header-diagnostic.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || 'http://localhost:8080').replace(/\/$/, '');
const EMAIL = process.env.DASH_EMAIL || 'admin@demoacademyuganda.sch.ug';
const PASSWORD = process.env.DASH_PASSWORD || 'password123';
const OUT = path.join(__dirname, 'screenshots', 'toshi-header-diagnostic');
fs.mkdirSync(OUT, { recursive: true });

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await context.newPage();

  // Login
  await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
  await page.fill('input[name="email"], input[type="email"]', EMAIL);
  await page.fill('input[name="password"], input[type="password"]', PASSWORD);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load', timeout: 90000 }).catch(() => null),
    page.click('[data-testid="ap-primary-submit"], button[type="submit"]'),
  ]);
  console.log('URL after login:', page.url());

  // Open the Toshi panel if collapsed
  await page.evaluate(() => document.body.classList.remove('toshi-collapsed'));
  await page.waitForTimeout(600);

  // ---- 1. Ancestor chain computed-style walk ----
  const ancestorReport = await page.evaluate(() => {
    const panel = document.querySelector('[data-toshi-root] .toshi-panel');
    if (!panel) return { error: 'no .toshi-panel found' };
    const chain = [];
    let el = panel.parentElement;
    while (el) {
      const cs = getComputedStyle(el);
      chain.push({
        tag: el.tagName.toLowerCase(),
        id: el.id || null,
        classes: el.className && el.className.baseVal !== undefined ? el.className.baseVal : (el.className || ''),
        position: cs.position,
        transform: cs.transform,
        filter: cs.filter,
        contain: cs.contain,
        willChange: cs.willChange,
        backdropFilter: cs.backdropFilter,
        perspective: cs.perspective,
        containerType: cs.containerType,
        contentVisibility: cs.contentVisibility,
        offsetParentProof: el.offsetParent === null && cs.position !== 'fixed' ? 'NULL offsetParent' : null,
      });
      el = el.parentElement;
    }
    return { chainLength: chain.length, chain };
  });
  console.log('\n=== ANCESTOR CHAIN (panel -> html) ===');
  console.log(JSON.stringify(ancestorReport, null, 2));

  // ---- 2. Cascade analysis for [data-toshi-root] position ----
  const cascadeReport = await page.evaluate(() => {
    const root = document.querySelector('[data-toshi-root]');
    const panel = document.querySelector('[data-toshi-root] .toshi-panel');
    const header = document.querySelector('[data-toshi-root] .toshi-header');
    const navbar = document.querySelector('.navbar.dashboard-themed-header');
    const r = (el) => el ? el.getBoundingClientRect().toJSON() : null;

    // Which stylesheets contain body [data-toshi-root] position declarations, in doc order
    const sheets = [];
    for (const sheet of document.styleSheets) {
      let href = 'inline <style>';
      try { if (sheet.href) href = sheet.href; } catch (e) {}
      let matches = [];
      try {
        for (const rule of sheet.cssRules) {
          if (!rule.selectorText) continue;
          if (rule.selectorText.includes('[data-toshi-root]') && rule.style && (rule.style.position || rule.style.marginTop || rule.style.height || rule.style.alignSelf)) {
            const at = rule.parentRule && rule.parentRule.cssText.startsWith('@media') ? rule.parentRule.conditionText : null;
            matches.push({
              selector: rule.selectorText,
              media: at,
              position: rule.style.position || null,
              marginTop: rule.style.marginTop || null,
              height: rule.style.height || null,
              alignSelf: rule.style.alignSelf || null,
              important: rule.style.getPropertyPriority('position') || null,
            });
          }
        }
      } catch (e) { matches.push({ error: 'CORS/blocked: ' + e.message }); }
      if (matches.length) sheets.push({ href, order: sheets.length, matches });
    }
    return {
      rootComputed: {
        position: getComputedStyle(root).position,
        transform: getComputedStyle(root).transform,
        marginTop: getComputedStyle(root).marginTop,
        height: getComputedStyle(root).height,
        alignSelf: getComputedStyle(root).alignSelf,
      },
      rootRect: r(root),
      panelComputed: { position: getComputedStyle(panel).position },
      panelRect: r(panel),
      headerRect: r(header),
      navbarRect: r(navbar),
      navbarComputedHeight: navbar ? getComputedStyle(navbar).height : null,
      viewport: { w: innerWidth, h: innerHeight },
      sheetsInDocOrder: sheets,
      headLinks: Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map(l => l.getAttribute('href')),
    };
  });
  console.log('\n=== CASCADE / GEOMETRY ===');
  console.log(JSON.stringify(cascadeReport, null, 2));

  // ---- 3. Direct hypothesis test: force root static->fixed inline, re-measure ----
  // If computed static is correct behaviour (dock), toggling inline position:fixed
  // should NOT change anything visually if something else constrains it. If an
  // ancestor containing block existed, fixed would resolve against it.
  const probe = await page.evaluate(() => {
    const root = document.querySelector('[data-toshi-root]');
    const before = root.getBoundingClientRect().toJSON();
    root.style.position = 'fixed';
    root.style.top = '0'; root.style.right = '0'; root.style.bottom = '0'; root.style.left = '0';
    const after = root.getBoundingClientRect().toJSON();
    const afterStyle = getComputedStyle(root).position;
    // cleanup
    root.style.removeProperty('position');
    root.style.removeProperty('top'); root.style.removeProperty('right');
    root.style.removeProperty('bottom'); root.style.removeProperty('left');
    return { before, after, afterStyle };
  });
  console.log('\n=== INLINE FIXED PROBE (root) ===');
  console.log(JSON.stringify(probe, null, 2));

  // ---- 4. Containing-block proof: a fixed probe element inside the panel ----
  const cbProbe = await page.evaluate(() => {
    const root = document.querySelector('[data-toshi-root]');
    const probe = document.createElement('div');
    probe.style.cssText = 'position:fixed;top:0;left:0;width:10px;height:10px;background:red;z-index:-1;';
    root.appendChild(probe);
    const rect = probe.getBoundingClientRect().toJSON();
    probe.remove();
    // If body/html have no transform/filter, the probe's containing block is the
    // viewport and top/left are 0,0 of the viewport. If an ancestor creates a
    // containing block, rect.top/left are relative to it and will typically be nonzero.
    return { probeRect: rect, viewport: { w: innerWidth, h: innerHeight } };
  });
  console.log('\n=== FIXED-CONTAINING-BLOCK PROBE (element inside root) ===');
  console.log(JSON.stringify(cbProbe, null, 2));

  await page.screenshot({ path: path.join(OUT, 'diagnostic-1440.png') });
  console.log('\nScreenshot:', path.join(OUT, 'diagnostic-1440.png'));
  fs.writeFileSync(path.join(OUT, 'diagnostic-report.json'), JSON.stringify({ ancestorReport, cascadeReport, probe, cbProbe }, null, 2));

  await browser.close();
})().catch(e => { console.error('DIAGNOSTIC FAILED:', e); process.exit(1); });
