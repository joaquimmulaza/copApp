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
     * Creates the `players` table to store squad members of each World Cup team.
     * Composite index on (team_id, position) optimises position-based roster queries.
     */
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('api_football_id')->unique()->index();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete()->index();
            $table->string('name', 100);
            $table->string('firstname', 80)->nullable();
            $table->string('lastname', 80)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality', 80)->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->decimal('height', 5, 2)->nullable();  // em cm
            $table->decimal('weight', 5, 2)->nullable();  // em kg
            $table->string('photo_url', 255)->nullable();
            $table->string('position', 30)->nullable();   // Goalkeeper, Defender, Midfielder, Attacker
            $table->string('number')->nullable();
            $table->timestamps();

            // Composite index: optimises lineup and position-based queries
            $table->index(['team_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
