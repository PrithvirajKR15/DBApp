<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // External POS / ordering-app store key (e.g. "STORE_99").
            $table->string('external_store_id')->nullable()->unique()->after('code');
            // Cached list for API responses; authoritative rows live in store_pincodes.
            $table->json('serviceable_pincodes')->nullable()->after('lng');
        });

        // Backfill external_store_id from the existing unique `code` so sync
        // can resolve stores that were seeded before this column existed.
        DB::table('stores')->whereNull('external_store_id')->update([
            'external_store_id' => DB::raw('code'),
        ]);
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['external_store_id', 'serviceable_pincodes']);
        });
    }
};
