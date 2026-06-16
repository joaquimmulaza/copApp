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
     * Creates the `push_subscriptions` table for Firebase Cloud Messaging (FCM) device tokens.
     *
     * Key design decisions:
     * - `fcm_token` is the device-specific FCM registration token (up to 512 chars per
     *   Firebase spec). `unique()` prevents duplicate subscriptions for the same device.
     *   The accompanying `index()` enables fast token lookup on push delivery.
     * - `device_type` ('web' | 'ios' | 'android') allows platform-targeted payloads;
     *   web push payloads differ from native app payloads in the FCM v1 API.
     * - `subscribed_fixtures` and `subscribed_teams` use `jsonb` arrays (not a relational
     *   junction table) to avoid join overhead for a read-heavy notification dispatch path.
     *   Stored as arrays of raw API IDs (not local PKs) so subscriptions survive DB re-seeds.
     *   Both default to '[]' (empty array) — a device without subscriptions is still
     *   registered for potential future opt-ins without needing a DELETE + re-INSERT.
     * - `notify_lineups` is the primary MVP notification type (lineup confirmed).
     * - `notify_goals` is scaffolded but defaults false — it is NOT implemented in the MVP
     *   as real-time goal detection would require a dedicated live polling layer beyond
     *   the 100 req/day budget. The column is present to avoid a future breaking migration.
     * - `last_active_at` is updated on each successful push delivery; stale tokens
     *   (e.g., > 90 days without activity) can be pruned by a maintenance job to
     *   keep the FCM delivery rate healthy.
     * - Full `timestamps()` for soft auditing of subscription creation/update times.
     */
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('fcm_token', 512)->unique()->index();
            $table->string('device_type', 20)->nullable();          // 'web' | 'ios' | 'android'
            $table->jsonb('subscribed_fixtures')->default('[]');     // [fixture_api_id, ...]
            $table->jsonb('subscribed_teams')->default('[]');        // [team_api_id, ...]
            $table->boolean('notify_lineups')->default(true);
            $table->boolean('notify_goals')->default(false);         // MVP: não implementado
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
