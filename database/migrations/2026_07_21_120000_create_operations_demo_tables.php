<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->json('views')->nullable()->after('lng');
            $table->string('locality')->nullable()->after('views');
            $table->string('zone_key')->nullable()->after('locality');
        });

        Schema::create('batch_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('orders_per_batch')->default(5);
            $table->unsignedInteger('accept_minutes')->default(5);
            $table->decimal('max_distance_km', 8, 2)->default(10);
            $table->unsignedInteger('max_route_minutes')->default(45);
            $table->boolean('prefer_store_drivers')->default(true);
            $table->boolean('auto_fallback_zone')->default(true);
            $table->string('slot_window')->nullable();
            $table->timestamps();
        });

        Schema::create('batch_hubs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('zone')->nullable();
            $table->string('branch')->nullable();
            $table->unsignedInteger('pending')->default(0);
            $table->unsignedInteger('drivers_count')->default(0);
            $table->unsignedInteger('est_batches')->default(0);
            $table->string('status')->default('active');
            $table->string('slot')->nullable();
            $table->string('color')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_batches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('zone')->nullable();
            $table->string('zone_key')->nullable();
            $table->string('route_label')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('stops')->default(0);
            $table->string('distance')->nullable();
            $table->string('est_time')->nullable();
            $table->decimal('value', 10, 2)->default(0);
            $table->string('driver_code')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_avatar')->nullable();
            $table->decimal('hub_lat', 10, 7)->nullable();
            $table->decimal('hub_lng', 10, 7)->nullable();
            $table->string('hub_name')->nullable();
            $table->string('route_hub_to_first')->nullable();
            $table->string('route_return')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_batch_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_batch_id')->constrained('delivery_batches')->cascadeOnDelete();
            $table->unsignedInteger('stop');
            $table->string('order_code');
            $table->string('customer');
            $table->string('address')->nullable();
            $table->string('locality')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->decimal('value', 10, 2)->default(0);
            $table->string('payment')->nullable();
            $table->string('prep')->nullable();
            $table->string('delivery')->nullable();
            $table->timestamps();
        });

        Schema::create('driver_payout_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('driver_code')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('type')->nullable();
            $table->string('zone')->nullable();
            $table->string('avatar')->nullable();
            $table->decimal('lifetime_paid', 12, 2)->default(0);
            $table->decimal('pending_amount', 12, 2)->default(0);
            $table->unsignedInteger('paid_orders')->default(0);
            $table->timestamp('last_payout_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('driver_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('driver_payout_profile_id')->constrained('driver_payout_profiles')->cascadeOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->string('period')->nullable();
            $table->string('method')->nullable();
            $table->string('reference')->nullable();
            $table->string('status')->default('paid');
            $table->timestamps();
        });

        Schema::create('driver_payout_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_payout_id')->constrained('driver_payouts')->cascadeOnDelete();
            $table->string('order_code');
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('bonus', 10, 2)->default(0);
            $table->decimal('deduction', 10, 2)->default(0);
            $table->decimal('net', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('bank_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('requested_date');
            $table->string('bank');
            $table->string('account_ending', 20)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->date('settled_date')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_order_earnings', function (Blueprint $table) {
            $table->id();
            $table->string('order_code');
            $table->timestamp('earned_at')->nullable();
            $table->string('store_name')->nullable();
            $table->string('customer')->nullable();
            $table->string('driver_name')->nullable();
            $table->decimal('order_amount', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('refund', 10, 2)->default(0);
            $table->decimal('net_earning', 10, 2)->default(0);
            $table->string('status')->default('succeeded');
            $table->timestamps();
        });

        Schema::create('platform_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->timestamp('occurred_at')->nullable();
            $table->string('type');
            $table->string('order_code')->nullable();
            $table->string('driver_name')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('succeeded');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_transactions');
        Schema::dropIfExists('platform_order_earnings');
        Schema::dropIfExists('bank_transfers');
        Schema::dropIfExists('driver_payout_orders');
        Schema::dropIfExists('driver_payouts');
        Schema::dropIfExists('driver_payout_profiles');
        Schema::dropIfExists('delivery_batch_stops');
        Schema::dropIfExists('delivery_batches');
        Schema::dropIfExists('batch_hubs');
        Schema::dropIfExists('batch_settings');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['views', 'locality', 'zone_key']);
        });
    }
};
