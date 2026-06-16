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
     * Creates the `player_statuses` table for tracking injuries and suspensions.
     *
     * Key design decisions:
     * - `is_active` flag avoids hard deletes — keeps full history for audit trail.
     * - `raw_api_data` (jsonb) stores the original API-Football payload for debugging.
     * - `synced_at` tracks when the record was last updated from the API.
     * - Composite indexes on (team_id, is_active) and (player_id, is_active) support
     *   the two primary query patterns: "active absences for a team" and "player status".
     *
     * Fix — duplicate constraint "1" on PostgreSQL:
     *   Chaining ->constrained()->index() (or ->is_active()->index()) on the same line
     *   causes Laravel to auto-generate a constraint name that collides with the numeric
     *   name PostgreSQL assigns to the next index in the same CREATE TABLE statement.
     *   Solution: declare every FK as two separate statements (foreignId + foreign()),
     *   and declare ALL indexes exclusively in the dedicated block at the end of the
     *   schema closure. No ->index() is chained inline on any column definition.
     */
    public function up(): void
    {
        Schema::create('player_statuses', function (Blueprint $table) {
            $table->id();

            // Raw API identifier — not a FK, just a reference to the external system
            $table->unsignedInteger('api_football_id');

            // FK columns declared first (no trailing ->constrained() or ->index() here)
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('team_id');

            $table->string('type', 20);                    // 'injury' | 'suspension'
            $table->string('reason', 150)->nullable();     // "Muscular", "Yellow Card Accumulation"
            $table->date('start_date')->nullable();
            $table->date('expected_return')->nullable();
            $table->boolean('is_active')->default(true);   // false = recuperado/cumpriu suspensão
            $table->jsonb('raw_api_data')->nullable();     // payload original para auditoria
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // ── Foreign key constraints (declared separately to avoid name collisions) ──
            $table->foreign('player_id')
                  ->references('id')
                  ->on('players')
                  ->cascadeOnDelete();

            $table->foreign('team_id')
                  ->references('id')
                  ->on('teams');

            // ── Indexes (all declared once, at the end) ───────────────────────────────
            // Single-column
            $table->index('api_football_id');
            $table->index('is_active');

            // Composite — cover player_id and team_id as leading columns,
            // so no separate single-column index is needed for those FKs.
            $table->index(['team_id', 'is_active']);
            $table->index(['player_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_statuses');
    }
};
