<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'staff_id', 'basic_salary', 'allowances', 'deductions', 'is_active',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'allowances'   => 'array',
        'deductions'   => 'array',
        'is_active'    => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function getTotalAllowancesAttribute(): float
    {
        return collect($this->allowances ?? [])->sum('amount');
    }

    public function getTotalDeductionsAttribute(): float
    {
        return collect($this->deductions ?? [])->sum('amount');
    }

    public function getGrossSalaryAttribute(): float
    {
        return (float) $this->basic_salary + $this->total_allowances;
    }

    public function getNetSalaryAttribute(): float
    {
        return $this->gross_salary - $this->total_deductions;
    }
}
