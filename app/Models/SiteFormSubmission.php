<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One person's answers to a form. The labels are copied in, so editing the form later never changes what was answered. */
class SiteFormSubmission extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'form_id', 'form_name', 'answers', 'consents', 'newsletter_asked', 'ip', 'page', 'status'];

    protected $casts = ['answers' => 'array', 'consents' => 'array', 'newsletter_asked' => 'boolean'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(SiteForm::class, 'form_id');
    }
}
