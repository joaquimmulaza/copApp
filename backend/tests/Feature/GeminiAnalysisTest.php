<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class GeminiAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure fake gemini service key and model
        Config::set('services.gemini.key', 'fake-gemini-key');
        Config::set('services.gemini.model', 'gemini-1.5-flash');
    }

    /**
     * Test that the GeminiService query parameters are passed in a clean, isolated way.
     */
    public function test_gemini_api_call_uses_query_parameters_and_hides_key_from_base_url(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'This is a simulated tactical analysis.']
                            ]
                        ]
                    ]
                ],
                'usageMetadata' => [
                    'candidatesTokenCount' => 120
                ]
            ], 200)
        ]);

        $home = Team::create([
            'api_football_id' => 1,
            'name' => 'Portugal',
            'code' => 'POR',
        ]);

        $away = Team::create([
            'api_football_id' => 2,
            'name' => 'Spain',
            'code' => 'ESP',
        ]);

        $fixture = Fixture::create([
            'api_football_id' => 10,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'kickoff_utc' => now(),
            'status_short' => 'NS',
        ]);

        $response = $this->getJson("/api/fixtures/{$fixture->id}/analysis?chip=tactical_flash");

        $response->assertStatus(200)
            ->assertJson([
                'fixture_id' => $fixture->id,
                'chip_type' => 'tactical_flash',
                'analysis' => 'This is a simulated tactical analysis.',
            ]);

        Http::assertSent(function ($request) {
            $parsed = parse_url($request->url());
            $queryParams = [];
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $queryParams);
            }

            return str_starts_with($request->url(), 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent')
                && ($queryParams['key'] ?? null) === 'fake-gemini-key';
        });
    }
}
