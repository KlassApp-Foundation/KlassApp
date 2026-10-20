<?php

namespace App\Console\Commands;

use App\Models\Academics\Exam;
use App\Models\User;
use App\Services\Google\Calendar\GoogleCalendarService;
use App\Services\Google\Classroom\GoogleClassroomSyncService;
use App\Services\Google\Drive\GoogleDriveService;
use App\Services\Google\Sheets\GoogleSheetsService;
use App\Services\Google\GoogleApiClient;
use App\Services\Google\GoogleApiException;
use App\Services\Google\GoogleConnectionService;
use App\Services\Google\GoogleConnectorCatalog;
use App\Services\Google\GoogleOAuthService;
use Illuminate\Console\Command;

/**
 * One real call as one user. Never prints tokens and never loads another user's row.
 */
class GoogleConnectorSmokeCommand extends Command
{
    protected $signature = 'google:smoke
        {connector : account, sheets, drive, classroom, calendar, or a connector key from config/google.php}
        {--user= : User id. The command acts only as this user}
        {--exam= : Exam id (sheets, drive)}
        {--spreadsheet= : Spreadsheet id (sheets import)}
        {--learner= : Student user id (drive)}';

    protected $description = 'Run one Google connector action as the named user';

    public function handle(
        GoogleOAuthService $oauth,
        GoogleConnectorCatalog $catalog,
        GoogleApiClient $client,
        GoogleConnectionService $connections,
    ): int {
        if (! $oauth->isConfigured()) {
            $this->error('Set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_CONNECTOR_REDIRECT_URI in the local environment.');

            return self::FAILURE;
        }

        $user = $this->user();

        if ($user === null) {
            return self::FAILURE;
        }

        $connector = (string) $this->argument('connector');

        if ($connector === 'account') {
            return $this->account($user, $client, $connections);
        }

        if ($connector === 'sheets') {
            return $this->sheets($user);
        }

        if ($connector === 'drive') {
            return $this->drive($user);
        }

        if ($connector === 'classroom') {
            return $this->classroom($user);
        }

        if ($connector === 'calendar') {
            return $this->calendar($user);
        }

        if (! $catalog->has($connector)) {
            $this->error("Unknown connector [{$connector}].");

            return self::FAILURE;
        }

        $this->error("The {$catalog->get($connector)['label']} action is not registered yet. That user connects it from My connected tools, then you run this command again after that connector's change is installed.");

        return self::FAILURE;
    }

    private function user(): ?User
    {
        $userId = (int) $this->option('user');
        $user = $userId > 0 ? User::query()->whereKey($userId)->first() : null;

        if ($user === null || $user->school_id === null) {
            $this->error('Pass --user= with a real user id on this machine.');

            return null;
        }

        return $user;
    }

    private function account(User $user, GoogleApiClient $client, GoogleConnectionService $connections): int
    {
        $connection = $connections->forUser($user);

        if ($connection === null || ! $connection->isActive() || $connection->accessToken() === null && $connection->refreshToken() === null) {
            $this->error('This user has not connected a Google account.');

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

    private function sheets(User $user): int
    {
        $examId = (int) $this->option('exam');
        $exam = Exam::query()->where('school_id', $user->school_id)->whereKey($examId)->first();

        if ($exam === null) {
            $this->error('Pass --exam= for an exam in this user\'s school.');

            return self::FAILURE;
        }

        $sheets = app(GoogleSheetsService::class);

        try {
            $spreadsheet = (string) $this->option('spreadsheet');
            if ($spreadsheet !== '') {
                $outcome = $sheets->import($user, $exam, $spreadsheet, false);
                $this->info($outcome['committed'] ? 'Import committed.' : 'Import refused: '.implode(' ', $outcome['plan']->blockers));

                return $outcome['committed'] ? self::SUCCESS : self::FAILURE;
            }
            $created = $sheets->export($user, $exam);
        } catch (GoogleApiException $e) {
            $this->error('Sheets call failed ('.$e->status.').');

            return self::FAILURE;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->error('This user cannot use that exam.');

            return self::FAILURE;
        }

        $this->info('Spreadsheet: '.$created['spreadsheetId']);
        $this->line($created['spreadsheetUrl']);

        return self::SUCCESS;
    }

    private function drive(User $user): int
    {
        $examId = (int) $this->option('exam');
        $learnerId = (int) $this->option('learner');
        $exam = Exam::query()->where('school_id', $user->school_id)->whereKey($examId)->first();

        if ($exam === null || $learnerId <= 0) {
            $this->error('Pass --exam= and --learner= for this user\'s school.');

            return self::FAILURE;
        }

        try {
            $filed = app(GoogleDriveService::class)->fileReportCard($user, $exam, $learnerId);
        } catch (GoogleApiException $e) {
            $this->error('Drive call failed ('.$e->status.').');

            return self::FAILURE;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->error('This user cannot file that report card.');

            return self::FAILURE;
        }

        $this->info('Filed: '.$filed['fileId']);
        $this->line($filed['folder']);
        $this->line($filed['webViewLink']);

        return self::SUCCESS;
    }

    private function classroom(User $user): int
    {
        try {
            $plan = app(GoogleClassroomSyncService::class)->sync($user);
        } catch (GoogleApiException $e) {
            $this->error('Classroom call failed ('.$e->status.').');

            return self::FAILURE;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->error('This user cannot read Classroom.');

            return self::FAILURE;
        }

        $matched = collect($plan['classes'])->sum(fn ($class) => count($class['matched']));
        $review = count($plan['needsReview']) + collect($plan['classes'])->sum(fn ($class) => count($class['needsReview']));
        $this->info('Classes matched: '.count($plan['classes']));
        $this->line('Students matched: '.$matched);
        $this->line('Needs review: '.$review);
        $this->line('No students were created. Tokens were not printed.');

        return self::SUCCESS;
    }

    private function calendar(User $user): int
    {
        try {
            $published = app(GoogleCalendarService::class)->publish($user);
        } catch (GoogleApiException $e) {
            $this->error('Calendar call failed ('.$e->status.').');

            return self::FAILURE;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->error('This user cannot publish the school calendar.');

            return self::FAILURE;
        }

        $this->info('Calendar: '.$published['calendarId']);
        $this->line('Terms: '.$published['terms']);
        $this->line('Events: '.$published['events']);
        $this->line('Tokens were not printed.');

        return self::SUCCESS;
    }
}
