#!/usr/bin/env node
/**
 * Live verify Phase 2 parent magic-link auth on production.
 *
 * Usage: node scripts/live-verify-parent-phase2.mjs
 */
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp.xyz';
const INBOUND = `${BASE}/api/whatsapp/inbound`;

const SCHOOL_A_KLS = 'KLS1020001';
const PHONE_RAW = `256700${String(Date.now()).slice(-6)}`;
const PHONE = `+${PHONE_RAW}`;
const ARTIFACT = path.join(process.cwd(), 'tmp', 'live-verify-parent-phase2');

function sshTinker(php) {
  const b64 = Buffer.from(php.trim()).toString('base64');
  const remote = `docker exec sms-app sh -c 'php artisan tinker --execute "$(echo ${b64} | base64 -d)"'`;
  return execFileSync(
    'ssh',
    ['-i', `${process.env.HOME}/.ssh/id_ed25519_do`, 'root@46.101.111.131', remote],
    { encoding: 'utf8', maxBuffer: 10 * 1024 * 1024 },
  ).trim();
}

function parseTinkerJson(out) {
  const candidates = out
    .split('\n')
    .map((l) => l.trim())
    .filter((l) => l.startsWith('{') || l.startsWith('['));
  if (candidates.length === 0) {
    throw new Error(`No JSON in tinker output: ${out.slice(0, 300)}`);
  }
  return JSON.parse(candidates[candidates.length - 1]);
}

function parseTinkerUrl(out) {
  const line = out.split('\n').find((l) => l.trim().startsWith('http'));
  if (!line) {
    throw new Error(`No URL in tinker output: ${out.slice(0, 200)}`);
  }
  return line.trim();
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
              contacts: [{ profile: { name: 'Phase2 Magic Parent' }, wa_id: PHONE_RAW }],
              messages: [
                {
                  from: PHONE_RAW,
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
      ->where('content_preview', 'like', '%magic-login%')
      ->orderByDesc('id')
      ->first(['id','flow_type','category','content_preview','sent_at']);
    if (!$row) { echo 'null'; return; }
    echo json_encode($row->toArray());
  `;
  const out = sshTinker(php);
  return out === 'null' ? null : parseTinkerJson(out);
}

function issueFreshMagicUrl(parentId) {
  const php = `
    $parent = \\App\\Models\\User::find(${parentId});
    echo app(\\App\\Services\\ParentMagicLoginService::class)
      ->issueLinkForPhone('${PHONE}', $parent);
  `;
  return parseTinkerUrl(sshTinker(php));
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
      'whatsapp_user_id' => $wa ? $wa->id : null,
      'parent_id' => $parent ? $parent->id : null,
      'usergroup_id' => $parent ? $parent->usergroup_id : null,
      'link_count' => $links,
    ], 0);
  `;
  return parseTinkerJson(sshTinker(php));
}

function cleanup() {
  const php = `
    $wa = \\App\\Models\\WhatsAppUser::where('phone', '${PHONE}')->first();
    if (!$wa) { echo 'no cleanup needed'; return; }
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

function extractSessionCookie(setCookieHeaders) {
  const headers = setCookieHeaders ?? [];
  for (const header of headers) {
    const segments = header.split(/,(?=[^;]+=[^;]+)/);
    for (const segment of segments) {
      const pair = segment.trim().split(';')[0];
      if (pair.startsWith('klassapp_session=')) {
        return pair;
      }
    }
  }
  return '';
}

async function followMagicLink(url) {
  const res = await fetch(url, { method: 'GET', redirect: 'manual' });
  const setCookies = res.headers.getSetCookie?.() ?? [];
  const location = res.headers.get('location') || '';
  const sessionCookie = extractSessionCookie(setCookies);

  let dashboardHasTitle = false;
  let dashboardStatus = null;

  if (res.status === 302 && location && sessionCookie) {
    const dashUrl = location.startsWith('http') ? location : `${BASE}${location}`;
    const dash = await fetch(dashUrl, { headers: { Cookie: sessionCookie } });
    dashboardStatus = dash.status;
    dashboardHasTitle = (await dash.text()).includes('Parent Portal');
  }

  return {
    status: res.status,
    location,
    sessionCookie: sessionCookie ? '(set)' : '',
    dashboardStatus,
    dashboardHasTitle,
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
    report.steps.push({ step: 'web_login', inbound: webLogin, outbound });

    const magicUrl = afterLink.parent_id ? issueFreshMagicUrl(afterLink.parent_id) : null;
    report.steps.push({ step: 'issue_click_url', magicUrl });

    const firstClick = magicUrl?.startsWith('http') ? await followMagicLink(magicUrl) : null;
    const secondClick = magicUrl?.startsWith('http') ? await followMagicLink(magicUrl) : null;
    report.steps.push({ step: 'click_magic_link', firstClick, secondClick });

    const dashboardKeyword = await postInbound('DASHBOARD', 'dashboard');
    await new Promise((r) => setTimeout(r, 1500));
    const dashOutbound = latestMagicLinkOutbound();
    report.steps.push({
      step: 'dashboard_keyword',
      inbound: dashboardKeyword,
      outbound: dashOutbound,
    });

    const preview = outbound?.content_preview || '';
    const checks = {
      linkInboundOk: linkA.status === 200,
      parentUg7WithLinks: afterLink.usergroup_id === 7 && afterLink.link_count >= 1,
      webLoginInboundOk: webLogin.status === 200,
      magicLinkSent: preview.includes('/parent/magic-login/'),
      firstClickRedirects:
        firstClick?.status === 302 && (firstClick?.location || '').includes('parent/dashboard'),
      dashboardLoads: firstClick?.dashboardHasTitle === true,
      secondClickRejected: secondClick?.status === 403,
      dashboardKeywordWorks:
        dashboardKeyword.status === 200
        && (dashOutbound?.content_preview || '').includes('/parent/magic-login/'),
    };

    report.checks = checks;
    report.pass = Object.values(checks).every(Boolean);

    fs.writeFileSync(path.join(ARTIFACT, 'REPORT.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify({ pass: report.pass, checks, phone: PHONE, artifact: ARTIFACT }, null, 2));

    if (!report.pass) process.exitCode = 1;
  } finally {
    console.log('Cleaning up…');
    try {
      console.log(cleanup());
    } catch (e) {
      console.error('Cleanup failed:', e.message);
    }
  }
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
