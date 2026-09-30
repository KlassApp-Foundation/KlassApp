<?php

namespace Tests\Feature\Mail;

use App\Mail\CoAdminInviteLinkMail;
use App\Mail\CoAdminInviteMail;
use App\Mail\RegistrationOtpMail;
use App\Mail\ResetPasswordCodeMail;
use App\Mail\TeacherInviteLinkMail;
use App\Mail\TeacherInviteMail;
use App\Models\User;
use App\Support\MailContent;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Task B1 · email shell: one themed layout, link-only invites, plain-text parts.
 */
class EmailShellTest extends TestCase
{
    private function user(): User
    {
        return new User(['name' => 'Grace Nakato', 'email' => 'grace@example.com']);
    }

    public function test_markdown_theme_is_klassapp(): void
    {
        $this->assertSame('klassapp', config('mail.markdown.theme'));
        $this->assertFileExists(resource_path('views/vendor/mail/html/themes/klassapp.css'));
    }

    public function test_no_email_view_carries_a_password(): void
    {
        foreach (File::allFiles(resource_path('views/emails')) as $file) {
            $this->assertStringNotContainsString('$password', $file->getContents(), $file->getRelativePathname());
        }

        foreach (['teacher-invite', 'co-admin-invite', 'admin/resetpassword'] as $removed) {
            $this->assertFileDoesNotExist(resource_path("views/emails/{$removed}.blade.php"));
        }
    }

    public function test_mail_views_use_no_css_variables_or_svgs(): void
    {
        $files = array_merge(
            File::allFiles(resource_path('views/vendor/mail')),
            File::allFiles(resource_path('views/emails')),
        );

        foreach ($files as $file) {
            $body = $file->getContents();
            $this->assertDoesNotMatchRegularExpression('/var\(|\.svg/', $body, $file->getPathname());
        }
    }

    public function test_email_logo_png_exists_for_the_header(): void
    {
        $this->assertFileExists(public_path('images/email/klassapp-logo-email-2x.png'));
        $this->assertFileExists(public_path('images/email/klassapp-logo-email-3x.png'));
    }

    public function test_teacher_invite_mail_renders_link_only_without_a_password(): void
    {
        $mail = new TeacherInviteMail('Jane', 'jane@example.com', 'Demo School', 'P.3');

        $mail->assertSeeInHtml('Set your password');
        $mail->assertDontSeeInHtml('Password:');
        $this->assertFalse(property_exists($mail, 'password'));
        $this->assertStringContainsString('#15803D', $mail->render());
    }

    public function test_teacher_reassignment_mail_is_a_sign_in_notice_without_a_password(): void
    {
        $mail = new TeacherInviteMail('Jane', 'jane@example.com', 'Demo School', 'P.3', existingAccount: true);

        $mail->assertSeeInHtml('Sign in to KlassApp');
        $mail->assertDontSeeInHtml('Password:');
    }

    public function test_co_admin_invite_mail_is_link_only_and_promotion_has_a_sign_in_button(): void
    {
        $invite = new CoAdminInviteMail('Sam', 'sam@example.com', 'Demo School', false);
        $invite->assertSeeInHtml('Set your password');
        $invite->assertDontSeeInHtml('Password:');

        $promoted = new CoAdminInviteMail('Sam', 'sam@example.com', 'Demo School', true);
        $promoted->assertSeeInHtml('Sign in to KlassApp');
        $this->assertFalse(property_exists($promoted, 'password'));
    }

    public function test_invite_link_mails_state_the_72_hour_expiry_with_the_absolute_date(): void
    {
        $expires = Carbon::parse('2026-10-04 09:30:00');

        $teacher = new TeacherInviteLinkMail('Jane', 'Demo School', 'https://klassapp.test/invite/teacher/abc', null, $expires);
        $teacher->assertSeeInHtml('72 hours');
        $teacher->assertSeeInHtml('4 Oct 2026, 9:30 AM');
        $teacher->assertDontSeeInHtml('3 days');

        $admin = new CoAdminInviteLinkMail('Sam', 'Demo School', 'https://klassapp.test/invite/co-admin/abc', $expires);
        $admin->assertSeeInHtml('72 hours');
        $this->assertSame(72, config('invites.expiry_hours'));
    }

    public function test_code_mails_use_the_shell_with_the_handoff_subjects(): void
    {
        $otp = new RegistrationOtpMail($this->user(), 418205);
        $otp->build();
        $this->assertSame('418205 is your KlassApp code', $otp->subject);
        $otp->assertSeeInHtml('418205');
        $otp->assertSeeInHtml('It expires in 5 minutes.');

        $reset = new ResetPasswordCodeMail($this->user(), 739104);
        $reset->build();
        $this->assertSame('Your KlassApp reset code: 739104', $reset->subject);
        $reset->assertSeeInHtml('739104');
    }

    public function test_mailcontent_frames_database_bodies_and_never_leaks_markdown_code_blocks(): void
    {
        $body = "                <table><tr><td>Hello Grace</td></tr></table>\n                <a href=\"https://klassapp.test/x\" style=\"border: none; color: white; padding: 10px 15px; text-align: center; background-color: #008CBA;\">Open</a>";

        $normalised = MailContent::normalise($body);

        $this->assertDoesNotMatchRegularExpression('/^[ \t]+/m', $normalised);
        $this->assertStringNotContainsString('#008CBA', $normalised);
        $this->assertStringContainsString('class="button button-primary"', $normalised);

        $mailable = new class($body) extends Mailable {
            public function __construct(private string $body)
            {
            }

            public function build()
            {
                return $this->markdown('emails.mailcontent')
                    ->text('emails.mailcontent-text')
                    ->subject('Framing test')
                    ->with(['content' => $this->body]);
            }
        };

        $html = $mailable->render();
        $this->assertStringNotContainsString('&lt;/td&gt;', $html);
        $this->assertStringNotContainsString('<pre><code>', $html);
        $this->assertStringContainsString('Hello Grace', $html);
        $mailable->assertSeeInText('Hello Grace');
    }

    public function test_mailcontent_plain_text_part_is_authored(): void
    {
        $text = view('emails.mailcontent-text', [
            'content' => '<p>Hello <b>Grace</b></p><a href="https://klassapp.test/x">Open</a>',
        ])->render();

        $this->assertStringContainsString('Hello Grace', $text);
        $this->assertStringContainsString('Open: https://klassapp.test/x', $text);
        $this->assertStringNotContainsString('<', $text);
    }

    public function test_every_mailcontent_mailable_ships_a_plain_text_part(): void
    {
        foreach (glob(app_path('Mail/*.php')) as $file) {
            $source = file_get_contents($file);

            if (str_contains($source, "markdown('emails.mailcontent')")) {
                $this->assertStringContainsString("text('emails.mailcontent-text')", $source, basename($file));
            }
        }
    }
}
