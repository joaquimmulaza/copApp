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
     */
    public function up(): void
    {
        Schema::create('player_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('api_football_id')->index();   // player api id
            $table->foreignId('player_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('team_id')->constrained()->index();
            $table->string('type', 20);  // 'injury' | 'suspension'
            $table->string('reason', 150)->nullable();            // "Muscular", "Yellow Card Accumulation"
            $table->date('start_date')->nullable();
            $table->date('expected_return')->nullable();
            $table->boolean('is_active')->default(true)->index(); // false = recuperado/cumpriu suspensão
            $table->jsonb('raw_api_data')->nullable();            // payload original para auditoria
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // Composite indexes for the two primary query patterns
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
