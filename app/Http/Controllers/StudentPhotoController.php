<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Student passport photos. They are kept on the private disk and shown only to staff of the
 * student's school who can view students, the student themselves, and their parent.
 */
class StudentPhotoController extends Controller
{
    /** Photos are JPG or PNG (what report card PDFs can print), up to 2 MB */
    public const RULE = 'image|mimes:jpg,jpeg,png|max:2048';

    public function show(Request $request, int $student): Response
    {
        $user = $request->user();
        $record = Student::withoutGlobalScopes()->findOrFail($student);

        $allowed = $user->hasRole('super-admin')
            || ($record->school_id === $user->school_id && (
                $user->can('students.view')
                || ($user->hasRole('student') && $record->user_id === $user->id)
                || ($user->hasRole('parent') && Guardian::where('user_id', $user->id)->whereKey($record->guardian_id)->exists())
            ));
        abort_unless($allowed && $record->photo && Storage::disk('private')->exists($record->photo), 404);

        return Storage::disk('private')->response($record->photo, null, ['Cache-Control' => 'private, max-age=86400']);
    }

    public function store(Request $request, Student $student): RedirectResponse
    {
        $request->validate(['photo' => 'required|'.self::RULE]);
        self::replace($student, $request->file('photo'));

        return back()->with('success', 'Photo updated.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        if ($student->photo) {
            Storage::disk('private')->delete($student->photo);
            $student->update(['photo' => null]);
        }

        return back()->with('success', 'Photo removed.');
    }

    /**
     * Many photos at once, each named after the student's admission number
     * (ADM-2026-0001.jpg). Files that match no student are listed back.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $request->validate([
            'photos' => 'required|array|max:200',
            'photos.*' => self::RULE,
        ]);

        $matched = 0;
        $unmatched = [];
        foreach ($request->file('photos') as $file) {
            $admissionNo = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $student = Student::where('admission_no', $admissionNo)->first();
            if (! $student) {
                $unmatched[] = $file->getClientOriginalName();
                continue;
            }
            self::replace($student, $file);
            $matched++;
        }

        $message = "{$matched} photo".($matched === 1 ? '' : 's').' added.';
        if ($unmatched) {
            $message .= ' No student has the admission number in these file names: '.implode(', ', array_slice($unmatched, 0, 10)).(count($unmatched) > 10 ? ' …' : '');
        }

        return back()->with($unmatched ? 'info' : 'success', $message);
    }

    private static function replace(Student $student, $file): void
    {
        $old = $student->photo;
        $path = $file->store("students/{$student->school_id}/photos", 'private');
        $student->update(['photo' => $path]);
        if ($old && $old !== $path) {
            Storage::disk('private')->delete($old);
        }
    }
}
