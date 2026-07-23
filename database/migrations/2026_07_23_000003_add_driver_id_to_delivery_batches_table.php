<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * delivery_batches previously only stored the assigned driver as
     * denormalized display strings (driver_code/driver_name/driver_avatar) —
     * fine for read-only display, but the assignment/dispatch services need
     * a real relation to query and update. Keep the display strings (still
     * used by existing pages) and add the real FK alongside them.
     */
    public function up(): void
    {
        Schema::table('delivery_batches', function (Blueprint $table) {
            $table->foreignId('driver_id')
                ->nullable()
                ->after('store_id')
                ->constrained('drivers')
                ->nullOnDelete();
        });

        DB::statement(<<<'SQL'
            UPDATE delivery_batches
            SET driver_id = (
                SELECT drivers.id
                FROM drivers
                INNER JOIN users ON users.id = drivers.user_id
                WHERE users.code = delivery_batches.driver_code
                LIMIT 1
            )
            WHERE delivery_batches.driver_code IS NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            UPDATE drivers
            SET current_batch_id = (
                SELECT delivery_batches.id
                FROM delivery_batches
                WHERE delivery_batches.driver_id = drivers.id
                AND delivery_batches.status IN ('assigned', 'in_progress')
                LIMIT 1
            )
            WHERE drivers.id IN (
                SELECT driver_id FROM delivery_batches
                WHERE driver_id IS NOT NULL AND status IN ('assigned', 'in_progress')
            )
        SQL);
    }

    public function down(): void
    {
        Schema::table('delivery_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('driver_id');
        });
    }
};
