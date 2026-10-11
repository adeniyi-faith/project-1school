<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sports houses (each student belongs to one, houses earn points) and clubs (a student can join several).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);                  // e.g. Red House, Aggrey House
            $table->string('colour', 7)->default('#dc2626');
            $table->string('motto', 150)->nullable();
            $table->foreignId('patron_id')->nullable()->constrained('staff')->nullOnDelete();     // house master/mistress
            $table->foreignId('captain_id')->nullable()->constrained('students')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
        });

        // Points are added line by line (never edited), so the scoreboard can show where they came from
        Schema::create('house_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('house_id')->constrained()->cascadeOnDelete();
            $table->integer('points');                   // can be negative, e.g. for a penalty
            $table->string('reason', 200);
            $table->string('event', 100)->nullable();    // e.g. Inter-house sports 2026
            $table->date('awarded_on');
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('school_id');
            $table->index(['house_id', 'awarded_on']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('house_id')->nullable()->after('section_id')->constrained()->nullOnDelete();
        });

        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);                 // e.g. Jets Club, Debate Society
            $table->string('description', 500)->nullable();
            $table->foreignId('patron_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('meets', 100)->nullable();    // e.g. Wednesdays, 2pm
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
        });

        Schema::create('club_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('role', 40)->default('Member'); // Member, President, Secretary ...
            $table->date('joined_on');
            $table->timestamps();

            $table->index('school_id');
            $table->unique(['club_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_members');
        Schema::dropIfExists('clubs');
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('house_id');
        });
        Schema::dropIfExists('house_points');
        Schema::dropIfExists('houses');
    }
};
