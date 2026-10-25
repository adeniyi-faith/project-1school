<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\PlatformAccess;
use App\Support\PlatformSecurityRules;
use Illuminate\Console\Command;

class PlatformResetTwoFactor extends Command
{
    protected $signature = 'platform:reset-2fa {email : The platform person who lost their authenticator app (the owner too)}';

    protected $description = 'Remove a platform person\'s authenticator app and sign them out everywhere; they set it up again at their next sign-in';

    public function handle(): int
    {
        $user = User::where('email', mb_strtolower(trim($this->argument('email'))))->first();

        if (! $user || ! PlatformAccess::isPlatform($user)) {
            $this->error('No platform account has that email.');

            return self::FAILURE;
        }

        PlatformSecurityRules::resetTwoFactor($user);
        PlatformSecurityRules::signOutEverywhere($user);
        activity('platform')->performedOn($user)->withProperties(['via' => 'command line'])->log('Platform second sign-in step reset');

        $this->info("{$user->name} was signed out everywhere and will set up their authenticator app at their next sign-in.");

        return self::SUCCESS;
    }
}
