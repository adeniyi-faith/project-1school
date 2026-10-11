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
            $this->info('The demo school already has its students; refreshing the extras (hostels, buses, store, front desk, notices, lessons, tests, houses and clubs).');

            return $this->extras();
        }

        $code = $this->call('db:seed', ['--class' => \Database\Seeders\DemoSchoolSeeder::class, '--force' => true]);

        return $code ?: $this->extras();
    }

    /** The side departments first, then the richer screens (lessons, tests, houses ...) that sit on top of them */
    private function extras(): int
    {
        return $this->call('db:seed', ['--class' => \Database\Seeders\DemoExtrasSeeder::class, '--force' => true])
            ?: $this->call('db:seed', ['--class' => \Database\Seeders\DemoRichSeeder::class, '--force' => true]);
    }
}
