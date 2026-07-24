<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_timeline_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // Canonical keys: placed, picking, packing, ready, assigned,
            // picked_up, out, delivered — updated from web & mobile.
            $table->string('step_key', 40);
            $table->string('label');
            $table->string('occurred_at')->nullable();
            $table->boolean('is_done')->default(false);
            $table->boolean('is_current')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['order_id', 'step_key']);
            $table->index(['order_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_timeline_steps');
    }
};
