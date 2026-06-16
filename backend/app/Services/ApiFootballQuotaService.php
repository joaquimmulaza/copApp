<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Concrete implementation of the API-Football quota control service.
 *
 * Responsibility: track, gate, and log usage of the 100-request/day budget.
 *
 * Database tables used (see CONTEXT.md §9.2, migrations 8):
 *   - api_quota_logs   : append-only audit trail per API call
 *   - api_quota_daily  : one row per calendar day; mutable counters
 *
 * Budget gate logic (CONTEXT.md §10.2):
 *   - layer1 / layer2 : may proceed only if remaining > reserved_for_layer3 + 20
 *   - layer3          : may proceed if remaining > 0
 */
final readonly class ApiFootballQuotaService implements ApiFootballQuotaServiceInterface
{
    /**
     * Daily API budget cap (matches the API-Football free plan).
     */
    private const int DAILY_BUDGET = 100;

    /**
     * Safety buffer kept free above the Layer 3 reservation for Layers 1 & 2.
     */
    private const int SAFETY_BUFFER = 20;

    /**
     * Maximum estimated polling requests per live fixture
     * (10-min interval × 7 polls + 5-min interval × 6 polls + 2 margin = 15).
     */
    private const int POLLS_PER_GAME = 15;

    /**
     * Alert threshold — percentage of DAILY_BUDGET consumed before alerting.
     */
    private const int ALERT_THRESHOLD_PERCENT = 80;

    // ─────────────────────────────────────────────────────────────────────────
    // Interface implementation
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * {@inheritDoc}
     *
     * Reads today's remaining budget from `api_quota_daily` and applies the
     * layer-specific gate rule defined in CONTEXT.md §10.2.
     */
    public function canProceed(string $layer): bool
    {
        $remaining = $this->getRemainingBudget();

        if ($layer === 'layer3') {
            return $remaining > 0;
        }

        // layer1 and layer2 require a safety buffer on top of the Layer 3 reserve.
        $reserved = $this->getReservedForLayer3();

        return $remaining > ($reserved + self::SAFETY_BUFFER);
    }

    /**
     * {@inheritDoc}
     *
     * Wraps the two write operations (insert log + update daily summary) in a
     * single database transaction to guarantee consistency even under failure.
     */
    public function recordUsage(string $endpoint, string $layer, int $remaining): void
    {
        DB::transaction(function () use ($endpoint, $layer, $remaining): void {
            $today = Carbon::today()->toDateString();

            // 1. Append an immutable audit row.
            DB::table('api_quota_logs')->insert([
                'endpoint'  => $endpoint,
                'layer'     => $layer,
                'cost'      => 1,
                'remaining' => $remaining,
                'status'    => 'success',
                'called_at' => Carbon::now(),
            ]);

            // 2. Upsert the daily summary row.
            //    On first call of the day the row is created; subsequent calls
            //    increment `used` and update `remaining` from the live header value.
            DB::table('api_quota_daily')->upsert(
                [
                    'date'      => $today,
                    'used'      => 1,
                    'remaining' => $remaining,
                    'reserved_for_layer3' => $this->getReservedForLayer3(),
                    'budget_alert_sent'   => false,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                uniqueBy: ['date'],
                update: [
                    'used'      => DB::raw('api_quota_daily.used + 1'),
                    'remaining' => $remaining,   // authoritative value from the API header
                    'reserved_for_layer3' => $this->getReservedForLayer3(),
                    'updated_at' => Carbon::now(),
                ],
            );
        });

        // Fire the alert check outside the transaction so a log-writing failure
        // does not silently swallow the alert.
        $this->sendAlertIfNeeded();
    }

    /**
     * {@inheritDoc}
     *
     * Returns the `remaining` column for today's row in `api_quota_daily`.
     * If no row exists yet (first call of the day), returns the full budget.
     */
    public function getRemainingBudget(): int
    {
        $today = Carbon::today()->toDateString();

        /** @var \stdClass|null $row */
        $row = DB::table('api_quota_daily')
            ->where('date', $today)
            ->select('remaining')
            ->first();

        return $row !== null ? (int) $row->remaining : self::DAILY_BUDGET;
    }

    /**
     * {@inheritDoc}
     *
     * Counts fixtures whose `kickoff_utc` falls within the next 24 hours and
     * whose status indicates they have not yet finished.  Each fixture reserves
     * POLLS_PER_GAME (15) requests.
     *
     * Only non-terminal statuses are considered — finished matches (FT, AET,
     * PEN, PST, CANC) do not require live polling.
     */
    public function getReservedForLayer3(): int
    {
        $now        = Carbon::now();
        $upperBound = $now->copy()->addHours(24);

        $terminalStatuses = ['FT', 'AET', 'PEN', 'PST', 'CANC'];

        $gamesCount = DB::table('fixtures')
            ->whereBetween('kickoff_utc', [$now, $upperBound])
            ->whereNotIn('status_short', $terminalStatuses)
            ->count();

        return $gamesCount * self::POLLS_PER_GAME;
    }

    /**
     * {@inheritDoc}
     *
     * Calculates today's consumption percentage and, if it has crossed the
     * ALERT_THRESHOLD_PERCENT, logs a critical warning and marks the
     * `budget_alert_sent` flag so only one alert is emitted per day.
     */
    public function sendAlertIfNeeded(): void
    {
        $today = Carbon::today()->toDateString();

        /** @var \stdClass|null $row */
        $row = DB::table('api_quota_daily')
            ->where('date', $today)
            ->select('used', 'remaining', 'budget_alert_sent')
            ->first();

        // Nothing to alert on if no usage has been recorded yet.
        if ($row === null) {
            return;
        }

        // Alert already sent today — remain idempotent.
        if ((bool) $row->budget_alert_sent) {
            return;
        }

        $used    = (int) $row->used;
        $percent = (int) round(($used / self::DAILY_BUDGET) * 100);

        if ($percent < self::ALERT_THRESHOLD_PERCENT) {
            return;
        }

        // Mark as sent BEFORE logging/sending to avoid duplicate alerts on
        // concurrent requests that pass the check simultaneously.
        DB::table('api_quota_daily')
            ->where('date', $today)
            ->update([
                'budget_alert_sent' => true,
                'updated_at'        => Carbon::now(),
            ]);

        Log::critical('[CopApp] API-Football budget alert', [
            'date'             => $today,
            'used'             => $used,
            'remaining'        => (int) $row->remaining,
            'percent_consumed' => $percent,
            'threshold'        => self::ALERT_THRESHOLD_PERCENT,
            'message'          => "Daily API budget has reached {$percent}% — only {$row->remaining} requests left.",
        ]);
    }
}
