const fs = require('fs');
const { chromium } = require('playwright');
const path = require('path');

const root = path.resolve(__dirname, '../..');
const jquery = path.join(root, 'public/js/jquery.min.js');
const custom = path.join(root, 'public/js/custom.js');

function assert(cond, message) {
  if (!cond) {
    throw new Error(message);
  }
}

async function testA13(page) {
  await page.setViewportSize({ width: 1280, height: 680 });
  await page.setContent(`<!doctype html><body style="margin:0">
    <div id="admin-sidebar" style="position:fixed;left:0;top:64px;width:248px;height:616px;box-sizing:border-box;">
      <div class="profile-click account-card" style="position:absolute;left:29px;bottom:8px;width:190px;">
        <button type="button" data-account-trigger aria-controls="account-menu" aria-expanded="false" style="width:190px;height:56px;">Account</button>
        <div id="account-menu" data-account-menu hidden role="menu">
          <button type="button" role="menuitem">Edit profile</button>
        </div>
      </div>
    </div>
    <a href="#content">Behind</a>
  </body>`);
  await page.addScriptTag({ path: jquery });
  await page.addScriptTag({ path: custom });
  await page.click('[data-account-trigger]');
  const box = await page.evaluate(() => {
    const menu = document.querySelector('[data-account-menu]');
    const side = document.getElementById('admin-sidebar').getBoundingClientRect();
    const card = document.querySelector('[data-account-trigger]').getBoundingClientRect();
    const m = menu.getBoundingClientRect();
    return {
      menuRight: Math.round(m.right),
      sideRight: Math.round(side.right),
      menuLeft: Math.round(m.left),
      cardLeft: Math.round(card.left),
      menuWidth: Math.round(m.width),
      cardWidth: Math.round(card.width),
      gap: Math.round(card.top - m.bottom),
    };
  });
  assert(box.menuRight <= box.sideRight, 'A13 menu overhangs the sidebar: ' + JSON.stringify(box));
  assert(Math.abs(box.menuLeft - box.cardLeft) <= 1, 'A13 menu is not on the card left edge: ' + JSON.stringify(box));
  assert(Math.abs(box.menuWidth - box.cardWidth) <= 1, 'A13 menu width is not the card width: ' + JSON.stringify(box));
  assert(box.gap === 8, 'A13 gap is not 8px: ' + JSON.stringify(box));
  return box;
}

async function testK111(page) {
  await page.setViewportSize({ width: 375, height: 812 });
  const blade = fs.readFileSync(path.join(root, 'resources/views/layouts/admin/sidebar.blade.php'), 'utf8');
  const logo = (blade.match(/ka-drawer-logo" src="\{\{ asset\('([^']+)'\) \}\}/) || [])[1] || '';
  const button = (blade.match(/id="drawer-close"[^>]*>([\s\S]*?)<\/button>/) || [])[1] || '';
  await page.setContent(`<!doctype html><body style="margin:0">
    <button type="button" id="mobile-menu-trigger" aria-expanded="false">Menu</button>
    <div id="res_sidebar" class="hidden">
      <div class="ka-drawer-head">
        <img class="ka-drawer-logo" src="/${logo}" alt="" height="24">
        <button type="button" id="drawer-close" class="ka-drawer-close" aria-label="Close menu">${button}</button>
      </div>
    </div>
    <div class="dashboard-content-area"><a id="behind" href="#x">Behind the drawer</a></div>
  </body>`);
  await page.addScriptTag({ path: jquery });
  await page.addScriptTag({ path: custom });
  await page.click('#mobile-menu-trigger');
  const opened = await page.evaluate(() => ({
    focus: document.activeElement && document.activeElement.id,
    label: document.getElementById('drawer-close').getAttribute('aria-label'),
    icon: document.getElementById('drawer-close').textContent.trim(),
    inert: document.querySelector('.dashboard-content-area').hasAttribute('inert'),
    logo: document.querySelector('.ka-drawer-logo').getAttribute('src'),
  }));
  assert(opened.focus === 'drawer-close', 'K111 focus did not move to Close: ' + JSON.stringify(opened));
  assert(opened.label === 'Close menu', 'K111 aria-label: ' + opened.label);
  assert(opened.icon === '✕', 'K111 icon: ' + opened.icon);
  assert(opened.inert === true, 'K111 content is not inert');
  assert(opened.logo.includes('klassapp-horizontal-dark.svg'), 'K111 logo: ' + opened.logo);
  await page.keyboard.press('Tab');
  const tabbed = await page.evaluate(() => {
    const content = document.querySelector('.dashboard-content-area');
    return content.contains(document.activeElement);
  });
  assert(tabbed === false, 'K111 Tab reached the page behind the drawer');
  await page.keyboard.press('Escape');
  const closed = await page.evaluate(() => document.activeElement && document.activeElement.id);
  assert(closed === 'mobile-menu-trigger', 'K111 Escape did not return focus: ' + closed);
  return opened;
}

(async () => {
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const page = await browser.newPage();
  const a13 = await testA13(page);
  const k111 = await testK111(page);
  console.log(JSON.stringify({ a13, k111 }));
  await browser.close();
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
