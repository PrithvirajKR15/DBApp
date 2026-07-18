<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Driver earnings, kept out of the driver profile. Each row is an earning
     * entry for a period (or a single running total for seeded/mock data).
     */
    public function up(): void
    {
        Schema::create('driver_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('period')->default('total'); // total | week | month | ...
            $table->timestamp('earned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_earnings');
    }
};
