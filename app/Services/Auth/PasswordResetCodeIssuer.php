<?php

namespace App\Services\Auth;

use App\Mail\ResetPasswordCodeMail;
use App\Models\Authentication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Throwable;

/**
 * Stores a 6-digit reset code, then emails it.
 *
 * Saving and sending are separate: a mail failure leaves the code in place
 * and is logged on its own. The code expires in five minutes, so the message
 * is sent in this request. A queued job that runs late would deliver a code
 * that no longer works. Queue dispatch is not used.
 */
class PasswordResetCodeIssuer
{
    public function issue(User $user, ?string $ip): void
    {
        $code = random_int(100000, 999999);

        try {
            DB::transaction(function () use ($user, $ip, $code) {
                Authentication::query()
                    ->where('user_id', $user->id)
                    ->where('type', 'password_reset')
                    ->where('status', 0)
                    ->update(['status' => 2]);

                Authentication::create([
                    'user_id' => $user->id,
                    'type' => 'password_reset',
                    'token' => (string) $code,
                    'ip_address' => $ip !== null && $ip !== '' ? $ip : '0.0.0.0',
                    'expires_on' => Carbon::now()->addMinutes(5),
                    'status' => 0,
                ]);
            });
        } catch (Throwable $e) {
            $this->report('Password reset code could not be saved', $e, $code, $user);
            throw new PasswordResetDeliveryException(
                PasswordResetDeliveryException::TEMPORARY,
                'Password reset code could not be saved',
                $e,
            );
        }

        try {
            $this->deliver($user, $code);
        } catch (Throwable $e) {
            $this->report('Password reset code could not be emailed', $e, $code, $user);
            throw new PasswordResetDeliveryException(
                $this->isConfigurationError($e)
                    ? PasswordResetDeliveryException::CONFIGURATION
                    : PasswordResetDeliveryException::TEMPORARY,
                'Password reset code could not be emailed',
                $e,
            );
        }
    }

    private function deliver(User $user, int $code): void
    {
        // Sent in the request. The code is only valid for five minutes.
        Mail::to($user->email)->send(new ResetPasswordCodeMail($user, $code));
    }

    private function isConfigurationError(Throwable $e): bool
    {
        $message = $e->getMessage();

        if ($e instanceof \LogicException
            && (str_contains($message, 'From') || str_contains($message, 'Sender'))) {
            return true;
        }

        if ($e instanceof RfcComplianceException) {
            return true;
        }

        if ($e instanceof InvalidArgumentException
            && (str_contains($message, 'Mailer [') || str_contains($message, 'is not defined'))) {
            return true;
        }

        return false;
    }

    private function report(string $event, Throwable $e, int $code, User $user): void
    {
        $message = str_replace((string) $code, '[redacted]', $e->getMessage());

        Log::warning($event, [
            'user_id' => $user->id,
            'exception' => $e::class,
            'message' => $message,
        ]);
    }
}
