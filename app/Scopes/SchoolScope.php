<?php

namespace App\Scopes;

use App\Support\ActingSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Super-admin sees all schools, unless they have stepped inside one
        if (auth()->check() && auth()->user()->hasRole('super-admin')) {
            if ($acting = ActingSchool::id()) {
                $builder->where($model->getTable() . '.school_id', $acting);
            }

            return;
        }

        if (auth()->check() && auth()->user()->school_id) {
            $builder->where($model->getTable() . '.school_id', auth()->user()->school_id);
        }
    }
}
