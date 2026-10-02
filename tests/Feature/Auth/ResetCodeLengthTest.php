<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Mail\ResetPasswordCodeMail;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The password reset code is generated as exactly 6 digits; every current page
 * and the email must state that number. If either side drifts, this fails.
 */
class ResetCodeLengthTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private string $email = 'reset-code-length@test.sch.ug';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutVite();

        $this->school = School::create([
            'name' => 'Reset Code Length School',
            'slug' => 'reset-code-length-school',
            'email' => 'school@resetcodelength.test',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'status' => 'active',
        ]);

        User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Reset Code Teacher',
            'email' => $this->email,
            'password' => Hash::make('OldPassword!123'),
            'is_reset' => 0,
            'status' => 'active',
        ]);
    }

    public function test_generated_code_is_six_digits_and_the_email_states_it(): void
    {
        Mail::fake();

        $this->post('/password/reset', ['email' => $this->email])
            ->assertRedirect(route('password.reset.code', ['email' => $this->email]));

        Mail::assertSent(ResetPasswordCodeMail::class, 1);

        $captured = null;
        Mail::assertSent(ResetPasswordCodeMail::class, function (ResetPasswordCodeMail $mail) use (&$captured) {
            $captured = $mail;

            return true;
        });

        $this->assertNotNull($captured);
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $captured->code, 'The generated reset code must be 6 digits.');

        $html = $captured->render();
        $this->assertStringContainsString('6-digit', $html, 'The reset email must state the 6-digit code length.');
    }

    public function test_reset_pages_state_the_code_length(): void
    {
        $this->get('/password/reset')
            ->assertOk()
            ->assertSee('6-digit');

        $this->get('/password/reset-code?email='.$this->email)
            ->assertOk()
            ->assertSee('6-digit');
    }
}
