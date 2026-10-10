<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bulk report card downloads (a class or the whole school in one ZIP). The work is split into
 * small steps that the browser asks for one after another, so each request stays short: no
 * background worker is needed and a host's time limit is never reached.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_card_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);          // term | session
            $table->string('layout', 10);        // class (one PDF per class) | student (one PDF per student)
            $table->json('units');               // the steps still to do: [{sheet, students|null}]
            $table->unsignedInteger('next_unit')->default(0);
            $table->unsignedInteger('cards')->default(0);
            $table->string('status', 10)->default('running'); // running | ready | failed
            $table->string('file_path')->nullable();
            $table->string('error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_card_exports');
    }
};
