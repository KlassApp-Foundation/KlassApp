<?php

namespace App\Services\Library;

use App\Models\BookLending;
use App\Models\LibraryCard;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * School-scoped library card lookup shared by admin + librarian card viewers.
 * Read-only — issue/return/create/update live elsewhere (follow-up).
 */
class LibraryCardLookupService
{
    /**
     * @return array{
     *     students: Collection<int, User>,
     *     student: ?User,
     *     card: ?LibraryCard,
     *     lends: Collection<int, BookLending>
     * }
     */
    public static function lookup(int $schoolId, ?int $userId = null, ?string $cardNo = null): array
    {
        $students = User::where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);

        $student = null;
        $card = null;
        $lends = collect();

        $normalizedCardNo = $cardNo !== null && $cardNo !== '' ? trim($cardNo) : null;

        if ($normalizedCardNo !== null) {
            $card = LibraryCard::where('school_id', $schoolId)
                ->where('library_card_no', $normalizedCardNo)
                ->first();
            if ($card) {
                $student = User::where('school_id', $schoolId)->find($card->user_id);
            }
        } elseif ($userId) {
            $student = User::where('school_id', $schoolId)->find($userId);
            if ($student) {
                $card = LibraryCard::where('school_id', $schoolId)
                    ->where('user_id', $userId)
                    ->first();
            }
        }

        if ($student) {
            $lends = BookLending::where('user_id', $student->id)
                ->with('book')
                ->orderByDesc('created_at')
                ->get();
        }

        return [
            'students' => $students,
            'student' => $student,
            'card' => $card,
            'lends' => $lends,
        ];
    }
}
