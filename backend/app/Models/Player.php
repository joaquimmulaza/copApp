<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Player — represents a squad member of a national team.
 *
 * @see CONTEXT.md §9.2 Migration 2
 *
 * @property int         $id
 * @property int         $api_football_id
 * @property int         $team_id
 * @property string      $name
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string|null $birth_date
 * @property string|null $nationality
 * @property int|null    $age
 * @property float|null  $height
 * @property float|null  $weight
 * @property string|null $photo_url
 * @property string|null $position
 * @property string|null $number
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class Player extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'api_football_id',
        'team_id',
        'name',
        'firstname',
        'lastname',
        'birth_date',
        'nationality',
        'age',
        'height',
        'weight',
        'photo_url',
        'position',
        'number',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'birth_date' => 'date',
        'height'     => 'float',
        'weight'     => 'float',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function status(): HasOne
    {
        return $this->hasOne(PlayerStatus::class);
    }

    public function stats(): HasOne
    {
        return $this->hasOne(PlayerStat::class);
    }
}
