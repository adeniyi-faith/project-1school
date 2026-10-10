<?php

namespace App\Models;

use App\Support\SchoolDefaults;
use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * How a school's report cards look: layout, colours, titles, which parts show,
 * and who comments and signs. A class uses its own design, else the school's default.
 */
class ReportCardDesign extends Model
{
    use BelongsToSchool, SoftDeletes;

    public const TEMPLATES = ['classic', 'modern', 'compact'];
    public const FONT_SIZES = ['small', 'normal', 'large'];
    public const PAPERS = ['a4', 'letter'];

    /** Every on/off switch and what it is when the school has not chosen */
    public const DEFAULT_OPTIONS = [
        'show_logo' => true,
        'logo_watermark' => false,
        'show_motto' => true,
        'show_address' => true,
        'show_photo' => false,
        'show_age' => true,
        'show_parts' => true,             // CA1, CA2, Exam ... columns
        'show_subject_position' => true,
        'show_class_stats' => true,       // class average, highest, lowest per subject
        'show_remarks' => true,           // Excellent, Very good ... per subject
        'show_overall_position' => true,
        'show_class_average' => true,
        'show_behaviour' => true,
        'show_skills' => true,
        'show_attendance' => true,
        'show_next_term' => true,
        'show_fees_owed' => false,
        'show_grade_key' => true,
        'show_signatures' => true,
    ];

    protected $fillable = [
        'school_id', 'name', 'is_default', 'template', 'primary_color', 'accent_color', 'font_size', 'paper',
        'term_title', 'session_title', 'options', 'stamp_path', 'footer_note',
    ];

    protected $casts = ['is_default' => 'boolean', 'options' => 'array'];

    public function signers(): HasMany
    {
        return $this->hasMany(ReportCardSigner::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Every switch, with defaults filled in for any the school has not set */
    public function settings(): array
    {
        return array_merge(self::DEFAULT_OPTIONS, array_intersect_key($this->options ?? [], self::DEFAULT_OPTIONS));
    }

    /** The design a class prints with: its own, else the school default (made if missing). */
    public static function forClass(int $schoolId, ?int $classId): self
    {
        $id = $classId ? SchoolClass::withTrashed()->whereKey($classId)->value('report_card_design_id') : null;

        $design = ($id ? static::where('school_id', $schoolId)->find($id) : null)
            ?? static::where('school_id', $schoolId)->orderByDesc('is_default')->orderBy('id')->first();

        if (! $design) {
            SchoolDefaults::ensureReportCardDesign($schoolId);
            $design = static::where('school_id', $schoolId)->orderByDesc('is_default')->orderBy('id')->firstOrFail();
        }

        return $design;
    }
}
