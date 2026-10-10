<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('tracking_token', 64)->nullable()->unique()->after('status');
        });

        // Give every existing vehicle its own secret so its GPS device can sign in
        DB::table('vehicles')->whereNull('tracking_token')->pluck('id')->each(
            fn ($id) => DB::table('vehicles')->where('id', $id)->update(['tracking_token' => Str::random(40)])
        );
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('tracking_token');
        });
    }
};
