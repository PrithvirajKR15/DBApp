<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store drivers and third-party (independent) drivers behave differently
     * enough — batching eligibility, ownership, assignment method — that
     * "which kind of driver is this" needs to be a fast, indexed check
     * rather than an implicit join through driver_assignments every time.
     *
     * driver_assignments remains the source of truth for *which* store/zone
     * a driver is tied to (and its history); driver_type is a denormalized,
     * kept-in-sync flag for the type itself.
     */
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('driver_type')->default('third_party')->after('user_id');
        });

        // Backfill from the existing assignment record: a driver with an
        // active "store" assignment is a store driver, everything else
        // (zone assignment, or no assignment yet) is third-party.
        DB::table('drivers')
            ->whereIn('id', function ($query) {
                $query->select('driver_id')
                    ->from('driver_assignments')
                    ->where('type', 'store')
                    ->where('is_active', true);
            })
            ->update(['driver_type' => 'store']);

        Schema::table('drivers', function (Blueprint $table) {
            $table->index('driver_type');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropIndex(['driver_type']);
            $table->dropColumn('driver_type');
        });
    }
};
