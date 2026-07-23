<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_batch_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('status')->default('open');
            $table->unsignedInteger('batch_count')->default(0);
            $table->unsignedInteger('order_count')->default(0);
            $table->unsignedInteger('overflow_count')->default(0);
            $table->string('slot_window')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
            $table->index('status');
        });

        Schema::table('delivery_batches', function (Blueprint $table) {
            $table->foreignId('batch_group_id')
                ->nullable()
                ->after('store_id')
                ->constrained('delivery_batch_groups')
                ->nullOnDelete();
        });

        // Wrap existing batches into one group per store so the index isn't empty.
        $storeIds = DB::table('delivery_batches')
            ->whereNotNull('store_id')
            ->distinct()
            ->pluck('store_id');

        foreach ($storeIds as $storeId) {
            $batches = DB::table('delivery_batches')
                ->where('store_id', $storeId)
                ->orderBy('id')
                ->get();

            if ($batches->isEmpty()) {
                continue;
            }

            $storeCode = DB::table('stores')->where('id', $storeId)->value('code') ?? 'ST';
            $orderCount = (int) $batches->sum('stops');
            $statuses = $batches->pluck('status')->unique()->values();

            $groupStatus = 'open';
            if ($statuses->every(fn ($s) => in_array($s, ['completed', 'cancelled'], true))) {
                $groupStatus = $statuses->contains('cancelled') && ! $statuses->contains('completed')
                    ? 'cancelled'
                    : 'completed';
            } elseif ($statuses->contains('in_progress')) {
                $groupStatus = 'in_progress';
            }

            $groupId = DB::table('delivery_batch_groups')->insertGetId([
                'code' => 'BG-'.$storeCode.'-LEGACY-'.$storeId,
                'store_id' => $storeId,
                'status' => $groupStatus,
                'batch_count' => $batches->count(),
                'order_count' => $orderCount,
                'overflow_count' => 0,
                'slot_window' => null,
                'created_at' => $batches->first()->created_at ?? now(),
                'updated_at' => now(),
            ]);

            DB::table('delivery_batches')
                ->where('store_id', $storeId)
                ->update(['batch_group_id' => $groupId]);
        }
    }

    public function down(): void
    {
        Schema::table('delivery_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_group_id');
        });

        Schema::dropIfExists('delivery_batch_groups');
    }
};
