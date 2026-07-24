<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agency_branch_zones')) {
            Schema::create('agency_branch_zones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['agency_branch_id', 'zone_id']);
            });
        }

        if (! Schema::hasColumn('agency_branches', 'zone_id')) {
            return;
        }

        if (DB::table('agency_branch_zones')->count() === 0) {
            $now = now();
            foreach (DB::table('agency_branches')->whereNotNull('zone_id')->get(['id', 'zone_id']) as $row) {
                DB::table('agency_branch_zones')->insertOrIgnore([
                    'agency_branch_id' => $row->id,
                    'zone_id' => $row->zone_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Composite unique may back the agency_id FK in MySQL — drop FK first.
        $this->dropForeignIfExists('agency_branches', 'agency_branches_agency_id_foreign');
        $this->dropForeignIfExists('agency_branches', 'agency_branches_zone_id_foreign');
        $this->dropIndexIfExists('agency_branches', 'agency_branches_agency_id_zone_id_unique');
        $this->dropIndexIfExists('agency_branches', 'agency_branches_zone_id_foreign');
        $this->dropIndexIfExists('agency_branches', 'agency_branches_agency_id_foreign');

        Schema::table('agency_branches', function (Blueprint $table) {
            $table->dropColumn('zone_id');
        });

        Schema::table('agency_branches', function (Blueprint $table) {
            $table->foreign('agency_id')->references('id')->on('agencies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agency_branches', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->after('agency_id')->constrained()->nullOnDelete();
        });

        $seen = [];
        foreach (DB::table('agency_branch_zones')->orderBy('id')->get() as $pivot) {
            if (isset($seen[$pivot->agency_branch_id])) {
                continue;
            }
            $seen[$pivot->agency_branch_id] = true;
            DB::table('agency_branches')
                ->where('id', $pivot->agency_branch_id)
                ->update(['zone_id' => $pivot->zone_id]);
        }

        Schema::table('agency_branches', function (Blueprint $table) {
            $table->unique(['agency_id', 'zone_id']);
        });

        Schema::dropIfExists('agency_branch_zones');
    }

    private function dropForeignIfExists(string $table, string $name): void
    {
        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $name, 'FOREIGN KEY']
        );

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$name}`");
        }
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        $exists = DB::selectOne(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$table, $name]
        );

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
        }
    }
};
