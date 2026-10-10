<?php

namespace App\Services;

use App\Models\GradeScale;
use App\Models\GradingScheme;
use App\Models\SchoolClass;
use Illuminate\Support\Collection;

class GradingService
{
    private Collection $scales;

    /**
     * Grades with one of the school's grade scales. With no scale named,
     * the school's default scale is used.
     */
    public function __construct(int $schoolId, ?int $gradingSchemeId = null)
    {
        $schemeId = $gradingSchemeId
            ?? GradingScheme::where('school_id', $schoolId)->where('is_default', true)->value('id');

        $this->scales = GradeScale::where('school_id', $schoolId)
            ->where('grading_scheme_id', $schemeId)
            ->orderByDesc('min_marks')
            ->get();
    }

    /** The grade scale a class uses: its own if set, else the school default. */
    public static function forClass(int $schoolId, ?int $classId): self
    {
        $schemeId = $classId ? SchoolClass::withTrashed()->whereKey($classId)->value('grading_scheme_id') : null;

        return new self($schoolId, $schemeId);
    }

    public function scales(): Collection
    {
        return $this->scales;
    }

    public function calculate(float $marks, float $fullMarks): array
    {
        $percentage = $fullMarks > 0 ? ($marks / $fullMarks) * 100 : 0;

        foreach ($this->scales as $scale) {
            if ($percentage >= (float) $scale->min_marks) {
                return [
                    'grade'   => $scale->grade,
                    'gpa'     => (float) $scale->gpa,
                    'remarks' => $scale->remarks,
                ];
            }
        }

        return ['grade' => 'F', 'gpa' => 0.00, 'remarks' => 'Fail'];
    }
}
