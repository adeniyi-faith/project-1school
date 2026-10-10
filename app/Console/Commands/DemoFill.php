<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Support\DemoSchool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DemoFill extends Command
{
    protected $signature = 'demo:fill {--fresh : Wipe and refill the demo school even if it already has data}';

    protected $description = 'Fill the public demo school with sample Nigerian school data (only when the demo is turned on)';

    public function handle(): int
    {
        if (! config('app.demo_enabled')) {
            $this->info('The demo is turned off (DEMO_ENABLED is not true), so nothing was filled.');

            return self::SUCCESS;
        }

        ['school' => $school] = DemoSchool::ensure();

        $hasData = DB::table('students')->where('school_id', $school->id)->exists();

        if ($hasData && ! $this->option('fresh')) {
            $this->info('The demo school already has data. Use --fresh to refill it.');

            return self::SUCCESS;
        }

        return $this->call('db:seed', ['--class' => \Database\Seeders\DemoSchoolSeeder::class, '--force' => true]);
    }
}
