<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zone_pincodes', function (Blueprint $table) {
            // Optional pin-level coords (defaults from the parent zone when saved).
            $table->decimal('lat', 10, 7)->nullable()->after('pincode');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
        });
    }

    public function down(): void
    {
        Schema::table('zone_pincodes', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
    }
};
