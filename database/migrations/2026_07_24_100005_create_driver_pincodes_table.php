<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_pincodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->string('pincode', 16);
            $table->timestamps();

            $table->unique(['driver_id', 'pincode']);
            $table->index('pincode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_pincodes');
    }
};
