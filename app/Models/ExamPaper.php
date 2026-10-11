<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/** A printed exam paper: sections of questions taken from the question banks, with a marking scheme */
class ExamPaper extends Model
{
    use BelongsToSchool, SoftDeletes;

    public const MAX_SECTIONS = 6;

    public const MAX_QUESTIONS = 150;

    protected $fillable = ['school_id', 'title', 'class_id', 'subject_id', 'term_id', 'duration_minutes', 'instructions', 'sections', 'show_marks', 'created_by'];

    protected $casts = ['sections' => 'array', 'show_marks' => 'boolean', 'duration_minutes' => 'integer'];

    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function subject(): BelongsTo     { return $this->belongsTo(Subject::class); }
    public function term(): BelongsTo        { return $this->belongsTo(Term::class); }
    public function creator(): BelongsTo     { return $this->belongsTo(User::class, 'created_by'); }

    /** Every question id on the paper, in order */
    public function questionIds(): array
    {
        return collect($this->sections ?? [])->flatMap(fn ($s) => $s['questions'] ?? [])->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * The paper's questions by id, including ones deleted from their bank since, so a paper still prints as it was made.
     *
     * @return Collection<int, CbtQuestion>
     */
    public function questions(): Collection
    {
        return CbtQuestion::withTrashed()->where('school_id', $this->school_id)->whereIn('id', $this->questionIds())->get()->keyBy('id');
    }

    /** Sections with their questions in order (questions that no longer exist are left out) */
    public function filled(?Collection $questions = null): array
    {
        $questions ??= $this->questions();

        return collect($this->sections ?? [])->map(fn ($s) => [
            'title' => (string) ($s['title'] ?? ''),
            'instructions' => $s['instructions'] ?? null,
            'questions' => collect($s['questions'] ?? [])->map(fn ($id) => $questions->get((int) $id))->filter()->values(),
        ])->all();
    }

    public function durationText(): ?string
    {
        if (! $this->duration_minutes) {
            return null;
        }
        $h = intdiv($this->duration_minutes, 60);
        $m = $this->duration_minutes % 60;

        return trim(($h ? $h.' hour'.($h > 1 ? 's' : '') : '').($m ? ' '.$m.' minutes' : ''));
    }
}
