<?php

namespace App\Services;

use App\Models\ReportCardDesign;
use App\Models\ReportCardSigner;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCertificate;
use App\Models\User;
use App\Support\EmbeddedImage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Testimonials and transfer certificates: the starting details for the form, issuing one
 * with the next serial number, and everything the PDF needs.
 */
class CertificateService
{
    public function __construct(private ReportCardService $cards) {}

    /** What the issue form starts with; staff can change any of it before issuing */
    public function defaults(Student $student, string $type): array
    {
        $student->loadMissing(['schoolClass' => fn ($q) => $q->withTrashed(), 'promotions.fromClass' => fn ($q) => $q->withTrashed()]);
        $owed = $this->cards->feesOwed([$student->id])[$student->id] ?? 0.0;

        return [
            'date_admitted' => $student->admission_date?->toDateString(),
            'date_left' => now()->toDateString(),
            'class_admitted' => $student->promotions->first()?->fromClass?->name ?? $student->schoolClass?->name,
            'last_class' => $student->schoolClass?->name,
            'conduct' => 'Good',
            'academic_ability' => $type === 'testimonial' ? 'Good' : null,
            'offices_held' => null,
            'activities' => null,
            'exams' => null,
            'reason_for_leaving' => $type === 'transfer' ? 'Change of school' : null,
            'destination_school' => null,
            'fees_owed' => $owed,
            'fees_cleared' => $owed <= 0,
            'remark' => null,
            'show_photo' => (bool) $student->photo,
        ];
    }

    /** Issue a certificate with the next number for this school and type */
    public function issue(Student $student, string $type, array $details, ?int $signerId, User $by, bool $markLeft): StudentCertificate
    {
        $details += [
            'name' => $student->full_name,
            'admission_no' => $student->admission_no,
            'gender' => $student->gender,
            'date_of_birth' => $student->date_of_birth?->toDateString(),
        ];

        // Two people issuing at the same moment could pick the same number; the unique index
        // catches that and the second one simply tries again with the next number
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($student, $type, $details, $signerId, $by, $markLeft) {
                    $number = (int) StudentCertificate::withTrashed()->withoutGlobalScopes()
                        ->where('school_id', $student->school_id)->where('type', $type)->max('number') + 1;
                    $year = now()->year;

                    $certificate = StudentCertificate::create([
                        'school_id' => $student->school_id,
                        'student_id' => $student->id,
                        'type' => $type,
                        'number' => $number,
                        'serial' => StudentCertificate::TYPES[$type]['prefix']."/{$year}/".str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                        'verify_code' => $this->newCode(),
                        'issued_on' => now()->toDateString(),
                        'details' => $details,
                        'signer_id' => $signerId,
                        'issued_by' => $by->id,
                    ]);

                    if ($markLeft && $student->status === 'active') {
                        $student->update(['status' => $type === 'transfer' ? 'transferred' : 'alumni']);
                    }

                    return $certificate;
                });
            } catch (QueryException $e) {
                if ($attempt >= 3 || ! str_contains(strtolower($e->getMessage()), 'unique')) {
                    throw $e;
                }
            }
        }
    }

    /** Everything the PDF view needs */
    public function pdfData(StudentCertificate $certificate): array
    {
        $certificate->loadMissing(['student', 'signer']);
        $design = $this->cards->design(ReportCardDesign::forClass($certificate->school_id, null));
        $d = $certificate->details;
        $female = ($d['gender'] ?? null) === 'female';
        $male = ($d['gender'] ?? null) === 'male';

        return [
            'certificate' => $certificate,
            'title' => $certificate->title(),
            'd' => $d,
            'school' => $this->cards->school($certificate->school_id),
            'color' => $design['primary'],
            'accent' => $design['accent'],
            'stamp' => $design['stamp'],
            'signer' => $certificate->signer ? [
                'label' => $certificate->signer->label,
                'name' => $certificate->signer->name,
                'signature' => EmbeddedImage::from($certificate->signer->signature_path),
            ] : null,
            'photo' => ! empty($d['show_photo']) ? EmbeddedImage::from($certificate->student?->photo) : null,
            'he' => $female ? 'She' : ($male ? 'He' : 'They'),
            'his' => $female ? 'her' : ($male ? 'his' : 'their'),
            'him' => $female ? 'her' : ($male ? 'him' : 'them'),
            'verify_url' => url('/verify/'.$certificate->verify_code),
        ];
    }

    /** Signers a certificate can be signed by: those of the school's designs, heads first */
    public function signers(int $schoolId): array
    {
        return ReportCardSigner::where('school_id', $schoolId)->get()
            ->sortBy(fn ($s) => [$s->writer_permission === 'results.publish' ? 0 : 1, $s->sort_order, $s->id])
            ->unique(fn ($s) => mb_strtolower($s->label).'|'.mb_strtolower((string) $s->name))
            ->map(fn ($s) => ['id' => $s->id, 'label' => $s->name ? "{$s->label} ({$s->name})" : $s->label, 'has_signature' => (bool) $s->signature_path])
            ->values()->all();
    }

    private function newCode(): string
    {
        do {
            // No 0/O or 1/I, so the code is easy to read off paper
            $code = substr(str_replace(['0', 'O', '1', 'I'], '', strtoupper(Str::random(20))), 0, 10);
        } while (strlen($code) < 10 || StudentCertificate::withoutGlobalScopes()->withTrashed()->where('verify_code', $code)->exists());

        return $code;
    }
}
