<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per member of the platform's own team (support, finance, sales ...). The login itself is a normal user.
        if (! Schema::hasTable('platform_staff')) {
            Schema::create('platform_staff', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                // 'all' = every school, 'assigned' = only the schools listed in platform_staff_schools
                $table->string('school_scope', 12)->default('all');
                $table->string('note', 255)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('platform_staff_schools')) {
            Schema::create('platform_staff_schools', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'school_id']);
                $table->index('school_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_staff_schools');
        Schema::dropIfExists('platform_staff');
    }
};
