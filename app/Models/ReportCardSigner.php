<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Someone who comments on and/or signs a report card, with the title the school uses:
 * Class Teacher, Form Mistress, Head Teacher, Principal, Academic Director, HOD, Proprietor ...
 */
class ReportCardSigner extends Model
{
    use BelongsToSchool;

    /** Who may write this signer's comment */
    public const WRITERS = [
        'marks.entry' => 'Teachers (staff who enter marks)',
        'results.publish' => 'Heads (staff who approve and publish results)',
    ];

    protected $fillable = ['school_id', 'report_card_design_id', 'label', 'name', 'has_comment', 'writer_permission', 'signature_path', 'sort_order'];

    protected $casts = ['has_comment' => 'boolean', 'sort_order' => 'integer'];

    public function design(): BelongsTo
    {
        return $this->belongsTo(ReportCardDesign::class, 'report_card_design_id');
    }

    public function canWrite(?User $user): bool
    {
        return $this->has_comment && $user !== null && $user->can($this->writer_permission);
    }
}
