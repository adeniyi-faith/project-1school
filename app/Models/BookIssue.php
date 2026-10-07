<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BookIssue extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'book_id', 'member_type', 'member_id',
        'issued_date', 'due_date', 'returned_date', 'fine', 'fine_per_day', 'status', 'note',
    ];

    protected $casts = [
        'issued_date'   => 'date',
        'due_date'      => 'date',
        'returned_date' => 'date',
        'fine'          => 'decimal:2',
        'fine_per_day'  => 'decimal:2',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function member(): MorphTo
    {
        return $this->morphTo();
    }

    public function getOverdueDaysAttribute(): int
    {
        if ($this->status === 'returned' && $this->returned_date) {
            return max(0, $this->due_date->diffInDays($this->returned_date, false));
        }
        if ($this->status !== 'returned') {
            return max(0, (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false));
        }
        return 0;
    }
}
