<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Creates one platform admin (usergroup 1) with a generated one-time password.
 *
 * The plain password is printed once and is never logged, stored in the
 * database in plain text, or mailed. is_reset=1 forces a password change on
 * first login through the existing ForceChangePasswordController.
 *
 * Refuses when the email is already present, including soft-deleted rows,
 * because users.email is unique across the table.
 */
class CreatePlatformAdminCommand extends Command
{
    protected $signature = 'platform:create-admin
        {email : The platform admin email address}
        {name : The platform admin full name}';

    protected $description = 'Create one platform admin (usergroup 1) with a one-time password that must be changed at first login';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $name = trim((string) $this->argument('name'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid email address.');

            return self::FAILURE;
        }

        if ($name === '') {
            $this->error('Enter the platform admin name.');

            return self::FAILURE;
        }

        if (User::withTrashed()->where('email', $email)->exists()) {
            $this->error("A user with {$email} already exists. Refusing to create another platform admin.");

            return self::FAILURE;
        }

        $plain = Str::password(20);

        try {
            DB::transaction(function () use ($email, $name, $plain): void {
                $user = new User([
                    'school_id' => null,
                    'usergroup_id' => 1,
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($plain),
                    'mobile_no' => null,
                    'is_reset' => 1,
                    'email_verified' => 1,
                    'email_verified_at' => now(),
                ]);

                $attributes = ['status' => 'active'];
                if (Schema::hasColumn('users', 'is_activated')) {
                    $attributes['is_activated'] = 1;
                }
                $user->forceFill($attributes)->save();

                [$firstname, $lastname] = $this->splitName($name);

                Userprofile::create([
                    'school_id' => null,
                    'user_id' => $user->id,
                    'usergroup_id' => 1,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'profession' => 'admin',
                    'status' => 'active',
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Platform admin creation failed', [
                'email' => $email,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $this->error('Could not create the platform admin. No account was created.');

            return self::FAILURE;
        }

        $this->info('Platform admin created.');
        $this->line("Email: {$email}");
        $this->line("One-time password: {$plain}");
        $this->warn('This password is shown once. The admin must change it at first login.');

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', $name, 2) ?: [$name];

        return [$parts[0] ?: $name, $parts[1] ?? ''];
    }
}
