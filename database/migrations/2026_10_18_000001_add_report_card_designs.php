<?php

use App\Support\SchoolDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Report card designs a school can make and give to classes (layout, colours, what shows),
 * and the people who comment and sign on them, with titles the school chooses
 * (Class Teacher, Form Mistress, Head Teacher, Principal, Academic Director, HOD ...).
 *
 * Comments move from the fixed teacher/principal columns in report_card_comments to
 * report_card_remarks, one row per student per signer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_card_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->boolean('is_default')->default(false);
            $table->string('template', 20)->default('classic');   // classic, modern, compact
            $table->string('primary_color', 9)->default('#312e81');
            $table->string('accent_color', 9)->default('#4f46e5');
            $table->string('font_size', 10)->default('normal');   // small, normal, large
            $table->string('paper', 10)->default('a4');           // a4, letter
            $table->string('term_title', 80)->default('Report Card');
            $table->string('session_title', 80)->default('Full-Year Report Card');
            $table->json('options')->nullable();                  // on/off switches for each part of the card
            $table->string('stamp_path')->nullable();             // school stamp image (private disk)
            $table->text('footer_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
        });

        Schema::create('report_card_signers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_card_design_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60);                          // e.g. Class Teacher, Head Teacher
            $table->string('name', 100)->nullable();              // printed under the signature, e.g. Mrs A. Bello
            $table->boolean('has_comment')->default(true);
            $table->string('writer_permission', 40)->default('marks.entry');
            $table->string('signature_path')->nullable();         // signature image (private disk)
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('school_id');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('report_card_design_id')->nullable()->after('grading_scheme_id')->constrained()->nullOnDelete();
        });

        Schema::create('report_card_remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_card_signer_id')->constrained()->cascadeOnDelete();
            $table->text('comment');
            $table->foreignId('written_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('school_id');
            $table->unique(['result_sheet_id', 'student_id', 'report_card_signer_id'], 'report_card_remarks_unique');
        });

        // Every school gets a default design with a Class Teacher and a Principal, and old comments move across
        foreach (DB::table('schools')->pluck('id') as $schoolId) {
            [$teacher, $principal] = SchoolDefaults::ensureReportCardDesign((int) $schoolId);
            $now = now();
            $rows = [];
            foreach (DB::table('report_card_comments')->where('school_id', $schoolId)->get() as $c) {
                foreach ([[$teacher, $c->teacher_comment, $c->teacher_by], [$principal, $c->principal_comment, $c->principal_by]] as [$signer, $text, $by]) {
                    if ($signer && $text !== null && $text !== '') {
                        $rows[] = [
                            'school_id' => $schoolId, 'result_sheet_id' => $c->result_sheet_id, 'student_id' => $c->student_id,
                            'report_card_signer_id' => $signer, 'comment' => $text, 'written_by' => $by,
                            'created_at' => $c->created_at ?? $now, 'updated_at' => $c->updated_at ?? $now,
                        ];
                    }
                }
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('report_card_remarks')->insert($chunk);
            }
        }

        Schema::drop('report_card_comments');
    }

    public function down(): void
    {
        Schema::create('report_card_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->text('teacher_comment')->nullable();
            $table->foreignId('teacher_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('principal_comment')->nullable();
            $table->foreignId('principal_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('school_id');
            $table->unique(['result_sheet_id', 'student_id']);
        });

        // Put back what fits the old two columns: comments by marks.entry signers count as the
        // teacher's, the rest as the principal's (the first of each per student wins)
        $remarks = DB::table('report_card_remarks')
            ->join('report_card_signers', 'report_card_signers.id', '=', 'report_card_remarks.report_card_signer_id')
            ->orderBy('report_card_signers.sort_order')
            ->get(['report_card_remarks.*', 'report_card_signers.writer_permission']);
        $merged = [];
        foreach ($remarks as $r) {
            $key = $r->result_sheet_id.'-'.$r->student_id;
            $merged[$key] ??= ['school_id' => $r->school_id, 'result_sheet_id' => $r->result_sheet_id, 'student_id' => $r->student_id,
                'teacher_comment' => null, 'teacher_by' => null, 'principal_comment' => null, 'principal_by' => null,
                'created_at' => $r->created_at, 'updated_at' => $r->updated_at];
            $who = $r->writer_permission === 'marks.entry' ? 'teacher' : 'principal';
            if ($merged[$key]["{$who}_comment"] === null) {
                $merged[$key]["{$who}_comment"] = $r->comment;
                $merged[$key]["{$who}_by"] = $r->written_by;
            }
        }
        foreach (array_chunk(array_values($merged), 500) as $chunk) {
            DB::table('report_card_comments')->insert($chunk);
        }

        Schema::drop('report_card_remarks');
        Schema::table('classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('report_card_design_id');
        });
        Schema::drop('report_card_signers');
        Schema::drop('report_card_designs');
    }
};
