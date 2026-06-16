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
     *
     * Note on FK + index pattern:
     *   foreignId()->constrained()->index() can generate conflicting constraint names in
     *   PostgreSQL when a composite index on the same column follows later in the schema.
     *   FK is declared via two separate statements; the composite index at the end covers
     *   team_id as the leading column, so no individual index on team_id is needed.
     */
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('api_football_id')->unique();
            $table->unsignedBigInteger('team_id');
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

            // FK constraint declared separately to avoid auto-generated name collisions
            $table->foreign('team_id')
                  ->references('id')
                  ->on('teams')
                  ->cascadeOnDelete();

            // Composite index: optimises lineup and position-based queries
            // team_id is the leading column — covers solo team_id lookups as well
            $table->index('api_football_id');
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
