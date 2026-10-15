<?php

namespace App\Website;

use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\Holiday;
use App\Models\School;
use App\Models\SchoolSitePost;
use App\Models\Staff;
use App\Models\Term;
use Illuminate\Support\Collection;

/**
 * Facts the website reads straight from the school's own records, so a page never has to be retyped when
 * a fee, a term date or a teacher changes. Each method returns plain arrays and only public facts.
 */
class LiveData
{
    private const FREQUENCY = ['per_term' => 'each term', 'annual' => 'once a year', 'one_time' => 'one time'];

    /** Fees by class for the current school year: [{class, lines: [{name, amount, when}], term_total}] */
    public static function fees(School $school): Collection
    {
        $year = AcademicYear::withoutGlobalScopes()->where('school_id', $school->id)->where('is_current', true)->value('name');

        return FeeStructure::withoutGlobalScopes()->with(['feeCategory:id,name', 'schoolClass:id,name,numeric_name'])
            ->where('school_id', $school->id)->where('is_active', true)->whereNull('deleted_at')
            ->where(fn ($q) => $q->whereNull('academic_year')->orWhere('academic_year', '')->when($year, fn ($q) => $q->orWhere('academic_year', $year)))
            ->get()
            ->filter(fn ($f) => $f->schoolClass !== null && (float) $f->amount > 0)
            ->groupBy('class_id')
            ->map(fn (Collection $rows) => [
                'class' => $rows->first()->schoolClass->name,
                'order' => [(int) $rows->first()->schoolClass->numeric_name, $rows->first()->schoolClass->name],
                'lines' => $rows->map(fn ($f) => ['name' => $f->feeCategory?->name ?? 'Fee', 'amount' => (float) $f->amount, 'when' => self::FREQUENCY[$f->frequency] ?? 'each term'])->values()->all(),
                'term_total' => (float) $rows->where('frequency', 'per_term')->sum('amount'),
            ])
            ->sortBy('order')->values();
    }

    /** Term dates of the current school year (and the next one when it exists) and holidays coming up */
    public static function dates(School $school): array
    {
        $today = now()->toDateString();
        $terms = Term::withoutGlobalScopes()->with('academicYear:id,name,is_current')
            ->where('school_id', $school->id)->whereNull('deleted_at')->whereNotNull('start_date')->whereNotNull('end_date')
            ->where('end_date', '>=', $today)->orderBy('start_date')->limit(4)->get()
            ->map(fn ($t) => ['name' => $t->name, 'year' => $t->academicYear?->name, 'start' => $t->start_date, 'end' => $t->end_date, 'now' => $t->start_date->toDateString() <= $today && $t->end_date->toDateString() >= $today])
            ->all();

        $holidays = Holiday::withoutGlobalScopes()->where('school_id', $school->id)->where('date', '>=', $today)
            ->orderBy('date')->limit(8)->get(['name', 'date'])->map(fn ($h) => ['name' => $h->name, 'date' => $h->date])->all();

        return ['terms' => $terms, 'holidays' => $holidays];
    }

    /** People the school ticked "show on the website" */
    public static function team(School $school, int $limit = 24): Collection
    {
        return Staff::withoutGlobalScopes()->with('designation:id,name')
            ->where('school_id', $school->id)->where('show_on_website', true)->where('status', 'active')->whereNull('deleted_at')
            ->orderBy('id')->limit($limit)->get()
            ->map(fn ($s) => ['name' => $s->full_name, 'role' => $s->designation?->name, 'photo' => $s->photo ? SiteRenderer::image($s->photo) ?? $s->photo_url : null]);
    }

    /** Events from News & events that have not happened yet, soonest first */
    public static function events(School $school, int $limit = 6): Collection
    {
        return SchoolSitePost::withoutGlobalScopes()->where('school_id', $school->id)->whereNull('deleted_at')->live()
            ->where('category', 'events')->whereNotNull('event_date')->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')->limit($limit)->get()
            ->map(fn ($p) => ['title' => $p->title, 'date' => $p->event_date, 'place' => $p->event_place, 'url' => SiteRenderer::postUrl($school, $p), 'excerpt' => $p->excerpt ?: Prose::plain($p->body, 140)]);
    }
}
