#!/usr/bin/env node
/**
 * Live verify: teacher web attendance is CT-scoped; sidebar Classes → /teacher/classes.
 */
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp.xyz';
const SSH_KEY = process.env.KLASSAPP_SSH_KEY || `${process.env.HOME}/.ssh/id_ed25519_do`;
const SSH_HOST = process.env.KLASSAPP_SSH_HOST || 'root@46.101.111.131';
const PASSWORD = 'Password123!';
const ARTIFACT = path.join(process.cwd(), 'tmp', 'live-verify-attendance-sidebar');

function shellQuote(s) {
    return `'${String(s).replace(/'/g, `'\\''`)}'`;
}

function sshTinker(php) {
    const oneLine = php.replace(/\n/g, ' ').replace(/\s+/g, ' ').trim();
    return execFileSync('ssh', [
        '-i', SSH_KEY, '-o', 'StrictHostKeyChecking=no', '-o', 'ConnectTimeout=20',
        SSH_HOST,
        `docker exec -w /var/www sms-app php artisan tinker --execute ${shellQuote(oneLine)}`,
    ], { encoding: 'utf8', timeout: 120_000, maxBuffer: 4 * 1024 * 1024 });
}

function parseJson(raw) {
    const start = raw.indexOf('{');
    const end = raw.lastIndexOf('}');
    return JSON.parse(raw.slice(start, end + 1));
}

function bootstrap(stamp) {
    const year = new Date().getFullYear();
    const ctEmail = `e2e.attct+${stamp}@example.test`;
    const raw = sshTinker(`
        $school = \\App\\Models\\School::create([
            'name' => 'E2E Att Scope ${stamp}',
            'email' => 'school-att-${stamp}@e2e.test',
            'phone' => '074${String(stamp).slice(-7)}',
            'slug' => 'e2e-att-${stamp}',
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'toshi_enabled' => 0,
        ]);
        $ay = \\App\\Models\\AcademicYear::create([
            'school_id' => $school->id,
            'name' => '${year}',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addMonths(6),
            'type' => 'Current Academic Year',
            'description' => 'Current Academic Year',
            'status' => 1,
        ]);
        \\Illuminate\\Support\\Facades\\Cache::forget('academic_year_for_school_'.$school->id);
        $sectionOwn = \\App\\Models\\Section::create(['school_id'=>$school->id,'name'=>'P.7 Att','status'=>1]);
        $sectionOther = \\App\\Models\\Section::create(['school_id'=>$school->id,'name'=>'P.6 Att','status'=>1]);
        $standard = \\App\\Models\\Standard::create(['school_id'=>$school->id,'name'=>'primary','order'=>1,'status'=>1]);
        $ct = \\App\\Models\\User::create([
            'school_id'=>$school->id,'usergroup_id'=>5,'name'=>'E2E Att CT',
            'email'=>${JSON.stringify(ctEmail)},'password'=>bcrypt('Password123!'),
            'status'=>'active','email_verified'=>1,
        ]);
        \\App\\Models\\Userprofile::create(['school_id'=>$school->id,'user_id'=>$ct->id,'usergroup_id'=>5,'firstname'=>'E2E','lastname'=>'CT','status'=>'active']);
        $peer = \\App\\Models\\User::create([
            'school_id'=>$school->id,'usergroup_id'=>5,'name'=>'E2E Att Peer',
            'email'=>'e2e.attpeer+${stamp}@example.test','password'=>bcrypt('Password123!'),
            'status'=>'active','email_verified'=>1,
        ]);
        $own = \\App\\Models\\StandardLink::create([
            'school_id'=>$school->id,'academic_year_id'=>$ay->id,'standard_id'=>$standard->id,
            'section_id'=>$sectionOwn->id,'class_teacher_id'=>$ct->id,'stream'=>'A','status'=>1,
        ]);
        $other = \\App\\Models\\StandardLink::create([
            'school_id'=>$school->id,'academic_year_id'=>$ay->id,'standard_id'=>$standard->id,
            'section_id'=>$sectionOther->id,'class_teacher_id'=>$peer->id,'stream'=>'A','status'=>1,
        ]);
        echo json_encode([
            'school_id'=>$school->id,'ct_email'=>${JSON.stringify(ctEmail)},
            'own_link_id'=>$own->id,'other_link_id'=>$other->id,'ct_id'=>$ct->id,
        ]);
    `).trim();
    return parseJson(raw);
}

async function loginAs(page, email) {
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(PASSWORD);
    await page.locator('form button[type="submit"]').first().click();
    await page.waitForURL(/\/(teacher|admin)\//, { timeout: 60_000 });
}

fs.mkdirSync(ARTIFACT, { recursive: true });
const stamp = Date.now();
console.log('[1] Bootstrap...');
const boot = bootstrap(stamp);
console.log('    boot:', boot);

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();
page.setDefaultTimeout(45_000);

try {
    await loginAs(page, boot.ct_email);

    console.log('[2] Attendance list scoped...');
    const listRes = await page.request.get(`${BASE}/teacher/attendance/list`);
    const listJson = await listRes.json();
    const linkIds = (listJson.standardlist || []).map((r) => Number(r.id));
    const listOk = linkIds.includes(Number(boot.own_link_id)) && !linkIds.includes(Number(boot.other_link_id));
    console.log('    linkIds:', linkIds, 'listOk:', listOk);

    console.log('[3] Store other class blocked...');
    const csrf = await page.evaluate(() => document.querySelector('meta[name="csrf-token"]')?.content);
    const storeRes = await page.request.post(`${BASE}/teacher/attendance/add`, {
        headers: { 'X-CSRF-TOKEN': csrf || '', Accept: 'application/json' },
        form: {
            standardLink_id: String(boot.other_link_id),
            date: new Date().toISOString().slice(0, 10),
            session: 'forenoon',
            absentCount: '0',
            presentCount: '0',
        },
    });
    const storeStatus = storeRes.status();
    const storeBlocked = storeStatus === 403;
    console.log('    store status:', storeStatus, 'blocked:', storeBlocked);

    console.log('[4] Sidebar Classes → /teacher/classes...');
    await page.goto(`${BASE}/teacher/dashboard`, { waitUntil: 'domcontentloaded' });
    const classesHref = await page.locator('a', { hasText: 'Classes' }).first().getAttribute('href');
    const sidebarOk = classesHref && classesHref.includes('/teacher/classes') && !classesHref.includes('standardLinks');
    await page.locator('a', { hasText: 'Classes' }).first().click();
    await page.waitForURL(/\/teacher\/classes/, { timeout: 30_000 });
    const classesPageOk = page.url().includes('/teacher/classes');
    await page.screenshot({ path: path.join(ARTIFACT, 'classes.png'), fullPage: true });

    sshTinker(`\$s=\\App\\Models\\School::find(${boot.school_id}); if(\$s){\$s->status=0;\$s->save();} echo 'ok';`);

    const pass = listOk && storeBlocked && sidebarOk && classesPageOk;
    const report = { stamp, boot, linkIds, listOk, storeStatus, storeBlocked, classesHref, sidebarOk, classesPageOk, pass };
    fs.writeFileSync(path.join(ARTIFACT, 'REPORT.json'), JSON.stringify(report, null, 2));
    console.log('\n=== RESULT ===');
    console.log(JSON.stringify(report, null, 2));
    console.log(pass ? 'PASS: attendance scoped + sidebar roster link OK' : 'FAIL');
    process.exit(pass ? 0 : 1);
} finally {
    await browser.close();
}
