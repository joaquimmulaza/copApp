<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds PostgreSQL-specific partial indexes for performance-critical query patterns.
     *
     * Why partial indexes?
     * Standard B-tree indexes cover the full table. Partial indexes (WHERE clause) cover
     * only a subset of rows — they are smaller, faster to scan, and can be used by the
     * PostgreSQL query planner when the WHERE clause matches the index predicate.
     *
     * `idx_fixtures_active_today` on `fixtures`:
     *   - Covers only fixtures that are NOT in a terminal state (FT, AET, PEN, PST, CANC).
     *   - At any given point during the World Cup, ~90% of fixtures will be in a terminal
     *     state. This index filters them out, making "today's active games" queries
     *     scan only ~10% of rows versus a full-table index.
     *   - Columns: (kickoff_utc, status_short) — the two columns used in every
     *     "get today's schedule" query: WHERE kickoff_utc BETWEEN ... AND status_short NOT IN (...)
     *
     * `idx_active_injuries` on `player_statuses`:
     *   - Covers only rows where is_active = true (current injuries/suspensions).
     *   - Once resolved, statuses flip to is_active = false and fall out of the index,
     *     keeping it lean throughout the tournament.
     *   - Columns: (team_id, type) — the two filters used in the "squad health" query
     *     on the fixture detail screen: WHERE team_id = ? AND type = 'injury' AND is_active = true
     *
     * Note: `Schema::table()` wrappers are required by Laravel's migration runner but
     * the actual DDL is executed via `DB::statement()` to use PostgreSQL-specific syntax
     * that Blueprint does not support natively.
     */
    public function up(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            // Partial index: active (non-terminal) fixtures only
            DB::statement("
                CREATE INDEX idx_fixtures_active_today
                ON fixtures (kickoff_utc, status_short)
                WHERE status_short NOT IN ('FT', 'AET', 'PEN', 'PST', 'CANC')
            ");
        });

        Schema::table('player_statuses', function (Blueprint $table) {
            // Partial index: currently active injuries and suspensions only
            DB::statement("
                CREATE INDEX idx_active_injuries
                ON player_statuses (team_id, type)
                WHERE is_active = true
            ");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_fixtures_active_today');
        DB::statement('DROP INDEX IF EXISTS idx_active_injuries');
    }
};
