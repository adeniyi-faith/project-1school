<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('user_id')->nullable();  // parent portal account
            $table->string('name');
            $table->string('relation')->default('Father');      // Father / Mother / Guardian
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('occupation')->nullable();
            $table->text('address')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id', 'guardians_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('user_id',   'guardians_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->index('school_id', 'guardians_school_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
