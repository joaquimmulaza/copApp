<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\FixtureResource;
use App\Models\Fixture;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

/**
 * FixtureController — HTTP entry point for RF03 (Calendar & Results).
 *
 * Provides:
 *   GET /api/fixtures          — paginated list with optional date/stage filters
 *   GET /api/fixtures/{id}     — single fixture detail
 *
 * Eager loads homeTeam and awayTeam on every query to prevent N+1 issues.
 *
 * @see CONTEXT.md §12 RF03
 */
final class FixtureController extends Controller
{
    /**
     * Allowed `stage` values that map to the `stage` column.
     * Any other value is ignored to prevent SQL injection via the query string.
     */
    private const VALID_STAGES = ['group', 'r32', 'r16', 'qf', 'sf', 'f'];

    /**
     * List fixtures with optional filters.
     *
     * Query Parameters:
     *   ?date=YYYY-MM-DD  — filter by UTC kickoff date (e.g. 2026-06-14)
     *   ?stage=group      — filter by tournament phase (group|r32|r16|qf|sf|f)
     *
     * Both filters can be combined. If neither is supplied all fixtures are returned.
     *
     * GET /api/fixtures
     *
     * @return AnonymousResourceCollection<FixtureResource>
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Fixture::query()
            ->with(['homeTeam', 'awayTeam'])   // prevent N+1
            ->orderBy('kickoff_utc');

        // ── Filter: date ──────────────────────────────────────────────────
        if ($request->filled('date')) {
            $date = $request->string('date')->toString();

            // Accept only well-formed YYYY-MM-DD strings to avoid bad queries
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                // Use PostgreSQL DATE() cast so the index on kickoff_utc is used
                $query->whereDate('kickoff_utc', $date);
            }
        }

        // ── Filter: stage ─────────────────────────────────────────────────
        if ($request->filled('stage')) {
            $stage = $request->string('stage')->lower()->toString();

            if (in_array($stage, self::VALID_STAGES, true)) {
                $query->where('stage', $stage);
            }
        }

        $fixtures = $query->get();

        Log::debug('[FixtureController@index] Query executed', [
            'count'  => $fixtures->count(),
            'date'   => $request->query('date'),
            'stage'  => $request->query('stage'),
        ]);

        return FixtureResource::collection($fixtures);
    }

    /**
     * Return the detail of a single fixture.
     *
     * GET /api/fixtures/{id}
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException (→ 404)
     */
    public function show(int $id): FixtureResource
    {
        $fixture = Fixture::with(['homeTeam', 'awayTeam'])
            ->findOrFail($id);

        return new FixtureResource($fixture);
    }
}
