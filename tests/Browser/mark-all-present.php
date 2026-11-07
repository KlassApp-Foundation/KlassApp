<?php

/**
 * Visible Mark all present check in Chrome, through the real Vue layout.
 *
 * Usage: php tests/Browser/mark-all-present.php
 * Exits 0 only when Absent radios become Present, no save request fires on
 * the click, and Save then persists just the open stream.
 */

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$db = sys_get_temp_dir().'/markall-register.sqlite';
@unlink($db);
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE='.$db);
$_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = $db;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

Artisan::call('migrate', ['--force' => true]);

$email = 'head.markall@syn.test';
$password = bin2hex(random_bytes(9));

DB::table('usergroups')->upsert([
    ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
    ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
], 'id');

$school = School::create([
    'name' => 'Mark All School',
    'slug' => 'markall-'.uniqid(),
    'email' => 'markall-'.uniqid().'@t.sch.ug',
    'phone' => '+256709'.random_int(100000, 999999),
    'status' => 1,
    'registration_country' => 'Uganda',
]);
$year = AcademicYear::create([
    'school_id' => $school->id,
    'name' => '2026',
    'description' => 'AY',
    'start_date' => now()->subMonths(2)->toDateString(),
    'end_date' => now()->addMonths(6)->toDateString(),
    'status' => 1,
]);
Cache::forget('academic_year_for_school_'.$school->id);
$standard = Standard::create([
    'school_id' => $school->id, 'name' => 'primary', 'order' => 4, 'status' => 1,
]);
$teacher = User::create([
    'school_id' => $school->id,
    'usergroup_id' => 5,
    'name' => 'Register Head',
    'email' => $email,
    'password' => 'placeholder',
    'status' => 'active',
    'email_verified' => 1,
]);
DB::table('users')->where('id', $teacher->id)->update(['password' => Hash::make($password)]);
if (! Hash::check($password, (string) DB::table('users')->where('id', $teacher->id)->value('password'))) {
    fwrite(STDERR, "password hash did not verify\n");
    exit(1);
}
Userprofile::create([
    'user_id' => $teacher->id, 'school_id' => $school->id, 'usergroup_id' => 5,
    'firstname' => 'Register', 'lastname' => 'Head',
]);

$base = Section::create([
    'school_id' => $school->id, 'name' => 'Primary Four', 'status' => 1, 'class_teacher_id' => $teacher->id,
]);
$eastSection = Section::create([
    'school_id' => $school->id, 'name' => 'Primary Four E', 'status' => 1,
]);
$westSection = Section::create([
    'school_id' => $school->id, 'name' => 'Primary Four W', 'status' => 1,
]);
$east = StandardLink::create([
    'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
    'section_id' => $eastSection->id, 'class_teacher_id' => null, 'stream' => null, 'status' => 1,
]);
$west = StandardLink::create([
    'school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id,
    'section_id' => $westSection->id, 'class_teacher_id' => null, 'stream' => null, 'status' => 1,
]);

$make = function (string $name, StandardLink $link) use ($school, $year): User {
    $user = User::create([
        'school_id' => $school->id, 'usergroup_id' => 6, 'name' => $name,
        'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@syn.test',
        'password' => Hash::make('unused'), 'status' => 'active', 'email_verified' => 1,
    ]);
    Userprofile::create([
        'user_id' => $user->id, 'school_id' => $school->id, 'usergroup_id' => 6,
        'firstname' => explode(' ', $name)[0], 'lastname' => explode(' ', $name, 2)[1] ?? '1',
    ]);
    StudentAcademic::create([
        'school_id' => $school->id, 'academic_year_id' => $year->id,
        'user_id' => $user->id, 'standardLink_id' => $link->id,
    ]);

    return $user;
};
$eastStudent = $make('EAST 1', $east);
$westStudent = $make('WEST 1', $west);
foreach ([[$eastStudent, $east], [$westStudent, $west]] as [$student, $link]) {
    Attendance::create([
        'school_id' => $school->id,
        'academic_year_id' => $year->id,
        'standardLink_id' => $link->id,
        'user_id' => $student->id,
        'date' => now()->toDateString(),
        'session' => 'forenoon',
        'status' => 0,
        'remarks' => 'Away',
        'recorded_by' => $teacher->id,
    ]);
}

