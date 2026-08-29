#!/usr/bin/env node
/**
 * Live verify CT report cards: CT sees only own class, downloads real PDF;
 * subject teacher + peer CT get 403; admin preview still works for same student.
 */
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.PLAYWRIGHT_BASE_URL || 'https://klassapp.xyz';
const SSH_KEY = process.env.KLASSAPP_SSH_KEY || `${process.env.HOME}/.ssh/id_ed25519_do`;
const SSH_HOST = process.env.KLASSAPP_SSH_HOST || 'root@46.101.111.131';
const PASSWORD = 'Password123!';
const ARTIFACT = path.join(process.cwd(), 'tmp', 'live-verify-ct-report-cards');

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
    const ctEmail = `e2e.ctreport+${stamp}@example.test`;
    const peerEmail = `e2e.peerctreport+${stamp}@example.test`;
    const subjEmail = `e2e.subjreport+${stamp}@example.test`;
    const adminEmail = `e2e.adminreport+${stamp}@example.test`;
    const raw = sshTinker(`
        $school = \\App\\Models\\School::create([
            'name' => 'E2E CT Reports ${stamp}',
            'email' => 'school-ctreport-${stamp}@e2e.test',
            'phone' => '074${String(stamp).slice(-7)}',
            'slug' => 'e2e-ctreport-${stamp}',
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'toshi_enabled' => 0,
            'report_template' => 'formal',
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
        $section = \\App\\Models\\Section::create(['school_id'=>$school->id,'name'=>'P.5 Reports','status'=>1]);
        $peerSection = \\App\\Models\\Section::create(['school_id'=>$school->id,'name'=>'P.6 Peer','status'=>1]);
        $standard = \\App\\Models\\Standard::create(['school_id'=>$school->id,'name'=>'primary_lower','order'=>5,'status'=>1]);
        $subject = \\App\\Models\\Subject::create([
            'school_id'=>$school->id,'standard_id'=>$standard->id,'section_id'=>$section->id,
            'academic_year_id'=>$ay->id,'name'=>'English','type'=>'core','status'=>1,
        ]);
        \\App\\Models\\Subject::create([
            'school_id'=>$school->id,'standard_id'=>$standard->id,'section_id'=>$section->id,
            'academic_year_id'=>$ay->id,'name'=>'Mathematics','type'=>'core','status'=>1,
        ]);
        $ct = \\App\\Models\\User::create([
            'school_id'=>$school->id,'usergroup_id'=>5,'name'=>'E2E CT Reports',
            'email'=>${JSON.stringify(ctEmail)},'password'=>bcrypt('Password123!'),
            'status'=>'active','email_verified'=>1,
        ]);
        \\App\\Models\\Userprofile::create(['school_id'=>$school->id,'user_id'=>$ct->id,'usergroup_id'=>5,'firstname'=>'E2E','lastname'=>'CT','status'=>'active']);
        $peer = \\App\\Models\\User::create([
            'school_id'=>$school->id,'usergroup_id'=>5,'name'=>'E2E Peer CT Reports',
            'email'=>${JSON.stringify(peerEmail)},'password'=>bcrypt('Password123!'),
            'status'=>'active','email_verified'=>1,
        ]);
        \\App\\Models\\Userprofile::create(['school_id'=>$school->id,'user_id'=>$peer->id,'usergroup_id'=>5,'firstname'=>'E2E','lastname'=>'Peer','status'=>'active']);
        $subj = \\App\\Models\\User::create([
            'school_id'=>$school->id,'usergroup_id'=>5,'name'=>'E2E Subj Reports',
            'email'=>${JSON.stringify(subjEmail)},'password'=>bcrypt('Password123!'),
            'status'=>'active','email_verified'=>1,
        ]);
        \\App\\Models\\Userprofile::create(['school_id'=>$school->id,'user_id'=>$subj->id,'usergroup_id'=>5,'firstname'=>'E2E','lastname'=>'Subj','status'=>'active']);
        $admin = \\App\\Models\\User::create([
            'school_id'=>$school->id,'usergroup_id'=>3,'name'=>'E2E Admin Reports',
            'email'=>${JSON.stringify(adminEmail)},'password'=>bcrypt('Password123!'),
            'status'=>'active','email_verified'=>1,
        ]);
        \\App\\Models\\Userprofile::create(['school_id'=>$school->id,'user_id'=>$admin->id,'usergroup_id'=>3,'firstname'=>'E2E','lastname'=>'Admin','status'=>'active']);
        $sl = \\App\\Models\\StandardLink::create([
            'school_id'=>$school->id,'academic_year_id'=>$ay->id,'standard_id'=>$standard->id,
            'section_id'=>$section->id,'class_teacher_id'=>$ct->id,'stream'=>'A','status'=>1,
        ]);
        \\App\\Models\\StandardLink::create([
            'school_id'=>$school->id,'academic_year_id'=>$ay->id,'standard_id'=>$standard->id,
            'section_id'=>$peerSection->id,'class_teacher_id'=>$peer->id,'stream'=>'B','status'=>1,
        ]);
        $eotType = \\App\\Models\\Academics\\ExamType::where('code','EOT')->first()
            ?: \\App\\Models\\Academics\\ExamType::create(['name'=>'End Of Term','code'=>'EOT','contributes_to_report_total'=>1]);
        $exam = \\App\\Models\\Academics\\Exam::withoutEvents(fn () => \\App\\Models\\Academics\\Exam::create([
            'school_id'=>$school->id,'standard_id'=>$standard->id,'section_id'=>$section->id,
            'subject_id'=>$subject->id,'teacher_id'=>$subj->id,'exam_type_id'=>$eotType->id,
            'academic_term_id'=>$term->id,'academic_year_id'=>$ay->id,
            'scheduled_at'=>now(),'status'=>'submitted',
        ]));
        $student = \\App\\Models\\User::create([
            'school_id'=>$school->id,'usergroup_id'=>6,'name'=>'E2E Report Student',
            'email'=>'student-ctreport-${stamp}@e2e.test','password'=>bcrypt('Password123!'),
            'status'=>'active','email_verified'=>1,
        ]);
        \\App\\Models\\Userprofile::create(['school_id'=>$school->id,'user_id'=>$student->id,'usergroup_id'=>6,'firstname'=>'E2E','lastname'=>'Student','status'=>'active']);
        \\App\\Models\\StudentAcademic::create([
            'school_id'=>$school->id,'academic_year_id'=>$ay->id,'user_id'=>$student->id,'standardLink_id'=>$sl->id,
        ]);
        \\App\\Models\\Academics\\Marks::create([
            'student_id'=>$student->id,'exam_id'=>$exam->id,'school_id'=>$school->id,
            'subject_id'=>$subject->id,'teacher_id'=>$subj->id,'section_id'=>$section->id,
            'marks'=>75,'grade'=>'D1',
        ]);
        echo json_encode([
            'school_id'=>$school->id,'std_link_id'=>$sl->id,'learner_id'=>$student->id,
            'ct_email'=>${JSON.stringify(ctEmail)},'peer_email'=>${JSON.stringify(peerEmail)},
            'subj_email'=>${JSON.stringify(subjEmail)},'admin_email'=>${JSON.stringify(adminEmail)},
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

async function main() {
    fs.mkdirSync(ARTIFACT, { recursive: true });
    const stamp = Date.now().toString().slice(-8);
    console.log('Bootstrapping school', stamp);
    const ctx = bootstrap(stamp);
    console.log(ctx);

    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();
    const results = [];

    // CT: index shows own class only
    await loginAs(page, ctx.ct_email);
    await page.goto(`${BASE}/teacher/reports/cards`, { waitUntil: 'domcontentloaded' });
    const indexHtml = await page.content();
    fs.writeFileSync(path.join(ARTIFACT, 'ct-index.html'), indexHtml);
    const seesOwn = indexHtml.includes('P.5 Reports');
    const seesPeer = indexHtml.includes('P.6 Peer');
    results.push({ step: 'ct_index_own_class', ok: seesOwn && !seesPeer });

    // CT: download PDF and save to disk
    const downloadUrl = `${BASE}/teacher/reports/cards/${ctx.std_link_id}/student/${ctx.learner_id}/download`;
    const dlResp = await page.request.get(downloadUrl);
    const pdfBuf = await dlResp.body();
    const pdfPath = path.join(ARTIFACT, 'ct-student-report.pdf');
    fs.writeFileSync(pdfPath, pdfBuf);
    const pdfOk = dlResp.status() === 200
        && (dlResp.headers()['content-type'] || '').includes('pdf')
        && pdfBuf.slice(0, 4).toString() === '%PDF'
        && pdfBuf.length > 1000;
    results.push({ step: 'ct_download_pdf', ok: pdfOk, status: dlResp.status(), bytes: pdfBuf.length, path: pdfPath });

    // CT: preview
    const previewUrl = `${BASE}/teacher/reports/cards/${ctx.std_link_id}/student/${ctx.learner_id}/preview`;
    const prevResp = await page.request.get(previewUrl);
    results.push({
        step: 'ct_preview_pdf',
        ok: prevResp.status() === 200 && (prevResp.headers()['content-type'] || '').includes('pdf'),
        status: prevResp.status(),
    });

    // Subject teacher: 403
    await page.context().clearCookies();
    await loginAs(page, ctx.subj_email);
    const subjShow = await page.request.get(`${BASE}/teacher/reports/cards/${ctx.std_link_id}`);
    const subjDl = await page.request.get(downloadUrl);
    results.push({ step: 'subj_show_403', ok: subjShow.status() === 403, status: subjShow.status() });
    results.push({ step: 'subj_download_403', ok: subjDl.status() === 403, status: subjDl.status() });

    // Peer CT: 403 on this class
    await page.context().clearCookies();
    await loginAs(page, ctx.peer_email);
    const peerShow = await page.request.get(`${BASE}/teacher/reports/cards/${ctx.std_link_id}`);
    const peerDl = await page.request.get(downloadUrl);
    results.push({ step: 'peer_show_403', ok: peerShow.status() === 403, status: peerShow.status() });
    results.push({ step: 'peer_download_403', ok: peerDl.status() === 403, status: peerDl.status() });

    // Admin: still works
    await page.context().clearCookies();
    await loginAs(page, ctx.admin_email);
    const adminUrl = `${BASE}/admin/reports/cards/${ctx.std_link_id}/student/${ctx.learner_id}/preview`;
    const adminResp = await page.request.get(adminUrl);
    const adminBuf = await adminResp.body();
    const adminPdfPath = path.join(ARTIFACT, 'admin-student-report.pdf');
    fs.writeFileSync(adminPdfPath, adminBuf);
    results.push({
        step: 'admin_preview_ok',
        ok: adminResp.status() === 200
            && (adminResp.headers()['content-type'] || '').includes('pdf')
            && adminBuf.slice(0, 4).toString() === '%PDF',
        status: adminResp.status(),
        bytes: adminBuf.length,
        path: adminPdfPath,
    });

    await browser.close();

    const summaryPath = path.join(ARTIFACT, 'summary.json');
    fs.writeFileSync(summaryPath, JSON.stringify({ stamp, ctx, results }, null, 2));
    console.log(JSON.stringify({ stamp, results }, null, 2));

    const failed = results.filter((r) => !r.ok);
    if (failed.length) {
        console.error('FAILED', failed);
        process.exit(1);
    }
    console.log('PASS — all live-verify steps ok');
}

main().catch((e) => {
    console.error(e);
    process.exit(1);
});
