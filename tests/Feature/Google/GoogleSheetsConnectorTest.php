<?php

namespace Tests\Feature\Google;

use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\UserGoogleConnection;
use App\Services\Google\GoogleAccessRevokedException;
use App\Services\Google\GoogleApiException;
use App\Services\Google\Sheets\GoogleSheetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithFakeGoogle;
use Tests\Support\MarksImportFixture;
use Tests\TestCase;

class GoogleSheetsConnectorTest extends TestCase
{
    use InteractsWithFakeGoogle;
    use MarksImportFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFakeGoogle();
        $this->buildMarksImportFixture();
    }

    protected function tearDown(): void
    {
        $this->tearDownFakeGoogle();
        parent::tearDown();
    }

    public function test_export_and_import_round_trip_through_marks_validation(): void
    {
        $this->connectGoogle($this->admin, 'sheets');

        $created = $this->actingAs($this->admin)
            ->postJson(route('admin.google.sheets.export'), ['exam_id' => $this->exam->id])
            ->assertOk()
            ->json();

        $id = $created['spreadsheetId'];
        $sheet = $this->google->state()['sheets'][$id];
        $this->assertSame('KLS number', $sheet['tabs']['Marks'][0][0]);
        $this->assertSame('KLS0000001', $sheet['tabs']['Marks'][1][0]);
        $this->assertSame('KLS0000002', $sheet['tabs']['Marks'][2][0]);
        $this->assertSame('KLS0000003', $sheet['tabs']['Marks'][3][0]);
        $info = collect($sheet['tabs']['Exam info'])->mapWithKeys(fn ($row) => [$row[0] => $row[1]]);
        $this->assertSame($this->exam->id.'-', substr($info['Template key'], 0, strlen((string) $this->exam->id) + 1));

        $this->google->update(function (array &$state) use ($id) {
            $state['sheets'][$id]['tabs']['Exam info'][8][1] = 'not-this-exam';
        });

        $blocked = $this->actingAs($this->admin)->postJson(route('admin.google.sheets.import'), [
            'exam_id' => $this->exam->id,
            'spreadsheet_id' => $id,
        ])->assertOk()->json();

        $this->assertFalse($blocked['committed']);
        $this->assertArrayHasKey('exam_mismatch', $blocked['blockers']);
        $this->assertSame(0, Marks::query()->where('exam_id', $this->exam->id)->count());

        $this->google->update(function (array &$state) use ($id, $info) {
            $state['sheets'][$id]['tabs']['Exam info'][8][1] = $info['Template key'];
            $state['sheets'][$id]['tabs']['Marks'][1][2] = '77';
        });

        $saved = $this->actingAs($this->admin)->postJson(route('admin.google.sheets.import'), [
            'exam_id' => $this->exam->id,
            'spreadsheet_id' => $id,
        ])->assertOk()->json();

        $this->assertTrue($saved['committed']);
        $mark = Marks::query()
            ->where('school_id', $this->school->id)
            ->where('exam_id', $this->exam->id)
            ->where('section_id', $this->section->id)
            ->where('student_id', $this->students[1]->id)
            ->first();
        $this->assertNotNull($mark);
        $this->assertEquals(77, $mark->marks);

        $other = School::query()->where('name', 'Demo Other School')->firstOrFail();
        $foreign = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $other->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->exam->academic_term_id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->owner->id,
            'exam_type_id' => 2,
            'status' => 'undone',
        ]));

        $this->actingAs($this->admin)
            ->postJson(route('admin.google.sheets.export'), ['exam_id' => $foreign->id])
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->post(route('admin.google.disconnect', ['connector' => 'sheets']))
            ->assertRedirect();
        $this->assertNull(UserGoogleConnection::query()->where('user_id', $this->admin->id)->first());
        $this->assertNotNull(collect($this->google->calls())->first(fn ($call) => $call['path'] === '/revoke'));
    }

    public function test_another_user_cannot_export_with_someone_elses_connection(): void
    {
        $this->connectGoogle($this->admin, 'sheets');
        $token = UserGoogleConnection::query()->where('user_id', $this->admin->id)->first()->accessToken();
        $seen = count($this->google->calls());

        $this->actingAs($this->stranger)
            ->postJson(route('admin.google.sheets.export'), ['exam_id' => $this->exam->id])
            ->assertNotFound();

        $later = array_slice($this->google->calls(), $seen);
        $this->assertFalse(collect($later)->contains(
            fn ($call) => str_contains((string) ($call['authorization'] ?? ''), (string) $token)
        ));
        $this->assertSame($token, UserGoogleConnection::query()->where('user_id', $this->admin->id)->first()->accessToken());
    }

    public function test_expired_token_refreshes_before_export(): void
    {
        $this->connectGoogle($this->admin, 'sheets');
        $row = UserGoogleConnection::query()->where('user_id', $this->admin->id)->first();
        $row->forceFill(['token_expires_at' => now()->subMinute()])->save();

        $this->actingAs($this->admin)
            ->postJson(route('admin.google.sheets.export'), ['exam_id' => $this->exam->id])
            ->assertOk();

        $this->assertNotNull(collect($this->google->calls())->first(
            fn ($call) => $call['path'] === '/token' && ($call['body']['grant_type'] ?? '') === 'refresh_token'
        ));
        $this->assertNotEmpty($this->google->state()['sheets']);
    }

    public function test_revoked_access_clears_tokens_and_does_not_export(): void
    {
        $this->connectGoogle($this->admin, 'sheets');
        UserGoogleConnection::query()->where('user_id', $this->admin->id)
            ->update(['token_expires_at' => now()->subMinute()]);
        $this->google->update(function (array &$state) {
            $state['modes']['refresh'] = 'invalid_grant';
        });

        $this->actingAs($this->admin)
            ->postJson(route('admin.google.sheets.export'), ['exam_id' => $this->exam->id])
            ->assertStatus(400);

        $row = UserGoogleConnection::query()->where('user_id', $this->admin->id)->first();
        $this->assertSame('disabled', $row->status);
        $this->assertNull($row->credentials);
        $this->assertSame([], $this->google->state()['sheets']);
    }

    public function test_sheets_api_error_keeps_the_connection_and_saves_no_marks(): void
    {
        $this->connectGoogle($this->admin, 'sheets');
        $this->google->update(function (array &$state) {
            $state['modes']['sheets'] = '500';
        });

        try {
            app(GoogleSheetsService::class)->export($this->admin, $this->exam);
            $this->fail('A Sheets API error must surface.');
        } catch (GoogleApiException $e) {
            $this->assertSame(500, $e->status);
            $this->assertNotInstanceOf(GoogleAccessRevokedException::class, $e);
        }

        $row = UserGoogleConnection::query()->where('user_id', $this->admin->id)->first();
        $this->assertSame('active', $row->status);
        $this->assertNotNull($row->accessToken());
        $this->assertSame(0, Marks::query()->where('exam_id', $this->exam->id)->count());
    }
}
