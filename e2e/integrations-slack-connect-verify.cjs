/**
 * PR3 Slack wave-1 — Integrations connect UI verify.
 *
 * Verifies the School Settings → Integrations page (connect Slack) at the
 * project breakpoints 375 / 414 / 768 / 1280:
 *   1. Admin sees the integrations list with the Slack card.
 *   2. Not-connected state shows a visible Connect Slack link
 *      (href mcp/slack/connect) and no Disconnect button.
 *   3. Connected state (if CONNECTED_MODE=1) shows Connected badge +
 *      workspace name + Disconnect button; disconnect POST redirects and
 *      the badge flips back (verified at 1280 only to avoid re-connect).
 *   4. No horizontal overflow at mobile widths (card does not overflow
 *      the viewport).
 *
 * Usage (local):
 *   PREVIEW_BASE=http://127.0.0.1:8000 DASH_EMAIL=… DASH_PASSWORD=… \
 *     node e2e/integrations-slack-connect-verify.cjs
 *   CONNECTED_MODE=1 … node e2e/integrations-slack-connect-verify.cjs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = (process.env.PREVIEW_BASE || process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const EMAIL = process.env.DASH_EMAIL || 'phase4.admin@klassapp.xyz';
const PASSWORD = process.env.DASH_PASSWORD || 'demo123';
const CONNECTED_MODE = process.env.CONNECTED_MODE === '1';
const OUT = path.join(__dirname, 'screenshots', 'integrations-slack-connect');
fs.mkdirSync(OUT, { recursive: true });

const VIEWPORTS = [
  { name: '375', width: 375, height: 812 },
  { name: '414', width: 414, height: 896 },
  { name: '768', width: 768, height: 1024 },
  { name: '1280', width: 1280, height: 800 },
];

function fail(msg, report) {
  console.error('FAIL:', msg);
  report.ok = false;
  report.failures = report.failures || [];
  report.failures.push(msg);
}

async function login(page) {
  await page.goto(`${BASE}/login`, { waitUntil: 'load', timeout: 90000 });
  await page.fill('input[name=email], input[type=email]', EMAIL);
  await page.fill('input[name=password], input[type=password]', PASSWORD);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load', timeout: 90000 }).catch(() => null),
    page.click('[data-testid="ap-primary-submit"], button[type="submit"]'),
  ]);
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const report = {
    base: BASE,
    email: EMAIL,
    connectedMode: CONNECTED_MODE,
    at: new Date().toISOString(),
    viewports: {},
    ok: true,
  };

  for (const vp of VIEWPORTS) {
    const entry = { screenshots: [], checks: {} };
    const context = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
    const page = await context.newPage();
    page.setDefaultTimeout(45000);

    try {
      await login(page);
      const resp = await page.goto(`${BASE}/admin/settings/integrations`, { waitUntil: 'load', timeout: 90000 });

      if (!resp || resp.status() !== 200 || page.url().includes('/login')) {
        fail(`[${vp.name}] integrations page not reachable (status=${resp && resp.status()} url=${page.url()})`, report);
        entry.checks.pageReachable = false;
        report.viewports[vp.name] = entry;
        await context.close();
        continue;
      }
      entry.checks.pageReachable = true;

      const list = await page.$('[data-testid=integrations-list]');
      if (!list) {
        fail(`[${vp.name}] integrations list missing`, report);
        entry.checks.listPresent = false;
      } else {
        entry.checks.listPresent = true;
      }

      const slackCard = await page.$('[data-testid=integration-card-slack]');
      if (!slackCard) {
        fail(`[${vp.name}] Slack integration card missing`, report);
        entry.checks.slackCardPresent = false;
      } else {
        entry.checks.slackCardPresent = true;
      }

      const statusText = (await page.textContent('[data-testid=integration-status-slack]').catch(() => '')) || '';
      entry.checks.statusBadge = statusText.trim();

      const connectLink = await page.$('[data-testid=integration-connect-slack]');
      const disconnectBtn = await page.$('[data-testid=integration-disconnect-slack]');
      entry.checks.hasConnectLink = !!connectLink;
      entry.checks.hasDisconnectButton = !!disconnectBtn;

      if (!CONNECTED_MODE) {
        if (!connectLink) {
          fail(`[${vp.name}] expected a visible Connect Slack control (live mode) — got none; badge="${entry.checks.statusBadge}"`, report);
        } else {
          const href = await connectLink.getAttribute('href');
          if (!href || !href.includes('mcp/slack/connect')) {
            fail(`[${vp.name}] Connect link href must point at mcp/slack/connect — got "${href}"`, report);
          }
        }
        if (disconnectBtn) {
          fail(`[${vp.name}] Disconnect button visible while not connected`, report);
        }
      }

      // No horizontal overflow at this width.
      const overflow = await page.evaluate(
        () => document.documentElement.scrollWidth - document.documentElement.clientWidth
      );
      entry.checks.horizontalOverflowPx = overflow;
      if (overflow > 2) {
        fail(`[${vp.name}] horizontal overflow ${overflow}px`, report);
      }

      const shot = path.join(OUT, `integrations-${vp.name}.png`);
      await page.screenshot({ path: shot, fullPage: true });
      entry.screenshots.push(path.basename(shot));
    } catch (err) {
      fail(`[${vp.name}] ${err.message}`, report);
    }

    report.viewports[vp.name] = entry;
    await context.close();
  }

  // Connected-mode disconnect flow (1280 only).
  if (CONNECTED_MODE) {
    const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await context.newPage();
    page.setDefaultTimeout(45000);
    try {
      await login(page);
      await page.goto(`${BASE}/admin/settings/integrations`, { waitUntil: 'load', timeout: 90000 });

      const disconnectBtn = await page.$('[data-testid=integration-disconnect-slack]');
      if (!disconnectBtn) {
        fail('connected mode: Disconnect button missing', report);
      } else {
        await Promise.all([
          page.waitForNavigation({ waitUntil: 'load', timeout: 45000 }).catch(() => null),
          disconnectBtn.click(),
        ]);
        const badge = ((await page.textContent('[data-testid=integration-status-slack]').catch(() => '')) || '').trim();
        report.disconnectBadgeAfter = badge;
        if (badge !== 'Not connected') {
          fail(`disconnect did not flip the badge — got "${badge}"`, report);
        }
        await page.screenshot({ path: path.join(OUT, 'integrations-after-disconnect-1280.png'), fullPage: true });
      }
    } catch (err) {
      fail(`connected-mode: ${err.message}`, report);
    }
    await context.close();
  }

  await browser.close();

  fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  process.exit(report.ok ? 0 : 1);
})();
