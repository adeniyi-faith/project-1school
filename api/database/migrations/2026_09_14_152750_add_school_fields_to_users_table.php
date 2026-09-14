<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Links this row to the Supabase login that owns it — Supabase
            // handles the actual password, this table only handles roles
            // and school membership.
            $table->uuid('supabase_user_id')->unique()->after('id');
            $table->foreignId('school_id')->nullable()->after('supabase_user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->after('school_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn(['supabase_user_id', 'role']);
        });
    }
};
