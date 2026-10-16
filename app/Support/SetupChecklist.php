<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSite;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;

/**
 * The "get your school ready" list shown on the dashboard of a school administrator. Each line is worked
 * out from what the school really has, so it ticks itself as the school fills in. Nothing is stored.
 */
class SetupChecklist
{
    /**
     * @return array{done: int, total: int, items: list<array{key: string, title: string, text: string, href: string, done: bool}>}
     */
    public static function for(int $schoolId): array
    {
        $school = School::find($schoolId);
        $site = SchoolSite::withoutGlobalScopes()->where('school_id', $schoolId)->first();

        $items = [
            ['key' => 'type', 'title' => 'Choose your kind of school', 'text' => 'Nigerian, British, American, Montessori: adds the right classes, subjects and grades.',
                'href' => '/school/settings/school-type', 'done' => SchoolTypes::current($schoolId) !== null || SchoolClass::where('school_id', $schoolId)->count() > 0],
            ['key' => 'logo', 'title' => 'Add your logo', 'text' => 'It appears on report cards, receipts, ID cards and your website.',
                'href' => '/school/settings', 'done' => (bool) $school?->logo],
            ['key' => 'classes', 'title' => 'Set up classes', 'text' => 'The classes and arms your pupils belong to.',
                'href' => '/school/classes', 'done' => SchoolClass::where('school_id', $schoolId)->exists()],
            ['key' => 'subjects', 'title' => 'Add subjects', 'text' => 'What each class studies.',
                'href' => '/school/subjects', 'done' => Subject::where('school_id', $schoolId)->exists()],
            ['key' => 'term', 'title' => 'Set the school year and current term', 'text' => 'Fees, results and attendance are tied to the term.',
                'href' => '/school/academics/terms', 'done' => AcademicYear::where('school_id', $schoolId)->where('is_current', true)->exists()
                    && Term::where('school_id', $schoolId)->where('is_current', true)->exists()],
            ['key' => 'fees', 'title' => 'Set your fees', 'text' => 'One amount per class, so invoices can be made.',
                'href' => '/school/fees/structures', 'done' => FeeStructure::where('school_id', $schoolId)->exists()],
            ['key' => 'staff', 'title' => 'Add your teachers and staff', 'text' => 'Give each a login in the same step if they should sign in.',
                'href' => '/school/staff', 'done' => Staff::where('school_id', $schoolId)->exists()],
            ['key' => 'students', 'title' => 'Add your pupils', 'text' => 'One at a time, from an admission, or many at once from Excel.',
                'href' => '/school/students/import', 'done' => Student::where('school_id', $schoolId)->exists()],
            ['key' => 'parents', 'title' => 'Give parents their logins', 'text' => 'So they can see fees and results and pay online.',
                'href' => '/school/students/logins', 'done' => User::where('school_id', $schoolId)->role('parent')->exists()],
            ['key' => 'website', 'title' => 'Make your website yours', 'text' => 'Pick a look and colours; it is already live with starter pages.',
                'href' => '/school/website/themes', 'done' => $site !== null && $site->updated_at && $site->created_at && $site->updated_at->gt($site->created_at->copy()->addMinute())],
        ];

        return ['done' => count(array_filter($items, fn ($i) => $i['done'])), 'total' => count($items), 'items' => $items];
    }
}
