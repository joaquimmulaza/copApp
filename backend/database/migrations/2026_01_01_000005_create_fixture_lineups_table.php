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
     * Creates the `fixture_lineups` table, storing confirmed starting XIs and substitutes
     * for each team in each match.
     *
     * Key design decisions:
     * - `cascadeOnDelete()` on `fixture_id`: lineup records have no meaning without the fixture.
     *   If a fixture is removed (e.g., data correction), its lineups are automatically purged.
     * - `team_id` does NOT cascade — teams are master data; no lineup should delete a team.
     * - `starting_xi` and `substitutes` are jsonb arrays defaulting to `[]` (not null) to allow
     *   safe array iteration in PHP/frontend without null checks. Structure:
     *     starting_xi:  [{player_id, name, number, pos, grid}, ...]
     *     substitutes:  [{player_id, name, number, pos}, ...]
     * - `coach` is jsonb (nullable) for {name, photo} — mirrors the API-Football payload.
     * - Unique constraint on (fixture_id, team_id) enforces exactly one lineup record per side.
     * - `is_confirmed` + `confirmed_at` allow the app to distinguish "provisional" from
     *   "official" lineups, driving push notifications and the badge-live UI component.
     *
     * Note on FK + index pattern:
     *   Two foreignId()->constrained()->index() on the same table cause a duplicate constraint
     *   name "1" error on PostgreSQL. All FKs are declared via separate foreign() calls,
     *   and all indexes are declared at the end of the schema block.
     */
    public function up(): void
    {
        Schema::create('fixture_lineups', function (Blueprint $table) {
            $table->id();

            // FK columns — no trailing ->constrained() or ->index() chained here
            $table->unsignedBigInteger('fixture_id');
            $table->unsignedBigInteger('team_id');

            $table->string('formation', 20)->nullable();    // "4-3-3", "3-5-2"
            $table->jsonb('starting_xi')->default('[]');    // [{player_id, name, number, pos, grid}]
            $table->jsonb('substitutes')->default('[]');    // [{player_id, name, number, pos}]
            $table->jsonb('coach')->nullable();             // {name, photo}
            $table->boolean('is_confirmed')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            // ── Foreign key constraints (declared separately) ─────────────────────────
            $table->foreign('fixture_id')
                  ->references('id')
                  ->on('fixtures')
                  ->cascadeOnDelete();

            $table->foreign('team_id')
                  ->references('id')
                  ->on('teams');

            // ── Indexes (all declared once, at the end) ───────────────────────────────
            $table->index('fixture_id');
            $table->index('team_id');
            $table->index('is_confirmed');

            // Enforces one lineup record per team per fixture (home + away)
            $table->unique(['fixture_id', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixture_lineups');
    }
};
