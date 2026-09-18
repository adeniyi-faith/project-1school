<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry_followups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inquiry_id')->index();
            $table->unsignedBigInteger('staff_id')->index();
            $table->text('note');
            $table->date('next_date')->nullable();
            $table->foreign('inquiry_id', 'if_inquiry_fk')->references('id')->on('admission_inquiries')->cascadeOnDelete();
            $table->foreign('staff_id',   'if_staff_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_followups');
    }
};
