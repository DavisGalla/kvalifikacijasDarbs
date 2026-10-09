<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Demo accounts and competitions (with well-known emails and passwords) are only seeded in
     * local and testing environments, never as part of initializing a production database.
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        if (! app()->environment('local', 'testing')) {
            $this->command?->info('Skipping demo data outside the local environment.');

            return;
        }

        $this->call(DemoDataSeeder::class);
    }
}
