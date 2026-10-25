<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A platform person's second sign-in step (authenticator app) and forced password change flag */
class PlatformSecurity extends Model
{
    protected $table = 'platform_security';

    protected $fillable = ['user_id', 'totp_secret', 'totp_confirmed_at', 'totp_last_step', 'recovery_codes', 'must_change_password', 'password_changed_at'];

    protected $hidden = ['totp_secret', 'recovery_codes'];

    protected function casts(): array
    {
        return [
            'totp_secret' => 'encrypted',
            'recovery_codes' => 'encrypted:array',
            'totp_confirmed_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function for(User|int $user): self
    {
        return static::firstOrCreate(['user_id' => $user instanceof User ? $user->id : $user]);
    }

    public function isEnrolled(): bool
    {
        return $this->totp_confirmed_at !== null && $this->totp_secret !== null;
    }
}
