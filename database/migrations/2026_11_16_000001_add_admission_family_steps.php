<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_inquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('admission_inquiries', 'reference')) {
                $table->string('reference', 30)->nullable()->index();
            }
            if (! Schema::hasColumn('admission_inquiries', 'student_gender')) {
                $table->string('student_gender', 10)->nullable();
            }
            if (! Schema::hasColumn('admission_inquiries', 'student_dob')) {
                $table->date('student_dob')->nullable();
            }
            if (! Schema::hasColumn('admission_inquiries', 'family_accepted_at')) {
                $table->timestamp('family_accepted_at')->nullable();
            }
        });

        // Give every earlier application a reference the family can quote
        foreach (DB::table('admission_inquiries')->whereNull('reference')->get(['id', 'created_at']) as $row) {
            $year = $row->created_at ? substr((string) $row->created_at, 0, 4) : date('Y');
            DB::table('admission_inquiries')->where('id', $row->id)->update(['reference' => 'APP-'.$year.'-'.str_pad((string) $row->id, 4, '0', STR_PAD_LEFT)]);
        }
    }

    public function down(): void
    {
        Schema::table('admission_inquiries', function (Blueprint $table) {
            foreach (['reference', 'student_gender', 'student_dob', 'family_accepted_at'] as $column) {
                if (Schema::hasColumn('admission_inquiries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
