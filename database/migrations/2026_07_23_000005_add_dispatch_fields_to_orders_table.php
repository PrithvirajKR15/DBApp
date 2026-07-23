<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * New dispatch lifecycle columns, additive to the existing `delivery`
     * free-text column (left untouched — it still drives existing pages).
     * `status` is the canonical state the new assignment/batching/broadcast
     * services read and write; `assignment_type` records which path an
     * order took (store_batch vs broadcast) and is permanent — a broadcast
     * order can never become store_batch later; `delivery_batch_id` is a
     * real FK replacing the code-string join through delivery_batch_stops.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('delivery');
            $table->string('assignment_type')->nullable()->after('status');
            $table->foreignId('delivery_batch_id')->nullable()->after('driver_id')
                ->constrained('delivery_batches')->nullOnDelete();
        });

        // Backfill delivery_batch_id from the existing code-string join.
        DB::statement(<<<'SQL'
            UPDATE orders
            SET delivery_batch_id = (
                SELECT delivery_batch_stops.delivery_batch_id
                FROM delivery_batch_stops
                WHERE delivery_batch_stops.order_code = orders.code
                LIMIT 1
            )
            WHERE orders.code IN (SELECT order_code FROM delivery_batch_stops)
        SQL);

        DB::table('orders')->whereNotNull('delivery_batch_id')
            ->update(['assignment_type' => 'store_batch']);

        // Backfill status from the legacy `delivery` free-text field.
        $deliveryToStatus = [
            'waiting' => 'pending',
            'assigned' => 'assigned',
            'out' => 'assigned',
            'transit' => 'assigned',
            'delivered' => 'delivered',
            'failed' => 'cancelled',
            'cancelled' => 'cancelled',
        ];

        foreach ($deliveryToStatus as $legacy => $status) {
            DB::table('orders')
                ->whereRaw('LOWER(delivery) = ?', [$legacy])
                ->update(['status' => $status]);
        }

        // Orders already grouped into a batch but not yet picked up by a
        // driver are "batched", not bare "pending".
        DB::table('orders')
            ->whereNotNull('delivery_batch_id')
            ->where('status', 'pending')
            ->update(['status' => 'batched']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_batch_id');
            $table->dropColumn(['status', 'assignment_type']);
        });
    }
};
