<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stops previously only linked to an order via the free-text order_code
     * string (fine for display, fragile for anything transactional). Add a
     * real FK; keep order_code for the existing display code paths.
     */
    public function up(): void
    {
        Schema::table('delivery_batch_stops', function (Blueprint $table) {
            $table->foreignId('order_id')
                ->nullable()
                ->after('delivery_batch_id')
                ->constrained('orders')
                ->nullOnDelete();
        });

        DB::statement(<<<'SQL'
            UPDATE delivery_batch_stops
            SET order_id = (
                SELECT orders.id FROM orders
                WHERE orders.code = delivery_batch_stops.order_code
                LIMIT 1
            )
            WHERE delivery_batch_stops.order_code IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('delivery_batch_stops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
