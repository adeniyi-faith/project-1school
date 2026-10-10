<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Something rated each term apart from subjects: behaviour (affective) or skills (psychomotor). */
class BehaviourTrait extends Model
{
    use BelongsToSchool, SoftDeletes;

    public const DOMAINS = ['affective', 'psychomotor'];

    protected $fillable = ['school_id', 'name', 'domain', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];
}
