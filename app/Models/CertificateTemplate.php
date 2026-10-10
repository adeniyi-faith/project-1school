<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** The look and wording of a school's testimonials or transfer certificates */
class CertificateTemplate extends Model
{
    use BelongsToSchool;

    public const BORDERS = ['classic', 'ornate', 'modern', 'simple'];

    /** Blanks the wording may use; each is filled in from the certificate */
    public const BLANKS = ['{name}', '{admission_no}', '{school}', '{date_admitted}', '{date_left}', '{class_admitted}', '{last_class}',
        '{conduct}', '{he_she}', '{his_her}', '{him_her}'];

    public const DEFAULTS = [
        'testimonial' => [
            'title' => 'Testimonial',
            'body' => "This is to certify that {name} (Admission No. {admission_no}) was a student of this school from {date_admitted} to {date_left}. {he_she} was admitted into {class_admitted} and was in {last_class} when {he_she} left.\n\nDuring {his_her} time in this school, {his_her} conduct and character were found to be {conduct}.",
            'closing' => 'We wish {him_her} every success in the future.',
        ],
        'transfer' => [
            'title' => 'Transfer Certificate',
            'body' => "This is to certify that {name} (Admission No. {admission_no}) was a student of this school from {date_admitted} to {date_left}. {he_she} was admitted into {class_admitted} and was in {last_class} when {he_she} left.\n\n{he_she} is leaving to continue {his_her} education elsewhere, and this certificate is given so that {he_she} may be admitted to another school.",
            'closing' => 'We wish {him_her} every success in the future.',
        ],
    ];

    protected $fillable = ['school_id', 'type', 'border', 'primary_color', 'accent_color', 'title', 'body', 'closing', 'show_details', 'show_qr', 'show_watermark'];

    protected $casts = ['show_details' => 'boolean', 'show_qr' => 'boolean', 'show_watermark' => 'boolean'];

    /** The school's template for a type, made with the standard wording the first time */
    public static function for(int $schoolId, string $type): self
    {
        return self::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $schoolId, 'type' => $type],
            self::DEFAULTS[$type] + ['border' => 'classic', 'show_details' => true, 'show_qr' => true, 'show_watermark' => true],
        );
    }

    /** What an issued certificate keeps, so later design changes don't alter it */
    public function snapshot(): array
    {
        return $this->only('border', 'primary_color', 'accent_color', 'title', 'body', 'closing', 'show_details', 'show_qr', 'show_watermark');
    }
}
