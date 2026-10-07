<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('name', 100);
            $table->string('code', 20)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id', 'departments_school_fk')
                  ->references('id')->on('schools')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
