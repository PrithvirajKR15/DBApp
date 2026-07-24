<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_executive_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_executive_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agency_branch_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['agency_executive_id', 'agency_branch_id'], 'agency_exec_branch_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_executive_branches');
    }
};
