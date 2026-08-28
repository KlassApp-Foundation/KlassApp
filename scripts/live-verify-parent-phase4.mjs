#!/usr/bin/env node
/**
 * Live verify Phase 4 parent portal data layer on production.
 * 2-school parent: cross-school fee scoping + web endpoint isolation.
 *
 * Usage: node scripts/live-verify-parent-phase4.mjs
 */
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp.xyz';
const INBOUND = `${BASE}/api/whatsapp/inbound`;
const SCHOOL_A_KLS = 'KLS1020001';
const SCHOOL_B_KLS = 'KLS1030001';
const SCHOOL_A_STUDENT = 'amina nakato50110';
const SCHOOL_B_STUDENT = 'lydia atim5158';
const PHONE_RAW = `256700${String(Date.now()).slice(-6)}`;
const PHONE = `+${PHONE_RAW}`;
const EMAIL = `parent.p4.${Date.now()}@live-verify.klassapp.xyz`;
const PASSWORD = 'Phase4LiveVerify!';
const ARTIFACT = path.join(process.cwd(), 'tmp', 'live-verify-parent-phase4');

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
        id: 'live-verify-p4',
        changes: [
          {
            value: {
              messaging_product: 'whatsapp',
              metadata: { phone_number_id: 'live-verify-p4' },
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
    body: JSON.stringify(metaPayload(body, `wamid.p4.${step}.${Date.now()}`)),
  });
  return { status: res.status, body: await res.text() };
}

function dbState() {
  const php = `
    $wa = \\App\\Models\\WhatsAppUser::where('phone', '${PHONE}')->first();
    $parent = $wa ? \\App\\Models\\User::find($wa->user_id) : null;
    $links = $parent
      ? \\App\\Models\\StudentParentLink::where('parent_id', $parent->id)->where('status', 1)
          ->with('userStudent:id,name,school_id')->get(['id','school_id','student_id'])
      : collect();
    $stranger = \\App\\Models\\User::where('usergroup_id', 6)->where('name', 'NOT A LINKED CHILD P4')->value('id');
    if (!$stranger) {
      $stranger = \\App\\Models\\User::where('usergroup_id', 6)->where('id', '!=', $links->pluck('student_id')->first() ?? 0)->value('id');
    }
    echo json_encode([
      'parent_id' => $parent?->id,
      'parent_school_id' => $parent?->school_id,
      'links' => $links->map(fn($l) => [
        'school_id' => $l->school_id,
        'student_id' => $l->student_id,
        'student_name' => $l->userStudent?->name,
      ])->values(),
      'stranger_student_id' => $stranger,
    ]);
  `;
  return parseTinkerJson(sshTinker(php));
}

function setCredentials() {
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

async function loginSession() {
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

  const sessionCookie =
    extractSessionCookie(loginRes.headers.getSetCookie?.() ?? []) ||
    extractSessionCookie(pageCookies);

  return { loginRes, sessionCookie };
}

async function fetchJson(path, sessionCookie) {
  const res = await fetch(`${BASE}${path}`, {
    headers: {
      Cookie: sessionCookie,
      Accept: 'application/json',
    },
  });
  const text = await res.text();
  let json = null;
  try {
    json = JSON.parse(text);
  } catch {
    json = { parseError: true, text: text.slice(0, 200) };
  }
  return { status: res.status, json, text: text.slice(0, 300) };
}

async function main() {
  fs.mkdirSync(ARTIFACT, { recursive: true });
  const report = { startedAt: new Date().toISOString(), phone: PHONE, email: EMAIL, steps: [], pass: false };

  try {
    const linkA = await postInbound(SCHOOL_A_KLS, 'link-a');
    await new Promise((r) => setTimeout(r, 2000));
    const linkB = await postInbound(SCHOOL_B_KLS, 'link-b');
    await new Promise((r) => setTimeout(r, 2000));

    const creds = setCredentials();
    const state = dbState();
    report.steps.push({ step: 'link_two_schools', linkA, linkB, creds, state });

    const { loginRes, sessionCookie } = await loginSession();
    report.steps.push({
      step: 'login',
      status: loginRes.status,
      location: loginRes.headers.get('location'),
      hasSession: Boolean(sessionCookie),
    });

    const links = state.links || [];
    const schoolAStudent = links.find((l) => l.school_id === 102);
    const schoolBStudent = links.find((l) => l.school_id === 103);

    const feesA = schoolAStudent
      ? await fetchJson(`/parent/children/${schoolAStudent.student_id}/fees`, sessionCookie)
      : null;
    const feesB = schoolBStudent
      ? await fetchJson(`/parent/children/${schoolBStudent.student_id}/fees`, sessionCookie)
      : null;
    const strangerId = state.stranger_student_id;
    const feesStranger = strangerId
      ? await fetchJson(`/parent/children/${strangerId}/fees`, sessionCookie)
      : null;

    const childrenPage = await fetch(`${BASE}/parent/children`, {
      headers: { Cookie: sessionCookie },
    });
    const childrenHtml = await childrenPage.text();

    report.steps.push({
      step: 'web_data',
      feesA,
      feesB,
      feesStranger,
      childrenPageStatus: childrenPage.status,
      childrenHtmlSnippet: childrenHtml.slice(0, 500),
    });

    const balanceA = feesA?.json?.data?.total_balance;
    const balanceB = feesB?.json?.data?.total_balance;

    const checks = {
      linkCountOk: links.length === 2,
      parentSchoolNull: state.parent_school_id === null,
      loginOk: loginRes.status === 302 && (loginRes.headers.get('location') || '').includes('/parent/dashboard'),
      sessionOk: Boolean(sessionCookie),
      feesAOk: feesA?.status === 200 && feesA?.json?.success === true,
      feesBOk: feesB?.status === 200 && feesB?.json?.success === true,
      schoolAFee450k: balanceA === 450000 || balanceA === 450000.0,
      schoolBFee650k: balanceB === 650000 || balanceB === 650000.0,
      strangerFees403: feesStranger?.status === 403,
      childrenPageOk: childrenPage.status === 200 && childrenHtml.includes('Linked student'),
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
