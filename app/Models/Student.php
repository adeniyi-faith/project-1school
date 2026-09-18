<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'user_id', 'class_id', 'section_id', 'guardian_id',
        'admission_no', 'roll_no', 'first_name', 'last_name',
        'gender', 'date_of_birth', 'blood_group', 'religion',
        'nationality', 'phone', 'email', 'address', 'photo',
        'category', 'status', 'admission_date', 'previous_school',
    ];

    protected $casts = [
        'date_of_birth'  => 'date',
        'admission_date' => 'date',
    ];

    protected $appends = ['full_name', 'photo_url'];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::url($this->photo) : null;
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Student $student) {
            if (empty($student->admission_no)) {
                $year  = now()->format('Y');
                $count = static::withoutGlobalScopes()->where('school_id', $student->school_id)->count() + 1;
                $student->admission_no = "ADM-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
