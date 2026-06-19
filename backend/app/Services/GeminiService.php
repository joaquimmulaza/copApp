<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Fixture;
use App\Models\GeminiResponseCache;
use App\Models\PlayerStatus;
use App\Models\Standing;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeminiService — Integration layer for the Google Gemini Flash API (RF05).
 *
 * Responsibilities:
 *  1. Build a deterministic SHA-256 cache key from (fixture_id, chip_type).
 *  2. Return cached responses immediately when valid (zero-cost path).
 *  3. On cache miss: fetch real fixture data, standings, and active absences
 *     from our PostgreSQL DB; assemble a structured System/User prompt; call
 *     Gemini Flash; and persist the response with a 10-minute TTL.
 *
 * Constraints (CONTEXT.md §1):
 *  - ONLY model `gemini-1.5-flash` — NEVER gemini-pro in production.
 *  - API key and base URL are read exclusively via config/services.php (from .env).
 *  - All credentials MUST pass through config(); never env() directly in service code.
 *
 * @see CONTEXT.md §12 RF05, §4 .env, §9.2 Migration 9
 */
final class GeminiService
{
    /**
     * The Gemini REST API base URL (v1beta, the stable Flash endpoint).
     */
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta';

    /**
     * Cache TTL in minutes as required by RF05.
     */
    private const CACHE_TTL_MINUTES = 10;

    /**
     * Analyse a fixture for a given chip type, leveraging the DB-backed cache.
     *
     * Returns the Gemini analysis string. If the Google API fails, returns an
     * elegant fallback message so the UI never shows a raw exception.
     *
     * @param  int    $fixtureId  The LOCAL (internal) fixture ID in our database
     * @param  string $chipType   One of: tactical_flash | injury_impact | head2head | guided_bet | recent_form
     */
    public function getAnalysis(int $fixtureId, string $chipType): string
    {
        // ── Step 1: Deterministic cache key (SHA-256 hex = 64 chars, fits cache_key VARCHAR 64) ──
        $cacheKey = hash('sha256', $fixtureId . '_' . $chipType);

        // ── Step 2: Cache lookup (zero-cost path) ──────────────────────────────────────────────
        $cached = GeminiResponseCache::query()
            ->where('cache_key', $cacheKey)
            ->where('expires_at', '>', now())
            ->first();

        if ($cached !== null) {
            Log::info('[GeminiService] Cache HIT', [
                'cache_key'  => $cacheKey,
                'fixture_id' => $fixtureId,
                'chip_type'  => $chipType,
                'expires_at' => $cached->expires_at->toIso8601String(),
            ]);

            return $cached->response;
        }

        Log::info('[GeminiService] Cache MISS — fetching from Gemini API', [
            'cache_key'  => $cacheKey,
            'fixture_id' => $fixtureId,
            'chip_type'  => $chipType,
        ]);

        // ── Step 3: Build context from our PostgreSQL database ────────────────────────────────
        $contextData = $this->gatherContext($fixtureId);

        if ($contextData === null) {
            Log::warning('[GeminiService] Fixture not found — cannot build context', [
                'fixture_id' => $fixtureId,
            ]);

            return $this->buildFallbackResponse('fixture_not_found');
        }

        // ── Step 4: Assemble structured System/User prompt ────────────────────────────────────
        $prompt = $this->buildPrompt($contextData, $chipType);

        // ── Step 5: Call Google Gemini Flash API ──────────────────────────────────────────────
        $analysis = $this->callGeminiApi($prompt, $cacheKey, $fixtureId, $chipType);

        return $analysis;
    }

    // ──────────────────────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────────────────────

    /**
     * Gather all relevant context from our PostgreSQL database:
     *  - The fixture record (with home/away team names, status, kickoff)
     *  - Standings for both teams' groups
     *  - Active absences (injuries + suspensions) for both squads
     *
     * Returns null if the fixture does not exist.
     *
     * @return array<string, mixed>|null
     */
    private function gatherContext(int $fixtureId): ?array
    {
        /** @var Fixture|null $fixture */
        $fixture = Fixture::query()
            ->with(['homeTeam', 'awayTeam'])
            ->find($fixtureId);

        if ($fixture === null) {
            return null;
        }

        $homeTeamId = $fixture->home_team_id;
        $awayTeamId = $fixture->away_team_id;

        // Standings for both teams (may be empty in knockout stage — handle gracefully)
        $standings = Standing::query()
            ->whereIn('team_id', [$homeTeamId, $awayTeamId])
            ->get();

        // Active absences affecting both squads
        $absences = PlayerStatus::query()
            ->with('player')
            ->whereIn('team_id', [$homeTeamId, $awayTeamId])
            ->where('is_active', true)
            ->get();

        return compact('fixture', 'standings', 'absences');
    }

