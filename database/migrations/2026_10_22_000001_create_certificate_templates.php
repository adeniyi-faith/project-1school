<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How a school's testimonials and transfer certificates look and read: border style,
 * colours, title, the wording (with blanks such as {name}), and whether a QR code is printed.
 * One per school and type. Each issued certificate keeps a copy, so changing the design
 * later never changes a certificate already given out.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('border', 20)->default('classic');
            $table->string('primary_color', 7)->nullable();
            $table->string('accent_color', 7)->nullable();
            $table->string('title', 80);
            $table->text('body');
            $table->string('closing', 300)->nullable();
            $table->boolean('show_details')->default(true);
            $table->boolean('show_qr')->default(true);
            $table->boolean('show_watermark')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};
