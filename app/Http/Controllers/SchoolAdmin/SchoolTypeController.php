<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Support\SchoolTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Pick the kind of school (Nigerian, British, American, Montessori ...) and add its classes, subjects, terms and grades */
class SchoolTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $sid = $request->user()->school_id;

        return Inertia::render('SchoolAdmin/Settings/SchoolType', [
            'types' => SchoolTypes::summaries(),
            'current' => SchoolTypes::current($sid),
            'parts' => collect(SchoolTypes::PARTS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'have' => ['classes' => SchoolClass::count(), 'subjects' => Subject::count()],
            'canEdit' => (bool) $request->user()->can('settings.edit'),
        ]);
    }

    public function apply(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(SchoolTypes::all()))],
            'parts' => 'array',
            'parts.*' => [Rule::in(array_keys(SchoolTypes::PARTS))],
        ]);

        $done = SchoolTypes::apply($request->user()->school_id, $data['type'], $data['parts'] ?? []);
        $label = SchoolTypes::all()[$data['type']]['label'];

        $bits = array_filter([
            $done['classes'] ? "{$done['classes']} class".($done['classes'] === 1 ? '' : 'es') : null,
            $done['subjects'] ? "{$done['subjects']} subject".($done['subjects'] === 1 ? '' : 's') : null,
            $done['terms_added'] + $done['terms_renamed'] ? ($done['terms_added'] + $done['terms_renamed']).' term name'.(($done['terms_added'] + $done['terms_renamed']) === 1 ? '' : 's') : null,
            $done['schemes'] ? "{$done['schemes']} score or grade setup".($done['schemes'] === 1 ? '' : 's') : null,
        ]);

        return back()->with('success', $bits
            ? "{$label} setup added: ".implode(', ', $bits).'. Nothing you already had was changed.'
            : "{$label} saved as your school type. Everything it lists was already in place, so nothing was added.");
    }
}
