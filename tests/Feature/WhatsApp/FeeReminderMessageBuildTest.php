<?php

namespace Tests\Feature\WhatsApp;

use App\Models\AcademicYear;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\OutboundWhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A fee reminder names the student's current class. studentAcademic is a
 * collection, so the class has to come from the current academic record.
 */
class FeeReminderMessageBuildTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+256770111001';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '106540352242922',
            'services.whatsapp.business_api_version' => 'v21.0',
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.demo_only_recipients' => false,
            'services.whatsapp.graph_api_base_url' => 'https://graph.facebook.com',
        ]);

        DB::table('usergroups')->insert([
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->relaxDeliveryLog();
        Http::preventStrayRequests();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.FEE']]], 200),
        ]);
    }

    public function test_fee_reminder_names_the_current_class_and_sends_for_a_real_school(): void
    {
        $school = School::create([
            'name' => 'Real Fee School',
            'slug' => 'real-fee-school',
            'email' => 'real-fee-school@schools.test',
            'phone' => '0701000001',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);
        $school->forceFill(['is_test' => 0, 'is_demo' => 0])->save();

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'y',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'Primary One',
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.1',
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
        FeesCategories::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'name' => 'Tuition',
            'amount' => 450000,
        ]);
        $student = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Ada Owing',
            'email' => 'ada@real-fee.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
            'klassapp_student_id' => sprintf('KLS%07d', $student->id),
        ]);
        $parent = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 7,
            'name' => 'Ada Parent',
            'email' => 'parent@real-fee.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        StudentParentLink::create([
            'school_id' => $school->id,
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'status' => 1,
        ]);
        WhatsAppUser::create([
            'phone' => self::PHONE,
            'user_id' => $parent->id,
            'school_id' => $school->id,
            'opted_in' => true,
            'last_inbound_at' => now(),
        ]);

        $sent = app(OutboundWhatsAppService::class)->notifyFeeReminder($student->id);

        $this->assertSame(1, $sent);
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $body = (string) ($request->data()['text']['body'] ?? '');

            return ($request->data()['to'] ?? null) === ltrim(self::PHONE, '+')
                && str_contains($body, 'Fee Payment Reminder')
                && str_contains($body, 'Primary One');
        });
    }

    private function relaxDeliveryLog(): void
    {
        Schema::drop('message_delivery_log');
        Schema::create('message_delivery_log', function ($table) {
            $table->id();
            $table->string('whatsapp_message_id', 100)->nullable();
            $table->string('phone', 20);
            $table->string('template_name', 100)->nullable();
            $table->string('direction')->default('outbound');
            $table->string('category')->nullable();
            $table->string('status')->default('sent');
            $table->decimal('cost_usd', 8, 6)->default(0);
            $table->text('content_preview')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('flow_type', 50)->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('error_message')->nullable();
        });
    }
}
