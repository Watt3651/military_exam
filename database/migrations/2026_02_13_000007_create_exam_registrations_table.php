<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Section 5.2.4 - exam_registrations
     * การลงทะเบียนสอบ — เชื่อม examinee กับ exam_session
     *
     * Dependencies: examinees, exam_sessions, test_locations, position_quotas
     *
     * Business Rules:
     * - unique(examinee_id, exam_session_id) → 1 คน สมัครได้ 1 รอบเท่านั้น
     * - exam_number: 5 หลัก XYZNN
     *     X = branch.code, Y = test_location.code, Z = exam_level digit, NN = ลำดับ
     * - status flow: pending → confirmed → (cancelled)
     */
    public function up(): void
    {
        Schema::create('exam_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('examinee_id')->comment('FK examinees — ผู้เข้าสอบ');
            $table->unsignedBigInteger('exam_session_id')->comment('FK exam_sessions — รอบสอบ');
            $table->string('exam_number', 5)->nullable()->comment('หมายเลขสอบ 5 หลัก: XYZNN');
            $table->unsignedBigInteger('test_location_id')->comment('FK test_locations — สถานที่สอบ');
            $table->unsignedBigInteger('position_quota_id')->nullable()->comment('FK position_quotas — อัตราตำแหน่ง');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending')->comment('สถานะการสมัคร');
            $table->timestamp('registered_at')->useCurrent()->comment('วันเวลาที่สมัคร');
            $table->timestamps();

            // Unique constraint: 1 คน ต่อ 1 รอบสอบ
            $table->unique(['examinee_id', 'exam_session_id'], 'unique_examinee_session');

            // Indexes
            $table->index('exam_session_id', 'idx_exam_registrations_session');
            $table->index('test_location_id', 'idx_exam_registrations_location');
            $table->index('exam_number', 'idx_exam_registrations_exam_number');
            $table->index('status', 'idx_exam_registrations_status');

            // Foreign Keys
            $table->foreign('examinee_id')
                ->references('id')
                ->on('examinees')
                ->onDelete('cascade');

            $table->foreign('exam_session_id')
                ->references('id')
                ->on('exam_sessions')
                ->onDelete('cascade');

            $table->foreign('test_location_id')
                ->references('id')
                ->on('test_locations')
                ->onDelete('restrict');

            $table->foreign('position_quota_id')
                ->references('id')
                ->on('position_quotas')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_registrations');
    }
};
