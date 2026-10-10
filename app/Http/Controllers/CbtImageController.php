<?php

namespace App\Http\Controllers;

use App\Models\CbtAttempt;
use App\Models\CbtQuestion;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/** Pictures on questions: staff of the school, or someone whose paper has the question */
class CbtImageController extends Controller
{
    public function show(Request $request, int $question): Response
    {
        $user = $request->user();
        $q = CbtQuestion::withoutGlobalScopes()->withTrashed()->findOrFail($question);
        abort_unless($q->image_path && (int) $q->school_id === (int) $user->school_id, 404);

        if (! $user->can('cbt.view')) {
            $student = Student::where('user_id', $user->id)->first();
            $onPaper = $student && CbtAttempt::where('student_id', $student->id)->get(['paper'])
                ->contains(fn (CbtAttempt $a) => collect($a->paper)->contains('q', $q->id));
            abort_unless($onPaper, 404);
        }
        abort_unless(Storage::disk('private')->exists($q->image_path), 404);

        return Storage::disk('private')->response($q->image_path, null, ['Cache-Control' => 'private, max-age=3600']);
    }
}
