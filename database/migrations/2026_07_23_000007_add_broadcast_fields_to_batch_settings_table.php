<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * batch_settings already holds the one global row of dispatch tuning
     * knobs (orders_per_batch, max_distance_km for route budgeting, ...).
     * Broadcast needs its own two knobs: how far to search for third-party
     * drivers (distinct from the route-distance cap above) and how long an
     * offer stays open before it expires and moves to the next driver.
     */
    public function up(): void
    {
        Schema::table('batch_settings', function (Blueprint $table) {
            $table->decimal('broadcast_radius_km', 8, 2)->default(5)->after('max_distance_km');
            $table->unsignedInteger('broadcast_offer_seconds')->default(90)->after('accept_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('batch_settings', function (Blueprint $table) {
            $table->dropColumn(['broadcast_radius_km', 'broadcast_offer_seconds']);
        });
    }
};
