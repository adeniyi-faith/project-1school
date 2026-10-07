<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BookReservation extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'book_id', 'member_type', 'member_id', 'reserved_date', 'status',
    ];

    protected $casts = [
        'reserved_date' => 'date',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function member(): MorphTo
    {
        return $this->morphTo();
    }
}
