<?php

use App\Models\School;
use App\Services\StaffAccountService;
use Illuminate\Database\Migrations\Migration;

/** School administrators now belong on the Staff list too (so they can be class or subject teachers). Safe to run twice. */
return new class extends Migration
{
    public function up(): void
    {
        $service = app(StaffAccountService::class);
        School::query()->orderBy('id')->each(fn (School $school) => $service->syncSchool($school->id));
    }

    public function down(): void
    {
        // The staff cards stay; they are ordinary records the school may edit or remove
    }
};
