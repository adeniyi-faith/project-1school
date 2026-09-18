<?php

namespace App\Services;

use App\Models\GradeScale;
use Illuminate\Support\Collection;

class GradingService
{
    private Collection $scales;

    public function __construct(int $schoolId)
    {
        $this->scales = GradeScale::where('school_id', $schoolId)
            ->orderByDesc('min_marks')
            ->get();
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

    public static function defaultScales(): array
    {
        return [
            ['grade' => 'A+', 'gpa' => 5.00, 'min_marks' => 80, 'max_marks' => 100, 'remarks' => 'Outstanding',   'sort_order' => 1],
            ['grade' => 'A',  'gpa' => 4.00, 'min_marks' => 70, 'max_marks' => 79,  'remarks' => 'Excellent',     'sort_order' => 2],
            ['grade' => 'A-', 'gpa' => 3.50, 'min_marks' => 60, 'max_marks' => 69,  'remarks' => 'Very Good',     'sort_order' => 3],
            ['grade' => 'B',  'gpa' => 3.00, 'min_marks' => 50, 'max_marks' => 59,  'remarks' => 'Good',          'sort_order' => 4],
            ['grade' => 'C',  'gpa' => 2.00, 'min_marks' => 40, 'max_marks' => 49,  'remarks' => 'Satisfactory',  'sort_order' => 5],
            ['grade' => 'D',  'gpa' => 1.00, 'min_marks' => 33, 'max_marks' => 39,  'remarks' => 'Pass',          'sort_order' => 6],
            ['grade' => 'F',  'gpa' => 0.00, 'min_marks' => 0,  'max_marks' => 32,  'remarks' => 'Fail',          'sort_order' => 7],
        ];
    }
}
