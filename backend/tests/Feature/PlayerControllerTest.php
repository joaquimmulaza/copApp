<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlayerControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to create a player and their associated stats and status records
     * so that PlayerResource eager loading does not throw null pointer errors.
     */
    private function createPlayer(array $attributes): Player
    {
        $player = Player::create($attributes);

        $player->stats()->create([
            'team_id' => $player->team_id,
        ]);

        $player->status()->create([
            'api_football_id' => $player->api_football_id,
            'team_id' => $player->team_id,
            'type' => 'injury',
        ]);

        return $player;
    }

    /**
     * Test displaying a listing of players without filters.
     */
    public function test_index_returns_paginated_players(): void
    {
        $team = Team::create([
            'api_football_id' => 100,
            'name' => 'Portugal',
            'code' => 'POR',
        ]);

        for ($i = 1; $i <= 25; $i++) {
            $this->createPlayer([
                'api_football_id' => 1000 + $i,
                'team_id' => $team->id,
                'name' => "Player $i",
                'firstname' => 'First',
                'lastname' => "Last $i",
                'position' => 'Midfielder',
                'number' => (string)$i,
            ]);
        }

        $response = $this->getJson('/api/players');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ])
            ->assertJsonCount(20, 'data'); // Paginated at 20
    }

    /**
     * Test filtering players by team_id.
     */
    public function test_index_filters_by_team_id(): void
    {
        $team1 = Team::create(['api_football_id' => 101, 'name' => 'Portugal', 'code' => 'POR']);
        $team2 = Team::create(['api_football_id' => 102, 'name' => 'Argentina', 'code' => 'ARG']);

        for ($i = 1; $i <= 3; $i++) {
            $this->createPlayer([
                'api_football_id' => 2000 + $i,
                'team_id' => $team1->id,
                'name' => "Port Player $i",
                'position' => 'Defender',
            ]);
        }

        for ($i = 1; $i <= 2; $i++) {
            $this->createPlayer([
                'api_football_id' => 3000 + $i,
                'team_id' => $team2->id,
                'name' => "Arg Player $i",
                'position' => 'Attacker',
            ]);
        }

        $response = $this->getJson("/api/players?team_id={$team1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test searching players by name.
     */
    public function test_index_searches_by_name(): void
    {
        $team = Team::create(['api_football_id' => 103, 'name' => 'Portugal', 'code' => 'POR']);

        $this->createPlayer([
            'api_football_id' => 4001,
            'team_id' => $team->id,
            'name' => 'Cristiano Ronaldo',
            'firstname' => 'Cristiano',
            'lastname' => 'Ronaldo',
            'position' => 'Attacker',
        ]);

        $this->createPlayer([
            'api_football_id' => 4002,
            'team_id' => $team->id,
            'name' => 'Bernardo Silva',
            'firstname' => 'Bernardo',
            'lastname' => 'Silva',
            'position' => 'Midfielder',
        ]);

        $response = $this->getJson('/api/players?name=Ronaldo');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Cristiano Ronaldo');
    }

    /**
     * Test that combined team_id filter and name search strictly respects the team_id.
     */
    public function test_index_combined_filters_respects_team_id(): void
    {
        $team1 = Team::create(['api_football_id' => 104, 'name' => 'Portugal', 'code' => 'POR']);
        $team2 = Team::create(['api_football_id' => 105, 'name' => 'Spain', 'code' => 'ESP']);

        // Player with matching name in team 1
        $this->createPlayer([
            'api_football_id' => 5001,
            'team_id' => $team1->id,
            'name' => 'Cristiano Ronaldo',
            'firstname' => 'Cristiano',
            'lastname' => 'Ronaldo',
            'position' => 'Attacker',
        ]);

        // Player with matching name in team 2 (should NOT be returned when filtering for team 1)
        $this->createPlayer([
            'api_football_id' => 5002,
            'team_id' => $team2->id,
            'name' => 'Cristiano Junior',
            'firstname' => 'Cristiano',
            'lastname' => 'Junior',
            'position' => 'Midfielder',
        ]);

        $response = $this->getJson("/api/players?team_id={$team1->id}&name=Cristiano");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Cristiano Ronaldo');
    }

    /**
     * Test that search name input limits and escapes wildcard pattern characters.
     */
    public function test_index_escapes_wildcard_patterns(): void
    {
        $team = Team::create(['api_football_id' => 106, 'name' => 'Portugal', 'code' => 'POR']);

        $this->createPlayer([
            'api_football_id' => 6001,
            'team_id' => $team->id,
            'name' => 'Cristiano Ronaldo',
            'firstname' => 'Cristiano',
            'lastname' => 'Ronaldo',
            'position' => 'Attacker',
        ]);

        $this->createPlayer([
            'api_football_id' => 6002,
            'team_id' => $team->id,
            'name' => 'Vitor %',
            'firstname' => 'Vitor',
            'lastname' => '%',
            'position' => 'Midfielder',
        ]);

        // Search for '%' should NOT return Cristiano Ronaldo, only 'Vitor %'
        $response = $this->getJson('/api/players?name=%');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Vitor %');
    }
}
