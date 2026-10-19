<?php

namespace App\Console\Commands;

use App\Models\Academics\Exam;
use App\Models\School;
use App\Models\SchoolGoogleConnection;
use App\Models\User;
use App\Services\Google\Sheets\GoogleSheetsService;
use App\Services\Google\GoogleApiClient;
use App\Services\Google\GoogleApiException;
use App\Services\Google\GoogleConnectorCatalog;
use App\Services\Google\GoogleOAuthService;
use Illuminate\Console\Command;

/**
 * One real call per connector. Local only. Never prints tokens.
 */
class GoogleConnectorSmokeCommand extends Command
{
    protected $signature = 'google:smoke
        {connector : account, sheets, drive, classroom, calendar, gmail, docs, slides, or tasks}
        {--school= : School id}
        {--exam= : Exam id (sheets)}
        {--spreadsheet= : Spreadsheet id (sheets import)}
        {--section= : Section id (drive, classroom)}
        {--learner= : Student user id (drive)}';

    protected $description = 'Run one Google connector action for a school that has already connected it';

    public function handle(GoogleOAuthService $oauth, GoogleConnectorCatalog $catalog, GoogleApiClient $client): int
    {
        if (! $oauth->isConfigured()) {
            $this->error('Set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_CONNECTOR_REDIRECT_URI in the local environment.');

            return self::FAILURE;
        }

        $schoolId = (int) $this->option('school');
        $school = $schoolId > 0 ? School::query()->whereKey($schoolId)->first() : null;

        if ($school === null) {
            $this->error('Pass --school= with a real school id on this machine.');

            return self::FAILURE;
        }

        $connector = (string) $this->argument('connector');

        if ($connector === 'account') {
            return $this->account($school->id, $client);
        }

        if ($connector === 'sheets') {
            return $this->sheets($school->id);
        }

        if (! $catalog->has($connector)) {
            $this->error("Unknown connector [{$connector}].");

            return self::FAILURE;
        }

        $this->error("The {$catalog->get($connector)['label']} action is not registered yet. Connect it from School Settings → Integrations, then run this command again after that connector's change is installed.");

        return self::FAILURE;
    }

    private function account(int $schoolId, GoogleApiClient $client): int
    {
        $connection = SchoolGoogleConnection::query()->forSchool($schoolId)->active()->first();

        if ($connection === null) {
            $this->error('This school has not connected a Google account.');

            return self::FAILURE;
        }

        try {
            $profile = $client->get($connection, (string) config('services.google.userinfo_url'));
        } catch (GoogleApiException $e) {
            $this->error('Google account lookup failed ('.$e->status.').');

            return self::FAILURE;
        }

        $this->info('Connected Google account: '.($profile['email'] ?? '(no email)'));
        $this->line('The access token was not printed.');

        return self::SUCCESS;
    }

    private function sheets(int $schoolId): int
    {
        $examId = (int) $this->option('exam');
        $exam = Exam::query()->where('school_id', $schoolId)->whereKey($examId)->first();
        $actor = User::query()->where('school_id', $schoolId)->where('usergroup_id', 3)->where('status', 'active')->first();

        if ($exam === null || $actor === null) {
            $this->error('Pass --exam= for an exam in this school, and have an active school admin.');

            return self::FAILURE;
        }

        $sheets = app(GoogleSheetsService::class);

        try {
            $spreadsheet = (string) $this->option('spreadsheet');
            if ($spreadsheet !== '') {
                $outcome = $sheets->import($actor, $exam, $spreadsheet, false);
                $this->info($outcome['committed'] ? 'Import committed.' : 'Import refused: '.implode(' ', $outcome['plan']->blockers));

                return $outcome['committed'] ? self::SUCCESS : self::FAILURE;
            }
            $created = $sheets->export($actor, $exam);
        } catch (GoogleApiException $e) {
            $this->error('Sheets call failed ('.$e->status.').');

            return self::FAILURE;
        }

        $this->info('Spreadsheet: '.$created['spreadsheetId']);
        $this->line($created['spreadsheetUrl']);

        return self::SUCCESS;
    }
}
