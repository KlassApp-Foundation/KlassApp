<?php

namespace Tests\Feature\Google;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Events;
use App\Models\School;
use App\Models\SchoolGoogleConnection;
use App\Models\User;
use App\Services\Google\Calendar\GoogleCalendarService;
use App\Support\DashboardGreeting;
use App\Services\Google\GoogleAccessRevokedException;
use App\Services\Google\GoogleApiException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithFakeGoogle;
use Tests\TestCase;

class GoogleCalendarConnectorTest extends TestCase
{
    use InteractsWithFakeGoogle;
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    private User $admin;

    private User $otherAdmin;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Events $visit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFakeGoogle();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Calendar School',
            'email' => 'calendar-school@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'calendar-school-'.uniqid(),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $old = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 0,
        ]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term 1',
            'starts_on' => '2026-01-06',
            'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);

        AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term undated',
            'status' => 'next',
        ]);

        AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $old->id,
            'name' => 'Old Term',
            'starts_on' => '2025-01-06',
            'ends_on' => '2025-04-30',
            'status' => 'last',
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'name' => 'Calendar Admin',
            'email' => 'admin.calendar@t.sch.ug',
            'status' => 'active',
        ]);

        $this->visit = $this->event('Visiting day', 'active', '2026-06-12 00:00:00');
        $this->event('Cancelled match', 'inactive', '2026-07-01 00:00:00');

        $this->otherSchool = School::create([
            'name' => 'Calendar Other School',
            'email' => 'calendar-other@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'calendar-other-'.uniqid(),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
        $this->otherAdmin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->otherSchool->id,
            'name' => 'Calendar Other Admin',
            'email' => 'admin.calendar.other@t.sch.ug',
            'status' => 'active',
        ]);
        $otherYear = AcademicYear::create([
            'school_id' => $this->otherSchool->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        AcademicTerm::create([
            'school_id' => $this->otherSchool->id,
            'academic_year_id' => $otherYear->id,
            'name' => 'Other Term',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-05-01',
            'status' => 'current',
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownFakeGoogle();
        parent::tearDown();
    }

    public function test_publishes_term_dates_and_active_events_then_disconnects(): void
    {
        $before = User::query()->count();
        $this->actingAs($this->admin)->get('/admin/settings/integrations')
            ->assertOk()
            ->assertSee('data-testid="google-connector-status-calendar"', false)
            ->assertDontSee('data-testid="google-connector-live-calendar"', false);

        $this->connectGoogle($this->admin, 'calendar');

        $this->actingAs($this->admin)->get('/admin/settings/integrations')
            ->assertOk()
            ->assertSee('data-testid="google-connector-live-calendar"', false)
            ->assertDontSee('data-testid="google-connector-live-sheets"', false);
        $this->actingAs($this->otherAdmin)->get('/admin/settings/integrations')
            ->assertOk()
            ->assertDontSee('data-testid="google-connector-live-calendar"', false);

        $scopes = SchoolGoogleConnection::query()->where('school_id', $this->school->id)->first()->granted_scopes;
        $this->assertContains('https://www.googleapis.com/auth/calendar.app.created', $scopes);
        $this->assertNotContains('https://www.googleapis.com/auth/calendar', $scopes);
        $this->assertNotContains('https://www.googleapis.com/auth/calendar.events', $scopes);

        $published = $this->actingAs($this->admin)
            ->postJson(route('admin.google.calendar.publish'))
            ->assertOk()
            ->json();

        $this->assertSame(1, $published['terms']);
        $this->assertSame(1, $published['events']);
        $this->assertSame($before, User::query()->count());

        $calendar = $this->onlyCalendar();
        $this->assertSame('KlassApp', $calendar['summary']);
        $this->assertSame(DashboardGreeting::timezoneFor($this->admin->fresh()), $calendar['timeZone']);
        $this->assertSame($published['calendarId'], $calendar['id']);

        $bySummary = collect($calendar['events'])->keyBy('summary');
        $this->assertSame('2026-01-06', $bySummary['Term 1']['start']['date']);
        $this->assertSame('2026-05-01', $bySummary['Term 1']['end']['date']);
        $this->assertSame('2026-06-12', $bySummary['Visiting day']['start']['date']);
        $this->assertSame('2026-06-13', $bySummary['Visiting day']['end']['date']);
        $this->assertArrayNotHasKey('Old Term', $bySummary->all());
        $this->assertArrayNotHasKey('Other Term', $bySummary->all());
        $this->assertArrayNotHasKey('Cancelled match', $bySummary->all());
        $this->assertArrayNotHasKey('Term undated', $bySummary->all());

        $this->term->forceFill(['name' => 'Term One'])->save();
        $this->visit->forceFill(['status' => 'inactive'])->save();

        $this->actingAs($this->admin)
            ->postJson(route('admin.google.calendar.publish'))
            ->assertOk();

        $again = collect($this->onlyCalendar()['events'])->keyBy('id');
        $this->assertCount(1, $again);
        $this->assertSame('Term One', $again['kappterm'.$this->term->id]['summary']);
        $this->assertArrayNotHasKey('kappevent'.$this->visit->id, $again->all());

        $this->actingAs($this->admin)
            ->post(route('admin.google.disconnect', ['connector' => 'calendar']))
            ->assertRedirect();
        $row = SchoolGoogleConnection::query()->where('school_id', $this->school->id)->first();
        $this->assertNull($row->credentials);
        $this->assertSame($calendar['id'], $row->google_calendar_id);
        $this->assertNotNull(collect($this->google->calls())->first(fn ($call) => $call['path'] === '/revoke'));
        $this->actingAs($this->admin)->get('/admin/settings/integrations')
            ->assertOk()
            ->assertDontSee('data-testid="google-connector-live-calendar"', false)
            ->assertSee('data-testid="google-connector-status-calendar"', false);
    }

    public function test_created_calendar_uses_the_school_country_timezone(): void
    {
        config(['app.timezone' => 'Africa/Kampala']);

        $countryId = DB::table('countries')->insertGetId([
            'name' => 'Japan',
            'short_name' => 'JP',
            'iso_code' => 'JP',
            'tel_prefix' => '+81',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->school->forceFill(['country_id' => $countryId])->save();

        $this->connectGoogle($this->admin, 'calendar');

        $this->actingAs($this->admin->fresh())
            ->postJson(route('admin.google.calendar.publish'))
            ->assertOk();

        $this->assertSame('Asia/Tokyo', $this->onlyCalendar()['timeZone']);
    }

    public function test_created_calendar_falls_back_to_the_app_timezone(): void
    {
        config(['app.timezone' => 'Pacific/Auckland']);
        $this->assertNull($this->school->country_id);

        $this->connectGoogle($this->admin, 'calendar');

        $this->actingAs($this->admin)
            ->postJson(route('admin.google.calendar.publish'))
            ->assertOk();

        $this->assertSame('Pacific/Auckland', $this->onlyCalendar()['timeZone']);
    }

    public function test_expired_token_refreshes_before_publish(): void
    {
        $this->connectGoogle($this->admin, 'calendar');
        SchoolGoogleConnection::query()->where('school_id', $this->school->id)
            ->update(['token_expires_at' => now()->subMinute()]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.google.calendar.publish'))
            ->assertOk();

        $this->assertNotNull(collect($this->google->calls())->first(
            fn ($call) => $call['path'] === '/token' && ($call['body']['grant_type'] ?? '') === 'refresh_token'
        ));
        $this->assertNotEmpty($this->google->state()['calendar']['calendars']);
    }

    public function test_revoked_access_clears_tokens_and_creates_no_calendar(): void
    {
        $this->connectGoogle($this->admin, 'calendar');
        SchoolGoogleConnection::query()->where('school_id', $this->school->id)
            ->update(['token_expires_at' => now()->subMinute()]);
        $this->google->update(function (array &$state) {
            $state['modes']['refresh'] = 'invalid_grant';
        });

        $this->actingAs($this->admin)
            ->postJson(route('admin.google.calendar.publish'))
            ->assertStatus(400);

        $row = SchoolGoogleConnection::query()->where('school_id', $this->school->id)->first();
        $this->assertSame('disabled', $row->status);
        $this->assertNull($row->credentials);
        $this->assertNull($row->google_calendar_id);
        $this->assertSame([], $this->google->state()['calendar']['calendars'] ?? []);
    }

    public function test_calendar_api_error_keeps_the_connection(): void
    {
        $this->connectGoogle($this->admin, 'calendar');
        $this->google->update(function (array &$state) {
            $state['modes']['calendar'] = '500';
        });

        try {
            app(GoogleCalendarService::class)->publish($this->admin);
            $this->fail('A Calendar API error must surface.');
        } catch (GoogleApiException $e) {
            $this->assertSame(500, $e->status);
            $this->assertNotInstanceOf(GoogleAccessRevokedException::class, $e);
        }

        $row = SchoolGoogleConnection::query()->where('school_id', $this->school->id)->first();
        $this->assertSame('active', $row->status);
        $this->assertNotNull($row->accessToken());
        $this->assertNull($row->google_calendar_id);
    }

    private function event(string $title, string $status, string $start): Events
    {
        return Events::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'batch' => 'school',
            'select_type' => 'school',
            'title' => $title,
            'category' => 'education',
            'color' => '#2563EB',
            'start_date' => $start,
            'end_date' => $start,
            'allDay' => 1,
            'status' => $status,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function onlyCalendar(): array
    {
        $calendars = $this->google->state()['calendar']['calendars'] ?? [];
        $this->assertCount(1, $calendars);

        return array_values($calendars)[0];
    }
}
