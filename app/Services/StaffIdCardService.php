<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\IdCardDesign;
use App\Models\Staff;
use App\Models\StaffIdCard;
use App\Models\User;
use App\Support\EmbeddedImage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Staff ID cards. Same idea and same look as student cards (the school's ID card design is reused),
 * with their own numbers (STF/2026/0001) and a QR that holds only a random code.
 */
class StaffIdCardService
{
    public function __construct(private IdCardService $students) {}

    /** Give a staff member a new card. Any card they had is marked replaced, with the reason. */
    public function issue(Staff $staff, User $by, ?string $reason = null): StaffIdCard
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($staff, $by, $reason) {
                    StaffIdCard::withoutGlobalScopes()->where('staff_id', $staff->id)->where('status', 'active')
                        ->update(['status' => 'replaced', 'ended_at' => now(), 'note' => Str::limit($reason ?: 'New card issued', 200, '')]);
                    $number = (int) StaffIdCard::withoutGlobalScopes()->where('school_id', $staff->school_id)->max('number') + 1;

                    return StaffIdCard::create([
                        'school_id' => $staff->school_id, 'staff_id' => $staff->id, 'number' => $number,
                        'card_no' => 'STF/'.now()->year.'/'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                        'token' => Str::random(40), 'status' => 'active',
                        'expires_on' => $this->students->expiry($staff->school_id), 'issued_by' => $by->id,
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

    /** Cards for the active staff given who have no working card. Returns how many were made. */
    public function issueMissing(Collection $staff, User $by): int
    {
        $have = StaffIdCard::whereIn('staff_id', $staff->pluck('id'))->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', now()->toDateString()))
            ->pluck('staff_id')->flip();
        $made = 0;
        foreach ($staff as $member) {
            if ($member->status === 'active' && ! $have->has($member->id)) {
                $this->issue($member, $by, 'Old card expired');
                $made++;
            }
        }

        return $made;
    }

    public function revoke(StaffIdCard $card, string $reason): void
    {
        $card->update(['status' => 'revoked', 'ended_at' => now(), 'note' => Str::limit($reason, 200, '')]);
    }

    /** Find a card from what a scanner or a person typed: the QR link, the bare code, or the printed card number */
    public static function find(string $input): ?StaffIdCard
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }
        if (preg_match('~/id/([A-Za-z0-9]{40})~', $input, $m) || preg_match('~^([A-Za-z0-9]{40})$~', $input, $m)) {
            return StaffIdCard::where('token', $m[1])->first();
        }

        return StaffIdCard::where('card_no', strtoupper($input))->first();
    }

    /** What the card PDF needs: the student card page, filled with staff details and a staff title */
    public function pdfData(IdCardDesign $design, Collection $cards, string $layout): array
    {
        $saved = new \Illuminate\Database\Eloquent\Collection($cards->filter(fn ($c) => $c->exists)->all());
        $saved->loadMissing(['staff.department:id,name', 'staff.designation:id,name']);

        // The student page does the layout; hand it no student cards and fill the faces ourselves
        $data = $this->students->pdfData($design, collect(), $layout);
        $staffDesign = $design->replicate();
        $staffDesign->title = 'Staff ID Card';
        $data['design'] = $staffDesign;
        $data['cards'] = $cards->map(fn (StaffIdCard $c) => $this->face($c))->values()->all();

        return $data;
    }

    /** One card's printed details, in the same shape as a student card so one card layout serves both */
    public function face(StaffIdCard $card): array
    {
        $s = $card->staff;

        return [
            'card_no' => $card->card_no,
            'name' => $s?->full_name ?? '',
            'initials' => mb_strtoupper(mb_substr((string) $s?->first_name, 0, 1).mb_substr((string) $s?->last_name, 0, 1)),
            'admission_no' => $s?->emp_id,
            'class' => trim(($s?->designation?->name ?? '').(($s?->designation && $s?->department) ? ', ' : '').($s?->department?->name ?? '')),
            'class_label' => 'Role',
            'house' => null,
            'date_of_birth' => $s?->date_of_birth?->format('j M Y'),
            'blood_group' => $s?->blood_group,
            'gender' => $s?->gender ? ucfirst($s->gender) : null,
            'guardian_phone' => $s?->phone,
            'phone_label' => 'Phone',
            'address' => $s?->address,
            'admission_date' => $s?->joining_date?->format('j M Y'),
            'admission_label' => 'Joined',
            'valid_until' => $card->expires_on?->format('j M Y'),
            'photo' => EmbeddedImage::from($s?->photo, 'public'),
            'qr' => CertificateService::qr($card->url()),
            'sample' => $card->exists === false,
        ];
    }

    /** What staff see after scanning a staff card, in the shape the scan screen already shows */
    public function scan(StaffIdCard $card): array
    {
        $card->loadMissing(['staff.department:id,name', 'staff.designation:id,name']);
        $s = $card->staff;
        $today = Attendance::where('attendable_type', Staff::class)->where('attendable_id', $s->id)->whereDate('date', now()->toDateString())->value('status');

        return [
            'kind' => 'staff',
            'card' => ['card_no' => $card->card_no, 'state' => $card->state(), 'expires_on' => $card->expires_on?->format('j M Y'), 'note' => $card->note],
            'student' => [
                'id' => $s->id, 'name' => $s->full_name, 'admission_no' => $s->emp_id, 'status' => $s->status,
                'class' => trim(($s->designation?->name ?? '').(($s->designation && $s->department) ? ', ' : '').($s->department?->name ?? '')),
                'house' => null,
                'photo_url' => $s->photo_url,
                'guardian' => null,
            ],
            'attendance' => $today,
            'fees' => null,
        ];
    }

    /** Mark the staff member present (or late) today, unless already marked */
    public function markAttendance(Staff $staff, string $status): string
    {
        $today = now()->toDateString();
        if (Attendance::where('attendable_type', Staff::class)->where('attendable_id', $staff->id)->whereDate('date', $today)->exists()) {
            return 'already';
        }
        Attendance::create([
            'school_id' => $staff->school_id, 'date' => $today, 'attendable_type' => Staff::class, 'attendable_id' => $staff->id,
            'status' => $status, 'remarks' => 'ID card scan at '.now()->format('g:i a'),
            'academic_year_id' => AcademicYear::where('is_current', true)->value('id'),
        ]);

        return 'marked';
    }
}
