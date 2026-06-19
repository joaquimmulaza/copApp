<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\PlayerResource;
use App\Models\Player;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

final class PlayerController extends Controller
{
    /**
     * Display a listing of players.
     * Optionally filter by team_id or search by name.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Player::query()->with(['team', 'stats', 'status']);

        if ($request->has('team_id')) {
            $query->where('team_id', $request->integer('team_id'));
        }

        if ($request->has('name')) {
            $nameInput = mb_substr($request->string('name')->value(), 0, 100);
            $escapedName = str_replace(['%', '_'], ['\%', '\_'], $nameInput);
            $searchTerm = '%' . $escapedName . '%';

            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'ILIKE', $searchTerm)
                    ->orWhere('firstname', 'ILIKE', $searchTerm)
                    ->orWhere('lastname', 'ILIKE', $searchTerm);
            });
        }

        // Using simple pagination to protect budget/response size
        $players = $query->paginate(20)->withQueryString();

        return PlayerResource::collection($players);
    }

    /**
     * Display the specified player by api_football_id.
     */
    public function show(int $apiFootballId): JsonResponse|PlayerResource
    {
        try {
            $player = Player::query()
                ->with(['team', 'stats', 'status'])
                ->where('api_football_id', $apiFootballId)
                ->firstOrFail();

            return new PlayerResource($player);
        } catch (ModelNotFoundException $e) {
            Log::warning("Player API-Football ID {$apiFootballId} not found.");

            return response()->json([
                'message' => 'Player not found.',
                'error'   => 'ModelNotFoundException',
            ], 404);
        }
    }
}
