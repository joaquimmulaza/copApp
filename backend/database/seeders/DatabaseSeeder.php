<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Run World Cup 2026 development seeders
        $this->call([
            TeamSeeder::class,
            PlayerSeeder::class,
            StandingSeeder::class,
            FixtureSeeder::class,
        ]);
    }
}
