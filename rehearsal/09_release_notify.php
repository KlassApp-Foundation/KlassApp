<?php
require __DIR__.'/boot.php';

use App\Models\{AcademicTerm, MarksRelease, School, StandardLink, User};
use App\Models\Academics\Exam;
use App\Services\MarksRelease\MarksReleaseService;
use App\Services\WhatsAppBusinessService;

$s = state();
$school = School::findOrFail($s['school_id']);
$term = AcademicTerm::findOrFail($s['term_id']);
$admin = User::findOrFail($s['admin_id']);
$link = StandardLink::findOrFail($s['link_id']);

config(['services.whatsapp.demo_only_recipients' => false]);
config(['whatsapp.approved_templates' => ['results_released']]);
$school->forceFill(['is_demo' => 0])->save();

$sent = [];
$mock = Mockery::mock(WhatsAppBusinessService::class);
foreach (['sendTemplate' => 'template', 'sendText' => 'text', 'sendDocument' => 'document'] as $m => $kind) {
    $mock->shouldReceive($m)->andReturnUsing(function (...$args) use (&$sent, $kind, $m) {
        $sent[] = ['kind' => $kind, 'method' => $m, 'args' => array_slice($args, 0, 3)];
        return ['success' => true];
    });
}
app()->instance(WhatsAppBusinessService::class, $mock);

$exam = Exam::where('school_id', $school->id)->where('academic_term_id', $term->id)->orderBy('id')->first();
$release = app(MarksReleaseService::class)->create($admin, (int) $exam->id, (int) $link->id, null, 'Africa/Kampala', true, true, 'Rehearsal: pilot notification');
say('release '.$release->id.' whatsapp='.(int) $release->whatsapp_send);
$ran = app(MarksReleaseService::class)->runDue();
say('runDue ran '.$ran);
$fresh = MarksRelease::find($release->id);
$result = $fresh->result ?? [];
$counts = ['sent' => 0, 'skipped' => 0, 'queued' => 0, 'failed' => 0];
$reasons = [];
foreach (($result['messages'] ?? []) as $m) {
    $counts[$m['status']] = ($counts[$m['status']] ?? 0) + 1;
    if (! empty($m['reason'])) { $reasons[$m['reason']] = ($reasons[$m['reason']] ?? 0) + 1; }
}
say('status '.$fresh->status.' counts '.json_encode($counts));
say('reasons '.json_encode($reasons));
say('stub calls '.count($sent).' kinds '.json_encode(array_count_values(array_column($sent, 'kind'))));
$s['release_notify'] = ['counts' => $counts, 'reasons' => $reasons, 'stub_calls' => count($sent)];
save_state($s);
