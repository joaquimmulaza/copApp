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
     * Creates the `teams` table to store the 48 World Cup 2026 national teams.
     * Uses PostgreSQL-native `jsonb` for venue and coach data (indexable, queryable).
     */
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('api_football_id')->unique()->index();
            $table->string('name', 100);
            $table->string('code', 10)->nullable();        // BRA, ARG, POR
            $table->string('country', 100)->nullable();
            $table->string('logo_url', 255)->nullable();
            $table->string('group_name', 10)->nullable();  // A, B, C ... L
            $table->jsonb('venue')->nullable();            // {name, city, capacity}
            $table->jsonb('coach')->nullable();            // {name, nationality, photo}
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
