<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Mail\ResetPasswordCodeMail;
use App\Mail\TeacherInviteLinkMail;
use App\Models\Authentication;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Support\MailSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

/**
 * Password reset codes are stored and emailed for imported accounts.
 * A blank MAIL_FROM_ADDRESS used to throw from the log mailer and the
 * controller hid that behind one generic sentence.
 */
class PasswordResetCodeDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('settings')->insertOrIgnore([
            ['key' => 'login_status', 'value' => '1'],
        ]);
        config(['settings.login_status' => 1]);

        $this->school = School::create([
            'name' => 'Imported Reset School',
            'email' => 'office@imported-reset.test',
            'phone' => '+256700555001',
            'slug' => 'imported-reset',
            'status' => 1,
        ]);
    }

    public function test_blank_from_address_with_the_log_mailer_stores_and_sends_a_code(): void
    {
        config([
            'mail.default' => 'log',
            'mail.from.address' => '',
            'mail.from.name' => '',
            'mail.mailers.log.channel' => null,
        ]);

        $teacher = $this->importedUser(5, 'imported.teacher@example.test', 'Imported Teacher');

        $this->post('/password/reset', ['email' => $teacher->email])
            ->assertRedirect(route('password.reset.code', ['email' => $teacher->email]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $code = Authentication::query()
            ->where('user_id', $teacher->id)
            ->where('type', 'password_reset')
            ->where('status', 0)
            ->first();

        $this->assertNotNull($code);
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $code->token);
        $this->assertSame(MailSender::DEFAULT_ADDRESS, MailSender::resolve()[0]);
    }

    public function test_reset_for_imported_teacher_parent_and_student_stores_the_code_and_sends_mail(): void
    {
        Mail::fake();

        $people = [
            $this->importedUser(5, 'roster.teacher@example.test', 'Roster Teacher'),
            $this->importedUser(7, 'parent.abc123@imported-reset.local', 'Roster Parent'),
            $this->importedUser(6, 'student.abc123@imported-reset.local', 'Roster Student'),
        ];

        foreach ($people as $user) {
            $this->post('/password/reset', ['email' => $user->email])
                ->assertRedirect(route('password.reset.code', ['email' => $user->email]))
                ->assertSessionHasNoErrors();

            $this->assertSame(
                1,
                Authentication::query()->where('user_id', $user->id)->where('type', 'password_reset')->where('status', 0)->count()
            );
        }

        Mail::assertSent(ResetPasswordCodeMail::class, 3);
    }

    public function test_imported_teacher_parent_and_student_can_set_a_password_and_log_in(): void
    {
        Mail::fake();

        $people = [
            [5, 'login.teacher@example.test', 'Login Teacher', '/teacher/dashboard'],
            [7, 'parent.login123@imported-reset.local', 'Login Parent', '/parent/dashboard'],
            [6, 'student.login123@imported-reset.local', 'Login Student', '/student/dashboard'],
        ];

        foreach ($people as [$group, $email, $name, $dashboard]) {
            $user = $this->importedUser($group, $email, $name);
            $this->assertSame(1, (int) $user->is_reset);

            $this->post('/password/reset', ['email' => $email])->assertSessionHasNoErrors();

            $code = (string) Authentication::query()
                ->where('user_id', $user->id)
                ->where('type', 'password_reset')
                ->where('status', 0)
                ->value('token');

            $verified = $this->post('/password/reset-code/verify', [
                'email' => $email,
                'code' => $code,
            ]);
            $verified->assertRedirect();
            $location = (string) $verified->headers->get('Location');
            $this->assertMatchesRegularExpression('#/password/reset/([^/?]+)#', $location);
            preg_match('#/password/reset/([^/?]+)#', $location, $match);
            $token = urldecode($match[1]);

            $this->post('/password/reset/change', [
                'token' => $token,
                'email' => $email,
                'password' => 'NewSecret123!x',
                'password_confirmation' => 'NewSecret123!x',
            ])->assertRedirect($dashboard);

            $this->assertAuthenticatedAs($user);
            $this->assertSame(0, (int) $user->fresh()->is_reset);
            $this->assertTrue(Hash::check('NewSecret123!x', (string) $user->fresh()->password));

            $this->post('/logout')->assertRedirect();
            $this->assertGuest();

            $this->post('/login', [
                'email' => $email,
                'password' => 'NewSecret123!x',
            ])->assertRedirect($dashboard);

            $this->assertAuthenticatedAs($user);
            $this->post('/logout');
            $this->assertGuest();
        }
    }

    public function test_null_school_imported_teacher_still_receives_a_reset_code(): void
    {
        config([
            'mail.default' => 'log',
            'mail.from.address' => '',
            'mail.from.name' => 'KlassApp',
        ]);

        $teacher = User::create([
            'school_id' => null,
            'usergroup_id' => 5,
            'name' => 'No School Teacher',
            'email' => 'noschool.teacher@example.test',
            'password' => Hash::make('TempReset!123'),
            'is_reset' => 1,
            'email_verified' => 1,
            'status' => 'active',
        ]);

        $this->post('/password/reset', ['email' => $teacher->email])
            ->assertRedirect(route('password.reset.code', ['email' => $teacher->email]))
            ->assertSessionHasNoErrors();

        $this->assertSame(
            1,
            Authentication::query()->where('user_id', $teacher->id)->where('type', 'password_reset')->where('status', 0)->count()
        );
    }

    public function test_a_failing_mailer_keeps_the_code_logs_the_cause_and_hides_the_code(): void
    {
        $this->useFailingTransport();

        $teacher = $this->importedUser(5, 'mailfail.teacher@example.test', 'Mail Fail Teacher');
        $logged = $this->captureLogs();

        $this->from('/password/reset')->post('/password/reset', ['email' => $teacher->email])
            ->assertRedirect('/password/reset')
            ->assertSessionHasErrors([
                'email' => 'We could not send a reset code right now. Please try again shortly.',
            ]);

        $row = Authentication::query()
            ->where('user_id', $teacher->id)
            ->where('type', 'password_reset')
            ->where('status', 0)
            ->first();
        $this->assertNotNull($row);

        $warning = $this->warningNamed($logged, 'Password reset code could not be emailed');
        $this->assertSame(TransportException::class, $warning->context['exception']);
        $this->assertStringContainsString('smtp connection refused', $warning->context['message']);
        $this->assertStringContainsString('[redacted]', $warning->context['message']);
        $this->assertStringNotContainsString((string) $row->token, $warning->context['message']);
        $this->assertStringNotContainsString((string) $row->token, json_encode($warning->context));
        $this->assertArrayNotHasKey('email', $warning->context);
    }

    public function test_an_undefined_mailer_is_reported_as_configuration_and_logged(): void
    {
        config(['mail.default' => 'not-a-mailer']);

        $teacher = $this->importedUser(5, 'nomailer.teacher@example.test', 'No Mailer Teacher');
        $logged = $this->captureLogs();

        $this->from('/password/reset')->post('/password/reset', ['email' => $teacher->email])
            ->assertSessionHasErrors([
                'email' => 'Password reset email is not set up. Please contact your school.',
            ]);

        $row = Authentication::query()->where('user_id', $teacher->id)->where('type', 'password_reset')->where('status', 0)->first();
        $this->assertNotNull($row);

        $warning = $this->warningNamed($logged, 'Password reset code could not be emailed');
        $this->assertSame(\InvalidArgumentException::class, $warning->context['exception']);
        $this->assertStringContainsString('Mailer [not-a-mailer] is not defined.', $warning->context['message']);
        $this->assertStringNotContainsString((string) $row->token, json_encode($warning->context));
    }

    public function test_a_save_failure_does_not_send_mail_and_logs_the_exception_class(): void
    {
        Mail::fake();

        Authentication::creating(function () {
            throw new \RuntimeException('authentications insert failed');
        });

        $teacher = $this->importedUser(5, 'savefail.teacher@example.test', 'Save Fail Teacher');
        $logged = $this->captureLogs();

        $this->from('/password/reset')->post('/password/reset', ['email' => $teacher->email])
            ->assertSessionHasErrors([
                'email' => 'We could not send a reset code right now. Please try again shortly.',
            ]);

        $this->assertSame(0, Authentication::query()->where('user_id', $teacher->id)->count());
        Mail::assertNothingSent();

        $warning = $this->warningNamed($logged, 'Password reset code could not be saved');
        $this->assertSame(\RuntimeException::class, $warning->context['exception']);
        $this->assertSame('authentications insert failed', $warning->context['message']);
    }

    public function test_null_password_account_gets_the_setup_link_instead_of_a_reset_code(): void
    {
        Mail::fake();

        $teacher = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Unset Teacher',
            'email' => 'unset.teacher@example.test',
            'password' => null,
            'email_verified' => 1,
            'is_reset' => 0,
            'status' => 'active',
        ]);

        $this->post('/password/reset', ['email' => $teacher->email])
            ->assertRedirect(route('password.reset.code', ['email' => $teacher->email]))
            ->assertSessionHas('status', 'If that email is registered, a reset code has been sent.');

        $this->assertSame(0, Authentication::query()->where('user_id', $teacher->id)->where('type', 'password_reset')->count());
        $this->assertNull($teacher->fresh()->password);
        Mail::assertNotSent(ResetPasswordCodeMail::class);
        Mail::assertQueued(TeacherInviteLinkMail::class);
    }

    private function importedUser(int $group, string $email, string $name): User
    {
        $user = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => $group,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('TempReset!123'),
            'is_reset' => 1,
            'email_verified' => 1,
            'status' => 'active',
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'usergroup_id' => $group,
            'firstname' => $name,
            'lastname' => '',
            'status' => 'active',
        ]);

        return $user;
    }

    private function useFailingTransport(): void
    {
        $manager = $this->app->make(MailManager::class);
        $manager->extend('failing', function () {
            return new class implements TransportInterface
            {
                public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
                {
                    $code = '000000';
                    if (preg_match('/\b(\d{6})\b/', $message->toString(), $match) === 1) {
                        $code = $match[1];
                    }

                    throw new TransportException('smtp connection refused '.$code);
                }

                public function __toString(): string
                {
                    return 'failing';
                }
            };
        });

        config([
            'mail.default' => 'failing',
            'mail.mailers.failing' => ['transport' => 'failing'],
            'mail.from.address' => MailSender::DEFAULT_ADDRESS,
            'mail.from.name' => MailSender::DEFAULT_NAME,
        ]);
    }

    private function captureLogs(): \ArrayObject
    {
        $logged = new \ArrayObject();
        Log::listen(function (MessageLogged $event) use ($logged) {
            $logged->append($event);
        });

        return $logged;
    }

    private function warningNamed(\ArrayObject $logged, string $message): MessageLogged
    {
        foreach ($logged as $event) {
            if ($event->level === 'warning' && $event->message === $message) {
                return $event;
            }
        }

        $seen = [];
        foreach ($logged as $event) {
            $seen[] = $event->level.':'.$event->message;
        }

        $this->fail('Missing warning ['.$message.']. Saw: '.json_encode($seen));
    }
}
