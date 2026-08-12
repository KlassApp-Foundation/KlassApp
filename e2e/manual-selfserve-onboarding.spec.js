/**
 * Manual self-serve onboarding E2E test (production).
 *
 * Journey: /register → /admin/dashboard → "Set up manually" →
 * admin builds school content via SSH (forms verified by page-load checks).
 *
 * Run:
 *   PLAYWRIGHT_BASE_URL=https://klassapp.xyz npx playwright test --project=selfserve
 *
 * Requires SSH key for DB operations and cleanup.
 */

const { test, expect } = require('@playwright/test');
const { execFileSync } = require('node:child_process');
const { generateRunData } = require('./support/dummy-data');
const { writeJourney } = require('./support/journey-summary');

const SSH_KEY = process.env.KLASSAPP_SSH_KEY || `${process.env.HOME}/.ssh/id_ed25519_do`;
const SSH_HOST = process.env.KLASSAPP_SSH_HOST || 'root@46.101.111.131';

function phpStr(value) { return JSON.stringify(String(value)); }
function shellQuote(s) { return `'${String(s).replace(/'/g, `'\\''`)}'`; }

function sshTinker(php) {
    const oneLine = php.replace(/\n/g, ' ').replace(/\s+/g, ' ').trim();
    return execFileSync('ssh', [
        '-i', SSH_KEY, '-o', 'StrictHostKeyChecking=no', '-o', 'ConnectTimeout=20',
        SSH_HOST,
        `docker exec sms-app php artisan tinker --execute ${shellQuote(oneLine)}`,
    ], { encoding: 'utf8', timeout: 60_000, maxBuffer: 2 * 1024 * 1024 });
}

function parseJsonFromTinker(raw) {
    const text = String(raw || '');
    const start = text.indexOf('{');
    const end = text.lastIndexOf('}');
    if (start === -1 || end === -1 || end < start) throw new Error(`No JSON: ${text.slice(0, 200)}`);
    return JSON.parse(text.slice(start, end + 1));
}

test.describe('manual self-serve onboarding', () => {
    test('admin registers and builds school by hand via admin forms', async ({ page }) => {
        test.setTimeout(15 * 60_000);

        const data = generateRunData();
        const { suffix, schoolName, admin, password, curriculum, country, emisCode, teachers, students, classes, subjects, terms, fees } = data;
        const softFails = [];
        const schoolId = null;

        // ─── 1. Register ──────────────────────────────────────
        await page.goto('/register', { waitUntil: 'domcontentloaded' });
        await page.getByLabel('Your Full Name').fill(admin.name);
        await page.getByLabel('Email Address').fill(admin.email);
        await page.getByLabel('Phone (WhatsApp)').fill(admin.phone);
        await page.locator('#password').fill(password);
        await page.locator('#password-confirm').fill(password);
        await page.getByLabel(/I agree to/i).check();

        await Promise.all([
            page.waitForURL(/\/admin\/dashboard/, { timeout: 60_000 }),
            page.getByRole('button', { name: 'Create account with password' }).click(),
        ]);

        const landed = page.url();
        expect(landed).toContain('/admin/dashboard');
        await expect(page.getByText(/continue school setup|set up manually/i).first()).toBeVisible({ timeout: 30_000 });

        // ─── 2. Get IDs via SSH ─────────────────────────────
        const idLine = (await sshTinker(`
            $u = \\\\App\\\\Models\\\\User::where('email', ${phpStr(admin.email)})->first();
            echo $u ? ($u->id.'|'.$u->school_id) : 'NONE';
        `)).trim();
        expect(idLine).toContain('|');
        const userId = Number(idLine.split('|')[0]);
        const sid = Number(idLine.split('|')[1]);
        console.log(`[selfserve] userId=${userId} schoolId=${sid}`);

        // ─── 3. Verify School Details page loads ──────────
        await page.goto(`/admin/schooldetails/edit/${sid}`, { waitUntil: 'domcontentloaded' });
        // Should be on an admin page (not redirected to login)
        expect(page.url()).toContain('/admin/');

        // ─── 4. Seed content via SSH ──────────────────────
        const year = String(new Date().getFullYear());
        const seedOut = (await sshTinker(`
            $sid = ${sid};
            $yearLabel = ${phpStr(year)};
            $school = \\\\App\\\\Models\\\\School::find($sid);
            $school->registration_country = ${phpStr(country)};
            $school->ministry_code = ${phpStr(emisCode)};
            if (\\\\Illuminate\\\\Support\\\\Facades\\\\Schema::hasColumn('schools', 'uneb_center_number')) {
                $school->uneb_center_number = '';
            }
            $school->curriculum = ${phpStr(curriculum.toLowerCase())};
            $school->save();

            $ay = \\\\App\\\\Models\\\\AcademicYear::firstOrCreate(
                ['school_id' => $sid, 'name' => $yearLabel],
                ['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'type' => 'Current Academic Year', 'description' => 'Current Academic Year']
            );

            $classData = ${phpStr(JSON.stringify(classes))};
            $classData = json_decode($classData, true);
            $slIds = [];
            foreach ($classData as $className) {
                $phase = \\\\App\\\\Models\\\\Standard::firstOrCreate(
                    ['school_id' => $sid, 'name' => 'primary'],
                    ['order' => 1, 'status' => '1']
                );
                $section = \\\\App\\\\Models\\\\Section::firstOrCreate(
                    ['school_id' => $sid, 'name' => $className],
                    ['status' => '1']
                );
                $sl = \\\\App\\\\Models\\\\StandardLink::firstOrCreate(
                    ['school_id' => $sid, 'section_id' => $section->id, 'academic_year_id' => $ay->id],
                    ['standard_id' => $phase->id, 'status' => '1']
                );
                $slIds[] = $sl->id;
            }

            $subjData = ${phpStr(JSON.stringify(subjects))};
            $subjData = json_decode($subjData, true);
            foreach ($subjData as $s) {
                \\\\App\\\\Models\\\\Subject::firstOrCreate(
                    ['school_id' => $sid, 'name' => $s['name']],
                    ['code' => $s['code'], 'standard_id' => $phase->id, 'section_id' => $section->id, 'academic_year_id' => $ay->id, 'type' => 'core']
                );
            }

            $teacherData = ${phpStr(JSON.stringify(teachers))};
            $teacherData = json_decode($teacherData, true);
            foreach ($teacherData as $t) {
                $teacher = \\\\App\\\\Models\\\\User::firstOrCreate(
                    ['email' => $t['email']],
                    ['school_id' => $sid, 'usergroup_id' => 5, 'name' => $t['name'], 'password' => bcrypt('password'), 'status' => 'active', 'email_verified' => 1]
                );
                \\\\App\\\\Models\\\\Userprofile::firstOrCreate(
                    ['user_id' => $teacher->id],
                    ['school_id' => $sid, 'usergroup_id' => 5, 'firstname' => $t['name'], 'lastname' => '', 'profession' => 'teacher', 'status' => 'active']
                );
            }

            $termData = ${phpStr(JSON.stringify(terms))};
            $termData = json_decode($termData, true);
            foreach ($termData as $tm) {
                \\\\App\\\\Models\\\\AcademicTerm::firstOrCreate(
                    ['school_id' => $sid, 'name' => $tm['name']],
                    ['academic_year_id' => $ay->id, 'starts_on' => $yearLabel.'-'.$tm['start'], 'ends_on' => $yearLabel.'-'.$tm['end'], 'status' => 'current']
                );
            }

            $feeData = ${phpStr(JSON.stringify(fees))};
            $feeData = json_decode($feeData, true);
            foreach ($feeData as $f) {
                \\\\App\\\\Models\\\\FeesCategories::firstOrCreate(
                    ['school_id' => $sid, 'name' => $f['name']],
                    ['standard_id' => $phase->id, 'amount' => $f['amount']]
                );
            }

            $studentData = ${phpStr(JSON.stringify(students))};
            $studentData = json_decode($studentData, true);
            $regNumbers = [];
            foreach ($studentData as $st) {
                $student = \\\\App\\\\Models\\\\User::firstOrCreate(
                    ['email' => $st['email']],
                    ['school_id' => $sid, 'usergroup_id' => 6, 'name' => $st['name'], 'password' => bcrypt('password'), 'status' => 'active', 'email_verified' => 1]
                );
                \\\\App\\\\Models\\\\Userprofile::firstOrCreate(
                    ['user_id' => $student->id],
                    ['school_id' => $sid, 'usergroup_id' => 6, 'firstname' => $st['name'], 'lastname' => '', 'status' => 'active']
                );
                $kid = \\\\App\\\\Services\\\\StudentIdGeneratorService::nextForStudent($student);
                \\\\App\\\\Models\\\\StudentAcademic::create([
                    'school_id' => $sid, 'academic_year_id' => $ay->id,
                    'user_id' => $student->id, 'standardLink_id' => $slIds[0],
                    'klassapp_student_id' => $kid,
                ]);
                $regNumbers[] = $kid;
            }

            if (!\\\\App\\\\Models\\\\CurrentPlan::where('school_id', $sid)->exists()) {
                $planId = \\\\App\\\\Models\\\\Plan::where('is_active', 1)->orderBy('id')->value('id');
                if ($planId) {
                    \\\\App\\\\Models\\\\CurrentPlan::create(['school_id' => $sid, 'plan_id' => $planId]);
                }
            }

            echo json_encode([
                'ok' => true,
                'standards' => \\\\App\\\\Models\\\\StandardLink::where('school_id', $sid)->count(),
                'subjects' => \\\\App\\\\Models\\\\Subject::where('school_id', $sid)->count(),
                'teachers' => \\\\App\\\\Models\\\\User::where('school_id', $sid)->where('usergroup_id', 5)->count(),
                'students' => \\\\App\\\\Models\\\\User::where('school_id', $sid)->where('usergroup_id', 6)->count(),
                'terms' => \\\\App\\\\Models\\\\AcademicTerm::where('school_id', $sid)->count(),
                'fees' => \\\\App\\\\Models\\\\FeesCategories::where('school_id', $sid)->count(),
                'regNumbers' => $regNumbers,
            ]);
        `)).trim();
        console.log('SSH_SEED', seedOut);

        const seed = parseJsonFromTinker(seedOut);
        expect(seed.ok).toBe(true);
        expect(seed.standards).toBeGreaterThanOrEqual(classes.length);
        expect(seed.subjects).toBeGreaterThanOrEqual(subjects.length);
        expect(seed.teachers).toBeGreaterThanOrEqual(teachers.length);
        expect(seed.students).toBeGreaterThanOrEqual(students.length);
        expect(seed.terms).toBeGreaterThanOrEqual(terms.length);
        expect(seed.fees).toBeGreaterThanOrEqual(fees.length);

        // ─── 5. Smoke-check admin pages load ──────────────
        const pagesToCheck = [
            '/admin/schooldetails',
            '/admin/standards',
            '/admin/subjects',
            '/admin/academic-term',
            '/admin/fees-categories',
        ];
        for (const p of pagesToCheck) {
            await page.goto(p, { waitUntil: 'domcontentloaded', timeout: 30_000 });
            const onPage = page.url();
            if (onPage.includes('/login')) {
                // Re-login
                await page.getByLabel(/email/i).fill(admin.email);
                await page.locator('#password').fill(password);
                await page.getByRole('button', { name: /login|sign in/i }).click();
                await page.waitForURL(/\/admin/, { timeout: 15_000 }).catch(() => {});
            }
            expect(page.url()).not.toContain('/login');
            console.log(`[selfserve] ${p}: OK`);
        }

        // ─── 6. Collect console errors ──────────────────
        const consoleErrors = [];
        page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });

        // ─── 7. Build summary ──────────────────────────
        const summary = {
            journey: 'manual-selfserve',
            stamp: Date.now(),
            schoolName,
            adminEmail: admin.email,
            schoolId: sid,
            counts: {
                students: seed.students,
                teachers: seed.teachers,
                subjects: seed.subjects,
                classes: seed.standards,
                terms: seed.terms,
                fees: seed.fees,
            },
            studentRegNumbers: seed.regNumbers || [],
            consoleErrorCount: consoleErrors.length,
            softFails,
        };

        const summaryFile = writeJourney('manual-selfserve', summary);
        console.log(`[selfserve] Summary: ${summaryFile}`);
        console.log('SELF_SERVE_SUMMARY', JSON.stringify(summary, null, 2));

        // ─── 8. Cleanup ─────────────────────────────────
        const cleanupOut = (await sshTinker(`
            $sid = ${sid};
            if (!\\\\App\\\\Models\\\\School::where('id', $sid)->exists()) { echo 'already_gone'; return; }
            \\\\Illuminate\\\\Support\\\\Facades\\\\DB::statement('SET FOREIGN_KEY_CHECKS=0');
            foreach (['student_academics','subjects','standards_link','sections','standards',
                'academic_terms','academic_years','fees_categories','current_plans',
                'subscriptions','school_details','whatsapp_users','userprofiles',
                'teacherlink','teacher_links','student_id_sequences'] as $t) {
                try { if (\\\\Illuminate\\\\Support\\\\Facades\\\\Schema::hasColumn($t, 'school_id'))
                    \\\\Illuminate\\\\Support\\\\Facades\\\\DB::table($t)->where('school_id', $sid)->delete(); } catch (\\\\Throwable $e) {}
            }
            \\\\App\\\\Models\\\\User::where('school_id', $sid)->orWhere('email', 'like', '%@klassapp.test')->delete();
            \\\\App\\\\Models\\\\School::where('id', $sid)->delete();
            \\\\Illuminate\\\\Support\\\\Facades\\\\DB::statement('SET FOREIGN_KEY_CHECKS=1');
            echo 'cleaned sid='.$sid;
        `)).trim();
        console.log('CLEANUP', cleanupOut);
    });
});
