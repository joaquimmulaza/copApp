<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class StandingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = DB::table('teams')->get();

        // Count to assign initial ranks per group
        $ranks = [];

        foreach ($teams as $team) {
            $group = $team->group_name ?? 'A';

            if (!isset($ranks[$group])) {
                $ranks[$group] = 1;
            } else {
                $ranks[$group]++;
            }

            DB::table('standings')->updateOrInsert(
                [
                    'team_id' => $team->id,
                    'group_name' => $group,
                ],
                [
                    'rank' => $ranks[$group],
                    'played' => 0,
                    'won' => 0,
                    'drawn' => 0,
                    'lost' => 0,
                    'goals_for' => 0,
                    'goals_against' => 0,
                    'goals_diff' => 0,
                    'points' => 0,
                    'form' => null,
                    'status' => 'same',
                    'description' => 'Group Stage',
                    'synced_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
