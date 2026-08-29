#!/usr/bin/env node
/**
 * Live verify Phase 5 parent dashboard UI on production.
 * 2-school parent: child selector by school + fees/grades panels.
 *
 * Usage: node scripts/live-verify-parent-phase5.mjs
 */
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp.xyz';
const INBOUND = `${BASE}/api/whatsapp/inbound`;
const SCHOOL_A_KLS = 'KLS1020001';
const SCHOOL_B_KLS = 'KLS1030001';
const PHONE_RAW = `256700${String(Date.now()).slice(-6)}`;
const PHONE = `+${PHONE_RAW}`;
const EMAIL = `parent.p5.${Date.now()}@live-verify.klassapp.xyz`;
const PASSWORD = 'Phase5LiveVerify!';
const ARTIFACT = path.join(process.cwd(), 'tmp', 'live-verify-parent-phase5');

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
        id: 'live-verify-p5',
        changes: [
          {
            value: {
              messaging_product: 'whatsapp',
              metadata: { phone_number_id: 'live-verify-p5' },
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
    body: JSON.stringify(metaPayload(body, `wamid.p5.${step}.${Date.now()}`)),
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
    $schoolNames = \\App\\Models\\School::whereIn('id', $links->pluck('school_id')->filter())->pluck('name', 'id');
    echo json_encode([
      'parent_id' => $parent?->id,
      'parent_school_id' => $parent?->school_id,
      'school_names' => $schoolNames,
      'links' => $links->map(fn($l) => [
        'school_id' => $l->school_id,
        'student_id' => $l->student_id,
        'student_name' => $l->userStudent?->name,
        'school_name' => $schoolNames[$l->school_id] ?? null,
      ])->values(),
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
    echo json_encode(['ok' => true, 'parent_id' => $parent->id]);
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

async function fetchHtml(urlPath, sessionCookie) {
  const res = await fetch(`${BASE}${urlPath}`, {
    headers: { Cookie: sessionCookie, Accept: 'text/html' },
  });
  return { status: res.status, html: await res.text() };
}

async function main() {
  fs.mkdirSync(ARTIFACT, { recursive: true });
  const report = { startedAt: new Date().toISOString(), phone: PHONE, email: EMAIL, steps: [], pass: false };

  try {
    await postInbound(SCHOOL_A_KLS, 'link-a');
    await new Promise((r) => setTimeout(r, 2000));
    await postInbound(SCHOOL_B_KLS, 'link-b');
    await new Promise((r) => setTimeout(r, 2000));

    const creds = setCredentials();
    const state = dbState();
    report.steps.push({ step: 'setup', creds, state });

    const { loginRes, sessionCookie } = await loginSession();
    const links = state.links || [];
    const schoolA = links.find((l) => l.school_id === 102);
    const schoolB = links.find((l) => l.school_id === 103);

    const dashA = schoolA
      ? await fetchHtml(`/parent/dashboard?child=${schoolA.student_id}`, sessionCookie)
      : null;
    const dashB = schoolB
      ? await fetchHtml(`/parent/dashboard?child=${schoolB.student_id}`, sessionCookie)
      : null;
    const childrenPage = await fetchHtml('/parent/children', sessionCookie);

    fs.writeFileSync(path.join(ARTIFACT, 'dashboard-a.html'), dashA?.html || '');
    fs.writeFileSync(path.join(ARTIFACT, 'dashboard-b.html'), dashB?.html || '');

    report.steps.push({
      step: 'pages',
      loginStatus: loginRes.status,
      loginLocation: loginRes.headers.get('location'),
      dashAStatus: dashA?.status,
      dashBStatus: dashB?.status,
      childrenStatus: childrenPage.status,
    });

    const htmlA = dashA?.html || '';
    const htmlB = dashB?.html || '';
    const htmlChildren = childrenPage.html || '';

    const nameA = schoolA?.school_name || '';
    const nameB = schoolB?.school_name || '';
    const firstA = (schoolA?.student_name || '').split(/\s+/)[0] || '';
    const firstB = (schoolB?.student_name || '').split(/\s+/)[0] || '';

    const checks = {
      linkCountOk: links.length === 2,
      parentSchoolNull: state.parent_school_id === null,
      loginOk: loginRes.status === 302 && (loginRes.headers.get('location') || '').includes('/parent/dashboard'),
      dashALoads: dashA?.status === 200,
      dashBLoads: dashB?.status === 200,
      selectorPresent: htmlA.includes('parent-child-selector') && htmlB.includes('parent-child-selector'),
      schoolANameOnDash: nameA !== '' && htmlA.includes(nameA) && htmlB.includes(nameA),
      schoolBNameOnDash: nameB !== '' && htmlA.includes(nameB) && htmlB.includes(nameB),
      childAVisible: firstA !== '' && htmlA.includes(firstA),
      childBVisible: firstB !== '' && htmlB.includes(firstB),
      feesA450: htmlA.includes('450,000') || htmlA.includes('450000'),
      feesB650: htmlB.includes('650,000') || htmlB.includes('650000'),
      feesScopedToB: htmlB.includes('650,000') || htmlB.includes('650000')
        ? !htmlB.includes('450,000')
        : false,
      usesDsKpi: htmlA.includes('ds-kpi-card') && !htmlA.includes('class="dashboard-kpi-card'),
      feesPanel: htmlA.includes('Fee Balance'),
      gradesPanel: htmlA.includes('Grades'),
      childrenPageOk: childrenPage.status === 200 && nameA !== '' && htmlChildren.includes(nameA),
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
