<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo users, teams and competitions for local development. Run on its own with
 * `php artisan db:seed --class=DemoDataSeeder`; DatabaseSeeder only calls it locally.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('Refusing to seed demo data in production.');

            return;
        }

        $testUser = User::where('email', 'test@example.com')->first();

        if ($testUser) {
            $testUser->update([
                'name' => 'Test User',
                'username' => 'testuser',
            ]);
        } else {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'username' => 'testuser',
            ]);
        }

        $this->call(CompetitionSeeder::class);
    }
}
