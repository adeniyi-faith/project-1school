<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LibraryController extends Controller
{
    // ── Book Catalog ──────────────────────────────────────────────

    public function index(Request $request)
    {
        $sid = $this->getSchoolId();

        $books = Book::where('school_id', $sid)
            ->when($request->search,   fn ($q) => $q->where(fn ($q2) =>
                $q2->where('title', 'like', "%{$request->search}%")
                   ->orWhere('author', 'like', "%{$request->search}%")
                   ->orWhere('isbn', 'like', "%{$request->search}%")
            ))
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->available === 'yes', fn ($q) => $q->where('available_copies', '>', 0))
            ->withCount(['issues' => fn ($q) => $q->where('status', 'issued')])
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        $categories = Book::where('school_id', $sid)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        $stats = [
            'total_books'    => Book::where('school_id', $sid)->sum('total_copies'),
            'available'      => Book::where('school_id', $sid)->sum('available_copies'),
            'issued_count'   => BookIssue::where('school_id', $sid)->where('status', 'issued')->count(),
            'overdue_count'  => BookIssue::where('school_id', $sid)->where('status', 'overdue')->count(),
        ];

        return Inertia::render('SchoolAdmin/Library/Books', [
            'books'      => $books,
            'categories' => $categories,
            'filters'    => $request->only('search', 'category', 'available'),
            'stats'      => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'isbn'             => 'nullable|string|max:20',
            'title'            => 'required|string|max:255',
            'author'           => 'required|string|max:200',
            'category'         => 'nullable|string|max:100',
            'publisher'        => 'nullable|string|max:200',
            'publication_year' => 'nullable|integer|min:1900|max:2099',
            'location'         => 'nullable|string|max:100',
            'total_copies'     => 'required|integer|min:1',
            'description'      => 'nullable|string',
        ]);

        $data['available_copies'] = $data['total_copies'];
        $data['school_id']        = $this->getSchoolId();

        Book::create($data);

        return back()->with('success', 'Book added to catalog.');
    }

    public function update(Request $request, Book $book)
    {
        $data = $request->validate([
            'isbn'             => 'nullable|string|max:20',
            'title'            => 'required|string|max:255',
            'author'           => 'required|string|max:200',
            'category'         => 'nullable|string|max:100',
            'publisher'        => 'nullable|string|max:200',
            'publication_year' => 'nullable|integer|min:1900|max:2099',
            'location'         => 'nullable|string|max:100',
            'total_copies'     => 'required|integer|min:1',
            'description'      => 'nullable|string',
            'is_active'        => 'boolean',
        ]);

        // Adjust available_copies based on total_copies change
        $diff = (int)$data['total_copies'] - $book->total_copies;
        $data['available_copies'] = max(0, $book->available_copies + $diff);

        $book->update($data);

        return back()->with('success', 'Book updated.');
    }

    public function destroy(Book $book)
    {
        $book->delete();
        return back()->with('success', 'Book removed from catalog.');
    }

    // ── Issue & Return ────────────────────────────────────────────

    public function issues(Request $request)
    {
        $sid = $this->getSchoolId();

        // Mark overdue automatically
        BookIssue::where('school_id', $sid)
            ->where('status', 'issued')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $issues = BookIssue::with(['book:id,title,author,isbn'])
            ->where('school_id', $sid)
            ->when($request->status,      fn ($q) => $q->where('status', $request->status))
            ->when($request->member_type, fn ($q) => $q->where('member_type', $request->member_type === 'student'
                ? 'App\\Models\\Student' : 'App\\Models\\Staff'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        // Load member names manually (polymorphic)
        $issues->getCollection()->each(function ($issue) {
            if ($issue->member_type === 'App\\Models\\Student') {
                $issue->member_name = Student::find($issue->member_id)?->full_name ?? 'Unknown';
                $issue->member_id_no = Student::find($issue->member_id)?->admission_no ?? '';
            } else {
                $m = Staff::find($issue->member_id);
                $issue->member_name  = $m ? ($m->first_name . ' ' . $m->last_name) : 'Unknown';
                $issue->member_id_no = $m?->emp_id ?? '';
            }
            $issue->member_type_label = str_contains($issue->member_type, 'Student') ? 'Student' : 'Staff';
        });

        return Inertia::render('SchoolAdmin/Library/Issues', [
            'issues'   => $issues,
            'books'    => Book::where('school_id', $sid)->where('is_active', true)->where('available_copies', '>', 0)
                             ->orderBy('title')->get(['id', 'title', 'author', 'available_copies', 'isbn']),
            'students' => Student::where('school_id', $sid)->where('status', 'active')
                             ->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'admission_no']),
            'staffList'=> Staff::where('school_id', $sid)->where('status', 'active')
                             ->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'emp_id']),
            'filters'  => $request->only('status', 'member_type'),
        ]);
    }

    public function issueBook(Request $request)
    {
        $data = $request->validate([
            'book_id'      => 'required|exists:books,id',
            'member_type'  => 'required|in:student,staff',
            'member_id'    => 'required|integer',
            'issued_date'  => 'required|date',
            'due_date'     => 'required|date|after:issued_date',
            'fine_per_day' => 'nullable|numeric|min:0',
            'note'         => 'nullable|string|max:500',
        ]);

        $sid  = $this->getSchoolId();
        $book = Book::findOrFail($data['book_id']);

        if ($book->available_copies < 1) {
            return back()->withErrors(['book_id' => 'No copies available.']);
        }

        $memberClass = $data['member_type'] === 'student' ? 'App\\Models\\Student' : 'App\\Models\\Staff';

        DB::transaction(function () use ($data, $sid, $book, $memberClass) {
            BookIssue::create([
                'school_id'   => $sid,
                'book_id'     => $data['book_id'],
                'member_type' => $memberClass,
                'member_id'   => $data['member_id'],
                'issued_date' => $data['issued_date'],
                'due_date'    => $data['due_date'],
                'fine_per_day'=> $data['fine_per_day'] ?? 2.00,
                'status'      => 'issued',
                'note'        => $data['note'] ?? null,
            ]);

            $book->decrement('available_copies');
        });

        return back()->with('success', 'Book issued successfully.');
    }

    public function returnBook(Request $request, BookIssue $bookIssue)
    {
        $data = $request->validate([
            'returned_date' => 'required|date',
            'note'          => 'nullable|string|max:500',
        ]);

        $returnDate = \Carbon\Carbon::parse($data['returned_date']);
        $dueDate    = $bookIssue->due_date;
        $overdueDays = max(0, $dueDate->diffInDays($returnDate, false));
        $fine = $overdueDays > 0 ? round($overdueDays * (float)$bookIssue->fine_per_day, 2) : 0;

        DB::transaction(function () use ($bookIssue, $data, $fine, $returnDate) {
            $bookIssue->update([
                'returned_date' => $returnDate->toDateString(),
                'fine'          => $fine,
                'status'        => 'returned',
                'note'          => $data['note'] ?? $bookIssue->note,
            ]);

            $bookIssue->book->increment('available_copies');
        });

        $msg = $fine > 0 ? "Book returned. Fine: ৳{$fine}" : 'Book returned successfully.';
        return back()->with('success', $msg);
    }

    public function overdue(Request $request)
    {
        $sid = $this->getSchoolId();

        BookIssue::where('school_id', $sid)
            ->where('status', 'issued')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $overdueIssues = BookIssue::with('book:id,title,author,isbn')
            ->where('school_id', $sid)
            ->where('status', 'overdue')
            ->orderBy('due_date')
            ->get();

        $overdueIssues->each(function ($issue) {
            $overdueDays = max(0, now()->startOfDay()->diffInDays($issue->due_date->startOfDay(), false));
            $issue->overdue_days        = $overdueDays;
            $issue->estimated_fine      = round($overdueDays * (float)$issue->fine_per_day, 2);

            if ($issue->member_type === 'App\\Models\\Student') {
                $m = Student::find($issue->member_id);
                $issue->member_name  = $m?->full_name ?? 'Unknown';
                $issue->member_id_no = $m?->admission_no ?? '';
                $issue->member_label = 'Student';
            } else {
                $m = Staff::find($issue->member_id);
                $issue->member_name  = $m ? ($m->first_name . ' ' . $m->last_name) : 'Unknown';
                $issue->member_id_no = $m?->emp_id ?? '';
                $issue->member_label = 'Staff';
            }
        });

        return Inertia::render('SchoolAdmin/Library/Overdue', [
            'overdue' => $overdueIssues,
            'summary' => [
                'count'          => $overdueIssues->count(),
                'total_fine_est' => $overdueIssues->sum('estimated_fine'),
            ],
        ]);
    }
}
