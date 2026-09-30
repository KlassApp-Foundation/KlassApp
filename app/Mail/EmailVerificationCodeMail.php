<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Signup verification: the 6-digit code plus a one-tap "Confirm email" link.
 * The expiry shown is the real value passed in by EmailVerificationCodeService.
 */
class EmailVerificationCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code,
        public int $minutes = 15,
        public ?string $confirmUrl = null,
    ) {
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Your KlassApp code is '.$this->code)
            ->markdown('emails.verify-email')
            ->text('emails.verify-email-text')
            ->with([
                'name' => $this->user->name,
                'code' => $this->code,
                'spacedCode' => substr($this->code, 0, 3).' '.substr($this->code, 3),
                'minutes' => $this->minutes,
                'confirmUrl' => $this->confirmUrl,
            ]);
    }
}
