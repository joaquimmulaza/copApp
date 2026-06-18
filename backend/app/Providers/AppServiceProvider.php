<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ApiFootballQuotaService;
use App\Services\ApiFootballQuotaServiceInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the quota service interface to its concrete implementation.
        // Registered as a singleton so the daily-budget counters remain
        // consistent across multiple resolutions within the same request/job.
        $this->app->singleton(
            ApiFootballQuotaServiceInterface::class,
            ApiFootballQuotaService::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── SEC-01: Rate Limiting Policies ───────────────────────────────────
        // Define named rate limiters to protect the API against abuse,
        // scraping, and Gemini Flash quota exhaustion (free tier: 15 req/min).

        // 60 req/min per IP — general read endpoints (/fixtures, /standings,
        // /players, /injuries). Shields PostgreSQL and the Oracle VM (free
        // tier CPU) from aggressive scrapers.
        RateLimiter::for('public-api', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->ip());
        });

        // 10 req/min per IP — Gemini Flash analysis endpoint. The free tier
        // allows 15 req/min globally; 10/IP ensures a single user cannot
        // exhaust the entire quota, even on cache misses.
        RateLimiter::for('gemini-analysis', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip());
        });

        // 5 req/min per IP — public POST /push-subscriptions. A legitimate
        // device registers its FCM token once; this low cap prevents the
        // push_subscriptions table from being flooded with fake tokens.
        RateLimiter::for('push-register', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
