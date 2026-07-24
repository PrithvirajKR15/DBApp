<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();

            // Customer extras (name/phone live on orders)
            $table->string('customer_code')->nullable();
            $table->boolean('vip')->default(false);
            $table->string('phone_alt')->nullable();
            $table->string('avatar')->nullable();

            // Delivery address extras + instructions
            $table->string('landmark')->nullable();
            $table->text('instructions')->nullable();

            // Package / payment extras shown on the detail page
            $table->string('packages')->nullable();
            $table->string('weight')->nullable();
            $table->string('card_last4', 4)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
