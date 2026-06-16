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
     * Creates the `standings` table for World Cup 2026 group stage rankings.
     *
     * Key design decisions:
     * - One row per team per group (48 teams × 1 group each = 48 rows maximum).
     * - Unique constraint on (team_id, group_name) prevents duplicate standings entries
     *   and allows safe UPSERT via `updateOrCreate()` during sync jobs.
     * - Goals columns use signed `smallInteger` — goals_diff can legitimately be negative.
     * - `points` uses signed `smallInteger` — while points are non-negative in football,
     *   using signed avoids a potential edge case in data corrections or future rules.
     * - `form` stores the last 5-match string (e.g., "WWDLW") directly — avoids expensive
     *   joins to fixtures table for the common scoreboard UI query.
     * - `synced_at` tracks freshness; the sync job checks this before consuming API quota.
     * - Composite index on (group_name, rank) is the primary query for the group table view:
     *   "give me all teams in Group A ordered by rank".
     *
     * Note on FK + index pattern:
     *   foreignId()->constrained()->index() followed by a unique/composite index can trigger
     *   duplicate constraint name errors on PostgreSQL. FK is declared via a separate
     *   foreign() call; team_id is covered as a leading column in the unique constraint.
     */
    public function up(): void
    {
        Schema::create('standings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->string('group_name', 10);               // A, B ... L
            $table->unsignedSmallInteger('rank')->default(1);
            $table->unsignedSmallInteger('played')->default(0);
            $table->unsignedSmallInteger('won')->default(0);
            $table->unsignedSmallInteger('drawn')->default(0);
            $table->unsignedSmallInteger('lost')->default(0);
            $table->smallInteger('goals_for')->default(0);
            $table->smallInteger('goals_against')->default(0);
            $table->smallInteger('goals_diff')->default(0);
            $table->smallInteger('points')->default(0);
            $table->string('form', 15)->nullable();         // "WWDLW"
            $table->string('status', 20)->nullable();       // "same", "up", "down"
            $table->string('description', 100)->nullable(); // "Promotion - Round of 32"
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // ── Foreign key constraint (declared separately) ──────────────────────────
            $table->foreign('team_id')->references('id')->on('teams');

            // ── Indexes (all declared once, at the end) ───────────────────────────────
            // One standing record per team per group (prevents duplicates, enables upsert)
            // team_id is the leading column — covers solo team_id lookups as well
            $table->unique(['team_id', 'group_name']);

            // Primary query: render a full group table ordered by rank
            $table->index(['group_name', 'rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standings');
    }
};
