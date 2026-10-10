<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A scholarship given to one student, with who approved it (and who revoked it, if anyone did). */
class StudentScholarship extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'student_id', 'scholarship_id', 'approved_by', 'approved_at', 'note'];

    protected $casts = ['approved_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class)->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
