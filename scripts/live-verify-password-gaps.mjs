#!/usr/bin/env node
/**
 * Live verify password provisioning gap fixes on production.
 * Creates admin-style parent + alumni via RegisterUser trait and asserts
 * Hash::check('password') is false and is_reset=1.
 *
 * Usage: node scripts/live-verify-password-gaps.mjs
 */
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const ARTIFACT = path.join(process.cwd(), 'tmp', 'live-verify-password-gaps');
const SUFFIX = String(Date.now()).slice(-8);

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
    throw new Error(`No JSON in tinker output: ${out.slice(0, 400)}`);
  }
  return JSON.parse(candidates[candidates.length - 1]);
}

function runVerify() {
  const php = `
    $school = \\App\\Models\\School::where('status', 1)->orderBy('id')->first();
    if (!$school) { echo json_encode(['ok' => false, 'error' => 'no school']); return; }

    $harness = new class {
      use \\App\\Traits\\RegisterUser;
    };

    $year = \\App\\Models\\AcademicYear::where('school_id', $school->id)->where('status', 1)->first()
      ?? \\App\\Models\\AcademicYear::where('school_id', $school->id)->first();

    $studentEmail = 'pwgap.student.${SUFFIX}@live-verify.klassapp.xyz';
    $parentEmail = 'pwgap.parent.${SUFFIX}@live-verify.klassapp.xyz';
    $alumniEmail = 'pwgap.alumni.${SUFFIX}@live-verify.klassapp.xyz';

    $stdLink = \\App\\Models\\StandardLink::where('school_id', $school->id)->first();

    $studentData = (object) [
      'name' => 'PwGap Student',
      'email' => $studentEmail,
      'mobile_no' => '+256700' . substr('${SUFFIX}', 0, 6),
      'registration_number' => 'KLS-PWGAP-${SUFFIX}',
      'firstname' => 'PwGap',
      'lastname' => 'Student',
      'gender' => 'male',
      'date_of_birth' => '2015-01-01',
      'blood_group' => 'o+',
      'address' => 'Kampala',
      'city_id' => null,
      'country_id' => null,
      'pincode' => null,
      'birth_place' => null,
      'native_place' => null,
      'caste' => null,
      'sub_caste' => null,
      'aadhar_number' => null,
      'joining_date' => '2024-01-01',
      'notes' => null,
      'standard' => $stdLink?->id,
      'std_school_pay_number' => null,
      'lin' => null,
      'school_student_id' => null,
      'board_registration_number' => null,
      'mode_of_transport' => 'walking',
      'siblings' => 'no',
      'siblings_count' => 0,
    ];

    $student = $harness->CreateUser($studentData, $school->id, $year?->id ?? 0, '', 6);

    $parentData = (object) [
      'parent' => 'add',
      'name' => 'PwGap Parent',
      'email' => $parentEmail,
      'mobile_no' => '+256701' . substr('${SUFFIX}', 0, 6),
      'firstname' => 'PwGap',
      'lastname' => 'Parent',
      'alternate_no' => null,
      'qualification_id' => null,
      'profession' => null,
      'sub_occupation' => null,
      'designation' => null,
      'organization_name' => null,
      'official_address' => null,
      'relation' => 'father',
      'annual_income' => null,
    ];

    $parent = $harness->CreateParent($student->id, $parentData, $school->id, 7);

    $alumniData = (object) [
      'name' => 'PwGap Alumni',
      'email' => $alumniEmail,
      'mobile_no' => '+256702' . substr('${SUFFIX}', 0, 6),
      'email_verification_code' => null,
      'registration_number' => null,
      'passing_session' => '2024',
      'institution_name' => null,
      'degree' => null,
      'specialization' => null,
      'college_start_year' => null,
      'current_studying' => 1,
      'college_end_year' => null,
      'grade' => null,
      'company_name' => null,
      'designation' => null,
      'location' => null,
      'job_start_year' => null,
      'job_start_month' => null,
      'present' => 1,
      'job_end_year' => null,
      'job_end_month' => null,
      'twitter' => null,
      'linkedin' => null,
      'telegram' => null,
      'facebook' => null,
      'about_me' => null,
    ];

    $alumni = $harness->AddAlumni($alumniData, 9, $school->id, '2024');

    $parent = $parent->fresh();
    $alumni = $alumni->fresh();

    $result = [
      'ok' => true,
      'school_id' => $school->id,
      'parent' => [
        'id' => $parent->id,
        'email' => $parent->email,
        'ug' => (int) $parent->usergroup_id,
        'is_reset' => (int) $parent->is_reset,
        'demo_password' => \\Illuminate\\Support\\Facades\\Hash::check('password', $parent->password),
      ],
      'alumni' => [
        'id' => $alumni->id,
        'email' => $alumni->email,
        'ug' => (int) $alumni->usergroup_id,
        'is_reset' => (int) $alumni->is_reset,
        'demo_password' => \\Illuminate\\Support\\Facades\\Hash::check('password', $alumni->password),
      ],
      'student_id' => $student->id,
      'source_clean' => !str_contains(
        file_get_contents(base_path('app/Traits/RegisterUser.php')),
        "bcrypt('password')"
      ),
      'admission_clean' => !str_contains(
        file_get_contents(base_path('app/Traits/AdmissionUser.php')),
        "bcrypt('password')"
      ),
      'import_clean' => !str_contains(
        file_get_contents(base_path('app/Http/Controllers/Admin/TeacherLinkImportController.php')),
        "bcrypt('password')"
      ),
      'enroll_clean' => !str_contains(
        file_get_contents(base_path('app/Console/Commands/EnrollStudents.php')),
        "bcrypt('password')"
      ),
    ];

    // cleanup created users
    \\App\\Models\\StudentParentLink::where('parent_id', $parent->id)->orWhere('student_id', $student->id)->delete();
    \\App\\Models\\Userprofile::whereIn('user_id', [$parent->id, $alumni->id, $student->id])->delete();
    \\App\\Models\\StudentAcademic::where('user_id', $student->id)->delete();
    \\App\\Models\\User::whereIn('id', [$parent->id, $alumni->id, $student->id])->delete();

    echo json_encode($result);
  `;
  return parseTinkerJson(sshTinker(php));
}

async function main() {
  fs.mkdirSync(ARTIFACT, { recursive: true });
  const result = runVerify();
  const checks = {
    ok: result.ok === true,
    parentNotDemo: result.parent?.demo_password === false,
    parentIsReset: result.parent?.is_reset === 1,
    alumniNotDemo: result.alumni?.demo_password === false,
    alumniIsReset: result.alumni?.is_reset === 1,
    sourceClean: result.source_clean === true,
    admissionClean: result.admission_clean === true,
    importClean: result.import_clean === true,
    enrollClean: result.enroll_clean === true,
  };
  const report = { startedAt: new Date().toISOString(), result, checks, pass: Object.values(checks).every(Boolean) };
  fs.writeFileSync(path.join(ARTIFACT, 'REPORT.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify({ pass: report.pass, checks, artifact: ARTIFACT }, null, 2));
  if (!report.pass) process.exitCode = 1;
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
