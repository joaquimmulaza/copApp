<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PlayerStatusResource — JSON representation of a player_statuses record.
 *
 * Exposes absences (injuries & suspensions) with the player's name bound
 * from the eagerly-loaded relationship, making the frontend rendering trivial.
 *
 * @see CONTEXT.md §12 RF01
 *
 * @property int         $id
 * @property int         $api_football_id
 * @property int         $player_id
 * @property int         $team_id
 * @property string      $type
 * @property string|null $reason
 * @property string|null $start_date
 * @property string|null $expected_return
 * @property bool        $is_active
 * @property string|null $synced_at
 * @property \App\Models\Player|null $player
 * @property \App\Models\Team|null   $team
 */
final class PlayerStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'type' => $this->type, // 'injury' | 'suspension'

            // ── Player identity (from eagerly-loaded relationship) ─────────
            'player' => $this->when(
                $this->relationLoaded('player') && $this->player !== null,
                fn () => [
                    'id'       => $this->player->id,
                    'name'     => $this->player->name,
                    'position' => $this->player->position,
                    'number'   => $this->player->number,
                    'photo_url' => $this->player->photo_url,
                ],
            ),

            // ── Team summary (for grouped-by-team rendering) ───────────────
            'team' => $this->when(
                $this->relationLoaded('team') && $this->team !== null,
                fn () => [
                    'id'       => $this->team->id,
                    'name'     => $this->team->name,
                    'code'     => $this->team->code,
                    'logo_url' => $this->team->logo_url,
                ],
            ),

            // ── Absence details ───────────────────────────────────────────
            'reason'          => $this->reason,
            'start_date'      => $this->start_date,
            'expected_return' => $this->expected_return,
            'is_active'       => (bool) $this->is_active,

            'synced_at'  => $this->synced_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
