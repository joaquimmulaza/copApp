<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Standing;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * StandingController — HTTP entry point for RF04 (Standings Tables).
 *
 * Provides:
 *   GET /api/standings — standings for all 12 groups, ordered by group and rank
 *
 * The response is grouped by group_name so the frontend StandingsTable
 * component can render each group's table without additional processing.
 *
 * @see CONTEXT.md §12 RF04
 */
final class StandingController extends Controller
{
    /**
     * Return standings for all 12 groups, sorted by group then rank.
     *
     * The response structure is a map of group_name → array of standing rows,
     * making it trivial to iterate over groups in the frontend.
     *
     * GET /api/standings
     *
     * Response (200 OK):
     * {
     *   "data": {
     *     "A": [ { rank, team, points, ... }, ... ],
     *     "B": [ ... ],
     *     ...
     *   }
     * }
     */
    public function index(): JsonResponse
    {
        $standings = Standing::with('team')
            ->orderBy('group_name')
            ->orderBy('rank')
            ->get();

        // ── Group by group_name and transform each row ────────────────────
        $grouped = $standings
            ->groupBy('group_name')
            ->map(fn ($rows) => $rows->map(fn ($s) => $this->formatRow($s))->values())
            ->sortKeys();  // ensure A → L alphabetical order

        Log::debug('[StandingController@index] Standings fetched', [
            'groups' => $grouped->count(),
            'total'  => $standings->count(),
        ]);

        return response()->json(['data' => $grouped]);
    }

    /**
     * Transform a single Standing model into a JSON-friendly array.
     *
     * @param \App\Models\Standing $standing
     * @return array<string, mixed>
     */
    private function formatRow(Standing $standing): array
    {
        return [
            'rank'         => $standing->rank,
            'group_name'   => $standing->group_name,

            // Simplified team payload — same shape as FixtureResource
            'team' => $standing->team ? [
                'id'              => $standing->team->id,
                'api_football_id' => $standing->team->api_football_id,
                'name'            => $standing->team->name,
                'code'            => $standing->team->code,
                'logo_url'        => $standing->team->logo_url,
            ] : null,

            // ── Statistics ────────────────────────────────────────────────
            'played'        => $standing->played,
            'won'           => $standing->won,
            'drawn'         => $standing->drawn,
            'lost'          => $standing->lost,
            'goals_for'     => $standing->goals_for,
            'goals_against' => $standing->goals_against,
            'goals_diff'    => $standing->goals_diff,
            'points'        => $standing->points,

            // ── Meta ──────────────────────────────────────────────────────
            'form'        => $standing->form,
            'status'      => $standing->status,
            'description' => $standing->description,
            'synced_at'   => $standing->synced_at,
        ];
    }
}
