<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dual-driver dispatch columns:
     * - store_id: denormalized home store for STORE_ASSIGNED drivers
     * - dispatch_status: IDLE | BUSY | OFFLINE (avoids clashing with users.status
     *   which Driver still exposes via getStatusAttribute)
     */
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('driver_type')
                ->constrained('stores')->nullOnDelete();
            $table->string('dispatch_status')->default('IDLE')->after('availability');
            $table->index('dispatch_status');
        });

        // Map legacy availability → dispatch_status.
        DB::table('drivers')->whereIn('availability', ['Online', 'online'])->update(['dispatch_status' => 'IDLE']);
        DB::table('drivers')->whereIn('availability', ['Transit', 'transit', 'Busy', 'busy'])->update(['dispatch_status' => 'BUSY']);
        DB::table('drivers')->whereIn('availability', ['Offline', 'offline'])->update(['dispatch_status' => 'OFFLINE']);

        // Backfill store_id from the active store assignment when present.
        DB::statement(<<<'SQL'
            UPDATE drivers
            SET store_id = (
                SELECT driver_assignments.store_id
                FROM driver_assignments
                WHERE driver_assignments.driver_id = drivers.id
                  AND driver_assignments.is_active = 1
                  AND driver_assignments.store_id IS NOT NULL
                LIMIT 1
            )
            WHERE drivers.driver_type = 'store'
              AND drivers.store_id IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
            $table->dropIndex(['dispatch_status']);
            $table->dropColumn('dispatch_status');
        });
    }
};
