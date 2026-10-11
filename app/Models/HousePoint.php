<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One award (or penalty) of house points. Lines are added, not edited; a mistake is undone with an opposite line. */
class HousePoint extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'house_id', 'points', 'reason', 'event', 'awarded_on', 'awarded_by'];

    protected $casts = ['points' => 'integer', 'awarded_on' => 'date:Y-m-d'];

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class)->withTrashed();
    }

    public function awarder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }
}
