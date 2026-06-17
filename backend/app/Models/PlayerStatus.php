<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PlayerStatus — represents an active injury or suspension.
 *
 * @see CONTEXT.md §9.2 Migration 3
 *
 * @property int         $id
 * @property int         $api_football_id
 * @property int         $player_id
 * @property int         $team_id
 * @property string      $type           'injury' | 'suspension'
 * @property string|null $reason
 * @property string|null $start_date
 * @property string|null $expected_return
 * @property bool        $is_active
 * @property array|null  $raw_api_data
 * @property string|null $synced_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class PlayerStatus extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'api_football_id',
        'player_id',
        'team_id',
        'type',
        'reason',
        'start_date',
        'expected_return',
        'is_active',
        'raw_api_data',
        'synced_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active'       => 'boolean',
        'start_date'      => 'date',
        'expected_return' => 'date',
        'synced_at'       => 'datetime',
        'raw_api_data'    => 'array',
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
