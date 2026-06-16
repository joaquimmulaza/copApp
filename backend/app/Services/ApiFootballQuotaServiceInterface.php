<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Contract for managing the API-Football daily request budget.
 *
 * Budget rule (CONTEXT.md §10.2):
 *   - Total daily budget: 100 requests.
 *   - Layer 3 (Live) slots are reserved dynamically based on today's fixtures.
 *   - Layers 1 & 2 may only proceed when:  remaining > reserved_for_layer3 + 20
 *   - Layer 3 may proceed when:            remaining > 0
 */
interface ApiFootballQuotaServiceInterface
{
    /**
     * Determine whether the given layer may consume one more API request.
     *
     * @param  string  $layer  One of: 'layer1', 'layer2', 'layer3'
     */
    public function canProceed(string $layer): bool;

    /**
     * Record a consumed API request, persisting the log entry and updating
     * the daily summary counters atomically.
     *
     * @param  string  $endpoint   The API endpoint called (e.g. '/fixtures').
     * @param  string  $layer      One of: 'layer1', 'layer2', 'layer3'.
     * @param  int     $remaining  Value from the x-ratelimit-requests-remaining header.
     */
    public function recordUsage(string $endpoint, string $layer, int $remaining): void;

    /**
     * Return the number of API requests remaining for today according to
     * the local `api_quota_daily` tracking table.
     */
    public function getRemainingBudget(): int;

    /**
     * Calculate how many requests must be held in reserve for Layer 3 (Live
     * polling) based on fixtures scheduled within the next 24 hours.
     *
     * Formula:  fixtures_in_next_24h × 15
     * (worst-case: 10-min interval for 70 min = 7 polls + 5-min for 30 min = 6 polls + 2 buffer)
     */
    public function getReservedForLayer3(): int;

    /**
     * Send an administrative alert if daily consumption has reached or
     * exceeded 80 % of the total budget.  Must be idempotent — only one
     * alert may be sent per calendar day (tracked via `budget_alert_sent`).
     */
    public function sendAlertIfNeeded(): void;
}
