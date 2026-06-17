<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Standing — one row per team per group in the tournament standings.
 *
 * @see CONTEXT.md §9.2 Migration 6
 *
 * @property int         $id
 * @property int         $team_id
 * @property string      $group_name
 * @property int         $rank
 * @property int         $played
 * @property int         $won
 * @property int         $drawn
 * @property int         $lost
 * @property int         $goals_for
 * @property int         $goals_against
 * @property int         $goals_diff
 * @property int         $points
 * @property string|null $form
 * @property string|null $status
 * @property string|null $description
 * @property string|null $synced_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class Standing extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'team_id',
        'group_name',
        'rank',
        'played',
        'won',
        'drawn',
        'lost',
        'goals_for',
        'goals_against',
        'goals_diff',
        'points',
        'form',
        'status',
        'description',
        'synced_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'synced_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
