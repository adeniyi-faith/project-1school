<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->date('date');
            $table->text('description');
            $table->decimal('cost', 10, 2)->default(0);
            $table->string('vendor', 200)->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->timestamps();
            $table->index('school_id');
        });
    }

    public function down(): void { Schema::dropIfExists('asset_maintenance_logs'); }
};
