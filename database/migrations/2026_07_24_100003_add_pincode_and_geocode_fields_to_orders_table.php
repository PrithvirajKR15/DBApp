<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Idempotent key from the external checkout app (e.g. "ORD_12345").
            $table->string('external_order_id')->nullable()->unique()->after('code');
            $table->string('pincode', 16)->nullable()->after('address');
            $table->timestamp('geocoded_at')->nullable()->after('lng');
            $table->string('geocode_status')->nullable()->after('geocoded_at'); // success|failed|skipped
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('pincode');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['pincode']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'external_order_id',
                'pincode',
                'geocoded_at',
                'geocode_status',
            ]);
        });
    }
};
