<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Section 5.2.3 - exam_sessions
     * รอบสอบ — 1 ปี มี 2 ระดับ (จ่าเอก / พันจ่าเอก)
     *
     * Dependencies: none
     *
     * Business Rules:
     * - unique(year, exam_level) → 1 ปี 1 ระดับ ได้แค่ 1 รอบ
     * - is_active: ใช้เปิด/ปิดรับสมัคร
     * - is_archived: เก็บข้อมูลปีเก่า
     */
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->comment('ปีการสอบ (พ.ศ.)');
            $table->enum('exam_level', ['sergeant_major', 'master_sergeant'])
                ->comment('ระดับการสอบ: จ่าเอก / พันจ่าเอก');
            $table->date('registration_start')->comment('วันเริ่มรับสมัคร');
            $table->date('registration_end')->comment('วันปิดรับสมัคร');
            $table->date('exam_date')->comment('วันสอบ');
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งานอยู่');
            $table->boolean('is_archived')->default(false)->comment('ถูก archive แล้ว');
            $table->timestamps();

            // Unique constraint: 1 ปี + 1 ระดับ = 1 รอบสอบเท่านั้น
            $table->unique(['year', 'exam_level'], 'unique_year_level');

            // Indexes
            $table->index('is_active', 'idx_exam_sessions_is_active');
            $table->index('year', 'idx_exam_sessions_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
