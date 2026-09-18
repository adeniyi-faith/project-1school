<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_route', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->string('stop', 200)->nullable();
            $table->boolean('fee_linked')->default(false);
            $table->timestamps();
            $table->unique(['student_id', 'route_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('student_route'); }
};
