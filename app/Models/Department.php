<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = ['school_id', 'name', 'code', 'description'];

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
