<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Operational availability (Online / Offline) is driver-specific and separate
     * from users.status (Active / Pending / Suspended / Rejected).
     */
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('availability')->default('Offline')->after('joined_at');
        });

        // Migrate legacy Offline account statuses onto drivers.availability.
        $offlineUserIds = DB::table('users')
            ->where('status', 'Offline')
            ->pluck('id');

        if ($offlineUserIds->isNotEmpty()) {
            DB::table('drivers')
                ->whereIn('user_id', $offlineUserIds)
                ->update(['availability' => 'Offline']);

            DB::table('users')
                ->whereIn('id', $offlineUserIds)
                ->update(['status' => 'Active']);
        }

        // Drivers still marked Active on the user were previously treated as online.
        $activeQuery = DB::table('drivers')
            ->whereIn('user_id', function ($query) {
                $query->select('id')
                    ->from('users')
                    ->where('status', 'Active');
            })
            ->where('availability', 'Offline');

        if ($offlineUserIds->isNotEmpty()) {
            $activeQuery->whereNotIn('user_id', $offlineUserIds);
        }

        $activeQuery->update(['availability' => 'Online']);
    }

    public function down(): void
    {
        $offlineDriverUserIds = DB::table('drivers')
            ->where('availability', 'Offline')
            ->pluck('user_id');

        if ($offlineDriverUserIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('id', $offlineDriverUserIds)
                ->where('status', 'Active')
                ->update(['status' => 'Offline']);
        }

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn('availability');
        });
    }
};
