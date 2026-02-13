<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add missing FK indexes and high-impact composite indexes
     * based on current query patterns in services/livewire.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('created_by', 'idx_users_created_by');
            $table->index(['role', 'is_active'], 'idx_users_role_active');
        });

        Schema::table('border_areas', function (Blueprint $table) {
            $table->index('created_by', 'idx_border_areas_created_by');
            $table->index('updated_by', 'idx_border_areas_updated_by');
        });

        Schema::table('position_quotas', function (Blueprint $table) {
            $table->unique(['exam_session_id', 'position_name'], 'uq_position_quotas_session_position');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->index(['is_active', 'is_archived', 'year'], 'idx_exam_sessions_active_archived_year');
        });

        Schema::table('exam_registrations', function (Blueprint $table) {
            // Missing FK index
            $table->index('position_quota_id', 'idx_exam_registrations_position_quota');

            // Frequent filters in dashboards/reports/generation
            $table->index(['exam_session_id', 'status'], 'idx_exam_registrations_session_status');
            $table->index(['exam_session_id', 'test_location_id'], 'idx_exam_registrations_session_location');
            $table->index(['exam_session_id', 'exam_number'], 'idx_exam_registrations_session_exam_number');

            // Latest registration lookups by examinee
            $table->index(['examinee_id', 'registered_at'], 'idx_exam_registrations_examinee_registered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->dropIndex('idx_exam_registrations_examinee_registered_at');
            $table->dropIndex('idx_exam_registrations_session_exam_number');
            $table->dropIndex('idx_exam_registrations_session_location');
            $table->dropIndex('idx_exam_registrations_session_status');
            $table->dropIndex('idx_exam_registrations_position_quota');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_exam_sessions_active_archived_year');
        });

        Schema::table('position_quotas', function (Blueprint $table) {
            $table->dropUnique('uq_position_quotas_session_position');
        });

        Schema::table('border_areas', function (Blueprint $table) {
            $table->dropIndex('idx_border_areas_updated_by');
            $table->dropIndex('idx_border_areas_created_by');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role_active');
            $table->dropIndex('idx_users_created_by');
        });
    }
};
