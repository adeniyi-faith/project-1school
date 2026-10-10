<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes;

    protected $fillable = [
        'school_id',
        'name',
        'email',
        'supabase_id',
        'phone',
        'avatar',
        'status',
        'password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        // The password and tokens are never written to the audit trail
        return LogOptions::defaults()
            ->logOnly(['school_id', 'name', 'email', 'phone', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('users')
            ->setDescriptionForEvent(fn (string $event) => "User account {$event}");
    }
}
