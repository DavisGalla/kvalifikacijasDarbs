<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed a user with access to the Filament admin panel.
     *
     * The email is read from ADMIN_EMAIL so each environment can seed its
     * own admin without editing this file; in production, sign in with that
     * Google account to pick up the admin flag.
     */
    public function run(): void
    {
        $email = config('app.admin_email', 'admin@example.com');

        $admin = User::where('email', $email)->first();

        if ($admin) {
            $admin->update(['is_admin' => true]);
        } else {
            User::factory()->create([
                'name' => 'Admin',
                'email' => $email,
                'username' => 'admin',
                'is_admin' => true,
            ]);
        }
    }
}
