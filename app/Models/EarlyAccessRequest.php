<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EarlyAccessRequest extends Model
{
    protected $table = 'early_access_requests';

    protected $fillable = [
        'school_name',
        'contact_name',
        'contact_role',
        'email',
        'phone',
        'country',
        'approx_students',
        'consented_at',
    ];

    protected $casts = [
        'approx_students' => 'integer',
        'consented_at' => 'datetime',
    ];

    /**
     * True when this request's email currently belongs to a KlassApp account.
     *
     * Derived from users.email (case-insensitive). No column: the account row
     * is the source of truth, and the label disappears if that account is gone.
     * Soft-deleted users are excluded; they cannot sign in.
     */
    public function isExistingUser(): bool
    {
        return self::existingAccountEmails([$this->email]) !== [];
    }

    /**
     * Lowercased emails from $emails that match a live KlassApp account.
     *
     * @param  list<string|null>  $emails
     * @return list<string>
     */
    public static function existingAccountEmails(array $emails): array
    {
        $wanted = [];
        foreach ($emails as $email) {
            $normal = mb_strtolower(trim((string) $email));
            if ($normal !== '') {
                $wanted[$normal] = true;
            }
        }

        if ($wanted === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($wanted), '?'));
        $found = User::query()
            ->whereRaw('lower(email) in ('.$placeholders.')', array_keys($wanted))
            ->pluck('email');

        $matched = [];
        foreach ($found as $email) {
            $normal = mb_strtolower(trim((string) $email));
            if (isset($wanted[$normal])) {
                $matched[$normal] = true;
            }
        }

        return array_keys($matched);
    }
}
