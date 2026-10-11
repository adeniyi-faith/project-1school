<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\IdCardDesign;
use App\Models\Invoice;
use App\Models\InvoiceInstalment;
use App\Models\ReportCardDesign;
use App\Models\Student;
use App\Models\StudentIdCard;
use App\Models\User;
use App\Support\EmbeddedImage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Student ID cards: numbering and replacing cards, everything the card PDF prints, and what a scan shows.
 * The QR holds only a random code (no name or number), so a lost card tells a finder nothing, and
 * replacing a card makes the old code stop working.
 */
class IdCardService
{
    public function __construct(private ReportCardService $cards) {}

    /** Give a student a new card. Any card they had is marked replaced, with the reason. */
    public function issue(Student $student, User $by, ?string $reason = null): StudentIdCard
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($student, $by, $reason) {
                    StudentIdCard::withoutGlobalScopes()->where('student_id', $student->id)->where('status', 'active')
                        ->update(['status' => 'replaced', 'ended_at' => now(), 'note' => Str::limit($reason ?: 'New card issued', 200, '')]);
                    $number = (int) StudentIdCard::withoutGlobalScopes()->where('school_id', $student->school_id)->max('number') + 1;

                    return StudentIdCard::create([
                        'school_id' => $student->school_id, 'student_id' => $student->id, 'number' => $number,
                        'card_no' => 'ID/'.now()->year.'/'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                        'token' => Str::random(40), 'status' => 'active',
                        'expires_on' => $this->expiry($student->school_id), 'issued_by' => $by->id,
                    ]);
                });
            } catch (QueryException $e) {
                // Two people issuing at the same moment picked the same number: try again with the next one
                if ($attempt >= 3 || ! str_contains(strtolower($e->getMessage()), 'unique')) {
                    throw $e;
                }
            }
        }
    }

    /** Cards for the active students given who have no working card. Returns how many were made. */
    public function issueMissing(Collection $students, User $by): int
    {
        $have = StudentIdCard::whereIn('student_id', $students->pluck('id'))->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', now()->toDateString()))
            ->pluck('student_id')->flip();
        $made = 0;
        foreach ($students as $student) {
            if ($student->status === 'active' && ! $have->has($student->id)) {
                $this->issue($student, $by, 'Old card expired');
                $made++;
            }
        }

        return $made;
    }

    public function revoke(StudentIdCard $card, string $reason): void
    {
        $card->update(['status' => 'revoked', 'ended_at' => now(), 'note' => Str::limit($reason, 200, '')]);
    }

    /** Valid for the design's number of months, else to the end of the current school year, else a year */
    public function expiry(int $schoolId): string
    {
        $design = IdCardDesign::forSchool($schoolId);
        if ($design->valid_months) {
            return now()->addMonths($design->valid_months)->toDateString();
        }
        $end = AcademicYear::withoutGlobalScopes()->where('school_id', $schoolId)->where('is_current', true)->value('end_date');

        return $end && strtotime((string) $end) > time() ? date('Y-m-d', strtotime((string) $end)) : now()->addYear()->toDateString();
    }

    /** Find a card from what a scanner or a person typed: the QR link, the bare code, or the printed card number */
    public static function find(string $input): ?StudentIdCard
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }
        if (preg_match('~/id/([A-Za-z0-9]{40})~', $input, $m) || preg_match('~^([A-Za-z0-9]{40})$~', $input, $m)) {
            return StudentIdCard::where('token', $m[1])->first();
        }

        return StudentIdCard::where('card_no', strtoupper($input))->first();
    }

    /** Everything the card PDF needs, for one design and some cards */
    public function pdfData(IdCardDesign $design, Collection $cards, string $layout): array
    {
        // the preview's made-up card is not saved, so there is nothing to load for it
        $saved = $cards->filter(fn ($c) => $c->exists)->all();
        (new \Illuminate\Database\Eloquent\Collection($saved))->loadMissing(['student.schoolClass:id,name', 'student.section:id,name', 'student.house:id,name,colour', 'student.guardian:id,phone']);
        $brand = $this->cards->design(ReportCardDesign::forClass($design->school_id, null));
        $design->loadMissing('signer');

        return [
            'design' => $design,
            'show' => $design->switches(),
            'landscape' => $design->orientation === 'landscape',
            'template' => in_array($design->template, IdCardDesign::TEMPLATES, true) ? $design->template : 'classic',
            'color' => $design->primary_color ?: $brand['primary'],
            'accent' => $design->accent_color ?: $brand['accent'],
            'school' => $this->cards->school($design->school_id),
            'signer' => $design->signer ? ['label' => $design->signer->label, 'signature' => EmbeddedImage::from($design->signer->signature_path)] : null,
            'layout' => $layout,
            'cards' => $cards->map(fn (StudentIdCard $c) => $this->face($c))->values()->all(),
        ];
    }

    /** One card's printed details */
    public function face(StudentIdCard $card): array
    {
        $s = $card->student;

        return [
            'card_no' => $card->card_no,
            'name' => $s?->full_name ?? '',
            'initials' => mb_strtoupper(mb_substr((string) $s?->first_name, 0, 1).mb_substr((string) $s?->last_name, 0, 1)),
            'admission_no' => $s?->admission_no,
            'class' => trim(($s?->schoolClass?->name ?? '').($s?->section ? ' '.$s->section->name : '')),
            'house' => $s?->house ? ['name' => $s->house->name, 'colour' => $s->house->colour] : null,
            'date_of_birth' => $s?->date_of_birth?->format('j M Y'),
            'blood_group' => $s?->blood_group,
            'gender' => $s?->gender ? ucfirst($s->gender) : null,
            'guardian_phone' => $s?->guardian?->phone,
            'address' => $s?->address,
            'admission_date' => $s?->admission_date?->format('j M Y'),
            'valid_until' => $card->expires_on?->format('j M Y'),
            'photo' => EmbeddedImage::from($s?->photo),
            'qr' => CertificateService::qr($card->url()),
            'sample' => $card->exists === false,
        ];
    }

    /** A made-up card for the design preview */
    public function sample(int $schoolId): StudentIdCard
    {
        $student = new Student(['first_name' => 'Adaeze', 'last_name' => 'Okafor', 'admission_no' => 'ADM-2026-0001', 'gender' => 'female', 'blood_group' => 'O+', 'address' => '12 Allen Avenue, Ikeja']);
        $student->date_of_birth = now()->subYears(12)->setDate(now()->year - 12, 3, 14);
        $student->admission_date = now()->subYears(2);
        $student->setRelation('schoolClass', new \App\Models\SchoolClass(['name' => 'JSS 2']));
        $student->setRelation('section', new \App\Models\Section(['name' => 'A']));
        $student->setRelation('house', new \App\Models\House(['name' => 'Blue House', 'colour' => '#2563eb']));
        $student->setRelation('guardian', new \App\Models\Guardian(['phone' => '0803 000 0000']));
        $card = new StudentIdCard(['card_no' => 'ID/'.now()->year.'/0000', 'token' => str_repeat('0', 40), 'expires_on' => $this->expiry($schoolId)]);
        $card->setRelation('student', $student);

        return $card;
    }

    /** What staff see after scanning a card: who it is, whether the card is good, today's attendance, and fees */
    public function scan(StudentIdCard $card, User $user): array
    {
        $card->loadMissing(['student.schoolClass:id,name', 'student.section:id,name', 'student.house:id,name,colour', 'student.guardian:id,name,phone']);
        $s = $card->student;
        $today = Attendance::where('attendable_type', Student::class)->where('attendable_id', $s->id)->whereDate('date', now()->toDateString())->value('status');

        $fees = null;
        if ($user->can('fees.view')) {
            $open = Invoice::where('student_id', $s->id)->whereIn('status', ['unpaid', 'partial']);
            $fees = [
                'owed' => round((float) (clone $open)->sum('balance'), 2),
                'open_invoices' => (clone $open)->count(),
                'overdue' => round((float) InvoiceInstalment::whereHas('invoice', fn ($q) => $q->where('student_id', $s->id)->whereIn('status', ['unpaid', 'partial']))
                    ->whereDate('due_date', '<', now()->toDateString())->whereColumn('paid', '<', 'amount')->get()->sum(fn ($p) => $p->left()), 2),
            ];
        }

        return [
            'card' => ['card_no' => $card->card_no, 'state' => $card->state(), 'expires_on' => $card->expires_on?->format('j M Y'), 'note' => $card->note],
            'student' => [
                'id' => $s->id, 'name' => $s->full_name, 'admission_no' => $s->admission_no, 'status' => $s->status,
                'class' => trim(($s->schoolClass?->name ?? '').($s->section ? ' '.$s->section->name : '')),
                'house' => $s->house ? ['name' => $s->house->name, 'colour' => $s->house->colour] : null,
                'photo_url' => $s->photo ? route('students.photo', $s->id) : null,
                'guardian' => $s->guardian ? ['name' => $s->guardian->name, 'phone' => $s->guardian->phone] : null,
            ],
            'attendance' => $today,
            'fees' => $fees,
        ];
    }

    /** Mark the student present (or late) today, unless already marked */
    public function markAttendance(Student $student, string $status): string
    {
        $today = now()->toDateString();
        if (Attendance::where('attendable_type', Student::class)->where('attendable_id', $student->id)->whereDate('date', $today)->exists()) {
            return 'already';
        }
        Attendance::create([
            'school_id' => $student->school_id, 'date' => $today, 'attendable_type' => Student::class, 'attendable_id' => $student->id,
            'status' => $status, 'remarks' => 'ID card scan at '.now()->format('g:i a'),
            'academic_year_id' => AcademicYear::where('is_current', true)->value('id'),
        ]);

        return 'marked';
    }
}
