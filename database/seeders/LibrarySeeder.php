<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\BookIssue;
use App\Models\School;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Database\Seeder;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('slug', 'greenfield-academy')->firstOrFail();
        $sid    = $school->id;

        $booksData = [
            ['isbn' => '978-0-7432-7356-5', 'title' => 'The Great Gatsby',              'author' => 'F. Scott Fitzgerald',  'category' => 'Fiction',     'publisher' => 'Scribner',      'publication_year' => 1925, 'total_copies' => 4, 'location' => 'A-1-1'],
            ['isbn' => '978-0-06-112008-4', 'title' => 'To Kill a Mockingbird',         'author' => 'Harper Lee',            'category' => 'Fiction',     'publisher' => 'HarperCollins', 'publication_year' => 1960, 'total_copies' => 3, 'location' => 'A-1-2'],
            ['isbn' => '978-0-7432-7357-2', 'title' => 'Of Mice and Men',               'author' => 'John Steinbeck',        'category' => 'Fiction',     'publisher' => 'Penguin',       'publication_year' => 1937, 'total_copies' => 2, 'location' => 'A-1-3'],
            ['isbn' => '978-0-14-028329-7', 'title' => 'Animal Farm',                   'author' => 'George Orwell',         'category' => 'Fiction',     'publisher' => 'Penguin',       'publication_year' => 1945, 'total_copies' => 3, 'location' => 'A-2-1'],
            ['isbn' => '978-0-452-28423-4', 'title' => '1984',                          'author' => 'George Orwell',         'category' => 'Fiction',     'publisher' => 'Signet Classic', 'publication_year' => 1949, 'total_copies' => 2, 'location' => 'A-2-2'],
            ['isbn' => '978-0-7432-7358-9', 'title' => 'Introduction to Algorithms',   'author' => 'Thomas H. Cormen',      'category' => 'Computer Science', 'publisher' => 'MIT Press',  'publication_year' => 2009, 'total_copies' => 2, 'location' => 'B-1-1'],
            ['isbn' => '978-0-13-468599-1', 'title' => 'Clean Code',                   'author' => 'Robert C. Martin',      'category' => 'Computer Science', 'publisher' => 'Prentice Hall', 'publication_year' => 2008, 'total_copies' => 2, 'location' => 'B-1-2'],
            ['isbn' => '978-0-7432-7359-6', 'title' => 'A Brief History of Time',      'author' => 'Stephen Hawking',       'category' => 'Science',     'publisher' => 'Bantam Books',  'publication_year' => 1988, 'total_copies' => 3, 'location' => 'C-1-1'],
            ['isbn' => '978-0-375-70680-1', 'title' => 'The Selfish Gene',             'author' => 'Richard Dawkins',       'category' => 'Science',     'publisher' => 'Oxford UP',     'publication_year' => 1976, 'total_copies' => 2, 'location' => 'C-1-2'],
            ['isbn' => '978-0-7432-7360-2', 'title' => 'Principles of Mathematics',    'author' => 'Bertrand Russell',      'category' => 'Mathematics', 'publisher' => 'Norton',        'publication_year' => 1903, 'total_copies' => 2, 'location' => 'D-1-1'],
            ['isbn' => '978-0-7432-7361-9', 'title' => 'The Art of War',               'author' => 'Sun Tzu',               'category' => 'History',     'publisher' => 'Penguin',       'publication_year' => 1910, 'total_copies' => 3, 'location' => 'E-1-1'],
            ['isbn' => '978-0-7432-7362-6', 'title' => 'Sapiens: A Brief History',     'author' => 'Yuval Noah Harari',     'category' => 'History',     'publisher' => 'Harper',        'publication_year' => 2011, 'total_copies' => 3, 'location' => 'E-1-2'],
            ['isbn' => '978-0-7432-7363-3', 'title' => 'The Alchemist',                'author' => 'Paulo Coelho',          'category' => 'Fiction',     'publisher' => 'HarperOne',     'publication_year' => 1988, 'total_copies' => 4, 'location' => 'A-3-1'],
            ['isbn' => '978-0-7432-7364-0', 'title' => 'Think and Grow Rich',          'author' => 'Napoleon Hill',         'category' => 'Self Help',   'publisher' => 'Tarcher',       'publication_year' => 1937, 'total_copies' => 2, 'location' => 'F-1-1'],
            ['isbn' => '978-0-7432-7365-7', 'title' => 'Atomic Habits',                'author' => 'James Clear',           'category' => 'Self Help',   'publisher' => 'Avery',         'publication_year' => 2018, 'total_copies' => 3, 'location' => 'F-1-2'],
        ];

        $books = [];
        foreach ($booksData as $bd) {
            $books[] = Book::firstOrCreate(
                ['school_id' => $sid, 'isbn' => $bd['isbn']],
                array_merge($bd, ['school_id' => $sid, 'available_copies' => $bd['total_copies'], 'is_active' => true])
            );
        }

        // ── Sample Issues ─────────────────────────────────────────────
        $students = Student::where('school_id', $sid)->where('status', 'active')->take(8)->get();
        $staffList = Staff::where('school_id', $sid)->where('status', 'active')->take(3)->get();

        $issueCount = 0;
        foreach ($students->take(6) as $i => $student) {
            $book = $books[$i % count($books)];
            if ($book->available_copies < 1) continue;

            $issuedDate = now()->subDays(rand(3, 20))->toDateString();
            $dueDate    = now()->addDays(rand(-5, 10))->toDateString();
            $status     = $dueDate < now()->toDateString() ? 'overdue' : 'issued';

            BookIssue::firstOrCreate(
                ['school_id' => $sid, 'book_id' => $book->id, 'member_type' => 'App\\Models\\Student', 'member_id' => $student->id],
                [
                    'issued_date'  => $issuedDate,
                    'due_date'     => $dueDate,
                    'fine_per_day' => 2.00,
                    'status'       => $status,
                ]
            );
            $book->decrement('available_copies');
            $issueCount++;
        }

        // A few returned records
        foreach ($students->slice(6) as $i => $student) {
            $book = $books[($i + 6) % count($books)];

            $existing = BookIssue::where('school_id', $sid)->where('member_id', $student->id)->where('member_type', 'App\\Models\\Student')->exists();
            if ($existing) continue;

            $issuedDate   = now()->subDays(20)->toDateString();
            $dueDate      = now()->subDays(7)->toDateString();
            $returnedDate = now()->subDays(rand(0, 5))->toDateString();
            $overdueDays  = max(0, (int) \Carbon\Carbon::parse($dueDate)->diffInDays(\Carbon\Carbon::parse($returnedDate), false));
            $fine         = $overdueDays * 2;

            BookIssue::create([
                'school_id'     => $sid,
                'book_id'       => $book->id,
                'member_type'   => 'App\\Models\\Student',
                'member_id'     => $student->id,
                'issued_date'   => $issuedDate,
                'due_date'      => $dueDate,
                'returned_date' => $returnedDate,
                'fine'          => $fine,
                'fine_per_day'  => 2.00,
                'status'        => 'returned',
            ]);
            $issueCount++;
        }

        $this->command->info('Library seeded: ' . count($books) . ' books, ' . $issueCount . ' issue records.');
    }
}
