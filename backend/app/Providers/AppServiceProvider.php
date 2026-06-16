<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ApiFootballQuotaService;
use App\Services\ApiFootballQuotaServiceInterface;
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
        //
    }
}
