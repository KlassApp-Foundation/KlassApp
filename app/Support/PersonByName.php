<?php

namespace App\Support;

use App\Models\User;

/**
 * T-107: resolve a person from a name in a URL for a write (delete, deactivate, reset password,
 * status change). Until T-104 moves these routes to ids, a name is not a safe address, so a write
 * only goes ahead when the name picks out exactly one person of the expected kind in this school.
 *
 * - Always scoped by school_id.
 * - Always scoped by user group, so a teacher's name on a parent route is a 404.
 * - More than one match is refused with a clear message instead of acting on the first.
 */
final class PersonByName
{
    public const AMBIGUOUS_MESSAGE = 'More than one person in this school has that name, so nothing was changed. Open the person from their list and try again, or ask the school to make the names different.';

    /**
     * @param  list<int>  $groupIds  user groups this route is allowed to act on
     */
    public static function forWrite(string $name, int $schoolId, array $groupIds, bool $allowIdFallback = false): User
    {
        $matches = User::query()
            ->where('school_id', $schoolId)
            ->whereIn('usergroup_id', $groupIds)
            ->where('name', $name)
            ->limit(2)
            ->get();

        abort_if($matches->count() > 1, 409, self::AMBIGUOUS_MESSAGE);

        if ($matches->isEmpty() && $allowIdFallback && ctype_digit($name)) {
            $matches = User::query()
                ->where('school_id', $schoolId)
                ->whereIn('usergroup_id', $groupIds)
                ->whereKey((int) $name)
                ->get();
        }

        abort_if($matches->isEmpty(), 404);

        return $matches->first();
    }
}
