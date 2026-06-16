<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class FixtureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get mapping from api_football_id to database id
        $teamsMap = DB::table('teams')->pluck('id', 'api_football_id');

        $fixtures = [
            [
                'api_football_id' => 100001,
                'home_team_id' => $teamsMap[27] ?? null, // Portugal
                'away_team_id' => $teamsMap[6] ?? null,  // Brasil
                'round' => 'Group Stage - 1',
                'stage' => 'group',
                'group_name' => 'A',
                'venue_name' => 'MetLife Stadium',
                'venue_city' => 'East Rutherford',
                'kickoff_utc' => '2026-06-15 18:00:00',
                'status_short' => 'NS',
                'status_long' => 'Not Started',
                'home_score' => null,
                'away_score' => null,
                'elapsed_minutes' => null,
                'lineup_confirmed' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'api_football_id' => 100002,
                'home_team_id' => $teamsMap[16] ?? null, // Angola
                'away_team_id' => $teamsMap[26] ?? null, // Argentina
                'round' => 'Group Stage - 1',
                'stage' => 'group',
                'group_name' => 'B',
                'venue_name' => 'SoFi Stadium',
                'venue_city' => 'Inglewood',
                'kickoff_utc' => '2026-06-16 20:00:00',
                'status_short' => 'NS',
                'status_long' => 'Not Started',
                'home_score' => null,
                'away_score' => null,
                'elapsed_minutes' => null,
                'lineup_confirmed' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'api_football_id' => 100003,
                'home_team_id' => $teamsMap[2] ?? null,  // França
                'away_team_id' => $teamsMap[9] ?? null,  // Espanha
                'round' => 'Group Stage - 1',
                'stage' => 'group',
                'group_name' => 'C',
                'venue_name' => 'Mercedes-Benz Stadium',
                'venue_city' => 'Atlanta',
                'kickoff_utc' => '2026-06-17 15:00:00',
                'status_short' => 'NS',
                'status_long' => 'Not Started',
                'home_score' => null,
                'away_score' => null,
                'elapsed_minutes' => null,
                'lineup_confirmed' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'api_football_id' => 100004,
                'home_team_id' => $teamsMap[25] ?? null, // Alemanha
                'away_team_id' => $teamsMap[2384] ?? null, // Estados Unidos
                'round' => 'Group Stage - 1',
                'stage' => 'group',
                'group_name' => 'D',
                'venue_name' => 'Hard Rock Stadium',
                'venue_city' => 'Miami',
                'kickoff_utc' => '2026-06-18 21:00:00',
                'status_short' => 'NS',
                'status_long' => 'Not Started',
                'home_score' => null,
                'away_score' => null,
                'elapsed_minutes' => null,
                'lineup_confirmed' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($fixtures as $fixture) {
            if ($fixture['home_team_id'] && $fixture['away_team_id']) {
                DB::table('fixtures')->updateOrInsert(
                    ['api_football_id' => $fixture['api_football_id']],
                    $fixture
                );
            }
        }
    }
}
