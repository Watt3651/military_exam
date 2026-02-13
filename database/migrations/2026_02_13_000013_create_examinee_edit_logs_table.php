<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Section 5.2.9 - examinee_edit_logs 🔥
     * บันทึกการแก้ไขข้อมูลผู้เข้าสอบโดย Staff
     *
     * Dependencies: examinees, users
     *
     * เก็บทุกครั้งที่ Staff แก้ไขข้อมูลผู้เข้าสอบ:
     * - field_name: ชื่อ field ที่แก้ เช่น 'rank', 'first_name', 'branch_id'
     * - old_value / new_value: ค่าก่อน/หลังแก้ไข
     * - reason: เหตุผลที่แก้ (required จาก UI)
     */
    public function up(): void
    {
        Schema::create('examinee_edit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('examinee_id')->comment('FK examinees — ผู้เข้าสอบที่ถูกแก้ไข');
            $table->unsignedBigInteger('edited_by')->comment('FK users — เจ้าหน้าที่ที่แก้ไข');
            $table->string('field_name', 100)->comment('ชื่อ field ที่แก้ไข');
            $table->text('old_value')->nullable()->comment('ค่าเดิม');
            $table->text('new_value')->nullable()->comment('ค่าใหม่');
            $table->text('reason')->nullable()->comment('เหตุผลในการแก้ไข');
            $table->timestamp('edited_at')->useCurrent()->comment('วันเวลาที่แก้ไข');
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('examinee_id', 'idx_edit_logs_examinee');
            $table->index('edited_by', 'idx_edit_logs_edited_by');
            $table->index('field_name', 'idx_edit_logs_field_name');
            $table->index('edited_at', 'idx_edit_logs_edited_at');

            // Foreign Keys
            $table->foreign('examinee_id')
                ->references('id')
                ->on('examinees')
                ->onDelete('cascade');

            $table->foreign('edited_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examinee_edit_logs');
    }
};
