<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Extra details about a member of the platform's own team: which schools they may reach */
class PlatformStaff extends Model
{
    protected $table = 'platform_staff';

    protected $fillable = ['user_id', 'school_scope', 'note', 'created_by'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'platform_staff_schools', 'user_id', 'school_id', 'user_id')->withTimestamps();
    }
}
