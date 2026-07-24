<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Areas (zones) own pincodes. Drivers register against zones they know;
     * order.pincode → zone_pincodes → drivers assigned to those zones.
     */
    public function up(): void
    {
        Schema::create('zone_pincodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones')->cascadeOnDelete();
            $table->string('pincode', 16);
            $table->timestamps();

            $table->unique(['zone_id', 'pincode']);
            $table->index('pincode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_pincodes');
    }
};
