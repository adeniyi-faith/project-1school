<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One signer's comment on one student's term report card. */
class ReportCardRemark extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'result_sheet_id', 'student_id', 'report_card_signer_id', 'comment', 'written_by'];

    public function signer(): BelongsTo
    {
        return $this->belongsTo(ReportCardSigner::class, 'report_card_signer_id');
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(ResultSheet::class, 'result_sheet_id');
    }
}
