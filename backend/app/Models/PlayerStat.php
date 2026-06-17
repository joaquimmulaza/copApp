<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PlayerStat — cumulative tournament statistics for a single player.
 *
 * @see CONTEXT.md §9.2 Migration 7
 *
 * @property int         $id
 * @property int         $player_id
 * @property int         $team_id
 * @property int         $appearances
 * @property int         $goals
 * @property int         $assists
 * @property int         $yellow_cards
 * @property int         $red_cards
 * @property int         $minutes_played
 * @property float|null  $rating
 * @property int         $shots_total
 * @property int         $shots_on
 * @property int         $passes_total
 * @property float|null  $passes_accuracy
 * @property int         $tackles
 * @property int         $dribbles_success
 * @property string|null $synced_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class PlayerStat extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'player_id',
        'team_id',
        'appearances',
        'goals',
        'assists',
        'yellow_cards',
        'red_cards',
        'minutes_played',
        'rating',
        'shots_total',
        'shots_on',
        'passes_total',
        'passes_accuracy',
        'tackles',
        'dribbles_success',
        'synced_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'rating'          => 'float',
        'passes_accuracy' => 'float',
        'synced_at'       => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
