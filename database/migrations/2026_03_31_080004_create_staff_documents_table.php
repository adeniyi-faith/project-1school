<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('staff_id')->index();
            $table->string('title', 150);
            $table->string('file_path');
            $table->string('file_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->foreign('school_id', 'staff_docs_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('staff_id',  'staff_docs_staff_fk')->references('id')->on('staff')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_documents');
    }
};
