<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\LineupResource;
use App\Models\Fixture;
use App\Models\FixtureLineup;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

/**
 * LineupController — HTTP entry point for RF02 (Official Lineup).
 *
 * Provides:
 *   GET /api/fixtures/{fixtureId}/lineup — lineups for both teams in a fixture
 *
 * Returns up to two FixtureLineup records (home and away team) so the
 * frontend's LineupGrid can render both sides from a single request.
 *
 * @see CONTEXT.md §12 RF02
 */
final class LineupController extends Controller
{
    /**
     * Return the lineup(s) associated with a given fixture.
     *
     * If the fixture does not exist a 404 is raised automatically via findOrFail.
     * If lineups have not been confirmed yet an empty collection is returned with
     * the HTTP 200 status so the frontend can render the "awaiting lineup" state.
     *
     * GET /api/fixtures/{fixtureId}/lineup
     *
     * @return AnonymousResourceCollection<LineupResource>
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException (→ 404)
     */
    public function show(int $fixtureId): AnonymousResourceCollection
    {
        // Validate the parent fixture exists first — raises 404 if not.
        Fixture::findOrFail($fixtureId);

        $lineups = FixtureLineup::with('team')
            ->where('fixture_id', $fixtureId)
            ->orderBy('id')   // deterministic order (home team inserted first by sync job)
            ->get();

        Log::debug('[LineupController@show] Lineup fetched', [
            'fixture_id' => $fixtureId,
            'found'      => $lineups->count(),
        ]);

        return LineupResource::collection($lineups);
    }
}
