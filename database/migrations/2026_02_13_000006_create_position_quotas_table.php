<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Section 5.2.10 - position_quotas
     * อัตรา (โควตา) ตำแหน่งต่อรอบสอบ
     *
     * Dependencies: exam_sessions
     *
     * เช่น รอบสอบจ่าเอก ปี 2569:
     *   - ตำแหน่ง "ผบ.หมู่" โควตา 50 คน
     *   - ตำแหน่ง "ผช.หน.ชุด" โควตา 30 คน
     */
    public function up(): void
    {
        Schema::create('position_quotas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_session_id')->comment('FK exam_sessions — รอบสอบ');
            $table->string('position_name')->comment('ชื่อตำแหน่ง เช่น ผบ.หมู่');
            $table->integer('quota_count')->default(0)->comment('จำนวนอัตราที่เปิด');
            $table->timestamps();

            // Indexes
            $table->index('exam_session_id', 'idx_position_quotas_exam_session_id');

            // Foreign Keys
            $table->foreign('exam_session_id')
                ->references('id')
                ->on('exam_sessions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('position_quotas');
    }
};
