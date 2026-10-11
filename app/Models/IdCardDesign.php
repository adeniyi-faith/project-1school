<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** How a school's ID cards look */
class IdCardDesign extends Model
{
    use BelongsToSchool, SoftDeletes;

    public const TEMPLATES = ['classic', 'modern', 'band'];
    public const ORIENTATIONS = ['portrait', 'landscape'];

    /** What can be printed, and whether it is on by default */
    public const SHOW = [
        'logo' => true, 'class' => true, 'house' => true, 'date_of_birth' => true, 'blood_group' => true,
        'gender' => false, 'guardian_phone' => true, 'address' => false, 'admission_date' => false,
        'valid_until' => true, 'qr' => true, 'signature' => true, 'back' => true,
    ];

    protected $fillable = [
        'school_id', 'name', 'orientation', 'template', 'primary_color', 'accent_color', 'title', 'show',
        'back_text', 'signer_id', 'valid_months', 'is_default',
    ];

    protected $casts = ['show' => 'array', 'is_default' => 'boolean', 'valid_months' => 'integer'];

    public function signer(): BelongsTo
    {
        return $this->belongsTo(ReportCardSigner::class, 'signer_id');
    }

    /** Every switch, with the defaults filled in for ones never set */
    public function switches(): array
    {
        $saved = is_array($this->show) ? $this->show : [];

        return collect(self::SHOW)->mapWithKeys(fn ($on, $key) => [$key => (bool) ($saved[$key] ?? $on)])->all();
    }

    /** The school's default design, made on first use */
    public static function forSchool(int $schoolId): self
    {
        $design = self::withoutGlobalScopes()->where('school_id', $schoolId)->whereNull('deleted_at')->orderByDesc('is_default')->orderBy('id')->first();

        return $design ?? self::withoutGlobalScopes()->create([
            'school_id' => $schoolId, 'name' => 'Standard', 'orientation' => 'portrait', 'template' => 'classic', 'is_default' => true,
            'title' => 'STUDENT ID CARD', 'show' => self::SHOW,
            'back_text' => "This card belongs to the school named on the front. It must be carried at all times on school grounds and shown when asked.\nIf found, please return it to the school.",
        ]);
    }
}
