<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * GeminiResponseCache — Immutable cache entry for a Gemini Flash API response.
 *
 * Cache entries are identified by a SHA-256 key built from (fixture_id + chip_type).
 * They are never updated in-place; expired entries are deleted and re-created on cache miss.
 * TTL is 10 minutes from the moment of creation.
 *
 * @property int         $id
 * @property string      $cache_key       SHA-256 hash of (fixture_api_id + chip_type)
 * @property int|null    $fixture_id_api  API-Football fixture ID (raw, not a local FK)
 * @property string|null $chip_type       'tactical_flash' | 'injury_impact' | 'head2head' | etc.
 * @property string      $response        The raw Gemini Flash response text (markdown)
 * @property int|null    $tokens_used     Token count reported by the Gemini API
 * @property Carbon      $expires_at      When this cache entry becomes invalid
 * @property Carbon      $created_at      Immutable creation timestamp (no updated_at)
 */
final class GeminiResponseCache extends Model
{
    /**
     * The table associated with the model.
     * Matches the migration name exactly (CONTEXT.md §9.2 Migration 9).
     */
    protected $table = 'gemini_response_cache';

    /**
     * Disable automatic timestamp management.
     * This table only has `created_at` (managed via useCurrent() in the migration)
     * and no `updated_at` column — cache entries are immutable.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cache_key',
        'fixture_id_api',
        'chip_type',
        'response',
        'tokens_used',
        'expires_at',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'fixture_id_api' => 'integer',
        'tokens_used'    => 'integer',
        'expires_at'     => 'datetime',
        'created_at'     => 'datetime',
    ];

    /**
     * Determine whether this cache entry is still valid (has not expired).
     */
    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }
}
