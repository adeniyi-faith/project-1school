<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'title', 'file_path', 'file_type', 'file_size',
    ];

    protected $appends = ['file_url'];

    public function getFileUrlAttribute(): string
    {
        // Private files are only served through a download route that checks who is asking
        return route('school.students.documents.download', $this->id);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
