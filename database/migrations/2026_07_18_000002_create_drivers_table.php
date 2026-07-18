<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The drivers table is a *profile* extension of a user account. Identity
     * (name, email, phone, image, code, status, ...) lives on the users table
     * so a driver can log into the mobile app. Live location lives in
     * driver_locations, zones in driver_assignments, earnings in
     * driver_earnings, and per-delivery figures (distance/eta/destination)
     * on orders. Only durable driver attributes remain here.
     */
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            $table->decimal('rating', 3, 2)->nullable();
            // deliveries / failed_deliveries are derived from orders (see Driver model).
            $table->date('joined_at')->nullable();

            // Vehicle
            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_brand')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->string('plate_number')->nullable();
            $table->string('vehicle_fuel')->nullable();
            $table->string('license_number')->nullable();

            // Work
            $table->string('shift')->nullable();
            $table->string('partner_type')->nullable();
            $table->json('service_areas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
