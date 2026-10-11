<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A subject the school teaches, such as Mathematics. One subject is offered by many classes (class_subjects).
 * `class_id` is the class it was first made for; it is kept for older records and is not a limit.
 */
class Subject extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'class_id', 'name', 'code', 'type', 'full_marks', 'pass_marks',
        'assessment_scheme_id',
    ];

    protected static function booted(): void
    {
        // Made for one class (the older way, and still how tests and seeders often do it): that class offers it
        static::created(function (Subject $subject) {
            if ($subject->class_id) {
                $subject->offerIn([$subject->class_id]);
            }
        });
    }

    /** The class it was first made for (older records); use classes() for every class that offers it */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_subjects', 'subject_id', 'class_id')
            ->withPivot(['departments', 'assessment_scheme_id'])->withTimestamps();
    }

    public function classLines(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }

    /**
     * Every subject for a picker: id, name and the classes that offer it, so pages can narrow the list to one class.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, name: string, class_ids: int[]}>
     */
    public static function picker(int $schoolId): \Illuminate\Support\Collection
    {
        $lines = ClassSubject::where('school_id', $schoolId)->get(['class_id', 'subject_id'])->groupBy('subject_id');

        return static::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name'])
            ->map(fn (Subject $s) => ['id' => $s->id, 'name' => $s->name, 'class_ids' => $lines->get($s->id)?->pluck('class_id')->map(fn ($c) => (int) $c)->values()->all() ?? []]);
    }

    /** Who takes it, in words for the AI: "JSS 2" for one class, "Junior secondary (JSS)" for a whole level, else the class names */
    public function levelLabel(): string
    {
        $classes = $this->classes()->orderBy('numeric_name')->get(['classes.id', 'classes.name', 'classes.level']);
        if ($classes->count() === 1) {
            return $classes->first()->name;
        }
        $levels = $classes->pluck('level')->unique();

        return $levels->count() === 1 && $levels->first()
            ? SchoolClass::LEVELS[$levels->first()] ?? ''
            : $classes->pluck('name')->take(4)->implode(', ');
    }

    /** Subjects offered by a class */
    public function scopeOfferedIn(Builder $query, ?int $classId): Builder
    {
        return $query->whereHas('classLines', fn ($q) => $q->where('class_id', $classId));
    }

    /** This class's line for the subject (departments, own score setup), if the class offers it */
    public function lineFor(?int $classId): ?ClassSubject
    {
        if (! $classId) {
            return null;
        }

        return $this->relationLoaded('classLines')
            ? $this->classLines->firstWhere('class_id', $classId)
            : $this->classLines()->where('class_id', $classId)->first();
    }

    /**
     * Offer the subject in these classes (adds lines, keeps existing ones).
     *
     * @param  int[]  $classIds
     * @param  array<int, string[]>  $departments  class id => departments (only for the classes given)
     */
    public function offerIn(array $classIds, array $departments = []): void
    {
        foreach (array_unique(array_map('intval', $classIds)) as $classId) {
            $line = ClassSubject::withoutGlobalScopes()->firstOrNew(['class_id' => $classId, 'subject_id' => $this->id], ['school_id' => $this->school_id]);
            if (array_key_exists($classId, $departments)) {
                $line->departments = array_values(array_filter($departments[$classId])) ?: null;
            }
            $line->school_id = $this->school_id;
            $line->save();
        }
    }
}
