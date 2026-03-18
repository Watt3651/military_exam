<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove quota_count column from position_quotas table
     */
    public function up(): void
    {
        Schema::table('position_quotas', function (Blueprint $table) {
            $table->dropColumn('quota_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('position_quotas', function (Blueprint $table) {
            $table->integer('quota_count')->default(10)->after('position_name');
        });
    }
};
