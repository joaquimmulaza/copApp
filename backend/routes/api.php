<?php

declare(strict_types=1);

use App\Http\Controllers\FixtureController;
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\InjuryController;
use App\Http\Controllers\LineupController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\StandingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─── Default Laravel user route (Sanctum-protected) ──────────────────────────
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ─── SEC-01: Public read endpoints (60 req/min per IP) ───────────────────────
// Protects PostgreSQL and the Oracle VM from scrapers and excessive traffic.
// Covers: calendar, lineup details, injuries, standings, and player data.
Route::middleware('throttle:public-api')->group(function (): void {
    // RF03 — Calendar & Results
    // GET /api/fixtures             ?date=YYYY-MM-DD  or  ?stage=group|r32|r16|qf|sf|f
    // GET /api/fixtures/{id}
    Route::get('/fixtures', [FixtureController::class, 'index']);
    Route::get('/fixtures/{id}', [FixtureController::class, 'show'])
        ->where('id', '[0-9]+');

    // RF02 — Official Lineup
    // GET /api/fixtures/{id}/lineup
    Route::get('/fixtures/{id}/lineup', [LineupController::class, 'show'])
        ->where('id', '[0-9]+');

    // RF01 — Absences Panel
    // GET /api/injuries
    Route::get('/injuries', [InjuryController::class, 'index']);

    // RF04 — Group Standings
    // GET /api/standings
    Route::get('/standings', [StandingController::class, 'index']);

    // Player Data
    // GET /api/players
    // GET /api/players/{id}
    Route::get('/players', [PlayerController::class, 'index']);
    Route::get('/players/{id}', [PlayerController::class, 'show'])
        ->where('id', '[0-9]+');
});

// ─── SEC-01: Gemini Flash analysis (10 req/min per IP) ───────────────────────
// Strict cap: the free tier allows 15 req/min globally. Limiting to 10/IP
// ensures a single client cannot exhaust the entire AI quota.
// RF05 — GET /api/fixtures/{id}/analysis?chip={type}
// Valid chip types: tactical_flash | injury_impact | head2head | guided_bet | recent_form
Route::middleware('throttle:gemini-analysis')->group(function (): void {
    Route::get('/fixtures/{id}/analysis', [GeminiController::class, 'analyse'])
        ->where('id', '[0-9]+');
});

// ─── SEC-01: Push subscription registration (5 req/min per IP) ───────────────
// A legitimate device only ever registers its FCM token once. This low cap
// prevents the push_subscriptions table from being flooded with fake tokens.
// RF02 — POST /api/push-subscriptions
// Body: { fcm_token: string, device_type?: "web" | "ios" | "android" }
// Public route — no Sanctum auth required (FCM tokens are not secrets).
Route::middleware('throttle:push-register')->group(function (): void {
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store']);
});


