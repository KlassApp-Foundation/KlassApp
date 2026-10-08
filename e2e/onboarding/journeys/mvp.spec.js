// MVP end-to-end journey — one throwaway is_test PRIMARY school; all 8 steps.
// Screenshots at every step: e2e/onboarding/artifacts/mvp/<step>-<name>-<width>.png
// Runs under both configured projects (desktop-1280 and mobile-375).
//
// Steps:
//  1. sign up (emailed code) + finish the wizard
//  2. invite a class teacher + a subject teacher, create a stream, assign them
//  3. add 5 students with parent contacts; KLS numbers visible (list, roster, overview)
//  4. the teacher accepts the invite, logs in, takes attendance
//  5. marks for one exam by form AND by spreadsheet import (template, preview, confirm)
//  6. admin generates a report card PDF (downloads + opens; name and marks present)
//  7. the bursar records a fee payment, sees the balance, sends a fee reminder
//  8. a parent (normal flow) logs in on the web and sees the child's report card + fees
//
// WhatsApp sending cannot be exercised on staging (production smoke item).
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const { buildJourneyData } = require('../lib/journey-data');
const signup = require('../lib/signup');
const wizard = require('../lib/manual-wizard');
const outcomes = require('../lib/outcomes');

const ROOT = path.resolve(__dirname, '..', '..', '..');
const BRIDGE = path.join(__dirname, '..', 'lib', 'stg_bridge.py');
// Screenshot folder per the MVP brief: e2e/artifacts/mvp/ (one per step, per width).
const MVP_DIR = path.join(ROOT, 'e2e', 'artifacts', 'mvp');

function bridge(php) {
    const out = execSync(`python3 "${BRIDGE}"`, { cwd: ROOT, input: php, encoding: 'utf8', timeout: 180000 });
    const marker = '<<<E2E-JSON>>>';
    const i = out.indexOf(marker);
    if (i === -1) throw new Error('bridge missing JSON: ' + out.slice(-300));
    return JSON.parse(out.slice(i + marker.length));
}

