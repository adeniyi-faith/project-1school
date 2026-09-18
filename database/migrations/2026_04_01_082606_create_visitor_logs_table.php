<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('name', 150);
            $table->string('phone', 25);
            $table->string('purpose', 150);
            $table->string('person_to_meet', 150);
            $table->timestamp('time_in');
            $table->timestamp('time_out')->nullable();
            $table->text('remarks')->nullable();
            $table->foreign('school_id', 'vl_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};
