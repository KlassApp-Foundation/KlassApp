// Compose side-by-side (concept | live) evidence images for SHELL POLISH.
// Left = admin-mvp concept frame (rendered from app.html), Right = the REAL
// harness screenshot already verified green by shell-polish-verify.cjs.
// Output: evidence/shell-polish/pairs/<item>-<label>.png + a manifest.
const { chromium, webkit } = require('playwright');
const { mkdirSync, writeFileSync } = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');

const root = path.resolve(__dirname, '..');
const conceptUrl = 'file://' + path.join(root, 'design/system/concepts/admin-mvp/app.html');
const shots = path.join(root, 'e2e', 'screenshots', 'shell-polish');
const outDir = path.join(root, 'evidence', 'shell-polish', 'pairs');
mkdirSync(outDir, { recursive: true });

const engines = { chromium, webkit };

// [item, conceptQuery, conceptViewport, conceptEngine, liveFrameRelative]
const PAIRS = [
  ['item4', '?s=dashboard', { width: 1280, height: 900 }, 'chromium', 'chromium/1280/dashboard.png'],
  ['item5', '?s=dashboard', { width: 1280, height: 900 }, 'chromium', 'chromium/1280/dashboard.png'],
  ['item6', '?s=dashboard', { width: 1280, height: 900 }, 'chromium', 'chromium/1280/dashboard.png'],
  ['item7', '?s=dashboard', { width: 1280, height: 900 }, 'chromium', 'chromium/1280/dashboard.png'],
  ['item8', '?s=dashboard', { width: 1280, height: 900 }, 'chromium', 'chromium/1280/dashboard.png'],
  ['item9', '?s=dashboard', { width: 1280, height: 900 }, 'chromium', 'chromium/1280/dashboard.png'],
  ['item10', '?s=dashboard', { width: 1280, height: 900 }, 'chromium', 'chromium/1280/dashboard.png'],
  ['item11', '?s=dashboard', { width: 1280, height: 900 }, 'chromium', 'chromium/1280/dashboard.png'],
  ['item12', '?s=dashboard', { width: 375, height: 812 }, 'webkit', 'webkit/375/dashboard.png'],
  ['item12b', '?s=dashboard', { width: 1280, height: 900 }, 'chromium', 'chromium/1280/dashboard.png'],
  // PR A items (1-3) get evidence too, since the branch stacks on PR A
  ['item2', '?s=dashboard&acct=1', { width: 375, height: 812 }, 'webkit', 'webkit/375/phone-popover-open.png'],
  ['item2b', '?s=dashboard&acct=1', { width: 1280, height: 900 }, 'chromium', 'chromium/1280/laptop-popover-open.png'],
  ['item1', '?s=dashboard', { width: 375, height: 812 }, 'webkit', 'webkit/375/drawer-open.png'],
  ['item3', '?s=dashboard', { width: 375, height: 812 }, 'webkit', 'webkit/375/dashboard.png'],
];

async function shootConcept(query, viewport, eng) {
  const browser = await engines[eng].launch();
  const page = await browser.newPage({ viewport });
  await page.goto(conceptUrl + query, { waitUntil: 'load' });
  await page.waitForTimeout(1200); // async lucide icons from unpkg
  const tmp = path.join(outDir, `.tmp-${eng}-${viewport.width}.png`);
  await page.screenshot({ path: tmp, fullPage: false });
  await browser.close();
  return tmp;
}

function compose(leftPng, rightPng, outPng, label) {
  // Use a throwaway python one-liner via PIL to compose side-by-side with a
  // caption bar. Kept here so the evidence branch carries its own tooling.
  const py = `
from PIL import Image, ImageDraw
import sys
l = Image.open(sys.argv[1]).convert("RGB")
r = Image.open(sys.argv[2]).convert("RGB")
h = max(l.height, r.height)
gap = 16
bar = 28
canvas = Image.new("RGB", (l.width + r.width + gap, h + bar), (17,24,39))
d = ImageDraw.Draw(canvas)
d.text((8, 8), sys.argv[3] + "   LEFT=concept (admin-mvp)   RIGHT=live app (verified)", fill=(226,232,240))
canvas.paste(l, (0, bar))
canvas.paste(r, (l.width + gap, bar))
canvas.save(sys.argv[4])
`;
  execFileSync('python3', ['-c', py, leftPng, rightPng, label, outPng], { stdio: 'inherit' });
}

async function run() {
  const manifest = [];
  for (const [item, q, vp, eng, liveRel] of PAIRS) {
    const liveAbs = path.join(shots, liveRel);
    const left = await shootConcept(q, vp, eng);
    const outPng = path.join(outDir, `${item}.png`);
    compose(left, liveAbs, outPng, item.toUpperCase());
    require('node:fs').unlinkSync(left);
    manifest.push({ item, conceptQuery: q, viewport: vp, engine: eng, liveFrame: liveRel, pair: path.relative(root, outPng) });
    console.log('composed', item, '->', path.basename(outPng));
  }
  writeFileSync(path.join(outDir, 'manifest.json'), JSON.stringify(manifest, null, 2));
  console.log(JSON.stringify({ pairs: manifest.length, outDir }, null, 2));
}

run().catch((e) => { console.error(e); process.exit(1); });
