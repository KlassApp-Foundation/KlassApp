#!/usr/bin/env node
/**
 * Live verify PR-B: class teacher creates an exam via teacher UI,
 * assigns a subject teacher; that subject teacher sees and can open it.
 */
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp.xyz';
const SSH_KEY = process.env.KLASSAPP_SSH_KEY || `${process.env.HOME}/.ssh/id_ed25519_do`;
const SSH_HOST = process.env.KLASSAPP_SSH_HOST || 'root@46.101.111.131';
const PASSWORD = 'Password123!';
const ARTIFACT = path.join(process.cwd(), 'tmp', 'live-verify-ct-exam-create');

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
    const ctEmail = `e2e.ctcreate+${stamp}@example.test`;
    const subjEmail = `e2e.subjcreate+${stamp}@example.test`;
    const raw = sshTinker(`
        $school = \\App\\Models\\School::create([
            'name' => 'E2E CT Exam Create ${stamp}',
            'email' => 'school-ctcreate-${stamp}@e2e.test',
            'phone' => '073${String(stamp).slice(-7)}',
            'slug' => 'e2e-ctcreate-${stamp}',
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'toshi_enabled' => 0,
        ]);
        $ay = \\App\\Models\\AcademicYear::create([
            'school_id' => $school->id,
            'name' => '${year}',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'Current Academic Year',
            'description' => 'Current Academic Year',
            'status' => 1,
        ]);
        $term = \\App\\Models\\AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'name' => 'First Term',
            'status' => 'current',
            'starts_on' => now()->startOfYear(),
            'ends_on' => now()->startOfYear()->addMonths(3),
        ]);
        $section = \\App\\Models\\Section::create(['school_id'=>$school->id,'name'=>'P.7 Create','status'=>1]);
        $standard = \\App\\Models\\Standard::create(['school_id'=>$school->id,'name'=>'primary','order'=>7,'status'=>1]);
        $subject = \\App\\Models\\Subject::create([
            'school_id'=>$school->id,'standard_id'=>$standard->id,'section_id'=>$section->id,
            'academic_year_id'=>$ay->id,'name'=>'Science','type'=>'core','status'=>1,
        ]);
        $ct = \\App\\Models\\User::create([
            'school_id'=>$school->id,'usergroup_id'=>5,'name'=>'E2E CT Creator',
            'email'=>${JSON.stringify(ctEmail)},'password'=>bcrypt('Password123!'),
            'status'=>'active','email_verified'=>1,
        ]);
        \\App\\Models\\Userprofile::create(['school_id'=>$school->id,'user_id'=>$ct->id,'usergroup_id'=>5,'firstname'=>'E2E','lastname'=>'CT','status'=>'active']);
        $subj = \\App\\Models\\User::create([
            'school_id'=>$school->id,'usergroup_id'=>5,'name'=>'E2E Subject Assignee',
            'email'=>${JSON.stringify(subjEmail)},'password'=>bcrypt('Password123!'),
            'status'=>'active','email_verified'=>1,
        ]);
        \\App\\Models\\Userprofile::create(['school_id'=>$school->id,'user_id'=>$subj->id,'usergroup_id'=>5,'firstname'=>'E2E','lastname'=>'Subject','status'=>'active']);
        $sl = \\App\\Models\\StandardLink::create([
            'school_id'=>$school->id,'academic_year_id'=>$ay->id,'standard_id'=>$standard->id,
            'section_id'=>$section->id,'class_teacher_id'=>$ct->id,'stream'=>'A','status'=>1,
        ]);
        \\App\\Models\\Teacherlink::create([
            'school_id'=>$school->id,'academic_year_id'=>$ay->id,'standardLink_id'=>$sl->id,
            'subject_id'=>$subject->id,'teacher_id'=>$subj->id,
        ]);
        $examType = \\App\\Models\\Academics\\ExamType::query()->first() ?: \\App\\Models\\Academics\\ExamType::create(['name'=>'Mid Term','code'=>'MID','contributes_to_report_total'=>true]);
        echo json_encode([
            'school_id'=>$school->id,'section_id'=>$section->id,'subject_id'=>$subject->id,
            'term_id'=>$term->id,'year_id'=>$ay->id,'exam_type_id'=>$examType->id,
            'ct_email'=>${JSON.stringify(ctEmail)},'subj_email'=>${JSON.stringify(subjEmail)},
            'ct_id'=>$ct->id,'subj_id'=>$subj->id,
        ]);
    `).trim();
    return parseJson(raw);
}

