<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A private note about a school, written by platform staff. School users never see these. */
class SchoolNote extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'user_id', 'body', 'pinned'];

    protected $casts = ['pinned' => 'boolean'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
