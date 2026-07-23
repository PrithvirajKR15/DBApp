<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O(1) "is this store driver busy?" check. A store driver is busy when
     * they hold exactly one active batch at a time (batches are 1 batch : 1
     * driver already). Cleared back to null when the batch is completed or
     * cancelled. Third-party drivers never use this column — their "busy"
     * state is a single active broadcast-accepted order instead.
     */
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->foreignId('current_batch_id')
                ->nullable()
                ->after('driver_type')
                ->constrained('delivery_batches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_batch_id');
        });
    }
};
