<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A read-only key for the school's own scripts. Only a fingerprint is stored; the key itself is shown once. */
class SchoolApiToken extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'name', 'token_hash', 'last_four', 'created_by', 'last_used_at'];

    protected $hidden = ['token_hash'];

    protected $casts = ['last_used_at' => 'datetime'];
}
