#!/usr/bin/env node
/**
 * Live verify Phase 2 parent magic-link auth on production.
 * 1. Link a child via KLS (establishes ug7 parent + WhatsAppUser)
 * 2. Simulated inbound WEB_LOGIN → outbound magic link
 * 3. Real HTTP GET on signed URL → parent dashboard with session
 * 4. Second GET on same URL → rejected (single-use)
 *
 * Usage: node scripts/live-verify-parent-phase2.mjs
 */
import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp.xyz';
const INBOUND = `${BASE}/api/whatsapp/inbound`;
const SSH = 'ssh -i ~/.ssh/id_ed25519_do root@46.101.111.131';
const DOCKER = 'docker exec sms-app php artisan tinker --execute';

const SCHOOL_A_KLS = 'KLS1020001';
const PHONE = `256700${String(Date.now()).slice(-6)}`;
const ARTIFACT = path.join(process.cwd(), 'tmp', 'live-verify-parent-phase2');

function sshTinker(php) {
  const escaped = php
    .replace(/\\/g, '\\\\')
    .replace(/"/g, '\\"')
    .replace(/\$/g, '\\$');
  return execSync(`${SSH} "${DOCKER} \\"${escaped}\\""`, {
    encoding: 'utf8',
    maxBuffer: 10 * 1024 * 1024,
  }).trim();
}

function metaPayload(body, msgId) {
  return {
    object: 'whatsapp_business_account',
    entry: [
      {
        id: 'live-verify-p2',
        changes: [
          {
            value: {
              messaging_product: 'whatsapp',
              metadata: { display_phone_number: '15550000000', phone_number_id: 'live-verify-p2' },
              contacts: [{ profile: { name: 'Phase2 Magic Parent' }, wa_id: PHONE }],
              messages: [
                {
                  from: PHONE,
                  id: msgId,
                  timestamp: String(Math.floor(Date.now() / 1000)),
                  type: 'text',
                  text: { body },
                },
              ],
            },
            field: 'messages',
          },
        ],
      },
    ],
  };
}

async function postInbound(body, step) {
  const msgId = `wamid.live.p2.${step}.${Date.now()}`;
  const res = await fetch(INBOUND, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(metaPayload(body, msgId)),
  });
  return { status: res.status, body: await res.text(), msgId };
}

function latestMagicLinkOutbound() {
  const php = `
    $row = \\App\\Models\\MessageDeliveryLog::where('phone', '${PHONE}')
      ->where('direction', 'outbound')
      ->where('flow_type', 'parent_magic_login')
      ->orderByDesc('id')
      ->first(['id','flow_type','content_preview','created_at']);
    if (!$row) { echo 'null'; return; }
    echo json_encode($row->toArray(), JSON_PRETTY_PRINT);
  `;
  const out = sshTinker(php);
  return out === 'null' ? null : JSON.parse(out);
}

function extractMagicUrl(preview) {
  if (!preview) return null;
  const m = preview.match(/https?:\\/\\/[^\\s]+\\/parent\\/magic-login\\/[^\\s]+/);
  return m ? m[0] : null;
}

function dbState() {
  const php = `
    $wa = \\App\\Models\\WhatsAppUser::where('phone', '${PHONE}')->first();
    $parent = $wa ? \\App\\Models\\User::find($wa->user_id) : null;
    $links = $parent
      ? \\App\\Models\\StudentParentLink::where('parent_id', $parent->id)->where('status', 1)->count()
      : 0;
    echo json_encode([
      'phone' => '${PHONE}',
      'whatsapp_user_id' => $wa?->id,
      'parent_id' => $parent?->id,
      'usergroup_id' => $parent?->usergroup_id,
      'link_count' => $links,
    ], JSON_PRETTY_PRINT);
  `;
  return JSON.parse(sshTinker(php));
}

function cleanup() {
  const php = `
    $wa = \\App\\Models\\WhatsAppUser::where('phone', '${PHONE}')->first();
    if (!$wa) { echo 'no cleanup'; return; }
    $pid = $wa->user_id;
    \\App\\Models\\StudentParentLink::where('parent_id', $pid)->delete();
    \\App\\Models\\MessageDeliveryLog::where('phone', '${PHONE}')->delete();
    $wa->delete();
    if ($pid) {
      \\App\\Models\\Userprofile::where('user_id', $pid)->delete();
      \\App\\Models\\User::where('id', $pid)->where('usergroup_id', 7)->delete();
    }
    echo 'cleaned ${PHONE}';
  `;
  return sshTinker(php);
}

