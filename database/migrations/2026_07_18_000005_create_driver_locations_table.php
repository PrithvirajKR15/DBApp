<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Time-series of driver location pings. The live map uses the most recent
     * row per driver. live_status (Transit/Idle/Offline) and speed are point-in-
     * time attributes, so they belong with the location fix, not the profile.
     */
    public function up(): void
    {
        Schema::create('driver_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->unsignedInteger('speed_kmh')->nullable();
            $table->string('live_status')->nullable(); // Transit | Idle | Offline
            $table->timestamp('recorded_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_locations');
    }
};
