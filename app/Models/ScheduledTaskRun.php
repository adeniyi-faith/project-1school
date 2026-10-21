<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One run of a scheduled task, kept for two weeks so the platform owner can see what ran, when and whether it failed */
class ScheduledTaskRun extends Model
{
    protected $fillable = ['name', 'expression', 'started_at', 'finished_at', 'duration_ms', 'status', 'output'];

    protected $casts = ['started_at' => 'datetime', 'finished_at' => 'datetime'];
}
