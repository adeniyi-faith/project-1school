<?php

namespace App\Services;

use App\Models\CertificateTemplate;
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
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;

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
            // The design and wording at the time of issue, so later changes never alter this certificate
            'layout' => CertificateTemplate::for($student->school_id, $type)->snapshot(),
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
        $layout = ($d['layout'] ?? []) + ['border' => 'classic', 'show_details' => true, 'show_qr' => false, 'show_watermark' => true]
            + CertificateTemplate::DEFAULTS[$certificate->type];
        $school = $this->cards->school($certificate->school_id);
        $verifyUrl = url('/verify/'.$certificate->verify_code);
        $female = ($d['gender'] ?? null) === 'female';
        $male = ($d['gender'] ?? null) === 'male';
        $fmt = fn ($date) => $date ? date('j F Y', strtotime($date)) : '—';

        $values = [
            '{name}' => ['text' => mb_strtoupper((string) ($d['name'] ?? '')), 'bold' => true],
            '{admission_no}' => ['text' => $d['admission_no'] ?? '—', 'bold' => false],
            '{school}' => ['text' => $school['name'] ?? '', 'bold' => false],
            '{date_admitted}' => ['text' => $fmt($d['date_admitted'] ?? null), 'bold' => true],
            '{date_left}' => ['text' => $fmt($d['date_left'] ?? null), 'bold' => true],
            '{class_admitted}' => ['text' => $d['class_admitted'] ?: ($d['last_class'] ?? ''), 'bold' => true],
            '{last_class}' => ['text' => $d['last_class'] ?? '', 'bold' => true],
            '{conduct}' => ['text' => mb_strtolower((string) ($d['conduct'] ?? '')), 'bold' => true],
            '{he_she}' => ['text' => $female ? 'she' : ($male ? 'he' : 'they'), 'bold' => false],
            '{his_her}' => ['text' => $female ? 'her' : ($male ? 'his' : 'their'), 'bold' => false],
            '{him_her}' => ['text' => $female ? 'her' : ($male ? 'him' : 'them'), 'bold' => false],
        ];

        return [
            'certificate' => $certificate,
            'title' => $layout['title'] ?: $certificate->title(),
            'layout' => $layout,
            'paragraphs' => self::wording((string) $layout['body'], $values),
            'closing' => $layout['closing'] ? self::wording((string) $layout['closing'], $values) : [],
            'd' => $d,
            'school' => $school,
            'color' => $layout['primary_color'] ?: $design['primary'],
            'accent' => $layout['accent_color'] ?: $design['accent'],
            'stamp' => $design['stamp'],
            'signer' => $certificate->signer ? [
                'label' => $certificate->signer->label,
                'name' => $certificate->signer->name,
                'signature' => EmbeddedImage::from($certificate->signer->signature_path),
            ] : null,
            'photo' => ! empty($d['show_photo']) ? EmbeddedImage::from($certificate->student?->photo) : null,
            'qr' => ! empty($layout['show_qr']) ? self::qr($verifyUrl) : null,
            'verify_url' => $verifyUrl,
        ];
    }

    /**
     * The wording as safe HTML paragraphs: every piece of text is escaped, blanks are filled in
     * (key facts in bold), and a blank that starts a sentence gets a capital letter.
     *
     * @return string[]
     */
    public static function wording(string $text, array $values): array
    {
        $paragraphs = preg_split('/\R\s*\R/', trim($text)) ?: [];

        return array_values(array_filter(array_map(function (string $paragraph) use ($values) {
            $parts = preg_split('/(\{[a-z_]+\})/', trim($paragraph), -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
            $html = '';
            $plain = '';
            foreach ($parts as $part) {
                if (isset($values[$part])) {
                    $value = (string) $values[$part]['text'];
                    if (preg_match('/(^|[.!?]\s*)$/u', $plain)) {
                        $value = mb_strtoupper(mb_substr($value, 0, 1)).mb_substr($value, 1);
                    }
                    $html .= $values[$part]['bold'] ? '<strong>'.e($value).'</strong>' : e($value);
                    $plain .= $value;
                } else {
                    $html .= nl2br(e($part), false);
                    $plain .= $part;
                }
            }

            return $html;
        }, $paragraphs)));
    }

    /** A QR code (PNG data URI) that opens the public check page */
    public static function qr(string $url): ?string
    {
        try {
            return (new QRCode(new QROptions([
                'outputType' => QROutputInterface::GDIMAGE_PNG,
                'outputBase64' => true,
                'scale' => 5,
                'quietzoneSize' => 1,
            ])))->render($url);
        } catch (\Throwable) {
            return null;
        }
    }

    /** A made-up student's certificate printed with a template, for the preview button */
    public function sample(CertificateTemplate $template): StudentCertificate
    {
        $certificate = new StudentCertificate([
            'school_id' => $template->school_id,
            'type' => $template->type,
            'serial' => StudentCertificate::TYPES[$template->type]['prefix'].'/'.now()->year.'/0000',
            'verify_code' => 'SAMPLE0000',
            'issued_on' => now()->toDateString(),
            'details' => [
                'name' => 'Adaeze Okafor (sample)', 'admission_no' => 'ADM-0000-0001', 'gender' => 'female', 'date_of_birth' => '2008-03-01',
                'date_admitted' => now()->subYears(6)->format('Y-09-14'), 'date_left' => now()->toDateString(),
                'class_admitted' => 'JSS 1', 'last_class' => 'SS 3', 'conduct' => 'Very good', 'academic_ability' => 'Excellent',
                'offices_held' => 'Head girl', 'activities' => 'Debate club, Netball', 'exams' => 'WAEC SSCE May/June '.now()->year,
                'reason_for_leaving' => 'Family moving to Abuja', 'destination_school' => 'Unity College, Abuja', 'fees_cleared' => true,
                'remark' => null, 'show_photo' => false, 'layout' => $template->snapshot(),
            ],
        ]);
        $certificate->setRelation('student', null);
        $signer = ReportCardSigner::where('school_id', $template->school_id)->get()
            ->sortBy(fn ($s) => [$s->writer_permission === 'results.publish' ? 0 : 1, $s->sort_order])->first();
        $certificate->setRelation('signer', $signer);

        return $certificate;
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
