<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_pincodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('pincode', 16);
            $table->timestamps();

            $table->unique(['store_id', 'pincode']);
            $table->index('pincode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_pincodes');
    }
};
