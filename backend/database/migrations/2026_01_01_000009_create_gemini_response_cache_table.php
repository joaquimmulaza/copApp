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
     * Creates the `gemini_response_cache` table for caching Gemini AI responses.
     *
     * Key design decisions:
     * - `cache_key` is a SHA-256 hash of (fixture_api_id + chip_type), ensuring
     *   deterministic cache lookup without exposing internal IDs in the key.
     *   The `unique()` + `index()` on this column is the primary lookup path.
     * - `fixture_id_api` stores the raw API-Football fixture ID (not the local FK)
     *   so cache entries survive potential fixture table truncation/re-seeding.
     *   It is nullable to support non-fixture-specific chips (e.g., tournament summaries).
     * - `chip_type` identifies the UI chip that triggered the Gemini request:
     *   'tactical_flash', 'injury_impact', 'head2head', etc. Combined with
     *   `fixture_id_api` it uniquely identifies the context of the cached response.
     * - `response` uses longText to accommodate Gemini Flash's up to 1024-token output
     *   which may expand into multi-paragraph markdown strings.
     * - `expires_at` (indexed) is checked by the cache service before returning a
     *   hit. A scheduled cleanup job uses this index to DELETE expired rows,
     *   keeping the table lean within the free-tier storage budget.
     * - `created_at` uses `useCurrent()` with a single timestamp (no `updated_at`)
     *   because cached responses are immutable — never updated in place, only
     *   deleted and re-created on cache miss after expiry.
     */
    public function up(): void
    {
        Schema::create('gemini_response_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key', 64)->unique()->index(); // SHA-256 de fixture_id + chip_type
            $table->unsignedInteger('fixture_id_api')->nullable()->index();
            $table->string('chip_type', 50)->nullable();        // 'tactical_flash' | 'injury_impact' | 'head2head'
            $table->longText('response');                       // resposta do Gemini (markdown)
            $table->unsignedSmallInteger('tokens_used')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gemini_response_cache');
    }
};
