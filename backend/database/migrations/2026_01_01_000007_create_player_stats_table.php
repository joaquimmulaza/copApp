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
     * Creates the `player_stats` table for tournament-wide accumulated statistics.
     *
     * Key design decisions:
     * - `cascadeOnDelete()` on `player_id`: stats are derived data — if a player record is
     *   deleted (e.g., duplicate resolution), stats are automatically purged with it.
     * - `team_id` does NOT cascade — teams are master reference data.
     * - One row per player (`unique('player_id')`): stats are accumulated totals across the
     *   entire tournament, not per-fixture. This simplifies top-scorers/assists queries to
     *   a single ORDER BY without aggregation.
     * - `rating` uses decimal(4,2) → supports 0.00–9.99 (API-Football scale 0–10).
     * - `passes_accuracy` uses decimal(5,2) → supports 0.00–100.00 (percentage).
     * - Composite index on (goals, team_id) optimises "top scorers" query with optional
     *   team filter (common on the Tactics screen).
     * - Composite index on (assists, team_id) mirrors the above for the assists leaderboard.
     * - Index on yellow_cards supports the "most booked players" query used by the
     *   suspension-risk prediction feature.
     * - `synced_at` tracks freshness per player; sync jobs skip players updated within
     *   the last 24h to stay within the 100 req/day API budget.
     *
     * Note on FK + index pattern:
     *   Two foreignId()->constrained()->index() on the same table cause a duplicate constraint
     *   name "1" error on PostgreSQL. All FKs are declared via separate foreign() calls,
     *   and all indexes are declared at the end of the schema block.
     */
    public function up(): void
    {
        Schema::create('player_stats', function (Blueprint $table) {
            $table->id();

            // FK columns — no trailing ->constrained() or ->index() chained here
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('team_id');

            $table->unsignedSmallInteger('appearances')->default(0);
            $table->unsignedSmallInteger('goals')->default(0);
            $table->unsignedSmallInteger('assists')->default(0);
            $table->unsignedSmallInteger('yellow_cards')->default(0);
            $table->unsignedSmallInteger('red_cards')->default(0);
            $table->unsignedSmallInteger('minutes_played')->default(0);
            $table->decimal('rating', 4, 2)->nullable();           // 0.00 — 9.99 (API-Football scale)
            $table->unsignedSmallInteger('shots_total')->default(0);
            $table->unsignedSmallInteger('shots_on')->default(0);
            $table->unsignedSmallInteger('passes_total')->default(0);
            $table->decimal('passes_accuracy', 5, 2)->nullable();  // 0.00 — 100.00 (%)
            $table->unsignedSmallInteger('tackles')->default(0);
            $table->unsignedSmallInteger('dribbles_success')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // ── Foreign key constraints (declared separately) ─────────────────────────
            $table->foreign('player_id')
                  ->references('id')
                  ->on('players')
                  ->cascadeOnDelete();

            $table->foreign('team_id')
                  ->references('id')
                  ->on('teams');

            // ── Indexes (all declared once, at the end) ───────────────────────────────
            // One accumulated stats record per player for the full tournament
            $table->unique('player_id');

            // Top scorers query: ORDER BY goals DESC, with optional team filter
            $table->index(['goals', 'team_id']);

            // Top assists query: ORDER BY assists DESC, with optional team filter
            $table->index(['assists', 'team_id']);

            // Suspension-risk query: players approaching yellow-card accumulation threshold
            $table->index(['yellow_cards']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_stats');
    }
};
