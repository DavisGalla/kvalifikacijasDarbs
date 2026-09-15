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
     */
    public function run(): void
    {
        // User::factory(10)->create();

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
