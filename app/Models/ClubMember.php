<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubMember extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'club_id', 'student_id', 'role', 'joined_on'];

    protected $casts = ['joined_on' => 'date:Y-m-d'];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class)->withTrashed();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
