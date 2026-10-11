<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonPlan extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'class_id', 'subject_id', 'teacher_id', 'term_id', 'scheme_of_work_id', 'lesson_note_format_id',
        'title', 'objectives', 'content', 'teaching_methods', 'resources', 'sections', 'ai_drafted', 'created_by',
        'week_start', 'week', 'duration_minutes', 'status', 'reviewer_feedback', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'week_start'  => 'date',
        'reviewed_at' => 'datetime',
        'sections'    => 'array',
        'ai_drafted'  => 'boolean',
    ];

    /** Headings of notes written before formats existed, mapped from their old columns */
    public const OLD_SECTIONS = [
        ['key' => 'objectives', 'label' => 'Objectives', 'hint' => ''],
        ['key' => 'content', 'label' => 'Content', 'hint' => ''],
        ['key' => 'teaching_methods', 'label' => 'Teaching methods', 'hint' => ''],
        ['key' => 'resources', 'label' => 'Resources', 'hint' => ''],
    ];

    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function subject(): BelongsTo     { return $this->belongsTo(Subject::class); }
    public function teacher(): BelongsTo     { return $this->belongsTo(Staff::class, 'teacher_id'); }
    public function reviewer(): BelongsTo    { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function term(): BelongsTo        { return $this->belongsTo(Term::class); }
    public function creator(): BelongsTo     { return $this->belongsTo(User::class, 'created_by'); }

    /** The teacher's name: from their staff record, else the account that wrote the note */
    public function teacherName(): ?string
    {
        return $this->teacher ? trim($this->teacher->first_name.' '.$this->teacher->last_name) : $this->creator?->name;
    }
    public function scheme(): BelongsTo      { return $this->belongsTo(SchemeOfWork::class, 'scheme_of_work_id'); }
    public function format(): BelongsTo      { return $this->belongsTo(LessonNoteFormat::class, 'lesson_note_format_id'); }

    /**
     * The headings and texts to show or print: the format's headings in order, then any text whose
     * heading the format no longer has, so nothing written is lost. Old notes use their old columns.
     *
     * @return array<int, array{key: string, label: string, hint: string, text: string}>
     */
    public function body(): array
    {
        if ($this->sections === null) {
            return array_map(fn ($s) => $s + ['text' => (string) $this->{$s['key']}], self::OLD_SECTIONS);
        }
        $headings = $this->format?->sections ?? [];
        $texts = $this->sections;
        $out = [];
        foreach ($headings as $h) {
            $out[] = $h + ['hint' => '', 'text' => (string) ($texts[$h['key']] ?? '')];
            unset($texts[$h['key']]);
        }
        foreach ($texts as $key => $text) {
            if (trim((string) $text) !== '') {
                $out[] = ['key' => (string) $key, 'label' => ucfirst(str_replace('_', ' ', (string) $key)), 'hint' => '', 'text' => (string) $text];
            }
        }

        return $out;
    }
}
