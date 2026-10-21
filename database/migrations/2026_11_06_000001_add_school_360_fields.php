<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support-side facts about a school: tags and an account manager on the school itself,
 * and private notes that only the platform team can see. Each step checks first, so it is safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (! Schema::hasColumn('schools', 'tags')) {
                $table->json('tags')->nullable();
            }
            if (! Schema::hasColumn('schools', 'account_manager_id')) {
                $table->unsignedBigInteger('account_manager_id')->nullable();
            }
        });

        if (! Schema::hasTable('school_notes')) {
            Schema::create('school_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->text('body');
                $table->boolean('pinned')->default(false);
                $table->timestamps();

                $table->index(['school_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_notes');

        Schema::table('schools', function (Blueprint $table) {
            foreach (['tags', 'account_manager_id'] as $column) {
                if (Schema::hasColumn('schools', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
