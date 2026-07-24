<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('cost_per_km', 10, 2)->default(0);
            $table->decimal('minimum_order_charge', 10, 2)->default(0);
            $table->string('status')->default('active'); // active|inactive
            $table->timestamps();

            $table->unique(['agency_id', 'zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_branches');
    }
};
