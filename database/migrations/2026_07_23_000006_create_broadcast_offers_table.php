<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per (order, notified third-party driver). First driver to
     * accept flips their row to 'accepted' and every sibling row for the
     * same order is atomically flipped to 'expired' in the same query — see
     * BroadcastDispatchService::acceptOffer(). A unique (order_id, driver_id)
     * pair stops the same driver being notified twice for the same order.
     */
    public function up(): void
    {
        Schema::create('broadcast_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending | accepted | expired | rejected
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'driver_id']);
            $table->index(['order_id', 'status']);
            $table->index(['driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_offers');
    }
};
