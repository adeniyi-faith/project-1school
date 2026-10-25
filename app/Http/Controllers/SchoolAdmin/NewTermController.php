<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PromotionBatch;
use App\Models\SchoolSetting;
use App\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Start a new term": the jobs an admin does at the start of every term, in order, each linking to the
 * screen where it is done. Steps the system can see for itself tick themselves; the rest are ticked by hand
 * and remembered per term.
 */
class NewTermController extends Controller
{
    /** Steps that only a person can say are finished. */
    private const MANUAL = ['classes', 'fees', 'timetable'];

    public function index(): Response
    {
        $sid  = $this->getSchoolId();
        $user = auth()->user();
        $term = Term::with('academicYear')->where('school_id', $sid)->where('is_current', true)->first();

        $ticked = $term ? $this->ticked($sid, $term) : [];
        $firstOfYear = $term && $term->sequence === 1;

        $steps = [
            ['key' => 'term', 'title' => 'Make the new term the current term',
                'text' => 'Fees, results and attendance follow the current term. Choose it, and check its dates.',
                'href' => '/school/academics/terms', 'permission' => 'exams.view', 'auto' => true,
                'done' => $term !== null && $term->start_date !== null && $term->start_date->lte(now()->addDays(14))],
            ['key' => 'classes', 'title' => 'Check your classes and arms',
                'text' => 'Add any new class or arm, and remove ones no longer used.',
                'href' => '/school/classes', 'permission' => null, 'auto' => false, 'done' => in_array('classes', $ticked, true)],
        ];

        if ($firstOfYear) {
            $steps[] = ['key' => 'promotion', 'title' => 'Move students up to their next class',
                'text' => 'A new school year has begun. Move students up, repeat or graduate them.',
                'href' => '/school/promotions', 'permission' => 'students.promote', 'auto' => true,
                'done' => PromotionBatch::where('school_id', $sid)->where('academic_year_id', $term->academic_year_id)->whereNull('undone_at')->exists()];
        }

        $steps[] = ['key' => 'fees', 'title' => 'Check the fee amounts',
            'text' => 'Change any fee that has gone up or down for this term.',
            'href' => '/school/fees/structures', 'permission' => 'fees.view', 'auto' => false, 'done' => in_array('fees', $ticked, true)];
        $steps[] = ['key' => 'invoices', 'title' => 'Create the term\'s invoices',
            'text' => 'Bill every class for this term. Parents then see what they owe.',
            'href' => '/school/fees/invoices', 'permission' => 'fees.view', 'auto' => true,
            'done' => $term !== null && Invoice::where('school_id', $sid)->where('term_id', $term->id)->exists()];
        $steps[] = ['key' => 'timetable', 'title' => 'Check the timetable',
            'text' => 'Make sure every class has its lessons and teachers for the new term.',
            'href' => '/school/timetable', 'permission' => 'timetable.view', 'auto' => false, 'done' => in_array('timetable', $ticked, true)];

        $steps = array_map(function ($s) use ($user) {
            $s['href'] = ($s['permission'] === null || $user->can($s['permission'])) ? $s['href'] : null;
            unset($s['permission']);

            return $s;
        }, $steps);

        return Inertia::render('SchoolAdmin/NewTerm/Index', [
            'term'  => $term ? ['id' => $term->id, 'name' => $term->name, 'year' => $term->academicYear?->name] : null,
            'steps' => $steps,
        ]);
    }

    /** Tick or untick a step that only a person can judge. */
    public function toggle(Request $request): RedirectResponse
    {
        $data = $request->validate(['step' => ['required', 'in:' . implode(',', self::MANUAL)]]);
        $sid  = $this->getSchoolId();
        $term = Term::where('school_id', $sid)->where('is_current', true)->first();
        abort_if(! $term, 422, 'Choose the current term first.');

        $ticked = $this->ticked($sid, $term);
        $ticked = in_array($data['step'], $ticked, true)
            ? array_values(array_diff($ticked, [$data['step']]))
            : [...$ticked, $data['step']];

        SchoolSetting::set($sid, "new_term_ticks_{$term->id}", json_encode($ticked), 'academic');

        return back();
    }

    private function ticked(int $sid, Term $term): array
    {
        $saved = json_decode((string) SchoolSetting::get($sid, "new_term_ticks_{$term->id}", '[]'), true);

        return is_array($saved) ? $saved : [];
    }
}
