<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * GeminiController — HTTP entry point for RF05 Gemini Flash analysis.
 *
 * Single endpoint: GET /api/fixtures/{id}/analysis?chip={type}
 *
 * The controller is intentionally thin — it validates input, delegates to
 * GeminiService, and formats the JSON response. No business logic lives here.
 *
 * @see CONTEXT.md §12 RF05
 */
final class GeminiController extends Controller
{
    /**
     * The valid chip types accepted by the endpoint.
     * Any other value returns a 422 validation error before hitting the service.
     */
    private const VALID_CHIP_TYPES = [
        'tactical_flash',
        'injury_impact',
        'head2head',
        'guided_bet',
        'recent_form',
    ];

    public function __construct(
        private readonly GeminiService $geminiService,
    ) {}

    /**
     * Retrieve AI analysis for a fixture.
     *
     * GET /api/fixtures/{id}/analysis?chip={type}
     *
     * Query Parameters:
     *   chip  (required) — One of: tactical_flash | injury_impact | head2head | guided_bet | recent_form
     *
     * Response (200 OK):
     * {
     *   "fixture_id": 42,
     *   "chip_type":  "tactical_flash",
     *   "analysis":   "..."
     * }
     *
     * Response (422 Unprocessable Entity) — invalid chip type.
     * Response (500 Internal Server Error) — only if GeminiService throws unexpectedly.
     */
    public function analyse(Request $request, int $id): JsonResponse
    {
        // ── Input validation ────────────────────────────────────────────────────────────────
        $validated = $request->validate([
            'chip' => ['required', 'string', 'in:' . implode(',', self::VALID_CHIP_TYPES)],
        ]);

        /** @var string $chipType */
        $chipType = $validated['chip'];

        Log::info('[GeminiController] Analysis requested', [
            'fixture_id' => $id,
            'chip_type'  => $chipType,
            'ip'         => $request->ip(),
        ]);

        // ── Delegate to service (never throws to the client — always returns a string) ──────
        $analysis = $this->geminiService->getAnalysis($id, $chipType);

        return response()->json([
            'fixture_id' => $id,
            'chip_type'  => $chipType,
            'analysis'   => $analysis,
        ]);
    }
}
