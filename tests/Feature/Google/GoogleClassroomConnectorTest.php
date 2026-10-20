<?php

namespace Tests\Feature\Google;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\UserGoogleConnection;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\Google\Classroom\GoogleClassroomSyncService;
use App\Services\Google\GoogleAccessRevokedException;
use App\Services\Google\GoogleApiException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithFakeGoogle;
use Tests\TestCase;

class GoogleClassroomConnectorTest extends TestCase
{
    use InteractsWithFakeGoogle;
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $ada;

    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFakeGoogle();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Classroom Sync School',
            'email' => 'classroom-sync@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'classroom-sync-'.uniqid(),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => '1',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.4',
            'status' => 1,
        ]);
        $old = Section::create(['school_id' => $this->school->id, 'name' => 'Old Class', 'status' => 1]);

        $link = $this->link($year, $standard, $this->section);
        $this->link($year, $standard, $old);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'name' => 'Classroom Admin',
            'email' => 'admin.classroom@t.sch.ug',
            'status' => 'active',
        ]);

        $this->ada = $this->student('Ada Lovelace', 'ada.classroom@t.sch.ug', 'KLS0000101', $year, $link);
        $graceLink = $link;
        $this->student('Grace Hopper', 'grace1.classroom@t.sch.ug', 'KLS0000102', $year, $graceLink);
        $this->student('Grace Hopper', 'grace2.classroom@t.sch.ug', 'KLS0000103', $year, $graceLink);
        $inactive = $this->student('Inactive Ian', 'ian.classroom@t.sch.ug', 'KLS0000104', $year, $link);
        $inactive->forceFill(['status' => 'inactive'])->save();

        $other = School::create([
            'name' => 'Classroom Other School',
            'email' => 'classroom-other@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'classroom-other-'.uniqid(),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
        Section::create(['school_id' => $other->id, 'name' => 'P.4', 'status' => 1]);

        $this->google->update(function (array &$state) {
            $state['classroom']['courses'] = [
                ['id' => 'c-p4', 'name' => 'P.4', 'courseState' => 'ACTIVE'],
                ['id' => 'c-missing', 'name' => 'No Such Class', 'courseState' => 'ACTIVE'],
                ['id' => 'c-old', 'name' => 'Old Class', 'courseState' => 'ARCHIVED'],
            ];
            $state['classroom']['students'] = [
                'c-p4' => [
                    ['userId' => 'g-ada', 'profile' => ['emailAddress' => 'someone-else@example.test', 'name' => ['fullName' => 'Ada Lovelace']]],
                    ['userId' => 'g-grace', 'profile' => ['name' => ['fullName' => 'Grace Hopper']]],
                    ['userId' => 'g-alan', 'profile' => ['name' => ['fullName' => 'Alan Turing']]],
                    ['userId' => 'g-ian', 'profile' => ['name' => ['fullName' => 'Inactive Ian']]],
                ],
            ];
        });
    }

    protected function tearDown(): void
    {
        $this->tearDownFakeGoogle();
        parent::tearDown();
    }

    public function test_sync_matches_unique_names_and_creates_nobody(): void
    {
        $before = User::query()->where('school_id', $this->school->id)->count();
        $this->connectGoogle($this->admin, 'classroom');

        $scopes = UserGoogleConnection::query()->where('user_id', $this->admin->id)->first()->granted_scopes;
        $this->assertContains('https://www.googleapis.com/auth/classroom.courses.readonly', $scopes);
        $this->assertContains('https://www.googleapis.com/auth/classroom.rosters.readonly', $scopes);
        $this->assertContains('https://www.googleapis.com/auth/classroom.coursework.students.readonly', $scopes);
        $this->assertNotContains('https://www.googleapis.com/auth/classroom.guardianlinks.students.readonly', $scopes);
        $this->assertNotContains('https://www.googleapis.com/auth/classroom.profile.emails', $scopes);

        $plan = $this->actingAs($this->admin)
            ->postJson(route('admin.google.classroom.sync'))
            ->assertOk()
            ->json();

        $this->assertSame($before, User::query()->where('school_id', $this->school->id)->count());
        $this->assertCount(1, $plan['classes']);
        $this->assertSame($this->section->id, $plan['classes'][0]['sectionId']);
        $this->assertSame([
            ['googleUserId' => 'g-ada', 'userId' => $this->ada->id, 'name' => 'Ada Lovelace'],
        ], $plan['classes'][0]['matched']);
        $reasons = collect($plan['classes'][0]['needsReview'])->keyBy('name');
        $this->assertSame('ambiguous_student', $reasons['Grace Hopper']['reason']);
        $this->assertSame('no_student', $reasons['Alan Turing']['reason']);
        $this->assertSame('no_student', $reasons['Inactive Ian']['reason']);
        $this->assertArrayNotHasKey('email', $plan['classes'][0]['matched'][0]);

        $courseReasons = collect($plan['needsReview'])->keyBy('courseId');
        $this->assertSame('no_section', $courseReasons['c-missing']['reason']);
        $this->assertArrayNotHasKey('c-old', $courseReasons->all());

        $paths = collect($this->google->calls())->pluck('path');
        $this->assertTrue($paths->contains('/v1/courses'));
        $this->assertTrue($paths->contains('/v1/courses/c-p4/students'));
        $this->assertFalse($paths->contains('/v1/courses/c-missing/students'));

        $this->artisan('google:smoke', ['connector' => 'classroom', '--user' => $this->admin->id])
            ->assertSuccessful();

        $this->actingAs($this->admin)
            ->post(route('admin.google.disconnect', ['connector' => 'classroom']))
            ->assertRedirect();
        $this->assertNull(UserGoogleConnection::query()->where('user_id', $this->admin->id)->first());
        $this->assertNotNull(collect($this->google->calls())->first(fn ($call) => $call['path'] === '/revoke'));
    }

    public function test_another_user_cannot_sync_with_this_users_token(): void
    {
        $this->connectGoogle($this->admin, 'classroom');
        $token = UserGoogleConnection::query()->where('user_id', $this->admin->id)->first()->accessToken();
        $colleague = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'status' => 'active',
        ]);
        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'status' => 'active',
        ]);
        $platform = User::factory()->create([
            'usergroup_id' => 1,
            'school_id' => $this->school->id,
            'status' => 'active',
        ]);
        $seen = count($this->google->calls());

        $this->actingAs($colleague)->postJson(route('admin.google.classroom.sync'))->assertNotFound();
        $this->actingAs($teacher)->postJson(route('admin.google.classroom.sync'))->assertNotFound();
        $this->actingAs($platform)->postJson(route('admin.google.classroom.sync'))->assertNotFound();
        $this->artisan('google:smoke', ['connector' => 'classroom', '--user' => $colleague->id])->assertFailed();
        $this->artisan('google:smoke', ['connector' => 'classroom', '--user' => $teacher->id])->assertFailed();
        $this->artisan('google:smoke', ['connector' => 'classroom'])->assertFailed();

        $later = array_slice($this->google->calls(), $seen);
        $this->assertFalse(collect($later)->contains(
            fn ($call) => str_contains((string) ($call['authorization'] ?? ''), (string) $token)
        ));
        $this->assertSame($token, UserGoogleConnection::query()->where('user_id', $this->admin->id)->first()->accessToken());
        $this->assertNull(UserGoogleConnection::query()->where('user_id', $colleague->id)->first());
    }

    public function test_expired_token_refreshes_before_sync(): void
    {
        $this->connectGoogle($this->admin, 'classroom');
        UserGoogleConnection::query()->where('user_id', $this->admin->id)
            ->update(['token_expires_at' => now()->subMinute()]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.google.classroom.sync'))
            ->assertOk();

        $this->assertNotNull(collect($this->google->calls())->first(
            fn ($call) => $call['path'] === '/token' && ($call['body']['grant_type'] ?? '') === 'refresh_token'
        ));
        $this->assertTrue(collect($this->google->calls())->pluck('path')->contains('/v1/courses'));
    }

    public function test_revoked_access_clears_tokens_and_creates_nobody(): void
    {
        $this->connectGoogle($this->admin, 'classroom');
        $before = User::query()->count();
        UserGoogleConnection::query()->where('user_id', $this->admin->id)
            ->update(['token_expires_at' => now()->subMinute()]);
        $this->google->update(function (array &$state) {
            $state['modes']['refresh'] = 'invalid_grant';
        });

        $this->actingAs($this->admin)
            ->postJson(route('admin.google.classroom.sync'))
            ->assertStatus(400);

        $row = UserGoogleConnection::query()->where('user_id', $this->admin->id)->first();
        $this->assertSame('disabled', $row->status);
        $this->assertNull($row->credentials);
        $this->assertSame($before, User::query()->count());
        $this->assertFalse(collect($this->google->calls())->pluck('path')->contains('/v1/courses'));
    }

    public function test_classroom_api_error_keeps_the_connection(): void
    {
        $this->connectGoogle($this->admin, 'classroom');
        $this->google->update(function (array &$state) {
            $state['modes']['classroom'] = '500';
        });

        try {
            app(GoogleClassroomSyncService::class)->sync($this->admin);
            $this->fail('A Classroom API error must surface.');
        } catch (GoogleApiException $e) {
            $this->assertSame(500, $e->status);
            $this->assertNotInstanceOf(GoogleAccessRevokedException::class, $e);
        }

        $row = UserGoogleConnection::query()->where('user_id', $this->admin->id)->first();
        $this->assertSame('active', $row->status);
        $this->assertNotNull($row->accessToken());
    }

    private function link(AcademicYear $year, Standard $standard, Section $section): StandardLink
    {
        return StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => '1',
        ]);
    }

    private function student(string $name, string $email, string $kls, AcademicYear $year, StandardLink $link): User
    {
        $student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => $name,
            'email' => $email,
            'registration_number' => $kls,
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);

        return $student;
    }
}
