<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('type');
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->enum('channel', ['in-app', 'email', 'sms', 'push'])->default('in-app');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->foreign('school_id', 'sn_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('user_id',   'sn_user_fk')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_notifications');
    }
};
