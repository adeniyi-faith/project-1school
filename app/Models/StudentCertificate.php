<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A testimonial or transfer certificate given to a student. `details` keeps exactly what was
 * printed, so reprints match. A certificate is never edited: a wrong one is revoked and a new
 * one issued, and the public check page then shows the old one as revoked.
 */
class StudentCertificate extends Model
{
    use BelongsToSchool, SoftDeletes;

    public const TYPES = [
        'testimonial' => ['title' => 'Testimonial', 'prefix' => 'TST'],
        'transfer' => ['title' => 'Transfer Certificate', 'prefix' => 'TC'],
    ];

    public const CONDUCT = ['Excellent', 'Very good', 'Good', 'Fair', 'Poor'];

    protected $fillable = [
        'school_id', 'student_id', 'type', 'number', 'serial', 'verify_code', 'issued_on', 'details',
        'signer_id', 'issued_by', 'revoked_at', 'revoke_reason', 'revoked_by',
    ];

    protected $casts = ['details' => 'array', 'issued_on' => 'date', 'revoked_at' => 'datetime', 'number' => 'integer'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(ReportCardSigner::class, 'signer_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function title(): string
    {
        return self::TYPES[$this->type]['title'] ?? 'Certificate';
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
