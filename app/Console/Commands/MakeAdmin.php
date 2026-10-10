<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

use function Laravel\Prompts\password;

class MakeAdmin extends Command
{
    protected $signature = 'make:admin {email : The login email} {--name=Super Admin : The display name}';

    protected $description = 'Create (or reset) a platform super admin who can sign in with a password';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('That does not look like a valid email address.');

            return self::FAILURE;
        }

        $plain = password('Choose a password (at least 8 characters)', required: true, validate: fn (string $v) => strlen($v) < 8 ? 'Use at least 8 characters.' : null);

        $user = User::withTrashed()->firstOrNew(['email' => $email]);
        $user->fill([
            'name' => $user->exists ? $user->name : $this->option('name'),
            'status' => 'active',
            'password' => $plain,
        ]);
        $user->save();

        if ($user->trashed()) {
            $user->restore();
        }

        $user->assignRole(Role::findOrCreate('super-admin', 'web'));

        $this->info("Super admin ready: {$email}");

        return self::SUCCESS;
    }
}