$port = 8771;
$env = [];
foreach (array_merge($_ENV, $_SERVER) as $key => $value) {
    if (is_string($key) && is_string($value)) {
        $env[$key] = $value;
    }
}
$env['PATH'] = getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';
$env['DB_CONNECTION'] = 'sqlite';
$env['DB_DATABASE'] = $db;
// PHPUnit forces an array session, which forgets the login on the next request.
$env['SESSION_DRIVER'] = 'file';
$env['CACHE_DRIVER'] = 'file';
$env['CACHE_STORE'] = 'file';
$server = proc_open(
    ['bash', '-lc', 'exec 3>&- 4>&- 5>&- 6>&- 7>&- 8>&- 9>&-; exec '.escapeshellarg(PHP_BINARY).' artisan serve --host=127.0.0.1 --port='.$port.' > '.escapeshellarg(sys_get_temp_dir().'/markall-serve.log').' 2>&1'],
    [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $pipes,
    dirname(__DIR__, 2),
    $env
);
if (! is_resource($server)) {
    fwrite(STDERR, "could not start the server\n");
    exit(1);
}

$ready = false;
for ($i = 0; $i < 40; $i++) {
    $fp = @fsockopen('127.0.0.1', $port, $errno, $err, 0.2);
    if ($fp) {
        fclose($fp);
        $ready = true;
        break;
    }
    usleep(250000);
}
if (! $ready) {
    fwrite(STDERR, "server did not start\n");
    proc_terminate($server);
    exit(1);
}

$node = <<<'JS'
import puppeteer from 'puppeteer-core';
const base = process.env.MARKALL_BASE;
const email = process.env.MARKALL_EMAIL;
const password = process.env.MARKALL_PASSWORD;
const browser = await puppeteer.launch({
  executablePath: process.env.CHROME_PATH || '/opt/google/chrome/chrome',
  headless: 'new',
  args: ['--no-sandbox'],
});
const page = await browser.newPage();
await page.setViewport({width: 1280, height: 900});
const posts = [];
page.on('request', (request) => {
  if (request.method() === 'POST') posts.push(request.url());
  console.error(request.method() + ' ' + request.url());
});
await page.goto(base + '/login', {waitUntil: 'domcontentloaded'});
await page.waitForSelector('form.ap-form button.ap-submit', {timeout: 10000});
await page.type('#email', email);
await page.type('#password', password);
const submitted = page.waitForNavigation({waitUntil: 'domcontentloaded', timeout: 15000}).catch(() => null);
await page.evaluate(() => document.querySelector('form.ap-form').requestSubmit());
await submitted;
if (page.url().endsWith('/login')) {
  const info = await page.evaluate(() => ({
    url: location.href,
    text: (document.body.innerText || '').replace(/\s+/g, ' ').slice(0, 600),
  }));
  console.error(JSON.stringify(info));
  throw new Error('login stayed on the sign-in page');
}
await page.goto(base + '/teacher/attendance/register', {waitUntil: 'domcontentloaded'});
await new Promise((r) => setTimeout(r, 1200));

async function rowState(name) {
  return page.evaluate((name) => {
    const row = [...document.querySelectorAll('[data-testid="attendance-row"]')].find((el) => (el.innerText || '').includes(name));
    if (!row) return null;
    const present = row.querySelector('[data-testid="status-present"]');
    const absent = row.querySelector('[data-testid="status-absent"]');
    const presentLabel = present.closest('.attendance-choice');
    const absentLabel = absent.closest('.attendance-choice');
    const bg = (el) => getComputedStyle(el).backgroundColor;
    return {
      presentChecked: present.checked,
      absentChecked: absent.checked,
      presentClass: presentLabel.classList.contains('is-present'),
      absentClass: absentLabel.classList.contains('is-absent'),
      presentBg: bg(presentLabel),
      absentBg: bg(absentLabel),
    };
  }, name);
}

const before = await rowState('EAST 1');
await page.evaluate(() => {
  const row = [...document.querySelectorAll('[data-testid="attendance-row"]')].find((el) => (el.innerText || '').includes('EAST 1'));
  row.querySelector('[data-testid="status-absent"]').click();
});
await new Promise((r) => setTimeout(r, 200));
const absent = await rowState('EAST 1');
const postsAfterAbsent = posts.length;
await page.click('[data-testid="mark-all-present"]');
await new Promise((r) => setTimeout(r, 300));
const marked = await rowState('EAST 1');
const markedWest = await rowState('WEST 1');
const postsAfterMark = posts.length;

const stream = await page.$eval('#attendance-stream', (select) => {
  const option = [...select.options].find((item) => item.textContent.trim() === 'W');
  return option ? option.value : '';
});
await page.select('#attendance-stream', stream);
await Promise.all([
  page.waitForNavigation({waitUntil: 'domcontentloaded'}),
  page.evaluate(() => document.querySelector('[data-testid="attendance-filters"]').requestSubmit()),
]);
await new Promise((r) => setTimeout(r, 800));
const filteredBefore = await rowState('WEST 1');
const filteredEast = await rowState('EAST 1');
await page.click('[data-testid="mark-all-present"]');
await new Promise((r) => setTimeout(r, 300));
const filteredAfter = await rowState('WEST 1');
const postsBeforeSave = posts.length;
await page.evaluate(() => document.querySelector('[data-testid="save-attendance"]').scrollIntoView({block: 'center'}));
await Promise.all([
  page.waitForNavigation({waitUntil: 'domcontentloaded'}),
  page.click('[data-testid="save-attendance"]'),
]);
const postsAfterSave = posts.length;
await browser.close();
const result = {before, absent, marked, markedWest, filteredBefore, filteredEast, filteredAfter, postsAfterAbsent, postsAfterMark, postsBeforeSave, postsAfterSave};
console.log(JSON.stringify(result));
JS;

$nodeFile = sys_get_temp_dir().'/mark-all-present.mjs';
file_put_contents($nodeFile, $node);
$nodeEnv = $env;
$nodeEnv['MARKALL_BASE'] = 'http://127.0.0.1:'.$port;
$nodeEnv['MARKALL_EMAIL'] = $email;
$nodeEnv['MARKALL_PASSWORD'] = $password;
$nodeEnv['NODE_PATH'] = '/tmp/node_modules';
$descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$nodeProc = proc_open(['node', $nodeFile], $descriptor, $nodePipes, '/tmp', $nodeEnv);
$stdout = stream_get_contents($nodePipes[1]);
$stderr = stream_get_contents($nodePipes[2]);
fclose($nodePipes[1]);
fclose($nodePipes[2]);
$nodeCode = proc_close($nodeProc);
proc_terminate($server);

if ($nodeCode !== 0) {
    fwrite(STDERR, "browser script failed\n".$stderr."\n".$stdout."\n");
    exit(1);
}

$result = json_decode(trim($stdout), true);
if (! is_array($result)) {
    fwrite(STDERR, "browser script returned no result\n".$stdout."\n".$stderr."\n");
    exit(1);
}

$fail = [];
$expect = function (bool $ok, string $message) use (&$fail): void {
    if (! $ok) {
        $fail[] = $message;
    }
};
$expect(($result['before']['absentChecked'] ?? false) === true, 'saved absence was not checked on load');
$expect(($result['absent']['absentChecked'] ?? false) === true && ($result['absent']['presentChecked'] ?? true) === false, 'clicking Absent did not check Absent');
$expect(($result['absent']['absentClass'] ?? false) === true, 'Absent label was not painted');
$expect(($result['marked']['presentChecked'] ?? false) === true && ($result['marked']['absentChecked'] ?? true) === false, 'Mark all present did not check Present');
$expect(($result['marked']['presentClass'] ?? false) === true && ($result['marked']['absentClass'] ?? true) === false, 'Present label was not painted');
$expect(($result['marked']['presentBg'] ?? '') === 'rgb(220, 252, 231)', 'Present control was not the green present colour');
$expect(($result['markedWest']['presentChecked'] ?? false) === true, 'the other stream on the register was not reset');
$expect((int) $result['postsAfterMark'] === (int) $result['postsAfterAbsent'], 'Mark all present sent a request');
$expect($result['filteredEast'] === null, 'the filtered register still showed the other stream');
$expect(($result['filteredAfter']['presentChecked'] ?? false) === true && ($result['filteredAfter']['absentChecked'] ?? true) === false, 'filtered Mark all present did not check Present');
$expect((int) $result['postsAfterSave'] > (int) $result['postsBeforeSave'], 'Save did not submit');

$eastStatus = (int) Attendance::query()->where('user_id', $eastStudent->id)->value('status');
$westStatus = (int) Attendance::query()->where('user_id', $westStudent->id)->value('status');
$expect($eastStatus === 0, 'saving the filtered stream changed the other stream');
$expect($westStatus === 1, 'Save did not persist Present for the open stream');

if ($fail !== []) {
    fwrite(STDERR, implode("\n", $fail)."\n".json_encode($result)."\n");
    exit(1);
}

echo "mark-all-present browser check passed\n";
exit(0);
