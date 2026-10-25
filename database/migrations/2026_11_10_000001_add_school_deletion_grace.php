<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A school is deleted after a waiting period, not at once: when, why, who asked, and what it was before. Safe to run twice. */
return new class extends Migration
{
    private array $columns = ['delete_scheduled_at', 'delete_reason', 'status_before_delete', 'delete_requested_by'];

    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (! Schema::hasColumn('schools', 'delete_scheduled_at')) {
                $table->timestamp('delete_scheduled_at')->nullable();
            }
            if (! Schema::hasColumn('schools', 'delete_reason')) {
                $table->string('delete_reason', 500)->nullable();
            }
            if (! Schema::hasColumn('schools', 'status_before_delete')) {
                $table->string('status_before_delete', 12)->nullable();
            }
            if (! Schema::hasColumn('schools', 'delete_requested_by')) {
                $table->unsignedBigInteger('delete_requested_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            foreach ($this->columns as $column) {
                if (Schema::hasColumn('schools', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
