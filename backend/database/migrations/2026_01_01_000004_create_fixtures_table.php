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
     * Creates the `fixtures` table for storing all World Cup 2026 match data.
     *
     * Key design decisions:
     * - `home_team_id` / `away_team_id` reference `teams` (no cascadeOnDelete — fixture
     *   records are preserved if a team entry is ever corrected).
     * - Score columns are split by period (FT, HT, ET, PEN) to support rich scoreboard UI
     *   without runtime JSON parsing.
     * - `status_short` stores the API-Football status code (NS, 1H, HT, 2H, FT, AET, PEN, PST)
     *   as a concise indexed string — the primary filter for live-sync queries.
     * - `lineup_confirmed` + `lineup_confirmed_at` drive the push notification trigger
     *   and WebSocket broadcast (Layer 3 sync jobs).
     * - `raw_api_data` (jsonb) stores the full API-Football fixture payload for auditability.
     * - Composite index on (kickoff_utc, status_short) is the critical query for "today's matches".
     * - Composite index on (status_short, lineup_confirmed) drives the lineup-alert pipeline.
     *
     * Note on FK + index pattern:
     *   Two foreignId()->constrained()->index() on the same table cause a duplicate constraint
     *   name "1" error on PostgreSQL. All FKs are declared via separate foreign() calls,
     *   and all indexes are declared at the end of the schema block.
     */
    public function up(): void
    {
        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('api_football_id')->unique();

            // FK columns — no trailing ->constrained() or ->index() chained here
            $table->unsignedBigInteger('home_team_id');
            $table->unsignedBigInteger('away_team_id');

            $table->string('round', 80)->nullable();    // "Group Stage - 1", "Round of 32", "Final"
            $table->string('stage', 30)->nullable();    // 'group' | 'r32' | 'r16' | 'qf' | 'sf' | 'f'
            $table->string('group_name', 10)->nullable();
            $table->string('venue_name', 100)->nullable();
            $table->string('venue_city', 80)->nullable();
            $table->timestamp('kickoff_utc');
            $table->string('status_short', 10)->default('NS'); // NS, 1H, HT, 2H, FT, AET, PEN, PST
            $table->string('status_long', 50)->nullable();
            $table->unsignedSmallInteger('home_score')->nullable();
            $table->unsignedSmallInteger('away_score')->nullable();
            $table->unsignedSmallInteger('home_score_ht')->nullable();  // half-time
            $table->unsignedSmallInteger('away_score_ht')->nullable();
            $table->unsignedSmallInteger('home_score_et')->nullable();  // extra-time
            $table->unsignedSmallInteger('away_score_et')->nullable();
            $table->unsignedSmallInteger('home_score_pen')->nullable(); // penalties
            $table->unsignedSmallInteger('away_score_pen')->nullable();
            $table->unsignedSmallInteger('elapsed_minutes')->nullable();
            $table->boolean('lineup_confirmed')->default(false);
            $table->timestamp('lineup_confirmed_at')->nullable();
            $table->jsonb('raw_api_data')->nullable();
            $table->timestamps();

            // ── Foreign key constraints (declared separately) ─────────────────────────
            $table->foreign('home_team_id')->references('id')->on('teams');
            $table->foreign('away_team_id')->references('id')->on('teams');

            // ── Indexes (all declared once, at the end) ───────────────────────────────
            $table->index('api_football_id');
            $table->index('home_team_id');
            $table->index('away_team_id');
            $table->index('lineup_confirmed');

            // Critical composite index: primary query for "today's matches" dashboard
            $table->index(['kickoff_utc', 'status_short']);

            // Drives lineup-alert pipeline (Layer 3 sync jobs)
            $table->index(['status_short', 'lineup_confirmed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixtures');
    }
};
