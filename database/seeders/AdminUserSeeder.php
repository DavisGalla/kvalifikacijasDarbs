<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the first Filament admin account for ADMIN_EMAIL.
     *
     * Safe to run against a database that is already in use: it only creates a new account, and
     * only while no admin exists. It never grants admin rights to an existing account, so a
     * mistyped or reused ADMIN_EMAIL cannot silently promote a regular user. Existing accounts
     * are promoted explicitly with `php artisan user:set-admin {email}`.
     */
    public function run(): void
    {
        $email = config('app.admin_email');

        if (blank($email)) {
            $this->command?->warn('ADMIN_EMAIL is not set; no admin account was created.');

            return;
        }

        if (User::withTrashed()->where('is_admin', true)->exists()) {
            $this->command?->info('An admin account already exists; skipping.');

            return;
        }

        if (User::withTrashed()->where('email', $email)->exists()) {
            $this->command?->warn("An account for {$email} already exists and was not promoted. Run `php artisan user:set-admin {$email}` to grant it admin access.");

            return;
        }

        User::factory()->create([
            'name' => 'Admin',
            'email' => $email,
            'username' => 'admin',
            'is_admin' => true,
        ]);

        $this->command?->info("Created admin account {$email}. Sign in with that Google account to use /admin.");
    }
}
