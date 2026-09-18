<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('package_id')->index();
            $table->string('module_slug', 60); // e.g. library, hostel, transport
            $table->foreign('package_id', 'pm_package_fk')->references('id')->on('packages')->cascadeOnDelete();
            $table->unique(['package_id', 'module_slug'], 'pm_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_modules');
    }
};
