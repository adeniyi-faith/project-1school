<?php

use App\Services\SchedulerMonitor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Proves the server's cron job is calling the scheduler (shown on Super Admin > Scheduled Tasks)
Schedule::call(fn () => SchedulerMonitor::beat())->everyMinute()->name(SchedulerMonitor::HEARTBEAT);
