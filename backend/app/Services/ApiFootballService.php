<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client wrapper for the API-Football REST API.
 *
 * Responsibilities:
 *  - Inject the correct authentication header automatically on every request.
 *  - Provide a single, resilient GET method that handles network/server failures
 *    gracefully (no exceptions bubble to the queue worker).
 *  - Read all credentials exclusively from config/services.php → .env.
 *    No key is ever hard-coded in this class.
 *
 * API-Football authentication:
 *  - Endpoint host v3.football.api-sports.io → header: x-apisports-key
 *  - Endpoint host v3.api-football.com       → header: x-rapidapi-key
 *    Both headers are sent on every request so the same client works regardless
 *    of which host is configured in .env (safe — the unused one is ignored).
 *
 * @see CONTEXT.md §4 (.env variables) and §10 (sync strategy)
 */
final readonly class ApiFootballService
{
    /**
     * Default request timeout in seconds.
     * Keep this generous enough for slow API responses but tight enough to
     * release queue workers promptly.
     */
    private const int TIMEOUT_SECONDS = 15;

    /**
     * Default connection timeout in seconds.
     */
    private const int CONNECT_TIMEOUT_SECONDS = 5;

    /** @var string Base URL, e.g. https://v3.football.api-sports.io */
    private string $baseUrl;

    /** @var string API key value (populated from .env — never exposed in logs). */
    private string $apiKey;

    /** @var int Default league ID (1 = FIFA World Cup). */
    private int $leagueId;

    /** @var int Default season year (2026). */
    private int $season;

    // ─────────────────────────────────────────────────────────────────────────
    // Constructor
    // ─────────────────────────────────────────────────────────────────────────

    public function __construct()
    {
        // Pull configuration from config/services.php, which in turn reads
        // exclusively from environment variables. This guarantees zero secrets
        // in committed source code.
        /** @var string $baseUrl */
        $baseUrl = config('services.api_football.base_url', 'https://v3.football.api-sports.io');

        /** @var string $apiKey */
        $apiKey = config('services.api_football.key', '');

        $this->baseUrl  = rtrim($baseUrl, '/');
        $this->apiKey   = $apiKey;
        $this->leagueId = (int) config('services.api_football.league', 1);
        $this->season   = (int) config('services.api_football.season', 2026);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Perform a GET request to the given API-Football endpoint.
     *
     * The method is intentionally resilient:
     *  - ConnectionException  (timeout / DNS failure)    → logged, null returned
     *  - 5xx server error responses                      → logged, null returned
     *  - 4xx client errors (misconfiguration)            → logged, null returned
     *
     * Callers must handle a null return value gracefully.
     *
     * @param  string               $endpoint  Relative path, e.g. '/teams'
     * @param  array<string, mixed> $params    Query parameters
     * @return array<string, mixed>|null       Decoded JSON body, or null on failure
     */
    public function get(string $endpoint, array $params = []): ?array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        try {
            $response = Http::withHeaders($this->buildAuthHeaders())
                ->timeout(self::TIMEOUT_SECONDS)
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->get($url, $params);

            return $this->handleResponse($response, $endpoint);
        } catch (ConnectionException $e) {
            // Network-level failure (DNS, TCP connection refused, read timeout).
            // Log and return null so the calling Job can decide how to proceed.
            Log::warning('[ApiFootballService] Connection failure', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Return the default league ID configured for this application.
     * Useful for Jobs that build standard query parameters.
     */
    public function getLeagueId(): int
    {
        return $this->leagueId;
    }

    /**
     * Return the default season year configured for this application.
     */
    public function getSeason(): int
    {
        return $this->season;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build the authentication headers required by API-Football.
     *
     * Both header names are sent simultaneously so the same client works with
     * either the api-sports.io or the api-football.com host.
     * The API ignores the header it does not recognise.
     *
     * @return array<string, string>
     */
    private function buildAuthHeaders(): array
    {
        return [
            'x-apisports-key' => $this->apiKey,  // api-sports.io host
            'x-rapidapi-key'  => $this->apiKey,  // api-football.com host
            'Accept'          => 'application/json',
        ];
    }

    /**
     * Inspect the HTTP response and either return the decoded JSON payload or
     * log the problem and return null.
     *
     * 5xx responses are treated as transient server failures.
     * 4xx responses indicate a configuration or usage error and are also
     * handled gracefully (so the queue worker does not crash).
     *
     * @param  Response $response
     * @param  string   $endpoint  Used only in log context — never contains secrets.
     * @return array<string, mixed>|null
     */
    private function handleResponse(Response $response, string $endpoint): ?array
    {
        if ($response->successful()) {
            /** @var array<string, mixed> $body */
            $body = $response->json();

            return $body;
        }

        // Log server-side errors (5xx) as warnings — they are typically transient.
        if ($response->serverError()) {
            Log::warning('[ApiFootballService] Server error from API-Football', [
                'endpoint'    => $endpoint,
                'status_code' => $response->status(),
            ]);

            return null;
        }

        // Log client-side errors (4xx) as errors — likely a configuration issue.
        Log::error('[ApiFootballService] Client error from API-Football', [
            'endpoint'    => $endpoint,
            'status_code' => $response->status(),
            // Deliberately omit the body — it may echo back header values.
        ]);

        return null;
    }
}
