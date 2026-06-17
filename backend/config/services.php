<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // ─── API-Football ────────────────────────────────────────────────────────
    // Credentials are read exclusively from environment variables.
    // NEVER hard-code keys in this file.
    // See CONTEXT.md §4 for the full list of required .env variables.
    'api_football' => [
        'key'      => env('API_FOOTBALL_KEY'),
        'base_url' => env('API_FOOTBALL_BASE_URL', 'https://v3.football.api-sports.io'),
        'league'   => (int) env('API_FOOTBALL_LEAGUE', 1),
        'season'   => (int) env('API_FOOTBALL_SEASON', 2026),
    ],

    // ─── Google Gemini Flash ─────────────────────────────────────────────────
    // Model is locked to gemini-1.5-flash — never gemini-pro in production.
    // See CONTEXT.md §1 (Restrições Absolutas) and §4 (.env).
    'gemini' => [
        'key'        => env('GEMINI_API_KEY'),
        'model'      => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'max_tokens' => (int) env('GEMINI_MAX_TOKENS', 1024),
    ],

    // ─── Firebase Cloud Messaging (FCM) ──────────────────────────────────────
    // The credentials path points to the Service Account JSON file.
    // The file path is NEVER logged; only the basename is used in error messages.
    // See CONTEXT.md §4 (FIREBASE_CREDENTIALS_PATH) and §12 RF02.
    'firebase' => [
        'project_id'       => env('FIREBASE_PROJECT_ID'),
        'credentials_path' => env('FIREBASE_CREDENTIALS_PATH'),
    ],

];
