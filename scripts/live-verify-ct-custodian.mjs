#!/usr/bin/env node
/**
 * Live verify PR-A: class-teacher custodian can save marks on an exam
 * assigned to a different subject teacher; that subject teacher can still
 * save marks afterward on the same exam.
 */
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp.xyz';
const SSH_KEY = process.env.KLASSAPP_SSH_KEY || `${process.env.HOME}/.ssh/id_ed25519_do`;
const SSH_HOST = process.env.KLASSAPP_SSH_HOST || 'root@46.101.111.131';
const PASSWORD = 'Password123!';
const ARTIFACT = path.join(process.cwd(), 'tmp', 'live-verify-ct-custodian');

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

function bootstrap(stamp) {
    const year = new Date().getFullYear();
    const ctEmail = `e2e.ct+${stamp}@example.test`;
    const subjEmail = `e2e.subj+${stamp}@example.test`;
    const raw = sshTinker(`
        $school = \\App\\Models\\School::create([
            'name' => 'E2E CT Custodian ${stamp}',
            'email' => 'school-ct-${stamp}@e2e.test',
            'phone' => '072${String(stamp).slice(-7)}',
            'slug' => 'e2e-ct-${stamp}',
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
        $section = \\App\\Models\\Section::create(['school_id'=>$school->id,'name'=>'P.7 CT','status'=>1]);
        $standard = \\App\\Models\\Standard::create(['school_id'=>$school->id,'name'=>'primary','order'=>7,'status'=>1]);
        $subject = \\App\\Models\\Subject::create([
            'school_id'=>$school->id,'standard_id'=>$standard->id,'section_id'=>$section->id,
            'academic_year_id'=>$ay->id,'name'=>'Mathematics','type'=>'core','status'=>1,
        ]);
        $ct = \\App\\Models\\User::create([
            'school_id'=>$school->id,'usergroup_id'=>5,'name'=>'E2E Class Teacher',
            'email'=>${JSON.stringify(ctEmail)},'password'=>bcrypt('Password123!'),
            'status'=>'active','email_verified'=>1,
        ]);
        \\App\\Models\\Userprofile::create(['school_id'=>$school->id,'user_id'=>$ct->id,'usergroup_id'=>5,'firstname'=>'E2E Class Teacher','status'=>'active']);
        $subj = \\App\\Models\\User::create([
            'school_id'=>$school->id,'usergroup_id'=>5,'name'=>'E2E Subject Teacher',
            'email'=>${JSON.stringify(subjEmail)},'password'=>bcrypt('Password123!'),
            'status'=>'active','email_verified'=>1,
        ]);
        \\App\\Models\\Userprofile::create(['school_id'=>$school->id,'user_id'=>$subj->id,'usergroup_id'=>5,'firstname'=>'E2E Subject Teacher','status'=>'active']);
        $student = \\App\\Models\\User::create([
            'school_id'=>$school->id,'usergroup_id'=>6,'name'=>'E2E Custodian Student',
            'email'=>'student-ct-${stamp}@e2e.test','password'=>bcrypt('Password123!'),
            'status'=>'active','email_verified'=>1,
        ]);
        \\App\\Models\\Userprofile::create(['school_id'=>$school->id,'user_id'=>$student->id,'usergroup_id'=>6,'firstname'=>'E2E','lastname'=>'Student','status'=>'active']);
        $sl = \\App\\Models\\StandardLink::create([
            'school_id'=>$school->id,'academic_year_id'=>$ay->id,'standard_id'=>$standard->id,
            'section_id'=>$section->id,'class_teacher_id'=>$ct->id,'stream'=>'A','status'=>1,
        ]);
        \\App\\Models\\StudentAcademic::create([
            'school_id'=>$school->id,'user_id'=>$student->id,'academic_year_id'=>$ay->id,
            'standardLink_id'=>$sl->id,'status'=>1,
        ]);
        $examType = \\App\\Models\\Academics\\ExamType::query()->first() ?: \\App\\Models\\Academics\\ExamType::create(['name'=>'Mid Term','code'=>'MID','contributes_to_report_total'=>true]);
        foreach ([[85,100,'D1',1],[80,84,'D2',2],[0,79,'P8',8]] as [$min,$max,$g,$p]) {
            \\App\\Models\\Academics\\SchoolGradingSystem::firstOrCreate(
                ['school_id'=>$school->id,'standard_id'=>$standard->id,'grade'=>$g],
                ['points'=>$p,'min_score'=>$min,'max_score'=>$max,'remark'=>$g]
            );
        }
        $exam = \\App\\Models\\Academics\\Exam::create([
            'school_id'=>$school->id,'standard_id'=>$standard->id,'section_id'=>$section->id,
            'academic_year_id'=>$ay->id,'academic_term_id'=>$term->id,'subject_id'=>$subject->id,
            'teacher_id'=>$subj->id,'exam_type_id'=>$examType->id,'status'=>'undone',
        ]);
        echo json_encode([
            'school_id'=>$school->id,'exam_id'=>$exam->id,'student_id'=>$student->id,
            'ct_email'=>${JSON.stringify(ctEmail)},'subj_email'=>${JSON.stringify(subjEmail)},
            'ct_id'=>$ct->id,'subj_id'=>$subj->id,
        ]);
    `).trim();
    return parseJson(raw);
}

