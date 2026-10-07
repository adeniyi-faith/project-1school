<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StudentDocument extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'title', 'file_path', 'file_type', 'file_size',
    ];

    protected $appends = ['file_url'];

    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
