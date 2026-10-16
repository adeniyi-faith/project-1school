<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A form a school built (prospectus request, job application, parent survey) and showed on its website */
class SiteForm extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'name', 'intro', 'fields', 'consents', 'button_text', 'success_message', 'notify_email', 'newsletter_optin', 'send_to_inquiries', 'active'];

    protected $casts = ['fields' => 'array', 'consents' => 'array', 'newsletter_optin' => 'boolean', 'send_to_inquiries' => 'boolean', 'active' => 'boolean'];

    public function submissions(): HasMany
    {
        return $this->hasMany(SiteFormSubmission::class, 'form_id');
    }
}