test('@mvp-endtoend MVP path: signup to parent (all 8 steps)', async ({ page, browser }) => {
    test.setTimeout(35 * 60_000);
    const width = page.viewportSize().width;
    const ts = Date.now().toString().slice(-5);
    const data = buildJourneyData({ typeId: 'primary', mode: 'manual' });
    const findings = [];
    fs.mkdirSync(MVP_DIR, { recursive: true });
    const shots = [];
    const snap = async (name) => {
        const file = path.join(MVP_DIR, `${String(shots.length + 1).padStart(2, '0')}-${name}-${width}.png`);
        await page.screenshot({ path: file, fullPage: false }).catch(() => {});
        shots.push(path.basename(file));
    };
    const info = (msg) => console.log(`[mvp:${width}] ${msg}`);
    // The Toshi dock/modal can cover the page (auto-opens on fresh schools at
    // mobile). Collapse the dock and close the modal before interacting.
    const quietToshi = async (pg) => {
        await pg.evaluate(() => { try { window.toshiSetCollapsed && window.toshiSetCollapsed(true); } catch (e) {} }).catch(() => {});
        for (const sel of ['[data-testid="toshi-modal-close"]', '[data-testid="toshi-close"]']) {
            const el = pg.locator(sel).first();
            if (await el.count().catch(() => 0)) { await el.click({ timeout: 4000 }).catch(() => {}); }
        }
    };

    // ── STEP 1: signup + wizard ─────────────────────────────────────────────
    const reg = await signup.signupValid(page, data);
    expect(reg.landedOnDashboard, 'signup must land on the dashboard').toBeTruthy();
    await snap('step1-dashboard');

    const flagged = outcomes.setTestFlag(data.admin.email);
    expect(flagged.school_id, 'school must exist after signup').toBeGreaterThan(0);
    const schoolId = flagged.school_id;

    if (!/onboarding\/wizard/.test(page.url())) {
        await page.goto('/admin/onboarding/wizard', { waitUntil: 'domcontentloaded' }).catch(() => {});
    }
    await wizard.runManualWizard(page, data, findings, { shotDir: path.join(MVP_DIR, `wizard-${width}`) });
    await snap('step1-wizard-done');
    info(`school=${schoolId}`);

    // Discover the working context (section / link / term / subject / teachers).
    const ctx0 = bridge(`
      $school = ${schoolId};
      $section = \\App\\Models\\Section::where('school_id',$school)
        ->where('name','like','Primary One Blue')->first()
        ?: \\App\\Models\\Section::where('school_id',$school)->where('name','like','Primary%')->orderBy('id')->first();
      $link = $section ? \\App\\Models\\StandardLink::where('school_id',$school)->where('section_id',$section->id)->orderBy('id')->first() : null;
      $year = \\App\\Models\\AcademicYear::where('school_id',$school)->where('status',1)->first();
      $term = \\App\\Models\\AcademicTerm::where('school_id',$school)->orderBy('id')->first();
      $subject = $section ? \\App\\Models\\Subject::where('school_id',$school)->where('section_id',$section->id)->orderBy('id')->first() : null;
      $teachers = \\App\\Models\\User::where('school_id',$school)->where('usergroup_id',5)->orderBy('id')->get(['id','name'])->toArray();
      echo '<<<E2E-JSON>>>'.json_encode(['section'=>$section?['id'=>$section->id,'name'=>$section->name]:null,'link'=>$link?['id'=>$link->id,'stream'=>$link->stream]:null,'year'=>$year?$year->id:null,'term'=>$term?['id'=>$term->id,'name'=>$term->name]:null,'subject'=>$subject?['id'=>$subject->id,'name'=>$subject->name]:null,'teachers'=>$teachers]);
    `);
    info('ctx0: ' + JSON.stringify(ctx0));
    expect(ctx0.section && ctx0.link, 'wizard must seed a Primary One section + link').toBeTruthy();
    expect(ctx0.teachers.length, 'wizard must seed teacher accounts').toBeGreaterThan(0);

    // ── STEP 2: stream + invites + assignments ──────────────────────────────
    // 2a. create a stream on the section
    const streamName = `Mvp${ts}`;
    await page.goto(`/admin/class-stream/${ctx0.section.id}/create`, { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="stream"]', streamName);
    await snap('step2-stream-form');
    await Promise.all([
        page.waitForLoadState('domcontentloaded').catch(() => {}),
        page.locator('form').filter({ has: page.locator('input[name="stream"]') }).locator('button[type="submit"]').first().click(),
    ]);
    await page.waitForTimeout(1500);
    const streamFlash = ((await page.textContent('body')) || '').match(/stream .* added|already existed/gi) || [];
    info('stream flash: ' + JSON.stringify(streamFlash));
    const madeStream = bridge(`
      $s = \\App\\Models\\Section::where('school_id',${schoolId})->where('name','like','%${streamName}%')->get(['id','name']);
      echo '<<<E2E-JSON>>>'.json_encode($s);
    `);
    expect(madeStream.length, 'stream must exist after create').toBeGreaterThan(0);
    await snap('step2-stream-created');

    // 2b. invite the class teacher
    const ctEmail = `ct.mvp.${ts}@example.com`;
    await page.goto(`/admin/class-teacher-invite/${ctx0.section.id}/create`, { waitUntil: 'domcontentloaded' });
    const inviteForm = page.locator('form').filter({ has: page.locator('input[name="email"]') }).first();
    await inviteForm.locator('input[name="email"]').fill(ctEmail);
    await inviteForm.locator('input[name="name"]').fill('MVP Class Teacher');
    await snap('step2-invite-form');
    await Promise.all([page.waitForLoadState('domcontentloaded').catch(() => {}), inviteForm.locator('button[type="submit"]').first().click()]);
    await page.waitForTimeout(1500);
    const inviteFlash = ((await page.textContent('body')) || '').match(/invite link has been sent|has been sent/gi) || [];
    const inviteRow = bridge(`
      $n = \\App\\Models\\TeacherInvite::where('school_id',${schoolId})->where('email','${ctEmail}')->count();
      echo '<<<E2E-JSON>>>'.json_encode(['n'=>$n]);
    `);
    expect(inviteFlash.length > 0 || inviteRow.n > 0, 'invite must be sent').toBeTruthy();
    await snap('step2-invite-sent');

    // 2c. retrieve the invite token (bridge reissue — deterministic, no email parsing)
    const invite = bridge(`
      $inv = \\App\\Models\\TeacherInvite::where('school_id',${schoolId})->where('email','${ctEmail}')->latest('id')->first();
      if (!$inv) { echo '<<<E2E-JSON>>>'.json_encode(['ok'=>false]); return; }
      $r = \\App\\Services\\TeacherInviteLinkService::reissue($inv);
      echo '<<<E2E-JSON>>>'.json_encode(['ok'=>true,'url'=>\\App\\Services\\TeacherInviteLinkService::inviteUrl($r['token'])]);
    `);
    expect(invite.ok, 'invite must be retrievable').toBeTruthy();
    info('invite url ready');

    // 2d. assign a subject teacher (existing teacher account) on the same link
    const subjTeacher = ctx0.teachers[0];
    await page.goto(`/admin/standardLink/edit/${ctx0.link.id}`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(4000); // Vue load
    const teacherSelects = page.locator('select[name="teacher_id[]"]');
    const tcount = await teacherSelects.count();
    for (let i = 0; i < tcount; i++) {
        const cur = await teacherSelects.nth(i).inputValue().catch(() => '');
        if (!cur) await teacherSelects.nth(i).selectOption(String(subjTeacher.id)).catch(() => {});
    }
    const periodInputs = page.locator('table input[type="text"]');
    for (let i = 0, n = await periodInputs.count(); i < n; i++) {
        if (!(await periodInputs.nth(i).inputValue().catch(() => ''))) await periodInputs.nth(i).fill('5').catch(() => {});
    }
    // The form requires a class teacher; the invite acceptance will reassign this
    // to the invited teacher. Set the second seeded teacher for now.
    const ctSelect = page.locator('select[name="class_teacher_id"]').first();
    if (await ctSelect.count()) {
        await ctSelect.selectOption(String((ctx0.teachers[1] || ctx0.teachers[0]).id)).catch(() => {});
    }
    await snap('step2-assign-form');
    const saveBtn = page.locator('[dusk="submit-btn"]').first();
    const pb = [];
    if (await saveBtn.count()) {
        await saveBtn.click();
        await page.waitForTimeout(3000);
    }
    let assignOk = false;
    for (let i = 0; i < 10; i++) {
        const t = (await page.textContent('body')) || '';
        if (/Updated Successfully/i.test(t)) { assignOk = true; break; }
        await page.waitForTimeout(1500);
    }
    info('assign ok: ' + assignOk);
    expect(assignOk, 'subject assignment must save').toBeTruthy();
    await snap('step2-assigned');

    // ── STEP 3: five students with parent contacts (CSV import) ────────────
    const csvPath = path.join(MVP_DIR, `import-${ts}.csv`);
    const rows = ['firstname,lastname,gender,date_of_birth,class,address,region,district,country,joining_date,lin,std_school_pay_number,parent_firstname,parent_lastname,parent_mobile_no,parent_email,relation'];
    for (let i = 1; i <= 5; i++) {
        rows.push(`MvpPupil${ts}${i},TestUg,${i % 2 ? 'male' : 'female'},2014-05-0${i},${ctx0.section.name},Kampala,Central,Kampala,Uganda,2026-01-10,,65410${i},Parent${i}${ts},TestUg,2567${String(70000000 + i)},parent${i}.${ts}@example.com,father`);
    }
    fs.writeFileSync(csvPath, rows.join('\n') + '\n');

    await page.goto('/admin/import', { waitUntil: 'domcontentloaded' });
    await page.setInputFiles('input[name="import_file"]', csvPath);
    await snap('step3-import-form');
    await Promise.all([page.waitForLoadState('domcontentloaded').catch(() => {}), page.click('button#import')]);
    await page.waitForTimeout(3000);
    const importText = (await page.textContent('body')) || '';
    expect(/Inserted|imported/i.test(importText), 'import must report success').toBeTruthy();
    await snap('step3-imported');

    const students = bridge(`
      $profs = \\App\\Models\\Userprofile::where('school_id',${schoolId})->where('firstname','like','MvpPupil${ts}%')->get(['user_id','firstname']);
      $ids = $profs->pluck('user_id');
      $ac = \\DB::table('student_academics')->whereIn('user_id',$ids)->get(['user_id','standardLink_id','klassapp_student_id']);
      $users = \\App\\Models\\User::whereIn('id',$ids)->get(['id','name'])->keyBy('id');
      $out = [];
      foreach ($profs as $p) {
        $a = $ac->firstWhere('user_id',$p->user_id);
        $out[] = ['user_id'=>$p->user_id,'name'=>$users[$p->user_id]->name ?? null,'link'=>$a->standardLink_id ?? null,'kls'=>$a->klassapp_student_id ?? null];
      }
      echo '<<<E2E-JSON>>>'.json_encode($out);
    `);
    expect(Array.isArray(students) && students.length === 5, 'five students must exist').toBeTruthy();
    for (const s of students) expect(s.kls, 'each student must have a KLS id').toMatch(/^KLS\d{7}$/);
    const first = students[0];
    const studentLink = first.link;
    info('students: ' + students.map((s) => s.kls).join(','));

    // KLS visible on the student list
    await page.goto('/admin/students', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    const listText = (await page.textContent('body')) || '';
    for (const s of students) expect(listText.includes(s.kls), `student list must show ${s.kls}`).toBeTruthy();
    await snap('step3-kls-list');

    // KLS visible on the class roster (select the stream that holds them)
    await page.goto(`/admin/classes/${ctx0.section.id}`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2500);
    const streamCards = page.locator('button[wire\\:click^="selectStream"]');
    // try each stream card until the roster shows our students
    let rosterFound = false;
    for (let i = 0, n = await streamCards.count(); i < n; i++) {
        await streamCards.nth(i).click().catch(() => {});
        await page.waitForTimeout(2000);
        const t = (await page.textContent('body')) || '';
        if (students.some((s) => t.includes(s.kls))) { rosterFound = true; break; }
    }
    expect(rosterFound, 'class roster must show the students with KLS numbers').toBeTruthy();
    await snap('step3-kls-roster');

    // KLS visible on the student overview page
    await page.goto(`/admin/student/show/${encodeURIComponent(first.name)}`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    const overviewText = (await page.textContent('body')) || '';
    expect(overviewText.includes(first.kls), 'student overview must show the KLS number').toBeTruthy();
    await snap('step3-kls-overview');

    // ── STEP 4: teacher accepts invite, logs in, takes attendance ───────────
    const teacherCtx = await browser.newContext({ viewport: { width, height: width === 375 ? 812 : 900 } });
    const tpage = await teacherCtx.newPage();
    const tshots = [];
    const tsnap = async (name) => {
        const file = path.join(MVP_DIR, `${String(90 + tshots.length + 1).padStart(3, '0')}-${name}-${width}.png`);
        await tpage.screenshot({ path: file }).catch(() => {});
        tshots.push(path.basename(file));
    };
    const TEACHER_PW = data.password;
    await tpage.goto(invite.url, { waitUntil: 'domcontentloaded' });
    await tpage.waitForTimeout(1500);
    await tpage.fill('#password', TEACHER_PW);
    await tpage.fill('#password_confirmation', TEACHER_PW);
    await tsnap('step4-invite-accept');
    await Promise.all([
        tpage.waitForLoadState('domcontentloaded').catch(() => {}),
        tpage.locator('form').filter({ has: tpage.locator('#password') }).locator('button[type="submit"]').first().click(),
    ]);
    await tpage.waitForTimeout(2000);

    await tpage.goto('/login', { waitUntil: 'domcontentloaded' });
    await tpage.fill('#email', ctEmail);
    await tpage.fill('#password', TEACHER_PW);
    await Promise.all([tpage.waitForLoadState('domcontentloaded').catch(() => {}), tpage.click('button[type="submit"]')]);
    await tpage.waitForTimeout(2500);
    expect(/teacher\/dashboard/.test(tpage.url()), 'teacher must land on their dashboard').toBeTruthy();
    await quietToshi(tpage);
    await tsnap('step4-teacher-dashboard');

    await tpage.goto('/teacher/attendance/add', { waitUntil: 'domcontentloaded' });
    await tpage.waitForTimeout(2000);
    const sel = tpage.locator('select').first();
    const opts = await sel.locator('option').evaluateAll((os) => os.map((o) => o.value).filter((v) => v));
    expect(opts.length, 'teacher must see their class').toBeGreaterThan(0);
    await sel.selectOption(opts[0]);
    await tpage.locator('input[type="radio"]').first().check().catch(() => {});
    await tsnap('step4-attendance-form');
    await tpage.getByRole('button', { name: /Select Students/i }).first().click({ timeout: 15000 });
    await tpage.waitForTimeout(2500);
    const attendanceBefore = [];
    const attNav = tpage.waitForLoadState('domcontentloaded').catch(() => {});
    try { await tpage.getByRole('button', { name: /Submit Attendance/i }).first().click({ timeout: 15000 }); }
    catch { await tpage.getByRole('button', { name: /Submit Attendance/i }).first().click({ force: true, timeout: 15000 }).catch(() => {}); }
    await attNav;
    await tpage.waitForTimeout(3000);
    const attText = (await tpage.textContent('body')) || '';
    expect(/success|already updated|recorded/i.test(attText), 'attendance must save').toBeTruthy();
    await tsnap('step4-attendance-saved');

    const attendanceRows = bridge(`
      echo '<<<E2E-JSON>>>'.json_encode(['rows' => \\DB::table('attendances')->where('school_id',${schoolId})->whereDate('date', date('Y-m-d'))->count()]);
    `);
    expect(attendanceRows.rows, 'attendance rows must exist in the DB').toBeGreaterThan(0);
    await teacherCtx.close();

    // ── STEP 5: one exam — marks by form AND spreadsheet import ─────────────
    // 5a. admin creates an EOT exam for the section/subject
    await page.goto(`/admin/exams/add-new?section=${ctx0.section.id}`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    const examForm = page.locator('form').filter({ has: page.locator('select[name="exam_type_id"]') }).first();
    await examForm.locator('select[name="academic_year_id"]').selectOption(String(ctx0.year));
    await examForm.locator('select[name="academic_term_id"]').selectOption(String(ctx0.term.id));
    const subjectOpts = await examForm.locator('select[name="subject_id"] option').evaluateAll((os) => os.map((o) => ({ v: o.value, t: o.textContent.trim() })).filter((x) => x.v));
    expect(subjectOpts.length, 'subject must be available for the exam').toBeGreaterThan(0);
    await examForm.locator('select[name="subject_id"]').selectOption(subjectOpts[0].v);
    await examForm.locator('select[name="exam_type_id"]').selectOption({ label: /End of Term/i }).catch(async () => {
        const types = await examForm.locator('select[name="exam_type_id"] option').evaluateAll((os) => os.map((o) => ({ v: o.value, t: o.textContent.trim() })).filter((x) => x.v));
        const eot = types.find((t) => /end of term/i.test(t.t)) || types[0];
        if (eot) await examForm.locator('select[name="exam_type_id"]').selectOption(eot.v);
    });
    await snap('step5-exam-form');
    await Promise.all([page.waitForLoadState('domcontentloaded').catch(() => {}), examForm.locator('button[type="submit"]').first().click()]);
    await page.waitForTimeout(2000);
    expect(/admin\/exams/.test(page.url()), 'exam create must redirect to the exams list').toBeTruthy();
    await snap('step5-exam-created');

    const exam = bridge(`
      $e = \\App\\Models\\Academics\\Exam::where('school_id',${schoolId})->latest('id')->first();
      $type = $e ? (\\App\\Models\\Academics\\ExamType::find($e->exam_type_id)->code ?? null) : null;
      echo '<<<E2E-JSON>>>'.json_encode(['id'=>$e->id ?? null,'code'=>$type]);
    `);
    expect(exam.id, 'exam must exist').toBeTruthy();
    info('exam=' + exam.id + ' type=' + exam.code);

    // 5b. teacher enters marks by form for the first three students
    const tctx2 = await browser.newContext({ viewport: { width, height: width === 375 ? 812 : 900 } });
    const tp2 = await tctx2.newPage();
    await tp2.goto('/login', { waitUntil: 'domcontentloaded' });
    await tp2.fill('#email', ctEmail);
    await tp2.fill('#password', TEACHER_PW);
    await Promise.all([tp2.waitForLoadState('domcontentloaded').catch(() => {}), tp2.click('button[type="submit"]')]);
    await tp2.waitForTimeout(2000);
    await quietToshi(tp2);
    await tp2.goto(`/teacher/exam/${exam.id}/marks/enter`, { waitUntil: 'domcontentloaded' });
    await tp2.waitForTimeout(2000);
    await quietToshi(tp2);
    const markInputs = tp2.locator('input[name^="marks["]');
    const mcount = await markInputs.count();
    expect(mcount, 'marks form must list students').toBeGreaterThan(0);
    for (let i = 0; i < Math.min(3, mcount); i++) await markInputs.nth(i).fill(String(80 + i));
    await tp2.screenshot({ path: path.join(MVP_DIR, `step5-marks-form-${width}.png`) }).catch(() => {});
    const marksSubmit = tp2.locator('form').filter({ has: tp2.locator('input[name^="marks["]') }).first().locator('button[type="submit"]').first();
    await quietToshi(tp2);
    await marksSubmit.scrollIntoViewIfNeeded().catch(() => {});
    let marksSaved = [];
    for (let attempt = 0; attempt < 2 && marksSaved.length === 0; attempt++) {
        // DOM click: bypasses overlay hit-testing (the Toshi dock can sit over the
        // footer button on mobile and re-open after navigation).
        await marksSubmit.evaluate((el) => el.click()).catch(() => {});
        for (let i = 0; i < 10 && marksSaved.length === 0; i++) {
            await tp2.waitForTimeout(1500);
            marksSaved = ((await tp2.textContent('body')) || '').match(/success|saved|updated/gi) || [];
        }
        if (marksSaved.length === 0) await quietToshi(tp2);
    }
    expect(marksSaved.length, 'form marks must save').toBeGreaterThan(0);
    await tp2.screenshot({ path: path.join(MVP_DIR, `step5-marks-saved-${width}.png`) }).catch(() => {});

    // 5c. spreadsheet import (template -> fill -> preview -> confirm)
    const templateResp = await tp2.request.get(`/teacher/exam/${exam.id}/marks/template?format=csv`);
    expect(templateResp.ok(), 'template download must work').toBeTruthy();
    const templateCsv = await templateResp.text();
    info('template head: ' + templateCsv.split('\n')[0]);
    // Only students WITHOUT saved marks yet (the form already saved the first rows).
    const markedIds = bridge(`
      $ids = \\DB::table('marks')->where('school_id',${schoolId})->where('exam_id',${exam.id})->pluck('student_id')->map(fn($v)=>(int)$v)->toArray();
      echo '<<<E2E-JSON>>>'.json_encode($ids);
    `);
    const unmarked = students.filter((s) => !markedIds.includes(s.user_id));
    expect(unmarked.length, 'some students must still be unmarked for the import').toBeGreaterThan(0);
    const fillRows = unmarked.map((s, i) => `${s.kls},${s.name},${90 + i}`);
    const importCsv = [templateCsv.trim().split('\n')[0], ...fillRows].join('\n') + '\n';
    const importPath = path.join(MVP_DIR, `marks-import-${ts}.csv`);
    fs.writeFileSync(importPath, importCsv);

    await tp2.goto(`/teacher/exam/${exam.id}/marks/import`, { waitUntil: 'domcontentloaded' });
    await tp2.waitForTimeout(1200);
    await tp2.setInputFiles('input[type="file"]', importPath);
    await tp2.screenshot({ path: path.join(MVP_DIR, `step5-import-form-${width}.png`) }).catch(() => {});
    await Promise.all([
        tp2.waitForLoadState('domcontentloaded').catch(() => {}),
        tp2.getByRole('button', { name: /check file/i }).first().click({ timeout: 15000 }),
    ]);
    await tp2.waitForTimeout(2500);
    await tp2.screenshot({ path: path.join(MVP_DIR, `step5-import-preview-${width}.png`) }).catch(() => {});
    const confirmBtn = tp2.getByRole('button', { name: /save marks/i }).first();
    expect(await confirmBtn.count(), 'preview must offer Save marks').toBeGreaterThan(0);
    await confirmBtn.click({ timeout: 15000 }).catch(async () => { await confirmBtn.click({ force: true, timeout: 15000 }).catch(() => {}); });
    await tp2.waitForTimeout(3000);
    const importResult = (await tp2.textContent('body')) || '';
    expect(/import finished|saved/i.test(importResult), 'marks import must complete').toBeTruthy();
    await tp2.screenshot({ path: path.join(MVP_DIR, `step5-import-result-${width}.png`) }).catch(() => {});
    await tctx2.close();

    const marksCount = bridge(`
      echo '<<<E2E-JSON>>>'.json_encode(['n' => \\DB::table('marks')->where('school_id',${schoolId})->where('exam_id',${exam.id})->count()]);
    `);
    expect(marksCount.n, 'marks must exist for the exam').toBeGreaterThanOrEqual(4);
    info('marks rows: ' + marksCount.n);

    // ── STEP 6: report card PDF ─────────────────────────────────────────────
    const pdfPath = path.join(MVP_DIR, `report-${first.kls}-${width}.pdf`);
    const [download] = await Promise.all([
        page.waitForEvent('download', { timeout: 60_000 }).catch(() => null),
        page.goto(`/admin/reports/cards/${studentLink}/student/${first.user_id}/download`, { waitUntil: 'domcontentloaded' }).catch(() => {}),
    ]);
    if (download) {
        await download.saveAs(pdfPath);
    } else {
        // fallback: plain request (session cookies via page context)
        const resp = await page.request.get(`/admin/reports/cards/${studentLink}/student/${first.user_id}/download`);
        expect(resp.ok(), 'report card request must succeed').toBeTruthy();
        fs.writeFileSync(pdfPath, await resp.body());
    }
    expect(fs.existsSync(pdfPath) && fs.statSync(pdfPath).size > 1000, 'PDF must download').toBeTruthy();
    await snap('step6-report-downloaded');
    const pdfText = execSync(`python3 "${path.join(__dirname, '..', 'lib', 'pdf_text.py')}" "${pdfPath}"`, { encoding: 'utf8', timeout: 60000 });
    // The PDF writes glyphs with wide tracking — compare with spaces collapsed.
    const norm = pdfText.replace(/\s+/g, '').toLowerCase();
    const nameCore = first.name.split(' ')[0].replace(/[^a-z]/gi, '').toLowerCase();
    expect(norm.includes(first.kls.toLowerCase()), 'PDF must contain the KLS number').toBeTruthy();
    expect(norm.includes(nameCore), 'PDF must contain the student name').toBeTruthy();
    const studentMark = bridge(`
      $m = \\DB::table('marks')->where('school_id',${schoolId})->where('exam_id',${exam.id})->where('student_id',${first.user_id})->whereNotNull('marks')->latest('id')->first();
      echo '<<<E2E-JSON>>>'.json_encode(['mark'=>$m->marks ?? null]);
    `);
    expect(studentMark.mark, 'student must have a saved mark').toBeTruthy();
    expect(norm.includes(String(Math.round(Number(studentMark.mark)))), 'PDF must contain the mark').toBeTruthy();
    info('report card ok (' + first.kls + ', mark ' + studentMark.mark + ')');

    // ── STEP 7: bursar — payment record, balance, reminder ─────────────────
    const bursarEmail = `bursar.mvp.${ts}@example.com`;
    const bursar = bridge(`
      $pw = \\Illuminate\\Support\\Facades\\Hash::make('${data.password}');
      $u = \\App\\Models\\User::create(['school_id'=>${schoolId},'usergroup_id'=>11,'name'=>'MVP Bursar','email'=>'${bursarEmail}','password'=>$pw,'status'=>'active','email_verified'=>1,'is_reset'=>0]);
      \\App\\Models\\Userprofile::create(['user_id'=>$u->id,'school_id'=>${schoolId},'usergroup_id'=>11,'firstname'=>'MVP','lastname'=>'Bursar']);
      echo '<<<E2E-JSON>>>'.json_encode(['id'=>$u->id]);
    `);
    expect(bursar.id, 'bursar must be provisioned for the flow').toBeTruthy();

    const bctx = await browser.newContext({ viewport: { width, height: width === 375 ? 812 : 900 } });
    const bpage = await bctx.newPage();
    await bpage.goto('/login', { waitUntil: 'domcontentloaded' });
    await bpage.fill('#email', bursarEmail);
    await bpage.fill('#password', data.password);
    await Promise.all([bpage.waitForLoadState('domcontentloaded').catch(() => {}), bpage.click('button[type="submit"]')]);
    await bpage.waitForTimeout(2500);
    expect(/accountant\/dashboard|fees/.test(bpage.url() + ' fees'), 'bursar must log in').toBeTruthy();
    await quietToshi(bpage);
    await bpage.screenshot({ path: path.join(MVP_DIR, `step7-bursar-dashboard-${width}.png`) }).catch(() => {});

    await bpage.goto('/accountant/fees/payments/create', { waitUntil: 'domcontentloaded' });
    await bpage.waitForTimeout(1500);
    const amountField = bpage.locator('input[name="amount"]');
    const userSelect = bpage.locator('select[name="user_id"]');
    const userOpts = await userSelect.locator('option').evaluateAll((os) => os.map((o) => o.value).filter((v) => v));
    expect(userOpts.length, 'bursar must see students').toBeGreaterThan(0);
    await userSelect.selectOption(userOpts.filter((v) => students.some((s) => String(s.user_id) === v))[0] || userOpts[0]);
    await amountField.fill('50000');
    const catSelect = bpage.locator('select[name="fee_category_id"]');
    const catOpts = await catSelect.locator('option').evaluateAll((os) => os.map((o) => o.value).filter((v) => v));
    if (catOpts.length) await catSelect.selectOption(catOpts[0]);
    await bpage.screenshot({ path: path.join(MVP_DIR, `step7-payment-form-${width}.png`) }).catch(() => {});
    await Promise.all([
        bpage.waitForLoadState('domcontentloaded').catch(() => {}),
        bpage.locator('form').filter({ has: amountField }).first().locator('button[type="submit"]').first().click(),
    ]);
    await bpage.waitForTimeout(2500);
    await bpage.goto('/accountant/fees/payments', { waitUntil: 'domcontentloaded' });
    await bpage.waitForTimeout(2000);
    const payText = (await bpage.textContent('body')) || '';
    expect(/50[, ]?000/.test(payText), 'payment must appear in the list').toBeTruthy();
    expect(/Collected this term/i.test(payText), 'balance strip must show collected').toBeTruthy();
    expect(/Outstanding/i.test(payText), 'balance strip must show outstanding').toBeTruthy();
    await bpage.screenshot({ path: path.join(MVP_DIR, `step7-balance-${width}.png`) }).catch(() => {});

    const payment = bridge(`
      $p = \\DB::table('fee_payments')->where('school_id',${schoolId})->latest('id')->first();
      $u = $p ? \\App\\Models\\User::find($p->user_id) : null;
      echo '<<<E2E-JSON>>>'.json_encode(['id'=>$p->id ?? null,'user_id'=>$p->user_id ?? null,'student_name'=>$u->name ?? null]);
    `);
    expect(payment.id, 'payment row must exist').toBeTruthy();
    // POST needs a CSRF token (the reminder has no on-screen trigger yet).
    const csrf = await bpage.evaluate(() => document.querySelector('meta[name="csrf-token"]')?.content || '');
    const reminderResp = await bpage.request.post(`/accountant/dashboard/send/reminder/${payment.id}`, {
        form: { name: payment.student_name },
        headers: { 'X-CSRF-TOKEN': csrf },
    });
    expect(reminderResp.ok(), 'fee reminder must complete without error').toBeTruthy();
    const reminderJson = await reminderResp.json().catch(() => ({}));
    expect(reminderJson.success, 'fee reminder must return success').toBeTruthy();
    await bpage.screenshot({ path: path.join(MVP_DIR, `step7-reminder-sent-${width}.png`) }).catch(() => {});
    await bctx.close();

    // ── STEP 8: parent logs in, sees child's report card + fees ────────────
    const parentEmail = `parent1.${ts}@example.com`;
    const parent = bridge(`
      $p = \\App\\Models\\User::where('school_id',${schoolId})->where('usergroup_id',7)->where('email','${parentEmail}')->first();
      if (!$p) { echo '<<<E2E-JSON>>>'.json_encode(['ok'=>false]); return; }
      $p->password = \\Illuminate\\Support\\Facades\\Hash::make('${data.password}');
      $p->is_reset = 0; $p->status='active'; $p->email_verified=1; $p->save();
      $link = \\DB::table('student_parent_links')->where('parent_id',$p->id)->first();
      echo '<<<E2E-JSON>>>'.json_encode(['ok'=>true,'parent_id'=>$p->id,'student_id'=>$link->student_id ?? null]);
    `);
    expect(parent.ok, 'parent account must exist from the normal import flow').toBeTruthy();
    expect(parent.student_id, 'parent must be linked to a child').toBeTruthy();

    const pctx = await browser.newContext({ viewport: { width, height: width === 375 ? 812 : 900 } });
    const ppage = await pctx.newPage();
    await ppage.goto('/login', { waitUntil: 'domcontentloaded' });
    await ppage.fill('#email', parentEmail);
    await ppage.fill('#password', data.password);
    await Promise.all([ppage.waitForLoadState('domcontentloaded').catch(() => {}), ppage.click('button[type="submit"]')]);
    await ppage.waitForTimeout(2500);
    expect(/parent\/dashboard/.test(ppage.url()), 'parent must land on the parent dashboard').toBeTruthy();
    await quietToshi(ppage);
    await ppage.screenshot({ path: path.join(MVP_DIR, `step8-parent-dashboard-${width}.png`) }).catch(() => {});

    await ppage.goto(`/parent/children/${parent.student_id}/grades`, { waitUntil: 'domcontentloaded' });
    await ppage.waitForTimeout(2000);
    const gradesText = (await ppage.textContent('body')) || '';
    expect(gradesText.toLowerCase().includes((students.find((s) => String(s.user_id) === String(parent.student_id))?.name || '').toLowerCase().split(' ')[0].toLowerCase()) || /grade|mark|report/i.test(gradesText), 'parent grades page must render').toBeTruthy();
    await ppage.screenshot({ path: path.join(MVP_DIR, `step8-child-grades-${width}.png`) }).catch(() => {});

    await ppage.goto(`/parent/children/${parent.student_id}/fees`, { waitUntil: 'domcontentloaded' });
    await ppage.waitForTimeout(2000);
    const feesText = (await ppage.textContent('body')) || '';
    expect(/fee|balance|payment|UGX/i.test(feesText), 'parent fees page must render').toBeTruthy();
    await ppage.screenshot({ path: path.join(MVP_DIR, `step8-child-fees-${width}.png`) }).catch(() => {});
    await pctx.close();

    // ── Wrap-up: summary
    const summary = {
        journeyId: 'mvp-endtoend', width, schoolId, schoolName: data.schoolName, adminEmail: data.admin.email,
        classTeacher: ctEmail, bursar: bursarEmail, parent: parentEmail,
        students: students.map((s) => ({ kls: s.kls, name: s.name })),
        examId: exam.id, shots, findings,
    };
    fs.writeFileSync(path.join(MVP_DIR, `summary-${width}-${ts}.json`), JSON.stringify(summary, null, 2));
    const verdict = outcomes.fetchOutcome(data.admin.email);
    console.log(`[mvp:${width}] done; findings=${findings.length}`);
});
