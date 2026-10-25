<?php

namespace App\Scopes;

use App\Support\ActingSchool;
use App\Support\PlatformAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Super-admin sees all schools, unless they have stepped inside one
        if (auth()->check() && PlatformAccess::isPlatform(auth()->user())) {
            if ($acting = ActingSchool::id()) {
                $builder->where($model->getTable() . '.school_id', $acting);
            } elseif (($ids = PlatformAccess::schoolIds(auth()->user())) !== null) {
                // Platform staff limited to some schools never see the others, even in counts
                $builder->whereIn($model->getTable() . '.school_id', $ids);
            }

            return;
        }

        if (auth()->check() && auth()->user()->school_id) {
            $builder->where($model->getTable() . '.school_id', auth()->user()->school_id);
        }
    }
}
