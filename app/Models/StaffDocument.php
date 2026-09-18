<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StaffDocument extends Model
{
    protected $fillable = [
        'school_id', 'staff_id', 'title', 'file_path', 'file_type', 'file_size',
    ];

    protected $appends = ['file_url'];

    public function getFileUrlAttribute(): string
    {
        return Storage::disk('private')->url($this->file_path);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
