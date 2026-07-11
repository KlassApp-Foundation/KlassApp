@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title">Library — Books</h1>
            <p class="dashboard-subtitle">Manage your school's book inventory.</p>
        </div>
        <a href="{{ route('admin.library.books.create') }}" class="ds-btn ds-btn-primary ds-btn-sm">
            + Add Book
        </a>
    </div>

    @include('partials.message')

    {{-- Search --}}
    <form method="GET" class="mb-4">
        <div class="flex gap-2 items-center">
            <input type="text" name="q" placeholder="Search by title, author, or book code..."
                value="{{ request('q') }}"
                class="ds-form-input max-w-xs">
            <button type="submit" class="ds-btn ds-btn-primary ds-btn-sm">Search</button>
            @if(request('q'))
                <a href="{{ route('admin.library.books') }}" class="ds-btn ds-btn-ghost ds-btn-sm">Clear</a>
            @endif
        </div>
    </form>

    {{-- Book table --}}
    <div class="ds-card">
        <div class="ds-table-wrap">
            <table class="ds-table ds-table-striped ds-table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Code</th>
                        <th>Qty</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($books as $i => $book)
                    <tr>
                        <td class="text-muted">{{ $books->firstItem() + $i }}</td>
                        <td><strong>{{ $book->title }}</strong></td>
                        <td>{{ $book->author ?? '—' }}</td>
                        <td>{{ $book->category->category ?? '—' }}</td>
                        <td><code>{{ $book->book_code ?? '—' }}</code></td>
                        <td>{{ $book->quantity ?? '—' }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.library.books.edit', $book->id) }}"
                               class="ds-btn ds-btn-outline ds-btn-sm">Edit</a>
                            <form method="POST" action="{{ route('admin.library.books.destroy', $book->id) }}"
                                  class="inline" onsubmit="return confirm('Remove this book?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="ds-btn ds-btn-danger ds-btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-6 text-muted">
                            @if(request('q'))
                                No books match your search.
                            @else
                                No books yet. <a href="{{ route('admin.library.books.create') }}" class="underline">Add your first book</a>.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $books->links() }}
    </div>
</div>
@endsection
