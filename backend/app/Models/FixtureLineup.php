<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FixtureLineup — the confirmed or probable lineup for one team in a fixture.
 *
 * @see CONTEXT.md §9.2 Migration 5
 *
 * @property int         $id
 * @property int         $fixture_id
 * @property int         $team_id
 * @property string|null $formation
 * @property array       $starting_xi    [{player_id, name, number, pos, grid}, ...]
 * @property array       $substitutes    [{player_id, name, number, pos}, ...]
 * @property array|null  $coach          {name, photo}
 * @property bool        $is_confirmed
 * @property string|null $confirmed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class FixtureLineup extends Model
{
    protected $table = 'fixture_lineups';

    /** @var list<string> */
    protected $fillable = [
        'fixture_id',
        'team_id',
        'formation',
        'starting_xi',
        'substitutes',
        'coach',
        'is_confirmed',
        'confirmed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'starting_xi'  => 'array',
        'substitutes'  => 'array',
        'coach'        => 'array',
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
