<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates two tables for API-Football quota management:
     *
     * `api_quota_logs` — append-only log of every API call made.
     *   - Intentionally has NO `timestamps()`: this is a write-once audit log.
     *     `called_at` (indexed) serves as the sole temporal marker and defaults
     *     to the current timestamp via `useCurrent()`.
     *   - `cost` defaults to 1 (standard request); some endpoints may cost more.
     *   - `remaining` mirrors the `x-ratelimit-requests-remaining` header returned
     *     by API-Football, allowing point-in-time budget reconstruction.
     *   - `status` distinguishes successful calls from errors/skipped requests,
     *     enabling the quota service to exclude skipped calls from daily totals.
     *
     * `api_quota_daily` — one row per calendar day, maintained by the quota service.
     *   - `unique('date')` enforces a single summary record per day.
     *   - `reserved_for_layer3` is recalculated each time Layer 3 (live polling)
     *     estimates how many requests it will need based on scheduled fixtures.
     *   - `budget_alert_sent` prevents duplicate alerts when consumption ≥ 80%.
     *   - Has full `timestamps()` since rows are updated (not just inserted).
     */
    public function up(): void
    {
        // Append-only log — no timestamps(), only called_at
        Schema::create('api_quota_logs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint', 150);                    // /fixtures, /injuries, etc.
            $table->string('layer', 10);                        // 'layer1' | 'layer2' | 'layer3'
            $table->unsignedSmallInteger('cost')->default(1);   // requisições gastas
            $table->unsignedSmallInteger('remaining')->nullable(); // x-ratelimit-requests-remaining
            $table->string('status', 20)->default('success');   // 'success' | 'error' | 'skipped'
            $table->text('notes')->nullable();
            $table->timestamp('called_at')->useCurrent()->index();

            // sem timestamps() — tabela é append-only
        });

        // Daily summary — one row per day, updated by ApiFootballQuotaService
        Schema::create('api_quota_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique()->index();
            $table->unsignedSmallInteger('used')->default(0);
            $table->unsignedSmallInteger('remaining')->default(100);
            $table->unsignedSmallInteger('reserved_for_layer3')->default(0); // calculado com base nos jogos do dia
            $table->boolean('budget_alert_sent')->default(false);             // alerta aos 80%
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_quota_daily');
        Schema::dropIfExists('api_quota_logs');
    }
};
