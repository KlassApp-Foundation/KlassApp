<?php

namespace Tests\Feature\Marks;

use App\Http\Controllers\Admin\MarksController;
use App\Http\Controllers\Api\WhatsAppController;
use App\Providers\EventServiceProvider;
use App\Services\OutboundWhatsAppService;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

/**
 * Row 3 of the marks plan: dead ungated mark paths are gone, and the live
 * parent and WhatsApp mark entry points are still registered.
 */
class DeadUngatedMarksPathsTest extends TestCase
{
    public function test_legacy_showmark_urls_are_not_routed(): void
    {
        foreach ([
            '/admin/student/showmark/ada',
            '/admin/student/showallmark/ada',
            '/admin/student/comparemark/ada',
            '/teacher/student/showmark/ada',
            '/teacher/student/showallmark/ada',
            '/teacher/student/comparemark/ada',
        ] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    public function test_unrouted_whatsapp_grade_and_report_actions_are_gone(): void
    {
        $actions = collect(Route::getRoutes())->map(fn ($route) => (string) $route->getActionName());

        $this->assertFalse($actions->contains(fn (string $action) => str_contains($action, 'WhatsAppController@grades')));
        $this->assertFalse($actions->contains(fn (string $action) => str_contains($action, 'WhatsAppController@report')));
        $this->assertFalse(method_exists(WhatsAppController::class, 'grades'));
        $this->assertFalse(method_exists(WhatsAppController::class, 'report'));
        $this->assertFalse(method_exists(MarksController::class, 'schoolMarksOverview'));
    }

    public function test_grades_published_listener_is_not_registered(): void
    {
        $this->assertFalse(class_exists(\App\Events\GradesPublished::class));
        $this->assertFalse(class_exists(\App\Listeners\SendGradesToWhatsApp::class));
        $this->assertFalse(method_exists(OutboundWhatsAppService::class, 'notifyGradesPublished'));

        $provider = new EventServiceProvider(app());
        $listen = (new ReflectionClass($provider))->getProperty('listen');
        $listen->setAccessible(true);

        $this->assertArrayNotHasKey('App\Events\GradesPublished', $listen->getValue($provider));
    }

    public function test_live_parent_and_whatsapp_marks_paths_remain(): void
    {
        $uris = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();

        $this->assertContains('api/whatsapp/inbound', $uris);
        $this->assertTrue(Route::has('parent.children.grades'));
        $this->assertTrue(method_exists(WhatsAppController::class, 'sendGrades'));
        $this->assertTrue(method_exists(WhatsAppController::class, 'sendStudentGrades'));
        $this->assertTrue(method_exists(WhatsAppController::class, 'sendParentReportCards'));
        $this->assertTrue(method_exists(OutboundWhatsAppService::class, 'notifyComprehensiveGrades'));
    }
}
