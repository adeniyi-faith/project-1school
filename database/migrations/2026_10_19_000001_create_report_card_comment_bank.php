<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saved report card comments a school reuses term after term. Each one is for teachers'
 * or heads' comment boxes, and can be tied to an average range (e.g. 70–100 → "An excellent
 * result ...") so the right comment is filled in for each student automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_card_comment_bank', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $table->string('writer_permission', 40);
            $table->decimal('min_average', 5, 2)->default(0);
            $table->decimal('max_average', 5, 2)->default(100);
            $table->text('comment');
            $table->timestamps();
            $table->index(['school_id', 'writer_permission']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_card_comment_bank');
    }
};
