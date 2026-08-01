<?php

namespace App\Services\Toshi;

use App\Helpers\SiteHelper;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookLending;
use App\Models\LibraryCard;
use App\Models\Task;
use App\Models\User;
use App\Services\Library\LibraryCardLookupService;
use Illuminate\Support\Facades\Validator;

/**
 * Librarian-scoped Toshi mutations / reads (ug8).
 * Mirrors librarian panel controllers — does not reuse ug3 ToshiActionService write paths.
 *
 * Write surfaces (Tier-2 ConfirmsBeforeWrite at tool layer):
 *   manageBooks, manageBookCategories, manageLending, createTask
 * Read-only:
 *   viewLibraryCards, viewDashboard
 */
class LibrarianActionService
{
    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public static function result(bool $success, string $message, array $data = []): array
    {
        $out = [
            'success' => $success,
            'message' => $message,
        ];
        if ($data !== []) {
            $out['data'] = $data;
        }

        return $out;
    }

    public static function manageBooks(User $librarian, array $data): array
    {
        $v = Validator::make($data, [
            'title' => 'required|string|max:100',
            'author' => 'nullable|string|max:100',
            'category_id' => 'required|integer',
            'book_code' => 'required|string|max:50',
            'isbn_number' => 'nullable|string|max:50',
            'quantity' => 'nullable|integer|min:1',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $schoolId = (int) $librarian->school_id;
        $category = BookCategory::where('school_id', $schoolId)->where('id', $data['category_id'])->first();
        if (! $category) {
            return self::result(false, 'Book category not found in your school.');
        }

        if (Book::where('school_id', $schoolId)->where('book_code', $data['book_code'])->exists()) {
            return self::result(false, 'Book code already exists.');
        }

        if (Book::where('school_id', $schoolId)->where('title', $data['title'])->exists()) {
            return self::result(false, 'A book with that title already exists.');
        }

        $academicYear = SiteHelper::getAcademicYear($schoolId);
        $book = Book::create([
            'school_id' => $schoolId,
            'academic_year_id' => $academicYear?->id,
            'category_id' => $category->id,
            'title' => $data['title'],
            'author' => $data['author'] ?? null,
            'book_code' => $data['book_code'],
            'isbn_number' => $data['isbn_number'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
        ]);

        return self::result(true, "Book **{$book->title}** added (code {$book->book_code}, id {$book->id}).", [
            'id' => $book->id,
        ]);
    }

    public static function manageBookCategories(User $librarian, array $data): array
    {
        $v = Validator::make($data, [
            'category' => 'required|string|max:100',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $schoolId = (int) $librarian->school_id;
        if (BookCategory::where('school_id', $schoolId)->where('category', $data['category'])->exists()) {
            return self::result(false, 'That book category already exists.');
        }

        $row = BookCategory::create([
            'school_id' => $schoolId,
            'category' => $data['category'],
        ]);

        return self::result(true, "Book category **{$row->category}** created (id {$row->id}).", [
            'id' => $row->id,
        ]);
    }

    public static function manageLending(User $librarian, array $data): array
    {
        $v = Validator::make($data, [
            'library_card_no' => 'required',
            'book_code_no' => 'required|string|max:50',
            'issue_date' => 'nullable|date_format:Y-m-d',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $schoolId = (int) $librarian->school_id;
        $card = LibraryCard::where('school_id', $schoolId)
            ->where('library_card_no', $data['library_card_no'])
            ->first();
        if (! $card) {
            return self::result(false, 'Library card not found in your school.');
        }

        if (! $card->status) {
            return self::result(false, 'Library card is inactive.');
        }

        $book = Book::where('school_id', $schoolId)
            ->where('book_code', $data['book_code_no'])
            ->first();
        if (! $book) {
            return self::result(false, 'Book code not found in your school.');
        }

        $activeLends = BookLending::where('user_id', $card->user_id)
            ->where('status', 'pending')
            ->count();
        if ($card->book_limit && $activeLends >= (int) $card->book_limit) {
            return self::result(false, "Borrower has reached the book limit ({$card->book_limit}).");
        }

        if (BookLending::where('book_code_no', $data['book_code_no'])->where('status', 'pending')->exists()) {
            return self::result(false, 'That book copy is already checked out.');
        }

        $issueDate = $data['issue_date'] ?? now()->toDateString();
        $lend = BookLending::create([
            'user_id' => $card->user_id,
            'library_card_no' => $card->library_card_no,
            'book_code_no' => $data['book_code_no'],
            'issue_date' => $issueDate,
            'return_date' => date('Y-m-d', strtotime('+5 days', strtotime($issueDate))),
            'status' => 'pending',
            'issued_by' => $librarian->id,
        ]);

        return self::result(true, "Issued **{$book->title}** ({$book->book_code}) on card {$card->library_card_no} (lending id {$lend->id}).", [
            'id' => $lend->id,
        ]);
    }

    public static function viewLibraryCards(User $librarian, array $data): array
    {
        $v = Validator::make($data, [
            'student_id' => 'nullable|integer',
            'library_card_no' => 'nullable',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        if (empty($data['student_id']) && empty($data['library_card_no'])) {
            return self::result(false, 'Provide student_id or library_card_no.');
        }

        $schoolId = (int) $librarian->school_id;
        $lookup = LibraryCardLookupService::lookup(
            $schoolId,
            ! empty($data['student_id']) ? (int) $data['student_id'] : null,
            ! empty($data['library_card_no']) ? (string) $data['library_card_no'] : null,
        );

        if (! $lookup['student'] && ! $lookup['card']) {
            return self::result(false, 'No matching student or library card in your school.');
        }

        $student = $lookup['student'];
        $card = $lookup['card'];
        $activeLends = $lookup['lends']->where('status', 'pending')->count();

        if (! $card) {
            return self::result(true, "Student **{$student->name}** (id {$student->id}) has no library card issued.", [
                'student_id' => $student->id,
            ]);
        }

        $statusLabel = $card->status ? 'active' : 'inactive';
        $expiry = $card->expiry_date ? date('Y-m-d', strtotime((string) $card->expiry_date)) : 'n/a';
        $limit = $card->book_limit ?? 'unlimited';
        $name = $student?->name ?? 'unknown';

        $msg = "**Library card {$card->library_card_no}**\n"
            ."- Student: {$name}\n"
            ."- Status: {$statusLabel}\n"
            ."- Book limit: {$limit}\n"
            ."- Expiry: {$expiry}\n"
            ."- Active lends: {$activeLends}";

        return self::result(true, $msg, [
            'library_card_no' => $card->library_card_no,
            'status' => $statusLabel,
            'book_limit' => $card->book_limit,
            'expiry_date' => $expiry,
        ]);
    }

    public static function viewDashboard(User $librarian): array
    {
        $schoolId = (int) $librarian->school_id;
        $bookCount = Book::where('school_id', $schoolId)->count();
        $categoryCount = BookCategory::where('school_id', $schoolId)->count();
        $cardCount = LibraryCard::where('school_id', $schoolId)->count();
        $activeLends = BookLending::where('status', 'pending')
            ->whereHas('userlent', fn ($q) => $q->where('school_id', $schoolId))
            ->count();
        $pendingTasks = Task::where('school_id', $schoolId)->where('task_status', 0)->count();

        $msg = "**Librarian dashboard**\n"
            ."- Books: {$bookCount}\n"
            ."- Categories: {$categoryCount}\n"
            ."- Card holders: {$cardCount}\n"
            ."- Active lends: {$activeLends}\n"
            ."- Pending tasks: {$pendingTasks}";

        return self::result(true, $msg, [
            'books' => $bookCount,
            'categories' => $categoryCount,
            'cards' => $cardCount,
            'active_lends' => $activeLends,
        ]);
    }

    public static function createTask(User $librarian, array $data): array
    {
        $v = Validator::make($data, [
            'title' => 'required|string|min:3',
            'to_do_list' => 'nullable|string',
            'task_date' => 'required|date_format:Y-m-d',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $row = Task::create([
            'school_id' => $librarian->school_id,
            'academic_year_id' => optional(SiteHelper::getAcademicYear($librarian->school_id))->id,
            'user_id' => $librarian->id,
            'title' => $data['title'],
            'type' => 'self',
            'to_do_list' => $data['to_do_list'] ?? $data['title'],
            'task_date' => $data['task_date'],
            'reminder' => 'one_day_before_the_task',
            'task_status' => 0,
            'task_flag' => 2,
        ]);

        return self::result(true, "Task **{$row->title}** created (id {$row->id}).", ['id' => $row->id]);
    }
}
