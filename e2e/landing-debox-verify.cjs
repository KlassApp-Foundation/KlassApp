/**
 * Landing de-box (Phase 2b item 3) structural verification.
 *   PREVIEW_BASE=http://127.0.0.1:8000 node e2e/landing-debox-verify.cjs
 * Exits 1 on failure.
 */
const { chromium } = require('playwright');
const BASE = (process.env.PREVIEW_BASE || 'http://127.0.0.1:8000').replace(/\/$/, '') + '/';
let fails = 0, checks = 0;
const ck = (name, ok, detail) => { checks++; if (!ok) fails++; console.log((ok ? 'PASS ' : 'FAIL ') + name + (ok ? '' : ' :: ' + detail)); };

(async () => {
  try { await fetch(BASE); } catch (e) { /* warm-up */ }
  const b = await chromium.launch({ channel: 'chrome', headless: true });
  const ctx = await b.newContext({ viewport: { width: 1280, height: 1000 } });
  const p = await ctx.newPage();
  await p.goto(BASE, { waitUntil: 'networkidle', timeout: 120000 });

  const css = (sel, prop, pseudo) => p.evaluate(([sel2, prop2, pseudo2]) => {
    const el = document.querySelector(sel2);
    if (!el) return null;
    return getComputedStyle(el, pseudo2 || null)[prop2];
  }, [sel, prop, pseudo]);

  // 1. overflow
  for (const w of [320, 360, 375, 414, 768, 1280]) {
    await p.setViewportSize({ width: w, height: 900 });
    await p.waitForTimeout(150);
    const o = await p.evaluate(() => ({ sw: document.documentElement.scrollWidth, iw: window.innerWidth }));
    ck(`no overflow at ${w}`, o.sw <= o.iw + 1, `scrollWidth=${o.sw} innerWidth=${o.iw}`);
  }
  await p.setViewportSize({ width: 1280, height: 1000 });

  // B1 toshi cards float
  const card = { bg: await css('.toshi-card', 'backgroundImage'), bw: await css('.toshi-card', 'borderTopWidth'), bc: await css('.toshi-card', 'borderTopColor'), br: await css('.toshi-card', 'borderTopLeftRadius') };
  ck('B1 card has no box (bg none)', card.bg === 'none', card.bg);
  ck('B1 card hairline top 1px', card.bw === '1px', card.bw);
  ck('B1 hairline is ledger tone', card.bc.includes('rgba(120, 95, 60, 0.14)'), card.bc);
  ck('B1 card radius 0', card.br === '0px', card.br);
  const ico = { img: await css('.toshi-card .toshi-card-icon', 'backgroundImage'), col: await css('.toshi-card .toshi-card-icon', 'color'), sh: await css('.toshi-card .toshi-card-icon', 'boxShadow') };
  ck('B1 icon tile gradient', ico.img.includes('linear-gradient'), ico.img);
  ck('B1 icon green accent', ico.col === 'rgb(21, 128, 61)', ico.col);
  ck('B1 icon depth shadow', ico.sh.includes('inset') && ico.sh.includes('14px'), ico.sh);
  const vio = await css('.toshi-card.violet-accent .toshi-card-icon', 'color');
  ck('B1 violet icon #6D28D9', vio === 'rgb(109, 40, 217)', vio);

  // B5 hitl
  const hitl = { bw: await css('.toshi-hitl', 'borderTopWidth'), img: await css('.toshi-hitl', 'backgroundImage'), pad: await css('.toshi-hitl', 'padding') };
  ck('B5 hitl no border', hitl.bw === '0px', hitl.bw);
  ck('B5 hitl no fill', hitl.img === 'none', hitl.img);
  ck('B5 hitl no padding', hitl.pad === '0px', hitl.pad);
  const halo = await css('.toshi-hitl-icon', 'backgroundImage', '::before');
  ck('B5 violet halo behind icon', halo.includes('radial-gradient'), halo);

  // B4 how columns
  const col = { bg: await css('.how-column', 'backgroundImage'), bw: await css('.how-column', 'borderTopWidth'), bc: await css('.how-column', 'borderTopColor') };
  ck('B4 column no card', col.bg === 'none' && col.bw === '1px' && col.bc.includes('rgba(120, 95, 60, 0.14)'), JSON.stringify(col));
  const prev = { bw: await css('.how-preview', 'borderTopWidth'), sh: await css('.how-preview', 'boxShadow') };
  ck('B4 preview borderless + soft shadow', prev.bw === '0px' && prev.sh.includes('0px 10px 24px -14px'), JSON.stringify(prev));

  // B2 pillars
  const pil = { bw: await css('.pillar', 'borderTopWidth'), bs: await css('.pillar', 'borderTopStyle') };
  ck('B2 pillar hairline', pil.bw === '1px' && pil.bs === 'solid', JSON.stringify(pil));
  const fut = { bw: await css('.pillar-future', 'borderTopWidth'), bs: await css('.pillar-future', 'borderTopStyle'), bc: await css('.pillar-future', 'borderTopColor') };
  ck('B2 provable dashed amber box', fut.bs === 'dashed' && fut.bc.includes('rgba(217, 119, 6, 0.55)') && (fut.bw === '1.5px' || fut.bw === '1px'), JSON.stringify(fut)); // Chrome floors computed border widths to device px at DPR 1

  // B3 compare band
  const rows = await p.evaluate(() => document.querySelectorAll('.compare-band-row').length);
  const heads = await p.evaluate(() => document.querySelectorAll('.compare-band-head .compare-label').length);
  const mob = await css('.compare-label-mobile', 'display');
  ck('B3 one band: 4 rows, single head', rows === 4 && heads === 2, `rows=${rows} heads=${heads}`);
  ck('B3 mobile labels hidden on desktop', mob === 'none', mob);
  const rule = await css('.compare-band-row .compare-band-cell + .compare-band-cell', 'borderLeftWidth');
  ck('B3 single vertical rule', rule === '1px', rule);
  const wash = await css('.compare-band', 'backgroundImage', '::after');
  ck('B3 green wash', wash.includes('linear-gradient'), wash);
  const cellB = { bw: await css('.compare-band-cell', 'borderTopWidth'), bc: await css('.compare-band-cell', 'borderTopColor') };
  ck('B3 row hairlines ledger', cellB.bw === '1px' && cellB.bc.includes('rgba(120, 95, 60, 0.14)'), JSON.stringify(cellB));

  // A6 faq
  const chevs = await p.evaluate(() => document.querySelectorAll('.faq-chev').length);
  const fsum = await p.evaluate(() => { const s = document.querySelector('.faq-item summary'); return s.getBoundingClientRect().height; });
  const fitem = { bw: await css('.faq-item:first-child', 'borderTopWidth'), bc: await css('.faq-item:first-child', 'borderTopColor'), br: await css('.faq-item:first-child', 'borderTopLeftRadius') };
  ck('A6 four chevrons', chevs === 4, String(chevs));
  ck('A6 56px summaries', fsum >= 55, String(fsum));
  ck('A6 divider list hairlines', fitem.bw === '1px' && fitem.bc.includes('rgba(120, 95, 60, 0.14)'), JSON.stringify(fitem));
  ck('A6 no card radius', fitem.br === '0px', fitem.br);
  await p.evaluate(() => { document.querySelector('.faq-item').open = true; });
  await p.waitForTimeout(350);
  const rot = await css('.faq-item[open] .faq-chev', 'transform');
  ck('A6 chevron rotates on open', rot === 'matrix(-1, 0, 0, -1, 0, 0)', rot);

  // B9 protocol
  const pcard = { bg: await css('.protocol-card', 'backgroundImage'), bw: await css('.protocol-card', 'borderTopWidth') };
  ck('B9 protocol floats', pcard.bg === 'none' && pcard.bw === '1px', JSON.stringify(pcard));
  const pblue = await css('.protocol-card.protocol-blue .protocol-icon', 'color');
  ck('B9 blue icon kept', pblue === 'rgb(30, 111, 217)', pblue);

  // B7 hero connected
  const cf = { bg: await css('.connector-float', 'backgroundImage'), bw: await css('.connector-float', 'borderTopWidth') };
  ck('B7 float has no card', cf.bg === 'none' && cf.bw === '0px', JSON.stringify(cf));
  const dot = await css('.connector-float h4', 'width', '::before');
  const pool = await css('.connector-float', 'backgroundImage', '::after');
  ck('B7 status dot', dot === '6px', dot);
  ck('B7 paper pool', pool.includes('radial-gradient'), pool);

  // B8 chips
  const chips = await p.evaluate(() => document.querySelectorAll('.connector-chip').length);
  const chip = { bg: await css('.connector-chip', 'backgroundImage'), bw: await css('.connector-chip', 'borderTopWidth') };
  ck('B8 six chips, no card', chips === 6 && chip.bg === 'none' && chip.bw === '0px', JSON.stringify({ chips, ...chip }));

  // B11 trust strip
  const ts = await css('.trust-strip', 'borderBottomWidth');
  ck('B11 trust strip borderless', ts === '0px', ts);

  // B6 orbit container
  const tw = { bg: await css('.toshi-tower', 'backgroundImage'), bw: await css('.toshi-tower', 'borderTopWidth'), sh: await css('.toshi-tower', 'boxShadow') };
  ck('B6 orbit card removed', tw.bg === 'none' && tw.bw === '0px' && tw.sh === 'none', JSON.stringify(tw));

  // A6 focus ring rule stays present
  const focus = await p.evaluate(() => [...document.styleSheets].some((sh) => { try { return [...sh.cssRules].some((r) => r.selectorText && r.selectorText.includes('summary:focus-visible')); } catch (e) { return false; } }));
  ck('A6 summary focus-visible rule present', focus, 'missing');

  await b.close();
  console.log(fails ? checks + ' checks, ' + fails + ' FAILED' : checks + ' checks ALL PASS');
  process.exit(fails ? 1 : 0);
})();
