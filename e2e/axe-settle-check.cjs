const { chromium } = require('playwright');
/* Runs axe (wcag2aa) up to 6 attempts with settling waits; reports every attempt with the
   hero's card opacity at scan time so fade-window hits are distinguishable. Exit 0 always
   (evidence tool); prints CLEAN when an attempt is clean. */
(async () => {
  const url = process.argv[2] || 'http://127.0.0.1:8000/';
  const br = await chromium.launch({ channel: 'chrome', headless: true });
  const p = await (await br.newContext({ viewport: { width: 1280, height: 1000 } })).newPage();
  await p.goto(url, { waitUntil: 'domcontentloaded' });
  await p.waitForTimeout(4000);
  await p.addScriptTag({ path: '/Users/mac/projects/KlassApp-quickstart/node_modules/axe-core/axe.min.js' });
  const attempts = [];
  for (let i = 1; i <= 6; i++) {
    const before = await p.evaluate(() => {
      const c = document.querySelector('.hero-role-card.is-active');
      return c ? parseFloat(getComputedStyle(c).opacity) : null;
    });
    const r = await p.evaluate(async () => {
      const res = await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2aa'] } });
      return res.violations.map((v) => ({ id: v.id, nodes: v.nodes.map((n) => (n.target || []).join(' ')).slice(0, 6) }));
    });
    const after = await p.evaluate(() => {
      const c = document.querySelector('.hero-role-card.is-active');
      return c ? parseFloat(getComputedStyle(c).opacity) : null;
    });
    attempts.push({ attempt: i, opacityBefore: before, opacityAfter: after, violations: r });
    if (r.length === 0) break;
    await p.waitForTimeout(1100);
  }
  console.log(JSON.stringify(attempts, null, 1));
  const clean = attempts.filter((a) => a.violations.length === 0).length;
  console.log(clean ? `CLEAN attempts: ${clean}/${attempts.length}` : `NO CLEAN ATTEMPT in ${attempts.length}`);
  await br.close();
})();
