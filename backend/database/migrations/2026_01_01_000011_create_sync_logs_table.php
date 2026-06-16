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
     * Creates the `sync_logs` table for tracking the execution history of all sync jobs.
     *
     * Key design decisions:
     * - `job_class` stores the fully-qualified (or short) class name of the queued job
     *   (e.g., 'SyncTeamsJob', 'PollLineupJob') and is indexed for fast filtering in
     *   the Horizon dashboard and admin reporting queries.
     * - `layer` mirrors the API quota layer concept ('layer1' | 'layer2' | 'layer3'),
     *   enabling per-layer budget attribution without joining `api_quota_logs`.
     * - `status` uses a controlled vocabulary: 'started' | 'completed' | 'failed' | 'skipped'.
     *   'skipped' means the quota service blocked the job from running (budget depleted).
     * - `records_synced` is the count of DB rows inserted/updated during the job run,
     *   useful for detecting silent failures (status='completed' but records_synced=0).
     * - `api_requests_used` is the number of API-Football calls consumed in this run,
     *   cross-referenced with `api_quota_logs` for reconciliation.
     * - `error_message` captures exception messages for 'failed' runs; kept as `text`
     *   to avoid truncation of long stack-trace prefixes.
     * - `duration_ms` (unsigned int) records wall-clock execution time. Supports
     *   performance trending and timeout tuning across environments.
     * - `started_at` uses `useCurrent()` and is indexed — the primary sort column
     *   for the most-recent-run query. NO `timestamps()` since `started_at` and
     *   `completed_at` together replace the conventional created_at/updated_at pair,
     *   avoiding ambiguity (a log row is "created" when the job starts, not when it ends).
     */
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('job_class', 100)->index();          // SyncTeamsJob, SyncInjuriesJob, etc.
            $table->string('layer', 10);                        // 'layer1' | 'layer2' | 'layer3'
            $table->string('status', 20);                       // 'started' | 'completed' | 'failed' | 'skipped'
            $table->unsignedInteger('records_synced')->default(0);
            $table->unsignedSmallInteger('api_requests_used')->default(0);
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('started_at')->useCurrent()->index();
            $table->timestamp('completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
