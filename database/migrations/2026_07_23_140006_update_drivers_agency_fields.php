<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->foreignId('agency_branch_id')->nullable()->after('partner_type')->constrained('agency_branches')->nullOnDelete();
            $table->string('agency_registration_number')->nullable()->after('agency_branch_id');
        });

        Schema::table('drivers', function (Blueprint $table) {
            if (Schema::hasColumn('drivers', 'agency_name')) {
                $table->dropColumn('agency_name');
            }
            if (Schema::hasColumn('drivers', 'agency_id')) {
                $table->dropColumn('agency_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('agency_name')->nullable()->after('partner_type');
            $table->string('agency_id')->nullable()->after('agency_name');
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agency_branch_id');
            $table->dropColumn('agency_registration_number');
        });
    }
};
