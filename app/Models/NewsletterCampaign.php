<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsletterCampaign extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'subject', 'preheader', 'body', 'audience', 'status', 'total', 'created_by', 'started_at', 'finished_at'];

    protected $casts = ['started_at' => 'datetime', 'finished_at' => 'datetime'];

    public function deliveries(): HasMany
    {
        return $this->hasMany(NewsletterDelivery::class, 'campaign_id');
    }
}