function latestExam(schoolId) {
    const raw = sshTinker(`
        \$e = \\App\\Models\\Academics\\Exam::where('school_id',${schoolId})->latest('id')->first();
        echo json_encode(['exam_id'=>\$e?->id,'teacher_id'=>\$e?->teacher_id,'subject_id'=>\$e?->subject_id,'exists'=>(bool)\$e]);
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
console.log('[1] Bootstrap CT / subject teacher / Teacherlink...');
const boot = bootstrap(stamp);
console.log('    boot:', boot);

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();
page.setDefaultTimeout(45_000);

try {
    console.log('[2] CT creates exam via teacher UI, assigned to subject teacher...');
    await loginAs(page, boot.ct_email);
    await page.goto(`${BASE}/teacher/exam/create?section=${boot.section_id}`, { waitUntil: 'domcontentloaded' });
    await page.locator('#academic_year_id').selectOption(String(boot.year_id));
    await page.locator('#academic_term_id').selectOption(String(boot.term_id));
    await page.locator('#subject_id').selectOption(String(boot.subject_id));
    await page.locator('#exam_type_id').selectOption(String(boot.exam_type_id));
    await page.locator('#teacher_id').selectOption(String(boot.subj_id));
    await page.getByRole('button', { name: 'Create Exam' }).click();
    await page.waitForURL(/\/teacher\/exam\/marks/, { timeout: 60_000 });
    await page.screenshot({ path: path.join(ARTIFACT, 'after-ct-create.png'), fullPage: true });

    const created = latestExam(boot.school_id);
    console.log('    created:', created);

    console.log('[3] Subject teacher sees exam and can open marks enter...');
    await page.context().clearCookies();
    await loginAs(page, boot.subj_email);
    await page.goto(`${BASE}/teacher/exam/marks`, { waitUntil: 'domcontentloaded' });
    await page.screenshot({ path: path.join(ARTIFACT, 'subj-list.png'), fullPage: true });
    const listHtml = await page.content();
    const listSeesExam = created.exam_id && listHtml.includes(`/teacher/exam/${created.exam_id}/marks/enter`);

    await page.goto(`${BASE}/teacher/exam/${created.exam_id}/marks/enter`, { waitUntil: 'domcontentloaded' });
    const enterOk = !page.url().includes('/login') && (await page.locator('form').count()) > 0;
    await page.screenshot({ path: path.join(ARTIFACT, 'subj-enter.png'), fullPage: true });

    sshTinker(`\$s=\\App\\Models\\School::find(${boot.school_id}); if(\$s){\$s->status=0;\$s->save();} echo 'ok';`);

    const pass = created.exists
        && Number(created.teacher_id) === Number(boot.subj_id)
        && Number(created.subject_id) === Number(boot.subject_id)
        && listSeesExam
        && enterOk;

    const report = { stamp, boot, created, listSeesExam, enterOk, pass };
    fs.writeFileSync(path.join(ARTIFACT, 'REPORT.json'), JSON.stringify(report, null, 2));

    console.log('\n=== RESULT ===');
    console.log(JSON.stringify(report, null, 2));
    console.log(pass
        ? 'PASS: CT created exam via UI; subject teacher sees and can enter marks'
        : 'FAIL: teacher exam create / assignee visibility broken');
    process.exit(pass ? 0 : 1);
} finally {
    await browser.close();
}
