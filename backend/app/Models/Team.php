<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Team — represents a national team participating in the 2026 World Cup.
 *
 * @see CONTEXT.md §9.2 Migration 1
 *
 * @property int         $id
 * @property int         $api_football_id
 * @property string      $name
 * @property string|null $code
 * @property string|null $country
 * @property string|null $logo_url
 * @property string|null $group_name
 * @property array|null  $venue
 * @property array|null  $coach
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class Team extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'api_football_id',
        'name',
        'code',
        'country',
        'logo_url',
        'group_name',
        'venue',
        'coach',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'venue' => 'array',
        'coach' => 'array',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function homeFixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'home_team_id');
    }

    public function awayFixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'away_team_id');
    }

    public function standing(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Standing::class);
    }
}
