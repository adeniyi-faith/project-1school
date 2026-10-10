<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fee payments used Bangladeshi mobile-money methods (bKash, Nagad, Rocket) and
     * schools defaulted to Bangladesh. Move both to Nigerian defaults: naira,
     * Africa/Lagos, and bank transfer / POS / USSD payment methods.
     */
    public function up(): void
    {
        // The method column was an enum, which on Postgres is a varchar plus a CHECK constraint.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE fee_payments DROP CONSTRAINT IF EXISTS fee_payments_method_check');
        }

        Schema::table('fee_payments', function ($table) {
            $table->string('method', 30)->default('cash')->change();
        });

        DB::table('fee_payments')
            ->whereIn('method', ['bkash', 'nagad', 'rocket'])
            ->update(['method' => 'online']);

        Schema::table('schools', function ($table) {
            $table->string('country')->default('NG')->change();
            $table->string('timezone')->default('Africa/Lagos')->change();
            $table->string('currency')->default('NGN')->change();
        });
    }

    public function down(): void
    {
        Schema::table('schools', function ($table) {
            $table->string('country')->default('BD')->change();
            $table->string('timezone')->default('Asia/Dhaka')->change();
            $table->string('currency')->default('BDT')->change();
        });
    }
};
