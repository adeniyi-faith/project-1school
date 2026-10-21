<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'support_ticket_id', 'user_id', 'from_staff', 'internal', 'body'];

    protected $casts = ['from_staff' => 'boolean', 'internal' => 'boolean'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
