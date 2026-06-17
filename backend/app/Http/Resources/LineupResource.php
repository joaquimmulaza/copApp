<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * LineupResource — JSON representation of a fixture_lineup record.
 *
 * Formats JSONB starting_xi and substitutes fields into clean arrays,
 * and includes team metadata for both sides of the lineup.
 *
 * @see CONTEXT.md §12 RF02
 *
 * @property int         $id
 * @property int         $fixture_id
 * @property int         $team_id
 * @property string|null $formation
 * @property array       $starting_xi
 * @property array       $substitutes
 * @property array|null  $coach
 * @property bool        $is_confirmed
 * @property string|null $confirmed_at
 * @property \App\Models\Team|null $team
 */
final class LineupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'fixture_id' => $this->fixture_id,

            // ── Team summary ──────────────────────────────────────────────
            'team' => $this->when(
                $this->relationLoaded('team') && $this->team !== null,
                fn () => [
                    'id'       => $this->team->id,
                    'name'     => $this->team->name,
                    'code'     => $this->team->code,
                    'logo_url' => $this->team->logo_url,
                ],
            ),

            // ── Tactical formation ────────────────────────────────────────
            'formation' => $this->formation,

            // ── Coach ─────────────────────────────────────────────────────
            'coach' => $this->coach,

            // ── Starting XI — JSONB decoded into a typed array ────────────
            // Each entry: {player_id, name, number, pos, grid}
            'starting_xi' => $this->parsePlayerList($this->starting_xi),

            // ── Substitutes — JSONB decoded into a typed array ────────────
            // Each entry: {player_id, name, number, pos}
            'substitutes' => $this->parsePlayerList($this->substitutes),

            // ── Confirmation state ────────────────────────────────────────
            'is_confirmed' => (bool) $this->is_confirmed,
            'confirmed_at' => $this->confirmed_at,

            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Safely decode a JSONB player list.
     *
     * The column arrives as either an already-decoded array (Eloquent cast)
     * or a raw JSON string. This helper ensures we always return an array.
     *
     * @param  mixed  $list
     * @return array<int, array<string, mixed>>
     */
    private function parsePlayerList(mixed $list): array
    {
        if (is_array($list)) {
            return $list;
        }

        if (is_string($list)) {
            $decoded = json_decode($list, associative: true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
