<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Player
 */
final class PlayerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'api_football_id' => $this->api_football_id,
            'name'            => $this->name,
            'firstname'       => $this->firstname,
            'lastname'        => $this->lastname,
            'birth_date'      => $this->birth_date?->format('Y-m-d'),
            'nationality'     => $this->nationality,
            'age'             => $this->age,
            'height'          => $this->height,
            'weight'          => $this->weight,
            'photo_url'       => $this->photo_url,
            'position'        => $this->position,
            'number'          => $this->number,
            'team'            => [
                'id'              => $this->whenLoaded('team')?->id,
                'api_football_id' => $this->whenLoaded('team')?->api_football_id,
                'name'            => $this->whenLoaded('team')?->name,
                'code'            => $this->whenLoaded('team')?->code,
                'logo_url'        => $this->whenLoaded('team')?->logo_url,
            ],
            'stats'           => $this->whenLoaded('stats', function () {
                return [
                    'appearances'      => $this->stats->appearances,
                    'goals'            => $this->stats->goals,
                    'assists'          => $this->stats->assists,
                    'yellow_cards'     => $this->stats->yellow_cards,
                    'red_cards'        => $this->stats->red_cards,
                    'minutes_played'   => $this->stats->minutes_played,
                    'rating'           => $this->stats->rating,
                    'shots_total'      => $this->stats->shots_total,
                    'shots_on'         => $this->stats->shots_on,
                    'passes_total'     => $this->stats->passes_total,
                    'passes_accuracy'  => $this->stats->passes_accuracy,
                    'tackles'          => $this->stats->tackles,
                    'dribbles_success' => $this->stats->dribbles_success,
                ];
            }),
            'status'          => $this->whenLoaded('status', function () {
                return [
                    'type'            => $this->status->type,
                    'reason'          => $this->status->reason,
                    'start_date'      => $this->status->start_date?->format('Y-m-d'),
                    'expected_return' => $this->status->expected_return?->format('Y-m-d'),
                    'is_active'       => $this->status->is_active,
                ];
            }),
        ];
    }
}
