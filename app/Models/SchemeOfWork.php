<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One subject's topics for one class and term, week by week */
class SchemeOfWork extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'schemes_of_work';

    protected $fillable = ['school_id', 'class_id', 'subject_id', 'term_id', 'title', 'source_path', 'source_name', 'weeks', 'created_by'];

    protected $casts = ['weeks' => 'array'];

    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function subject(): BelongsTo     { return $this->belongsTo(Subject::class); }
    public function term(): BelongsTo        { return $this->belongsTo(Term::class); }
    public function notes(): HasMany         { return $this->hasMany(LessonPlan::class, 'scheme_of_work_id'); }

    /** @return array{week: int, topic: string, details: string}|null */
    public function week(int $week): ?array
    {
        return collect($this->weeks ?? [])->firstWhere('week', $week);
    }
}