function markSnap(examId, studentId) {
    const raw = sshTinker(`
        $m = \\App\\Models\\Academics\\Marks::where('exam_id',${examId})->where('student_id',${studentId})->first();
        echo json_encode(['marks'=>$m?->marks,'teacher_id'=>$m?->teacher_id,'exists'=>(bool)$m]);
    `).trim();
    return parseJson(raw);
}

async function loginAs(page, email) {
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
    await page.getByLabel(/email/i).fill(email);
    await page.locator('#password').fill(PASSWORD);
    await page.getByRole('button', { name: /login|sign in/i }).click();
    await page.waitForURL(/\/(teacher|admin)\/dashboard/, { timeout: 60_000 });
}

async function submitMarks(page, examId, studentId, score) {
    await page.goto(`${BASE}/teacher/exam/${examId}/marks/enter`, { waitUntil: 'domcontentloaded' });
    const input = page.locator(`input[name="marks[${studentId}]"]`).first();
    if (await input.count()) {
        await input.fill(String(score));
    } else {
        // Fallback: any marks input
        const any = page.locator('input[name^="marks["]').first();
        await any.waitFor({ timeout: 15_000 });
        await any.fill(String(score));
    }
    await page.locator('button[type="submit"], input[type="submit"]').first().click();
    await page.waitForTimeout(2000);
}

fs.mkdirSync(ARTIFACT, { recursive: true });
const stamp = Date.now();
console.log('[1] Bootstrap school / CT / subject teacher / exam...');
const boot = bootstrap(stamp);
console.log('    boot:', boot);

const before = markSnap(boot.exam_id, boot.student_id);
console.log('[2] Marks before:', before);

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();
page.setDefaultTimeout(45_000);

try {
    console.log('[3] CT enters marks on subject-teacher exam...');
    await loginAs(page, boot.ct_email);
    await submitMarks(page, boot.exam_id, boot.student_id, 88);
    await page.screenshot({ path: path.join(ARTIFACT, 'after-ct.png'), fullPage: true });
    const afterCt = markSnap(boot.exam_id, boot.student_id);
    console.log('    after CT:', afterCt);

    console.log('[4] Subject teacher updates same exam marks...');
    await page.context().clearCookies();
    await loginAs(page, boot.subj_email);
    await submitMarks(page, boot.exam_id, boot.student_id, 92);
    await page.screenshot({ path: path.join(ARTIFACT, 'after-subj.png'), fullPage: true });
    const afterSubj = markSnap(boot.exam_id, boot.student_id);
    console.log('    after subject teacher:', afterSubj);

    const pass = afterCt.exists && Number(afterCt.marks) === 88
        && afterSubj.exists && Number(afterSubj.marks) === 92
        && Number(afterSubj.teacher_id) === Number(boot.subj_id);

    // Deactivate disposable school
    sshTinker(`$s=\\App\\Models\\School::find(${boot.school_id}); if($s){$s->status=0;$s->save();} echo 'ok';`);

    const report = { stamp, boot, before, afterCt, afterSubj, pass };
    fs.writeFileSync(path.join(ARTIFACT, 'REPORT.json'), JSON.stringify(report, null, 2));

    console.log('\n=== RESULT ===');
    console.log(JSON.stringify(report, null, 2));
    console.log(pass
        ? 'PASS: CT custodian saved marks; subject teacher still saved afterward'
        : 'FAIL: custodian / subject-teacher marks path broken');
    process.exit(pass ? 0 : 1);
} finally {
    await browser.close();
}
