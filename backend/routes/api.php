<?php

declare(strict_types=1);

use App\Http\Controllers\GeminiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─── Default Laravel user route (Sanctum-protected) ──────────────────────────
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ─── RF05 — Gemini Flash Analysis ────────────────────────────────────────────
// GET /api/fixtures/{id}/analysis?chip={type}
//
// Returns a Gemini Flash tactical analysis for a given fixture and chip type.
// Response is served from the DB-backed cache (10-min TTL) when available,
// or fetched from the Google Gemini API and then cached on a miss.
//
// Valid chip types: tactical_flash | injury_impact | head2head | guided_bet | recent_form
Route::get('/fixtures/{id}/analysis', [GeminiController::class, 'analyse'])
    ->where('id', '[0-9]+');
