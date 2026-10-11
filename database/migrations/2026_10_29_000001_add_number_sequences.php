<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Safe numbering. Admission numbers, staff IDs, asset codes and old receipt numbers used to be made by
 * counting rows, so two people saving at the same moment (or a deleted row) could give two records the
 * same number. Each school now has a counter per series that only one save can move at a time.
 * Where a school has no repeated numbers already, the database also refuses a repeat from now on.
 */
return new class extends Migration
{
    /** table => column whose values must be unique within a school */
    private const UNIQUE = ['students' => 'admission_no', 'staff' => 'emp_id', 'assets' => 'asset_code'];

    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $table->string('name', 60);              // the number's fixed start, e.g. "ADM-2026-"
            $table->unsignedBigInteger('last')->default(0);
            $table->timestamps();
            $table->unique(['school_id', 'name']);
        });

        foreach (self::UNIQUE as $table => $column) {
            $repeats = DB::table($table)->whereNotNull($column)->get(['school_id', $column])
                ->countBy(fn ($r) => $r->school_id.'|'.$r->{$column})->filter(fn ($n) => $n > 1)->count();
            if ($repeats > 0) {
                // Never renumber real records here: printed cards and files carry these numbers. The counter
                // still stops new repeats; the old ones can be fixed by hand and the index added later.
                echo "  Note: {$table}.{$column} already has {$repeats} repeated number(s), so the database rule was not added.\n";
                continue;
            }
            Schema::table($table, fn (Blueprint $t) => $t->unique(['school_id', $column], "{$table}_school_{$column}_unique"));
        }
    }

    public function down(): void
    {
        foreach (self::UNIQUE as $table => $column) {
            if (Schema::hasIndex($table, "{$table}_school_{$column}_unique")) {
                Schema::table($table, fn (Blueprint $t) => $t->dropUnique("{$table}_school_{$column}_unique"));
            }
        }
        Schema::dropIfExists('number_sequences');
    }
};
