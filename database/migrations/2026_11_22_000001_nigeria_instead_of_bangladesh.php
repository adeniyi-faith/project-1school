<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Early versions assumed Bangladesh. Make Nigeria the starting choice for new
     * students and staff, and fix the rows that only got "Bangladeshi" / Dhaka / BDT
     * because nobody chose anything else.
     */
    public function up(): void
    {
        Schema::table('students', function ($table) {
            $table->string('nationality')->default('Nigerian')->change();
        });
        Schema::table('staff', function ($table) {
            $table->string('nationality', 50)->nullable()->default('Nigerian')->change();
        });

        DB::table('students')->where('nationality', 'Bangladeshi')->update(['nationality' => 'Nigerian']);
        DB::table('staff')->where('nationality', 'Bangladeshi')->update(['nationality' => 'Nigerian']);

        DB::table('schools')->where('country', 'BD')->update(['country' => 'NG']);
        DB::table('schools')->where('timezone', 'Asia/Dhaka')->update(['timezone' => 'Africa/Lagos']);
        DB::table('schools')->where('currency', 'BDT')->update(['currency' => 'NGN']);
    }

    public function down(): void
    {
        Schema::table('students', function ($table) {
            $table->string('nationality')->default('Bangladeshi')->change();
        });
        Schema::table('staff', function ($table) {
            $table->string('nationality', 50)->nullable()->default('Bangladeshi')->change();
        });
    }
};
