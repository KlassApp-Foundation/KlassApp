<?php

namespace Tests\Feature\Auth;

use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use App\Services\EmailVerificationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Staging MAIL_MAILER=log has no mailbox; E2E reads the code from Cloud logs.
 * When the log mailer is active, issue() must emit a structured marker.
 */
class EmailVerificationCodeLogSandboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Mail::fake();
    }

    public function test_issue_logs_structured_code_when_mailer_is_log(): void
    {
        config(['mail.default' => 'log']);

        $user = User::factory()->create([
            'usergroup_id' => 3,
            'email' => 'sandbox@example.com',
            'email_verified' => 0,
            'status' => 'active',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($user) {
                return $message === 'klassapp.email_verification_code'
                    && ($context['email'] ?? null) === $user->email
                    && preg_match('/^\d{6}$/', (string) ($context['code'] ?? '')) === 1;
            });

        $code = app(EmailVerificationCodeService::class)->issue($user);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        Mail::assertQueued(EmailVerificationCodeMail::class);
    }

    public function test_issue_does_not_log_code_when_mailer_is_not_log(): void
    {
        config(['mail.default' => 'smtp']);

        $user = User::factory()->create([
            'usergroup_id' => 3,
            'email' => 'prodlike@example.com',
            'email_verified' => 0,
            'status' => 'active',
        ]);

        Log::shouldReceive('info')->never();

        app(EmailVerificationCodeService::class)->issue($user);
        Mail::assertQueued(EmailVerificationCodeMail::class);
    }
}
