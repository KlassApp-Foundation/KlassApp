<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookLending;
use App\Models\LibraryCard;
use App\Models\User;
use App\Helpers\SiteHelper;

class LibraryController extends Controller
{
    /**
     * Books list with search.
     */
    public function bookIndex(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $query = Book::with('category')->where('school_id', $schoolId);

        if ($q = $request->get('q')) {
            $query->where(function ($qry) use ($q) {
                $qry->where('title', 'LIKE', "%{$q}%")
                    ->orWhere('author', 'LIKE', "%{$q}%")
                    ->orWhere('book_code', 'LIKE', "%{$q}%");
            });
        }

        $books = $query->orderBy('title')->paginate(15)->withQueryString();
        $categories = BookCategory::where('school_id', $schoolId)->orderBy('category')->get();

        return view('admin.library.books.index', compact('books', 'categories'));
    }

    /**
     * Show create book form.
     */
    public function bookCreate()
    {
        $categories = BookCategory::where('school_id', Auth::user()->school_id)
            ->orderBy('category')
            ->get();

        return view('admin.library.books.create', compact('categories'));
    }

    /**
     * Store a new book.
     */
    public function bookStore(Request $request)
    {
        $schoolId = Auth::user()->school_id;

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author'      => 'nullable|string|max:255',
            'category_id' => 'required|exists:books_category,id',
            'book_code'   => 'nullable|string|max:50',
            'isbn_number' => 'nullable|string|max:50',
            'quantity'    => 'nullable|integer|min:1',
        ]);

        $validated['school_id']        = $schoolId;
        $validated['academic_year_id'] = SiteHelper::getAcademicYear($schoolId)->id;
        $validated['cover_image']      = null;

        Book::create($validated);

        return redirect()->route('admin.library.books')
            ->with('successmessage', 'Book added successfully.');
    }

    /**
     * Show edit book form.
     */
    public function bookEdit($id)
    {
        $schoolId = Auth::user()->school_id;
        $book = Book::where('school_id', $schoolId)->findOrFail($id);
        $categories = BookCategory::where('school_id', $schoolId)
            ->orderBy('category')
            ->get();

        return view('admin.library.books.edit', compact('book', 'categories'));
    }

    /**
     * Update a book.
     */
    public function bookUpdate(Request $request, $id)
    {
        $schoolId = Auth::user()->school_id;
        $book = Book::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author'      => 'nullable|string|max:255',
            'category_id' => 'required|exists:books_category,id',
            'book_code'   => 'nullable|string|max:50',
            'isbn_number' => 'nullable|string|max:50',
            'quantity'    => 'nullable|integer|min:1',
        ]);

        $book->update($validated);

        return redirect()->route('admin.library.books')
            ->with('successmessage', 'Book updated successfully.');
    }

    /**
     * Delete a book.
     */
    public function bookDestroy($id)
    {
        $schoolId = Auth::user()->school_id;
        $book = Book::where('school_id', $schoolId)->findOrFail($id);
        $book->delete();

        return redirect()->route('admin.library.books')
            ->with('successmessage', 'Book removed successfully.');
    }

    /**
     * Book lending list (active lendings).
     */
    public function lendIndex(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $query = BookLending::whereHas('userlent', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })->with(['userlent', 'book']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        } else {
            // Default: show pending (active) lendings
            $query->where('status', 'pending');
        }

        if ($q = $request->get('q')) {
            $query->where(function ($qry) use ($q) {
                $qry->where('book_code_no', 'LIKE', "%{$q}%")
                    ->orWhere('library_card_no', 'LIKE', "%{$q}%");
            });
        }

        $lends = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.library.lends.index', compact('lends'));
    }

    /**
     * Show check-out form.
     */
    public function lendCreate()
    {
        $books = Book::where('school_id', Auth::user()->school_id)
            ->orderBy('title')
            ->get();

        return view('admin.library.lends.create', compact('books'));
    }

    /**
     * Process check-out (create lending record).
     */
    public function lendStore(Request $request)
    {
        $validated = $request->validate([
            'library_card_no' => 'required|exists:library_card,library_card_no',
            'book_code'       => 'required|exists:books,book_code',
            'issue_date'      => 'nullable|date',
            'return_date'     => 'nullable|date|after_or_equal:issue_date',
        ]);

        $libraryCard = LibraryCard::where('library_card_no', $validated['library_card_no'])->first();

        // Check if user belongs to same school
        $user = User::find($libraryCard->user_id);
        if (! $user || $user->school_id !== Auth::user()->school_id) {
            return back()->withErrors(['library_card_no' => 'Library card does not belong to this school.'])->withInput();
        }

        // Check book belongs to school
        $book = Book::where('book_code', $validated['book_code'])
            ->where('school_id', Auth::user()->school_id)
            ->first();
        if (! $book) {
            return back()->withErrors(['book_code' => 'Book not found in this school.'])->withInput();
        }

        // Check if book is already lent out
        $activeLend = BookLending::where('book_code_no', $validated['book_code'])
            ->where('status', 'pending')
            ->first();
        if ($activeLend) {
            return back()->withErrors(['book_code' => 'This book is already checked out.'])->withInput();
        }

        BookLending::create([
            'user_id'         => $libraryCard->user_id,
            'library_card_no' => $validated['library_card_no'],
            'book_code_no'    => $validated['book_code'],
            'issue_date'      => $validated['issue_date'] ?? now()->toDateString(),
            'return_date'     => $validated['return_date'] ?? now()->addDays(5)->toDateString(),
            'issued_by'       => Auth::id(),
            'status'          => 'pending',
        ]);

        return redirect()->route('admin.library.lends')
            ->with('successmessage', 'Book checked out successfully.');
    }

    /**
     * Check-in (return) a book.
     */
    public function lendReturn($id)
    {
        $lend = BookLending::findOrFail($id);

        // Verify the lending belongs to this school via the user
        $user = User::find($lend->user_id);
        if (! $user || $user->school_id !== Auth::user()->school_id) {
            abort(403, 'Unauthorized.');
        }

        $lend->update(['status' => 'returned']);

        return redirect()->route('admin.library.lends')
            ->with('successmessage', 'Book returned successfully.');
    }

    /**
     * Library card view — search by student name.
     */
    public function cardIndex(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $card = null;
        $lends = collect();
        $student = null;

        if ($userId = $request->get('user_id')) {
            $student = User::where('school_id', $schoolId)->find($userId);
            if ($student) {
                $card = LibraryCard::where('school_id', $schoolId)
                    ->where('user_id', $userId)
                    ->first();
                $lends = BookLending::where('user_id', $userId)
                    ->with('book')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        }

        $students = User::where('school_id', $schoolId)
            ->where('usergroup_id', 6) // Student role
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);

        return view('admin.library.cards.index', compact('card', 'lends', 'student', 'students'));
    }
}
