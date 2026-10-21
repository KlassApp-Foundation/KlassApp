<?php

namespace Tests\Feature\Auth;

use App\Livewire\AgentToshi;
use App\Mail\SetPasswordLinkMail;
use App\Models\Plan;
use App\Models\User;
use App\Traits\ResetPasswordProcess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Invites and admin password resets email a signed set-password link.
 * A generated password is not included in the message.
 */
class SetPasswordLinkInsteadOfPasswordMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_password_reset_emails_a_signed_link_and_not_a_password(): void
    {
        $this->enableMail();
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'reset-me@klassapp.test',
            'name' => 'Reset Me',
            'password' => Hash::make('old-secret-value'),
            'usergroup_id' => 3,
        ]);

        $sender = new class
        {
            use ResetPasswordProcess;

            public function send($user): void
            {
                $this->resetPasswordToUser($user);
            }
        };
        $sender->send($user);

        Mail::assertQueued(SetPasswordLinkMail::class, function (SetPasswordLinkMail $mail) use ($user) {
            $html = $mail->render();
            $token = basename((string) parse_url($mail->url, PHP_URL_PATH));

            return $mail->hasTo($user->email)
                && str_contains($mail->url, '/password/reset/')
                && str_contains($html, 'Set your password')
                && ! str_contains($html, 'old-secret-value')
                && ! str_contains($html, 'Password:')
                && Password::broker()->tokenExists($user, $token);
        });

        $mail = Mail::queued(SetPasswordLinkMail::class)->first();
        $token = basename((string) parse_url($mail->url, PHP_URL_PATH));

        $this->post('/password/reset/change', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewSecret123!x',
            'password_confirmation' => 'NewSecret123!x',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('NewSecret123!x', $user->fresh()->password));
    }

    public function test_school_create_without_a_chosen_password_emails_a_link_and_hides_it(): void
    {
        $this->prepareSchoolCreate();
        Mail::fake();

        $component = Livewire::test(AgentToshi::class);
        $component->set('step', 14);
        $component->set('schoolName', 'Link Only School');
        $component->set('schoolType', 'primary');
        $component->set('schoolCountry', 'Uganda');
        $component->set('academicYearLabel', '2026');
        $component->set('adminName', 'Link Admin');
        $component->set('adminEmail', 'link-admin@klassapp.test');
        $component->set('adminPassword', '');
        $component->set('standards', [['name' => 'Primary One']]);
        $component->set('mode', 'create');
        $component->call('commit');

        $admin = User::where('email', 'link-admin@klassapp.test')->firstOrFail();
        $this->assertSame(1, (int) $admin->is_reset);
        $this->assertFalse(Hash::check('password', $admin->password));

        Mail::assertQueued(SetPasswordLinkMail::class, function (SetPasswordLinkMail $mail) {
            $html = $mail->render();

            return str_contains($mail->url, '/password/reset/')
                && str_contains($html, $mail->url)
                && ! str_contains($html, 'Password:');
        });

        $component->assertSee('A set-password link was emailed');
        $component->assertDontSee('Password:');
    }

    public function test_school_create_with_a_chosen_password_does_not_email_or_show_it(): void
    {
        $this->prepareSchoolCreate();
        Mail::fake();

        $component = Livewire::test(AgentToshi::class);
        $component->set('step', 14);
        $component->set('schoolName', 'Chosen Password School');
        $component->set('schoolType', 'primary');
        $component->set('schoolCountry', 'Uganda');
        $component->set('academicYearLabel', '2026');
        $component->set('adminName', 'Chosen Admin');
        $component->set('adminEmail', 'chosen-admin@klassapp.test');
        $component->set('adminPassword', 'chosen-secret-99');
        $component->set('standards', [['name' => 'Primary One']]);
        $component->set('mode', 'create');
        $component->call('commit');

        $admin = User::where('email', 'chosen-admin@klassapp.test')->firstOrFail();
        $this->assertTrue(Hash::check('chosen-secret-99', $admin->password));
        Mail::assertNotQueued(SetPasswordLinkMail::class);

        $component->assertSee('A password was set during setup');
        $component->assertDontSee('chosen-secret-99');
        $component->assertDontSee('Password:');
    }

    private function enableMail(): void
    {
        putenv('MAIL_STATUS=on');
        $_ENV['MAIL_STATUS'] = 'on';
        $_SERVER['MAIL_STATUS'] = 'on';
    }

    private function prepareSchoolCreate(): void
    {
        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Plan::create([
            'name' => 'free',
            'amount' => 0,
            'no_of_students' => 10,
            'cycle' => 365,
            'display_name' => 'Free',
            'is_active' => true,
        ]);
    }
}
