<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Detailed line items from the checkout app (code, name, qty, price, …).
            // `items` stays as the integer count used by existing ops UI.
            $table->json('line_items')->nullable()->after('items');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('line_items');
        });
    }
};
