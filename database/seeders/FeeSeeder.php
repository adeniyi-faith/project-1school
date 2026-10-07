<?php

namespace Database\Seeders;

use App\Models\FeeCategory;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Database\Seeder;

class FeeSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('slug', 'greenfield-academy')->firstOrFail();
        $sid    = $school->id;

        // ── Fee Categories ────────────────────────────────────────────
        $categoryData = [
            ['name' => 'Tuition Fee',      'type' => 'tuition',   'description' => 'Monthly academic tuition'],
            ['name' => 'Exam Fee',          'type' => 'exam',      'description' => 'Per-exam assessment fee'],
            ['name' => 'Library Fee',       'type' => 'library',   'description' => 'Annual library membership'],
            ['name' => 'Sports Fee',        'type' => 'sports',    'description' => 'Annual sports & physical education'],
            ['name' => 'Transport Fee',     'type' => 'transport', 'description' => 'Monthly school bus fee'],
        ];

        $categories = [];
        foreach ($categoryData as $cat) {
            $categories[$cat['type']] = FeeCategory::firstOrCreate(
                ['school_id' => $sid, 'name' => $cat['name']],
                ['type' => $cat['type'], 'description' => $cat['description'], 'is_active' => true]
            );
        }

        // ── Fee Structures (per class, 2025-2026) ─────────────────────
        $classes = SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get();

        // Tuition increases with class level
        $tuitionAmounts = [1500, 1500, 1800, 1800, 2000, 2000, 2500, 2500, 3000, 3000];

        $structures = [];
        foreach ($classes as $i => $class) {
            $tuition = $tuitionAmounts[$i] ?? 2000;

            $structures[$class->id]['tuition'] = FeeStructure::firstOrCreate(
                ['school_id' => $sid, 'class_id' => $class->id, 'fee_category_id' => $categories['tuition']->id, 'academic_year' => '2025-2026'],
                ['amount' => $tuition, 'frequency' => 'monthly', 'due_date' => '2026-01-10', 'is_active' => true]
            );

            $structures[$class->id]['exam'] = FeeStructure::firstOrCreate(
                ['school_id' => $sid, 'class_id' => $class->id, 'fee_category_id' => $categories['exam']->id, 'academic_year' => '2025-2026'],
                ['amount' => 500, 'frequency' => 'one_time', 'due_date' => '2026-02-28', 'is_active' => true]
            );

            $structures[$class->id]['library'] = FeeStructure::firstOrCreate(
                ['school_id' => $sid, 'class_id' => $class->id, 'fee_category_id' => $categories['library']->id, 'academic_year' => '2025-2026'],
                ['amount' => 1200, 'frequency' => 'annual', 'due_date' => '2026-01-15', 'is_active' => true]
            );
        }

        // ── Payments for first 3 classes ──────────────────────────────
        $months = ['2026-01', '2026-02', '2026-03'];
        $payCount = 0;

        $firstThreeClasses = $classes->take(3);
        foreach ($firstThreeClasses as $class) {
            $students = Student::where('class_id', $class->id)->where('status', 'active')->get();
            $tuitionStruct = $structures[$class->id]['tuition'];
            $examStruct    = $structures[$class->id]['exam'];

            foreach ($students as $student) {
                // Tuition for 3 months (Jan–Mar)
                foreach ($months as $month) {
                    $alreadyPaid = FeePayment::where('student_id', $student->id)
                        ->where('fee_structure_id', $tuitionStruct->id)
                        ->where('month_year', $month)
                        ->exists();
                    if ($alreadyPaid) continue;

                    $amountDue  = (float) $tuitionStruct->amount;
                    $amountPaid = rand(0, 10) < 8 ? $amountDue : round($amountDue * 0.5, 2);
                    $status     = $amountPaid >= $amountDue ? 'paid' : 'partial';

                    FeePayment::create([
                        'school_id'        => $sid,
                        'student_id'       => $student->id,
                        'fee_structure_id' => $tuitionStruct->id,
                        'receipt_no'       => sprintf('RCP-%s-%05d', date('Y'), $payCount + 1),
                        'amount_due'       => $amountDue,
                        'amount_paid'      => $amountPaid,
                        'discount'         => 0,
                        'fine'             => 0,
                        'payment_date'     => $month . '-' . rand(5, 28),
                        'month_year'       => $month,
                        'method'           => ['cash', 'bkash', 'nagad'][rand(0, 2)],
                        'status'           => $status,
                    ]);
                    $payCount++;
                }

                // Exam fee (one-time)
                $examExists = FeePayment::where('student_id', $student->id)
                    ->where('fee_structure_id', $examStruct->id)
                    ->exists();
                if (!$examExists) {
                    FeePayment::create([
                        'school_id'        => $sid,
                        'student_id'       => $student->id,
                        'fee_structure_id' => $examStruct->id,
                        'receipt_no'       => sprintf('RCP-%s-%05d', date('Y'), $payCount + 1),
                        'amount_due'       => (float) $examStruct->amount,
                        'amount_paid'      => (float) $examStruct->amount,
                        'discount'         => 0,
                        'fine'             => 0,
                        'payment_date'     => '2026-02-15',
                        'month_year'       => null,
                        'method'           => 'cash',
                        'status'           => 'paid',
                    ]);
                    $payCount++;
                }
            }
        }

        $this->command->info("Seeded " . count($categoryData) . " categories, " . count($structures) * 3 . " structures, {$payCount} payments.");
    }
}
