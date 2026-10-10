<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffDocument extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'staff_id', 'title', 'file_path', 'file_type', 'file_size',
    ];

    protected $appends = ['file_url'];

    public function getFileUrlAttribute(): string
    {
        // Private files are only served through a download route that checks who is asking
        return route('school.staff.documents.download', $this->id);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
