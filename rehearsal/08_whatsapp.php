<?php
/**
 * WhatsApp rehearsal with a stubbed sender. Nothing leaves the machine:
 * WhatsAppBusinessService is replaced by a mock that records calls.
 */
require __DIR__.'/boot.php';

use App\Http\Controllers\Api\WhatsAppController;
use App\Models\{School, StudentParentLink, User, WhatsAppUser};
use App\Services\StudentReportCardService;
use App\Services\WhatsAppBusinessService;
use App\Services\WhatsAppReportCardDeliveryService;
use Illuminate\Support\Facades\RateLimiter;

$s = state();
$school = School::findOrFail($s['school_id']);
$learners = User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->orderBy('id')->take(8)->get();

$parents = [];
foreach ($learners as $i => $learner) {
    $parent = User::firstOrCreate(['email' => 'parent'.($i + 1).'@kabale-rehearsal.test'], [
        'school_id' => $school->id, 'usergroup_id' => 7, 'name' => 'Parent '.($i + 1), 'status' => 'active',
    ]);
    StudentParentLink::firstOrCreate(['parent_id' => $parent->id, 'student_id' => $learner->id], [
        'school_id' => $school->id, 'status' => 1,
    ]);
    $wa = WhatsAppUser::firstOrCreate(['phone' => '+25670000'.sprintf('%04d', 100 + $i)], [
        'user_id' => $parent->id, 'school_id' => $school->id, 'opted_in' => true, 'verified_at' => now(), 'last_inbound_at' => now(),
    ]);
    $parents[] = ['parent' => $parent, 'learner' => $learner, 'wa' => $wa];
}
say('parents linked: '.count($parents));

$sent = [];
$mock = Mockery::mock(WhatsAppBusinessService::class);
$mock->shouldReceive('sendDocument')->andReturnUsing(function ($phone, $url, $caption, $filename, $type, $uid) use (&$sent) {
    $sent[] = ['kind' => 'document', 'phone' => $phone, 'filename' => $filename, 'caption' => $caption, 'type' => $type, 'uid' => $uid];
    return ['success' => true];
});
$mock->shouldReceive('sendText')->andReturnUsing(function ($phone, $text, $type, $uid) use (&$sent) {
    $sent[] = ['kind' => 'text', 'phone' => $phone, 'text' => $text, 'type' => $type, 'uid' => $uid];
    return ['success' => true];
});
$mock->shouldReceive('sendTemplate')->andReturnUsing(function ($phone, $template, $vars, $lang, $type) use (&$sent) {
    $sent[] = ['kind' => 'template', 'phone' => $phone, 'template' => $template, 'vars' => $vars];
    return ['success' => true];
});
$mock->shouldReceive('sendList')->andReturnUsing(function ($phone, $title, $body, $button, $section, $type) use (&$sent) {
    $sent[] = ['kind' => 'list', 'phone' => $phone, 'type' => $type];
    return ['success' => true];
});
$mock->shouldReceive('sendButtons')->andReturnUsing(function (...$args) use (&$sent) {
    $sent[] = ['kind' => 'buttons'];
    return ['success' => true];
});
app()->instance(WhatsAppBusinessService::class, $mock);

$controller = app(WhatsAppController::class);
$method = new ReflectionMethod($controller, 'sendParentReportCards');
$method->setAccessible(true);

foreach ($parents as $p) {
    $method->invoke($controller, $p['wa'], $p['wa']->phone, $mock);
}
say('documents sent: '.count(array_filter($sent, fn ($x) => $x['kind'] === 'document')));
foreach ($sent as $row) { say(json_encode($row)); }

$delivery = app(WhatsAppReportCardDeliveryService::class);
$orphan = User::firstOrCreate(['email' => 'parent9@kabale-rehearsal.test'], [
    'school_id' => $school->id, 'usergroup_id' => 7, 'name' => 'Parent 9', 'status' => 'active',
]);
$r = $delivery->prepareForStudent($orphan, $learners->first());
say('unlinked parent: '.json_encode(['ok' => $r['ok'], 'flow' => $r['flow_type'] ?? null]));

$s['whatsapp'] = ['documents' => count(array_filter($sent, fn ($x) => $x['kind'] === 'document')), 'sends' => $sent];
save_state($s);
