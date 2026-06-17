<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * FixtureResource — JSON representation of a single fixture.
 *
 * Exposes game data with formatted scores and simplified team payloads
 * (home & away) loaded via eager loading to prevent N+1 queries.
 *
 * @see CONTEXT.md §12 RF03
 *
 * @property int         $id
 * @property int         $api_football_id
 * @property \App\Models\Team $homeTeam
 * @property \App\Models\Team $awayTeam
 * @property string|null $round
 * @property string|null $stage
 * @property string|null $group_name
 * @property string|null $venue_name
 * @property string|null $venue_city
 * @property string      $kickoff_utc
 * @property string      $status_short
 * @property string|null $status_long
 * @property int|null    $home_score
 * @property int|null    $away_score
 * @property int|null    $home_score_ht
 * @property int|null    $away_score_ht
 * @property int|null    $home_score_et
 * @property int|null    $away_score_et
 * @property int|null    $home_score_pen
 * @property int|null    $away_score_pen
 * @property int|null    $elapsed_minutes
 * @property bool        $lineup_confirmed
 * @property string|null $lineup_confirmed_at
 */
final class FixtureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'api_football_id'     => $this->api_football_id,

            // ── Teams (simplified — avoids leaking full model data) ────────
            'home_team' => $this->simplifyTeam($this->homeTeam),
            'away_team' => $this->simplifyTeam($this->awayTeam),

            // ── Match context ─────────────────────────────────────────────
            'round'      => $this->round,
            'stage'      => $this->stage,
            'group_name' => $this->group_name,
            'venue'      => [
                'name' => $this->venue_name,
                'city' => $this->venue_city,
            ],
            'kickoff_utc' => $this->kickoff_utc,

            // ── Live status ───────────────────────────────────────────────
            'status' => [
                'short'           => $this->status_short,
                'long'            => $this->status_long,
                'elapsed_minutes' => $this->elapsed_minutes,
                'is_live'         => $this->isLive(),
                'is_finished'     => $this->isFinished(),
            ],

            // ── Scores — grouped for easy frontend consumption ─────────────
            'scores' => [
                'fulltime' => [
                    'home' => $this->home_score,
                    'away' => $this->away_score,
                ],
                'halftime' => [
                    'home' => $this->home_score_ht,
                    'away' => $this->away_score_ht,
                ],
                'extratime' => [
                    'home' => $this->home_score_et,
                    'away' => $this->away_score_et,
                ],
                'penalty' => [
                    'home' => $this->home_score_pen,
                    'away' => $this->away_score_pen,
                ],
            ],

            // ── Lineup flags ──────────────────────────────────────────────
            'lineup_confirmed'    => (bool) $this->lineup_confirmed,
            'lineup_confirmed_at' => $this->lineup_confirmed_at,

            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Returns a minimal team payload to avoid over-fetching.
     *
     * @param \App\Models\Team|null $team
     * @return array<string, mixed>|null
     */
    private function simplifyTeam(mixed $team): ?array
    {
        if ($team === null) {
            return null;
        }

        return [
            'id'             => $team->id,
            'api_football_id' => $team->api_football_id,
            'name'           => $team->name,
            'code'           => $team->code,
            'logo_url'       => $team->logo_url,
            'group_name'     => $team->group_name,
        ];
    }

    /**
     * Statuses that mean the match is currently being played.
     */
    private function isLive(): bool
    {
        return in_array($this->status_short, ['1H', 'HT', '2H', 'ET', 'BT', 'P', 'LIVE'], true);
    }

    /**
     * Statuses that mean the match has concluded.
     */
    private function isFinished(): bool
    {
        return in_array($this->status_short, ['FT', 'AET', 'PEN'], true);
    }
}
