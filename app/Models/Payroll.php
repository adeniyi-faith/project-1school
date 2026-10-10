<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    use BelongsToSchool, LogsActivity;

    protected $fillable = [
        'school_id', 'staff_id', 'month_year',
        'basic_salary', 'total_allowances', 'total_deductions', 'net_salary',
        'working_days', 'present_days', 'leave_days',
        'allowances_snapshot', 'deductions_snapshot',
        'status', 'paid_on', 'note',
    ];

    protected $casts = [
        'basic_salary'        => 'decimal:2',
        'total_allowances'    => 'decimal:2',
        'total_deductions'    => 'decimal:2',
        'net_salary'          => 'decimal:2',
        'allowances_snapshot' => 'array',
        'deductions_snapshot' => 'array',
        'paid_on'             => 'date',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['staff_id', 'month_year', 'basic_salary', 'total_allowances', 'total_deductions', 'net_salary', 'status', 'paid_on', 'note'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('payroll')
            ->setDescriptionForEvent(fn (string $event) => "Payroll {$event}");
    }
}
