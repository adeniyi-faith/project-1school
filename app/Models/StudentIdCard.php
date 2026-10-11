<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One student's numbered ID card. Only the latest active card's QR is accepted. */
class StudentIdCard extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'number', 'card_no', 'token', 'status', 'expires_on', 'note', 'issued_by', 'ended_at', 'prints', 'printed_at',
    ];

    protected $casts = ['expires_on' => 'date:Y-m-d', 'ended_at' => 'datetime', 'printed_at' => 'datetime', 'prints' => 'integer'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->lt(now()->startOfDay());
    }

    /** valid, expired, replaced or revoked */
    public function state(): string
    {
        return $this->status === 'active' ? ($this->isExpired() ? 'expired' : 'valid') : $this->status;
    }

    /** What the QR code opens */
    public function url(): string
    {
        return url('/id/'.$this->token);
    }
}
