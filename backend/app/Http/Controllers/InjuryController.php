<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\PlayerStatusResource;
use App\Models\PlayerStatus;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

/**
 * InjuryController — HTTP entry point for RF01 (Absences Panel).
 *
 * Provides:
 *   GET /api/injuries — all active absences grouped by team
 *
 * Returns only `is_active = true` records, ordered so the frontend can
 * render the InjuryPanel grouped by team without any client-side sorting.
 *
 * @see CONTEXT.md §12 RF01
 */
final class InjuryController extends Controller
{
    /**
     * List all active absences (injuries & suspensions), grouped by team.
     *
     * The response is an flat array of PlayerStatus records sorted by
     * `team_id` then `type` so the frontend can group them easily.
     * We eager-load player and team to avoid N+1 queries.
     *
     * GET /api/injuries
     *
     * @return AnonymousResourceCollection<PlayerStatusResource>
     */
    public function index(): AnonymousResourceCollection
    {
        $statuses = PlayerStatus::with(['player', 'team'])
            ->where('is_active', true)
            ->orderBy('team_id')
            ->orderBy('type')       // injuries before suspensions within each team
            ->orderBy('player_id')
            ->get();

        Log::debug('[InjuryController@index] Active absences fetched', [
            'total' => $statuses->count(),
        ]);

        return PlayerStatusResource::collection($statuses);
    }
}