    /**
     * Build the structured System/User prompt (Quiet Luxury tone: sober, analytical, direct).
     *
     * The prompt deliberately uses plain text (no markdown decoration in the instruction)
     * to keep the Gemini response clean and frontend-renderable.
     *
     * @param array<string, mixed> $ctx
     */
    private function buildPrompt(array $ctx, string $chipType): string
    {
        /** @var Fixture $fixture */
        $fixture = $ctx['fixture'];

        $homeName = $fixture->homeTeam?->name ?? 'Home Team';
        $awayName = $fixture->awayTeam?->name ?? 'Away Team';
        $kickoff  = $fixture->kickoff_utc ? $fixture->kickoff_utc->format('Y-m-d H:i') . ' UTC' : 'N/A';
        $stage    = $fixture->stage ?? $fixture->round ?? 'N/A';
        $venue    = trim(($fixture->venue_name ?? '') . ' — ' . ($fixture->venue_city ?? ''));

        // ── Standings block ────────────────────────────────────────────────────────────────
        $standingsText = '';
        /** @var \Illuminate\Database\Eloquent\Collection<int, Standing> $standings */
        $standings = $ctx['standings'];

        if ($standings->isNotEmpty()) {
            $standingsText = "STANDINGS DATA:\n";
            foreach ($standings as $s) {
                $teamName = ($s->team_id === $fixture->home_team_id) ? $homeName : $awayName;
                $standingsText .= sprintf(
                    "  %s (Group %s): Rank %d | P%d W%d D%d L%d | GF:%d GA:%d GD:%+d | Pts:%d | Form:%s\n",
                    $teamName,
                    $s->group_name,
                    $s->rank,
                    $s->played,
                    $s->won,
                    $s->drawn,
                    $s->lost,
                    $s->goals_for,
                    $s->goals_against,
                    $s->goals_diff,
                    $s->points,
                    $s->form ?? '—',
                );
            }
        } else {
            $standingsText = "STANDINGS DATA: Not available (knockout stage or pre-tournament).\n";
        }

        // ── Absences block ──────────────────────────────────────────────────────────────────
        /** @var \Illuminate\Database\Eloquent\Collection<int, PlayerStatus> $absences */
        $absences = $ctx['absences'];

        $absencesText = '';
        if ($absences->isNotEmpty()) {
            $absencesText = "ACTIVE ABSENCES:\n";
            foreach ($absences as $ps) {
                $teamName   = ($ps->team_id === $fixture->home_team_id) ? $homeName : $awayName;
                $playerName = $ps->player?->name ?? 'Unknown Player';
                $position   = $ps->player?->position ?? '—';
                $absencesText .= sprintf(
                    "  [%s] %s | %s | Type: %s | Reason: %s\n",
                    $teamName,
                    $playerName,
                    $position,
                    ucfirst($ps->type),
                    $ps->reason ?? '—',
                );
            }
        } else {
            $absencesText = "ACTIVE ABSENCES: None reported for either squad.\n";
        }

        // ── Chip-specific instruction ───────────────────────────────────────────────────────
        $chipInstruction = $this->resolveChipInstruction($chipType, $homeName, $awayName);

        // ── Full prompt assembly ────────────────────────────────────────────────────────────
        $systemInstruction = <<<SYSTEM
You are a professional football tactical analyst. Your role is to provide concise, data-driven,
technically precise match analyses. Tone: sober and direct, never hyperbolic. No fan language,
no slogans. Use third-person references to teams and players. Keep responses between 150 and 300
words. Structure the response in 2-3 short paragraphs. No bullet lists unless explicitly requested.
SYSTEM;

        $userMessage = <<<USER
MATCH CONTEXT:
  Home: {$homeName}
  Away: {$awayName}
  Stage: {$stage}
  Kickoff: {$kickoff}
  Venue: {$venue}

{$standingsText}
{$absencesText}

ANALYSIS REQUESTED:
{$chipInstruction}
USER;

        return json_encode([
            'system_instruction' => ['parts' => [['text' => $systemInstruction]]],
            'user_message'       => $userMessage,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Resolve the chip-specific analytical instruction.
     */
    private function resolveChipInstruction(string $chipType, string $homeName, string $awayName): string
    {
        return match ($chipType) {
            'tactical_flash'  => "Based exclusively on the data above, provide a tactical flash analysis of {$homeName} vs {$awayName}. Focus on likely formations, pressing strategies, and key match-ups.",
            'injury_impact'   => "Analyse the tactical impact of the listed absences on {$homeName} and {$awayName}. Identify which positions are most affected and how each team may adapt.",
            'head2head'       => "Based on the standings and form data, analyse the competitive dynamics between {$homeName} and {$awayName}. Identify which team holds a structural advantage entering this fixture.",
            'guided_bet'      => "Using only the objective data provided, assess the statistical probability distribution of this match outcome. Reference goal averages, form, and absences. Do not make a betting recommendation.",
            'recent_form'     => "Analyse the recent form sequences for {$homeName} and {$awayName} using the standings data. Identify momentum trends and how they may influence this fixture.",
            default           => "Provide a general pre-match technical analysis of {$homeName} vs {$awayName} based strictly on the data provided.",
        };
    }

    /**
     * Call the Gemini 1.5 Flash REST API, persist the result to cache, and return the text.
     * On any API failure, log the error and return an elegant fallback string.
     */
    private function callGeminiApi(
        string $promptJson,
        string $cacheKey,
        int    $fixtureId,
        string $chipType,
    ): string {
        /** @var string $apiKey */
        $apiKey = config('services.gemini.key');
        /** @var string $model */
        $model  = config('services.gemini.model', 'gemini-1.5-flash');

        if (empty($apiKey)) {
            Log::error('[GeminiService] GEMINI_API_KEY is not configured. Cannot proceed.');

            return $this->buildFallbackResponse('api_key_missing');
        }

        $promptData = json_decode($promptJson, true, 512, JSON_THROW_ON_ERROR);

        $requestBody = [
            'system_instruction' => $promptData['system_instruction'],
            'contents'           => [
                [
                    'role'  => 'user',
                    'parts' => [['text' => $promptData['user_message']]],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => (int) config('services.gemini.max_tokens', 1024),
                'temperature'     => 0.4,  // Factual/analytical mode — lower temperature
                'topP'            => 0.9,
            ],
        ];

        $url = self::API_BASE . "/models/{$model}:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout(30)
                ->retry(2, 1000)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $requestBody);

            $response->throw(); // Throws RequestException on 4xx/5xx

            $data = $response->json();

            // Extract the response text from the Gemini API response structure
            /** @var string|null $analysisText */
            $analysisText = data_get($data, 'candidates.0.content.parts.0.text');

            if (empty($analysisText)) {
                Log::warning('[GeminiService] Gemini returned an empty or malformed response', [
                    'fixture_id' => $fixtureId,
                    'chip_type'  => $chipType,
                    'response'   => $data,
                ]);

                return $this->buildFallbackResponse('empty_response');
            }

            // Token usage for cost tracking / audit
            $tokensUsed = (int) data_get($data, 'usageMetadata.candidatesTokenCount', 0);

            // ── Persist to cache with 10-minute TTL ─────────────────────────────────────
            // Correção SEC-02: Race Condition na escrita da cache
            GeminiResponseCache::updateOrCreate(
                ['cache_key' => $cacheKey],
                [
                    'fixture_id_api' => $fixtureId,
                    'chip_type'      => $chipType,
                    'response'       => $analysisText,
                    'tokens_used'    => $tokensUsed > 0 ? $tokensUsed : null,
                    'expires_at'     => now()->addMinutes(self::CACHE_TTL_MINUTES),
                    'created_at'     => now(),
                ]
            );

            Log::info('[GeminiService] Response cached successfully', [
                'cache_key'   => $cacheKey,
                'fixture_id'  => $fixtureId,
                'chip_type'   => $chipType,
                'tokens_used' => $tokensUsed,
                'expires_at'  => now()->addMinutes(self::CACHE_TTL_MINUTES)->toIso8601String(),
            ]);

            return $analysisText;

        } catch (RequestException $e) {
            Log::error('[GeminiService] Gemini API returned an HTTP error', [
                'fixture_id'  => $fixtureId,
                'chip_type'   => $chipType,
                'status'      => $e->response->status(),
                'body'        => $e->response->body(),
            ]);

            return $this->buildFallbackResponse('api_http_error');

        } catch (ConnectionException $e) {
            Log::error('[GeminiService] Could not connect to Gemini API', [
                'fixture_id' => $fixtureId,
                'chip_type'  => $chipType,
                'message'    => $e->getMessage(),
            ]);

            return $this->buildFallbackResponse('connection_error');

        } catch (\Throwable $e) {
            Log::error('[GeminiService] Unexpected error during Gemini API call', [
                'fixture_id' => $fixtureId,
                'chip_type'  => $chipType,
                'exception'  => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return $this->buildFallbackResponse('unexpected_error');
        }
    }

    /**
     * Return an elegant, user-facing fallback message for any failure scenario.
     * These strings are intentional — they keep the UI functional without exposing internals.
     */
    private function buildFallbackResponse(string $reason): string
    {
        return match ($reason) {
            'fixture_not_found' => 'The requested match data is not available in our database. Please ensure the fixture has been synchronised.',
            'api_key_missing'   => 'Tactical analysis is temporarily unavailable. The service is being configured.',
            'empty_response'    => 'The analysis engine returned an incomplete response. Please retry in a few moments.',
            'api_http_error'    => 'The analysis service is experiencing a temporary disruption. Please try again shortly.',
            'connection_error'  => 'Unable to reach the analysis engine. Please check your connection and retry.',
            default             => 'Tactical analysis is temporarily unavailable. Our team has been notified.',
        };
    }
}
