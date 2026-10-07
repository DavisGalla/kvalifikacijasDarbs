<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetUserAdmin extends Command
{
    protected $signature = 'user:set-admin {email} {--revoke : Remove admin access instead of granting it}';

    protected $description = 'Grant or revoke access to the Filament admin panel for a user';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No user found with email [{$this->argument('email')}].");

            return self::FAILURE;
        }

        $user->update(['is_admin' => ! $this->option('revoke')]);

        $this->info($this->option('revoke')
            ? "Revoked admin access for {$user->email}."
            : "Granted admin access to {$user->email}.");

        return self::SUCCESS;
    }
}
