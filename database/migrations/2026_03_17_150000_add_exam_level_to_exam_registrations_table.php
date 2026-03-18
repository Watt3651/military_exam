<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add exam_level to exam_registrations table
     */
    public function up(): void
    {
        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->enum('exam_level', ['sergeant_major', 'master_sergeant'])->nullable()->after('position_quota_id');
            $table->index('exam_level', 'idx_exam_registrations_exam_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->dropIndex('idx_exam_registrations_exam_level');
            $table->dropColumn('exam_level');
        });
    }
};
