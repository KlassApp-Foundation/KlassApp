#!/usr/bin/env node
/**
 * Live verify Phase 3 parent web auth shell on production.
 *
 * Usage: node scripts/live-verify-parent-phase3.mjs
 */
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp.xyz';
const INBOUND = `${BASE}/api/whatsapp/inbound`;
const SCHOOL_A_KLS = 'KLS1020001';
const PHONE_RAW = `256700${String(Date.now()).slice(-6)}`;
const PHONE = `+${PHONE_RAW}`;
const EMAIL = `parent.p3.${Date.now()}@live-verify.klassapp.xyz`;
const PASSWORD = 'Phase3LiveVerify!';
const ARTIFACT = path.join(process.cwd(), 'tmp', 'live-verify-parent-phase3');

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

function metaPayload(body, msgId) {
  return {
    object: 'whatsapp_business_account',
    entry: [
      {
        id: 'live-verify-p3',
        changes: [
          {
            value: {
              messaging_product: 'whatsapp',
              metadata: { phone_number_id: 'live-verify-p3' },
              contacts: [{ wa_id: PHONE_RAW }],
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
  const res = await fetch(INBOUND, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(metaPayload(body, `wamid.p3.${step}.${Date.now()}`)),
  });
  return { status: res.status, body: await res.text() };
}

function setParentCredentials() {
  const php = `
    $wa = \\App\\Models\\WhatsAppUser::where('phone', '${PHONE}')->first();
    if (!$wa) { echo json_encode(['ok' => false]); return; }
    $parent = \\App\\Models\\User::find($wa->user_id);
    $parent->email = '${EMAIL}';
    $parent->password = bcrypt('${PASSWORD}');
    $parent->save();
    echo json_encode(['ok' => true, 'parent_id' => $parent->id, 'email' => $parent->email]);
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

async function loginAndFetchDashboard() {
  const loginPage = await fetch(`${BASE}/login`);
  const loginHtml = await loginPage.text();
  const pageCookies = loginPage.headers.getSetCookie?.() ?? [];
  const cookieJar = pageCookies.map((c) => c.split(';')[0]).join('; ');
  const tokenMatch = loginHtml.match(/name="_token" value="([^"]+)"/);
  const csrf = tokenMatch?.[1] ?? '';

  const loginRes = await fetch(`${BASE}/login`, {
    method: 'POST',
    redirect: 'manual',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      Cookie: cookieJar,
    },
    body: new URLSearchParams({ _token: csrf, email: EMAIL, password: PASSWORD }),
  });

  const loginSetCookies = loginRes.headers.getSetCookie?.() ?? [];
  const sessionCookie =
    extractSessionCookie(loginSetCookies) || extractSessionCookie(pageCookies);

  let dashboardHtml = '';
  let dashboardStatus = null;

  if (loginRes.status === 302 && sessionCookie) {
    const loc = loginRes.headers.get('location') || '';
    const dashUrl = loc.startsWith('http') ? loc : `${BASE}${loc}`;
    const dashRes = await fetch(dashUrl, {
      redirect: 'manual',
      headers: { Cookie: sessionCookie },
    });
    dashboardStatus = dashRes.status;
    if (dashRes.status === 302) {
      const follow = await fetch(dashRes.headers.get('location') || `${BASE}/login`, {
        headers: { Cookie: sessionCookie },
      });
      dashboardStatus = follow.status;
      dashboardHtml = await follow.text();
    } else {
      dashboardHtml = await dashRes.text();
    }
  }

  return {
    loginStatus: loginRes.status,
    loginLocation: loginRes.headers.get('location') || '',
    sessionCookie: sessionCookie ? '(set)' : '',
    dashboardStatus,
    dashboardHtml,
  };
}

async function main() {
  fs.mkdirSync(ARTIFACT, { recursive: true });
  const report = { startedAt: new Date().toISOString(), phone: PHONE, email: EMAIL, steps: [], pass: false };

  try {
    const link = await postInbound(SCHOOL_A_KLS, 'link');
    await new Promise((r) => setTimeout(r, 2000));
    const creds = setParentCredentials();
    report.steps.push({ step: 'link_and_credentials', link, creds });

    const auth = await loginAndFetchDashboard();
    report.steps.push({ step: 'password_login', auth });

    const checks = {
      linkOk: link.status === 200,
      credentialsSet: creds.ok === true,
      loginRedirectsParentDashboard: auth.loginLocation.includes('/parent/dashboard'),
      dashboardLoads: auth.dashboardStatus === 200,
      shellHasPortalTitle: auth.dashboardHtml.includes('Parent Portal'),
      shellHasNavDashboard: auth.dashboardHtml.includes('Dashboard'),
      shellHasNavChildren: auth.dashboardHtml.includes('Children'),
    };

    report.checks = checks;
    report.pass = Object.values(checks).every(Boolean);

    fs.writeFileSync(path.join(ARTIFACT, 'REPORT.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify({ pass: report.pass, checks, email: EMAIL, artifact: ARTIFACT }, null, 2));
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
