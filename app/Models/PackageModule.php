<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageModule extends Model
{
    public $timestamps = false;

    protected $fillable = ['package_id', 'module_slug'];
}
