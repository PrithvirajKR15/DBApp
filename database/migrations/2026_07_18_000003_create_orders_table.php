<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();

            $table->string('customer');
            $table->string('phone')->nullable();
            $table->string('area')->nullable();
            $table->string('address')->nullable();
            $table->string('slot')->nullable();
            $table->string('slot_label')->nullable();
            $table->string('placed_at')->nullable();

            $table->boolean('urgent')->default(false);
            $table->decimal('value', 10, 2)->default(0);
            $table->unsignedInteger('items')->default(0);
            $table->string('payment')->nullable();
            $table->string('prep')->nullable();
            $table->unsignedInteger('prep_pct')->default(0);
            $table->string('delivery')->nullable();
            $table->string('eta')->nullable();
            $table->decimal('distance_km', 6, 2)->nullable();

            // Destination is stored on `address`; lat/lng are its coordinates.
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
