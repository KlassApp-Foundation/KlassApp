#!/usr/bin/env node
/**
 * Live verify: Toshi confirmOnboarding must persist board_registration_number
 * + school_student_id on candidate-class students (AgentToshi commitAll pass-through).
 *
 * Flow: SSH bootstrap (category+AY) → login → Toshi complete students/terms/fees
 * → inject board_reg onto actionData student → confirmOnboarding → assert DB.
 */
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp.xyz';
const SSH_KEY = process.env.KLASSAPP_SSH_KEY || `${process.env.HOME}/.ssh/id_ed25519_do`;
const SSH_HOST = process.env.KLASSAPP_SSH_HOST || 'root@46.101.111.131';
const PASSWORD = 'Password123!';
const ARTIFACT = path.join(process.cwd(), 'tmp', 'live-verify-toshi-board-reg');
const BOARD_REG = 'U9876/543';
const SCHOOL_STUDENT_ID = 'SCH-LIVE-P7-001';
const STUDENT_NAME = 'BoardReg Live Verify';

function shellQuote(s) {
    return `'${String(s).replace(/'/g, `'\\''`)}'`;
}

function sshTinker(php) {
    const oneLine = php.replace(/\n/g, ' ').replace(/\s+/g, ' ').trim();
    return execFileSync('ssh', [
        '-i', SSH_KEY, '-o', 'StrictHostKeyChecking=no', '-o', 'ConnectTimeout=20',
        SSH_HOST,
        `docker exec sms-app php artisan tinker --execute ${shellQuote(oneLine)}`,
    ], { encoding: 'utf8', timeout: 120_000, maxBuffer: 4 * 1024 * 1024 });
}

function parseJson(raw) {
    const start = raw.indexOf('{');
    const end = raw.lastIndexOf('}');
    return JSON.parse(raw.slice(start, end + 1));
}

function bootstrapSchool(stamp, email) {
    const year = new Date().getFullYear();
    const raw = sshTinker(`
        $email = ${JSON.stringify(email)};
        $school = \\App\\Models\\School::create([
            'name' => 'E2E BoardReg Verify ${stamp}',
            'email' => 'school-boardreg-${stamp}@e2e.test',
            'phone' => '071${String(stamp).slice(-7)}',
            'slug' => 'e2e-boardreg-${stamp}',
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'toshi_enabled' => 1,
        ]);
        $admin = \\App\\Models\\User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'BoardReg Verify Admin',
            'email' => $email,
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        \\App\\Models\\Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'usergroup_id' => 3,
            'firstname' => 'BoardReg Verify Admin',
            'status' => 'active',
        ]);
        \\App\\Models\\AcademicYear::create([
            'school_id' => $school->id,
            'name' => '${year}',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'Current Academic Year',
            'description' => 'Current Academic Year',
            'status' => 1,
        ]);
        app(\\App\\Services\\OnboardingEngine::class)->saveSchoolCategory($school->fresh(), 'primary_nursery');
        echo json_encode(['school_id'=>$school->id,'admin_id'=>$admin->id]);
    `).trim();
    return parseJson(raw);
}

function dbSnap(schoolId) {
    const raw = sshTinker(`
        $sid = ${schoolId};
        $rows = [];
        foreach (\\App\\Models\\StudentAcademic::where('school_id',$sid)->with('user:id,name')->get() as $sa) {
            $rows[] = [
                'name' => $sa->user?->name,
                'school_student_id' => $sa->school_student_id,
                'board_registration_number' => $sa->board_registration_number,
            ];
        }
        echo json_encode([
            'students_count' => \\App\\Models\\User::where('school_id',$sid)->where('usergroup_id',6)->count(),
            'student_academics' => $rows,
            'terms_count' => \\App\\Models\\AcademicTerm::where('school_id',$sid)->count(),
            'fees_count' => \\App\\Models\\FeesCategories::where('school_id',$sid)->count(),
        ]);
    `).trim();
    return parseJson(raw);
}

async function getWireId(page) {
    return page.evaluate(() => document.querySelector('[wire\\:id]')?.getAttribute('wire:id'));
}

async function lwSet(page, key, value) {
    const id = await getWireId(page);
    await page.evaluate(({ wireId, key, value }) => {
        window.Livewire.find(wireId).set(key, value);
    }, { wireId: id, key, value });
}

async function lwGet(page, key) {
    const id = await getWireId(page);
    return page.evaluate(({ wireId, key }) => window.Livewire.find(wireId).get(key), { wireId: id, key });
}

async function lwCall(page, method, ...args) {
    const id = await getWireId(page);
    return page.evaluate(async ({ wireId, method, args }) => {
        return window.Livewire.find(wireId).call(method, ...args);
    }, { wireId: id, method, args });
}

async function botText(page) {
    return page.locator('[data-toshi-root]').innerText().catch(() => '');
}

async function waitBot(page, re, ms = 90_000) {
    const deadline = Date.now() + ms;
    while (Date.now() < deadline) {
        if (re.test(await botText(page))) return;
        await page.waitForTimeout(600);
    }
    throw new Error(`waitBot timeout: ${re}`);
}

async function clickYes(page) {
    const btn = page.locator('button[wire\\:click="confirmYes"]:visible').last();
    if (await btn.count()) await btn.click();
    else await lwCall(page, 'confirmYes');
    await page.waitForTimeout(800);
}

const stamp = Date.now();
const email = `e2e.boardreg+${stamp}@example.test`;

fs.mkdirSync(ARTIFACT, { recursive: true });

console.log('[1] SSH bootstrap (category + AY)...');
const boot = bootstrapSchool(stamp, email);
console.log('    boot:', boot);

const before = dbSnap(boot.school_id);
console.log('[2] DB before:', JSON.stringify(before));
if (before.students_count > 0) {
    console.error('FAIL: school already has students');
    process.exit(1);
}

console.log('[3] Playwright: Toshi student + board_reg inject + commit...');
const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();
page.setDefaultTimeout(45_000);

try {
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
    await page.getByLabel(/email/i).fill(email);
    await page.locator('#password').fill(PASSWORD);
    await page.getByRole('button', { name: /login|sign in/i }).click();
    await page.waitForURL(/\/admin\/dashboard/, { timeout: 60_000 });
    await page.waitForSelector('[data-toshi-root]', { timeout: 45_000 });

    if (!(await page.locator('#toshi-modal:visible').count())) {
        const banner = page.locator('[data-testid="setup-banner-toshi"]');
        if (await banner.isVisible().catch(() => false)) await banner.click();
        else await lwCall(page, 'maximize').catch(() => {});
        await page.waitForTimeout(1000);
    }

    await waitBot(page, /finish setting up|student|teacher|term|fee|things to set up/i, 60_000).catch(() => {});

    const STUDENTS_STEP = 10;
    await lwSet(page, 'step', STUDENTS_STEP);
    await lwSet(page, 'substep', 0);
    await page.waitForTimeout(400);

    await lwCall(page, 'showStudentFormFn');
    await page.waitForTimeout(600);
    await page.locator('input[wire\\:model="studentFormName"]:visible').last().fill(STUDENT_NAME);
    await page.locator('input[wire\\:model="studentFormClass"]:visible').last().fill('Primary Seven');
    await lwCall(page, 'saveStudent');
    await page.waitForTimeout(500);

    // Toshi student form has no board_reg inputs yet — inject onto the collected
    // actionData row so confirmOnboarding exercises the commitAll pass-through.
    const actionData = await lwGet(page, 'actionData') || {};
    const students = Array.isArray(actionData.students) ? [...actionData.students] : [];
    if (students.length < 1) {
        throw new Error('saveStudent did not populate actionData.students');
    }
    students[0] = {
        ...students[0],
        school_student_id: SCHOOL_STUDENT_ID,
        board_registration_number: BOARD_REG,
    };
    await lwSet(page, 'actionData', { ...actionData, students });
    console.log('    injected board_reg onto actionData.students[0]:', students[0]);

    await lwCall(page, 'doneStudents');
    await page.waitForTimeout(1000);

    await waitBot(page, /Term I|Default Ugandan terms|Is this correct/i, 60_000);
    await clickYes(page);
    await page.waitForTimeout(1000);

    await waitBot(page, /fee|Add Fee|fee categor/i, 60_000);
    await lwCall(page, 'showFeeFormFn');
    await page.waitForTimeout(500);
    await page.locator('input[wire\\:model="feeFormName"]:visible').last().fill('Tuition');
    await page.locator('input[wire\\:model="feeFormAmount"]:visible').last().fill('500000');
    await page.locator('input[wire\\:model="feeFormClass"]:visible').last().fill('Primary One');
    await lwCall(page, 'saveFee');
    await page.waitForTimeout(800);

    sshTinker(`
        $u = \\App\\Models\\User::where('email', ${JSON.stringify(email)})->first();
        \\App\\Models\\WhatsAppUser::updateOrCreate(['user_id'=>$u->id], ['phone'=>'+256700000088', 'school_id'=>$u->school_id, 'verified_at'=>now(), 'opted_in'=>true]);
        echo 'wa_ok';
    `);

    // Re-assert inject survived intervening steps (session restore can wipe nested data).
    const beforeCommit = await lwGet(page, 'actionData') || {};
    const beforeStudents = Array.isArray(beforeCommit.students) ? [...beforeCommit.students] : [];
    if (!beforeStudents.some((s) => s?.board_registration_number === BOARD_REG)) {
        const rebuilt = beforeStudents.length
            ? beforeStudents.map((s, i) => (i === 0 ? { ...s, school_student_id: SCHOOL_STUDENT_ID, board_registration_number: BOARD_REG } : s))
            : [{ name: STUDENT_NAME, class: 'Primary Seven', school_student_id: SCHOOL_STUDENT_ID, board_registration_number: BOARD_REG }];
        await lwSet(page, 'actionData', { ...beforeCommit, students: rebuilt });
        console.log('    re-injected board_reg immediately before confirmOnboarding');
    }

    console.log('[4] confirmOnboarding...');
    await lwCall(page, 'confirmOnboarding');
    await page.waitForTimeout(3000);
    await page.screenshot({ path: path.join(ARTIFACT, 'after-commit.png'), fullPage: true });
} finally {
    await browser.close();
}

const after = dbSnap(boot.school_id);
console.log('[5] DB after:', JSON.stringify(after, null, 2));

const match = (after.student_academics || []).find(
    (r) => r.board_registration_number === BOARD_REG && r.school_student_id === SCHOOL_STUDENT_ID,
);

const report = {
    stamp,
    email,
    school_id: boot.school_id,
    expected: { board_registration_number: BOARD_REG, school_student_id: SCHOOL_STUDENT_ID, name: STUDENT_NAME },
    before,
    after,
    match: match || null,
    pass: Boolean(match),
};

fs.writeFileSync(path.join(ARTIFACT, 'REPORT.json'), JSON.stringify(report, null, 2));

console.log('\n=== RESULT ===');
console.log(`school_id: ${boot.school_id}`);
console.log(`email: ${email}`);
console.log(`match:`, match || null);
console.log(report.pass
    ? 'PASS: board_registration_number + school_student_id persisted via Toshi confirmOnboarding'
    : 'FAIL: board_reg / school_student_id missing on student_academics after commit');

process.exit(report.pass ? 0 : 1);
