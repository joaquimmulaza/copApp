<?php

declare(strict_types=1);

use App\Http\Controllers\FixtureController;
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\InjuryController;
use App\Http\Controllers\LineupController;
use App\Http\Controllers\StandingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─── Default Laravel user route (Sanctum-protected) ──────────────────────────
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ─── RF03 — Calendar & Results ───────────────────────────────────────────────
// GET /api/fixtures             ?date=YYYY-MM-DD  or  ?stage=group|r32|r16|qf|sf|f
// GET /api/fixtures/{id}
Route::get('/fixtures', [FixtureController::class, 'index']);
Route::get('/fixtures/{id}', [FixtureController::class, 'show'])
    ->where('id', '[0-9]+');

// ─── RF02 — Official Lineup ───────────────────────────────────────────────────
// GET /api/fixtures/{id}/lineup
Route::get('/fixtures/{id}/lineup', [LineupController::class, 'show'])
    ->where('id', '[0-9]+');

// ─── RF05 — Gemini Flash Analysis ────────────────────────────────────────────
// GET /api/fixtures/{id}/analysis?chip={type}
//
// Valid chip types: tactical_flash | injury_impact | head2head | guided_bet | recent_form
Route::get('/fixtures/{id}/analysis', [GeminiController::class, 'analyse'])
    ->where('id', '[0-9]+');

// ─── RF01 — Absences Panel ───────────────────────────────────────────────────
// GET /api/injuries
Route::get('/injuries', [InjuryController::class, 'index']);

// ─── RF04 — Group Standings ───────────────────────────────────────────────────
// GET /api/standings
Route::get('/standings', [StandingController::class, 'index']);