async function followMagicLink(url, cookieJar = '') {
  const res = await fetch(url, {
    method: 'GET',
    redirect: 'manual',
    headers: cookieJar ? { Cookie: cookieJar } : {},
  });
  const setCookie = res.headers.get('set-cookie') || '';
  const location = res.headers.get('location') || '';
  const html = await res.text();
  return {
    status: res.status,
    location,
    setCookie,
    onDashboard: location.includes('/parent/dashboard') || html.includes('Parent Dashboard'),
    htmlSnippet: html.slice(0, 500),
  };
}

async function main() {
  fs.mkdirSync(ARTIFACT, { recursive: true });
  const report = {
    startedAt: new Date().toISOString(),
    phone: PHONE,
    steps: [],
    pass: false,
  };

  try {
    const linkA = await postInbound(SCHOOL_A_KLS, 'link');
    await new Promise((r) => setTimeout(r, 2000));
    const afterLink = dbState();
    report.steps.push({ step: 'link_kls', inbound: linkA, db: afterLink });

    const webLogin = await postInbound('WEB_LOGIN', 'web_login');
    await new Promise((r) => setTimeout(r, 2000));
    const outbound = latestMagicLinkOutbound();
    const magicUrl = extractMagicUrl(outbound?.content_preview || '');
    report.steps.push({ step: 'web_login', inbound: webLogin, outbound, magicUrl });

    let firstClick = null;
    let secondClick = null;
    let cookieJar = '';

    if (magicUrl) {
      firstClick = await followMagicLink(magicUrl);
      if (firstClick.setCookie) {
        cookieJar = firstClick.setCookie.split(';')[0];
      }
      if (firstClick.status === 302 && firstClick.location) {
        const dash = await fetch(`${BASE}${firstClick.location.startsWith('http') ? new URL(firstClick.location).pathname : firstClick.location}`, {
          headers: cookieJar ? { Cookie: cookieJar } : {},
        });
        firstClick.dashboardStatus = dash.status;
        firstClick.dashboardHasTitle = (await dash.text()).includes('Parent Dashboard');
      }
      secondClick = await followMagicLink(magicUrl, cookieJar);
    }

    report.steps.push({ step: 'click_magic_link', firstClick, secondClick });

    const dashboardKeyword = await postInbound('DASHBOARD', 'dashboard');
    await new Promise((r) => setTimeout(r, 1500));
    const dashOutbound = latestMagicLinkOutbound();
    report.steps.push({
      step: 'dashboard_keyword',
      inbound: dashboardKeyword,
      outboundFlow: dashOutbound?.flow_type,
      hasMagicPath: (dashOutbound?.content_preview || '').includes('/parent/magic-login/'),
    });

    const checks = {
      linkInboundOk: linkA.status === 200,
      parentUg7WithLinks: afterLink.usergroup_id === 7 && afterLink.link_count >= 1,
      webLoginInboundOk: webLogin.status === 200,
      magicLinkSent: outbound?.flow_type === 'parent_magic_login' && !!magicUrl,
      magicUrlSigned: magicUrl?.includes('signature=') ?? false,
      firstClickRedirects: firstClick?.status === 302 && (firstClick?.location || '').includes('parent/dashboard'),
      dashboardLoads: firstClick?.dashboardHasTitle === true || firstClick?.dashboardStatus === 200,
      secondClickRejected: secondClick?.status === 403 || (secondClick?.status === 302 && !(secondClick?.location || '').includes('dashboard')),
      dashboardKeywordWorks: dashboardKeyword.status === 200,
    };

    report.checks = checks;
    report.pass = Object.values(checks).every(Boolean);

    fs.writeFileSync(path.join(ARTIFACT, 'REPORT.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify({ pass: report.pass, checks, phone: PHONE, magicUrl, artifact: ARTIFACT }, null, 2));

    if (!report.pass) process.exitCode = 1;
  } finally {
    console.log('Cleaning up…');
    console.log(cleanup());
  }
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
