<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Fixture — represents a single World Cup match.
 *
 * @see CONTEXT.md §9.2 Migration 4
 *
 * @property int         $id
 * @property int         $api_football_id
 * @property int         $home_team_id
 * @property int         $away_team_id
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
 * @property array|null  $raw_api_data
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class Fixture extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'api_football_id',
        'home_team_id',
        'away_team_id',
        'round',
        'stage',
        'group_name',
        'venue_name',
        'venue_city',
        'kickoff_utc',
        'status_short',
        'status_long',
        'home_score',
        'away_score',
        'home_score_ht',
        'away_score_ht',
        'home_score_et',
        'away_score_et',
        'home_score_pen',
        'away_score_pen',
        'elapsed_minutes',
        'lineup_confirmed',
        'lineup_confirmed_at',
        'raw_api_data',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'lineup_confirmed'    => 'boolean',
        'lineup_confirmed_at' => 'datetime',
        'kickoff_utc'         => 'datetime',
        'raw_api_data'        => 'array',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function lineups(): HasMany
    {
        return $this->hasMany(FixtureLineup::class);
    }
}
