<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Staff extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'staff';

    protected $fillable = [
        'school_id', 'user_id', 'department_id', 'designation_id',
        'emp_id', 'first_name', 'last_name', 'gender',
        'date_of_birth', 'blood_group', 'religion', 'nationality',
        'phone', 'email', 'address', 'photo',
        'joining_date', 'salary_type', 'salary', 'status', 'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date'  => 'date',
        'salary'        => 'decimal:2',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StaffDocument::class);
    }

    public function salaryStructure(): HasOne
    {
        return $this->hasOne(SalaryStructure::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Staff $staff) {
            if (empty($staff->emp_id)) {
                $year  = now()->format('Y');
                $count = static::withoutGlobalScopes()->where('school_id', $staff->school_id)->count() + 1;
                $staff->emp_id = "EMP-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
