<?php

namespace Tests\Feature\Public;

use App\Models\School;
use App\Models\SchoolDetail;
use App\Services\WhatsAppReportCardDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Public error handling: the admission form and the report-file links must
 * degrade gracefully (no raw exceptions, no blank pages) and keep friendly
 * states for invalid or missing data.
 */
class PublicErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'abcdefghijklmnopqrstuvwxyz0123456789ABCD';

    private School $school;

    private string $slug;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->school = School::create([
            'name' => 'Public Error Handling School',
            'slug' => 'public-error-handling',
            'email' => 'school@publichandling.test',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'status' => 'active',
        ]);

        // SchoolObserver rewrites the slug from the name and seeds school_details
        // rows with a "-" sentinel; use the stored slug for requests.
        $this->slug = ''.$this->school->refresh()->slug;
    }

    public function test_unknown_school_slug_returns_404(): void
    {
        $this->get('/no-such-school-xyz/admission-form')->assertNotFound();
    }

    public function test_missing_admission_open_meta_shows_closed_state(): void
    {
        SchoolDetail::where('school_id', $this->school->id)->where('meta_key', 'admission_open')->delete();

        $this->get('/'.$this->slug.'/admission-form')
            ->assertOk()
            ->assertSee('Admissions are currently closed.')
            ->assertSee('data-testid="admission-closed"', false);
    }

    public function test_sentinel_admission_open_shows_closed_state(): void
    {
        // The observer seeds meta rows with "-": that sentinel must also read as closed.
        $this->get('/'.$this->slug.'/admission-form')
            ->assertOk()
            ->assertSee('Admissions are currently closed.')
            ->assertDontSee('Admissions reopen next term.');
    }

    public function test_admission_open_zero_shows_school_close_message(): void
    {
        SchoolDetail::updateOrCreate(
            ['school_id' => $this->school->id, 'meta_key' => 'admission_open'],
            ['meta_value' => '0']
        );
        SchoolDetail::updateOrCreate(
            ['school_id' => $this->school->id, 'meta_key' => 'admission_close_message'],
            ['meta_value' => 'Admissions reopen next term.']
        );

        $this->get('/'.$this->slug.'/admission-form')
            ->assertOk()
            ->assertSee('Admissions reopen next term.');

        $this->assertDatabaseHas('school_details', ['school_id' => $this->school->id]);
    }

    public function test_admission_open_one_shows_the_form(): void
    {
        SchoolDetail::updateOrCreate(
            ['school_id' => $this->school->id, 'meta_key' => 'admission_open'],
            ['meta_value' => '1']
        );

        $this->get('/'.$this->slug.'/admission-form')
            ->assertOk()
            ->assertSee('Admission Form')
            ->assertSee('<add-admission', false);
    }

    public function test_data_failure_shows_friendly_unavailable_page(): void
    {
        Schema::drop('school_details');

        $this->get('/'.$this->slug.'/admission-form')
            ->assertStatus(503)
            ->assertSee('We could not load the admission form right now.')
            ->assertSee('data-testid="admission-unavailable"', false);
    }

    public function test_report_file_link_without_signature_shows_expired_page(): void
    {
        $this->get('/whatsapp/report-files/'.self::TOKEN)
            ->assertStatus(410)
            ->assertSee('This link has expired. Please ask the school to resend it.')
            ->assertDontSee('Invalid signature');
    }

    public function test_report_file_link_with_tampered_token_shows_expired_page(): void
    {
        $url = URL::temporarySignedRoute('whatsapp.report-file', now()->addMinutes(5), ['token' => self::TOKEN]);

        $this->get(str_replace(self::TOKEN, str_repeat('x', 40), $url))
            ->assertStatus(410)
            ->assertSee('This link has expired')
            ->assertDontSee('Invalid signature');
    }

    public function test_report_file_link_with_valid_signature_but_missing_file_shows_expired_page(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $url = URL::temporarySignedRoute('whatsapp.report-file', now()->addMinutes(5), ['token' => self::TOKEN]);

        $this->get($url)
            ->assertStatus(410)
            ->assertSee('This link has expired')
            ->assertDontSee('Invalid signature');
    }

    public function test_report_file_link_with_valid_signature_and_file_still_streams_the_pdf(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $relative = WhatsAppReportCardDeliveryService::STORAGE_DIR.'/'.self::TOKEN.'.pdf';
        Storage::disk('local')->put($relative, '%PDF-1.4 test');

        $url = URL::temporarySignedRoute('whatsapp.report-file', now()->addMinutes(5), ['token' => self::TOKEN]);

        $this->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
