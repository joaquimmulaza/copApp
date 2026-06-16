<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the database seeders run and populate all tables correctly.
     */
    public function test_database_seeder_populates_tables_correctly(): void
    {
        // Run database seeder
        $this->seed();

        // Assert tables are populated
        $this->assertDatabaseCount('teams', 8);
        $this->assertDatabaseCount('players', 40); // 8 teams * 5 players
        $this->assertDatabaseCount('player_stats', 40);
        $this->assertDatabaseCount('player_statuses', 2); // Vinicius Júnior and Nico Williams
        $this->assertDatabaseCount('fixtures', 4);
        $this->assertDatabaseCount('standings', 8);

        // Assert specific team structure
        $portugal = DB::table('teams')->where('code', 'POR')->first();
        $this->assertNotNull($portugal);
        $this->assertEquals('A', $portugal->group_name);
        
        $venue = json_decode($portugal->venue, true);
        $this->assertEquals('Estádio da Luz', $venue['name']);
        
        $coach = json_decode($portugal->coach, true);
        $this->assertEquals('Roberto Martínez', $coach['name']);

        // Assert player structure
        $ronaldo = DB::table('players')->where('name', 'Cristiano Ronaldo')->first();
        $this->assertNotNull($ronaldo);
        $this->assertEquals('Attacker', $ronaldo->position);
        $this->assertEquals('7', $ronaldo->number);

        // Assert player stats
        $ronaldoStats = DB::table('player_stats')->where('player_id', $ronaldo->id)->first();
        $this->assertNotNull($ronaldoStats);
        $this->assertEquals(4, $ronaldoStats->goals);

        // Assert player status
        $vini = DB::table('players')->where('name', 'Vinícius Júnior')->first();
        $this->assertNotNull($vini);
        $viniStatus = DB::table('player_statuses')->where('player_id', $vini->id)->first();
        $this->assertNotNull($viniStatus);
        $this->assertEquals('injury', $viniStatus->type);
        $this->assertEquals('Thigh Muscle Strain', $viniStatus->reason);
        $this->assertTrue((bool)$viniStatus->is_active);

        // Assert fixture
        $fixture = DB::table('fixtures')->where('api_football_id', 100001)->first();
        $this->assertNotNull($fixture);
        $this->assertEquals('NS', $fixture->status_short);
        $this->assertEquals('Group Stage - 1', $fixture->round);

        // Assert standings
        $portugalStanding = DB::table('standings')->where('team_id', $portugal->id)->first();
        $this->assertNotNull($portugalStanding);
        $this->assertEquals('A', $portugalStanding->group_name);
        $this->assertEquals(0, $portugalStanding->played);
        $this->assertEquals(0, $portugalStanding->points);
    }
}
