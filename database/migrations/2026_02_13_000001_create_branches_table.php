<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Section 5.2.6 - branches
     * ตารางเหล่าทหาร (ทหารราบ, ทหารปืนใหญ่, ทหารม้า, etc.)
     *
     * code: ตัวเลข 1 หลัก (1-9) ใช้เป็นส่วนหนึ่งของหมายเลขสอบ
     * เช่น code = '3' → หมายเลขสอบเหล่านี้ขึ้นต้นด้วย 3
     */
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('ชื่อเหล่า เช่น ทหารราบ, ทหารปืนใหญ่');
            $table->char('code', 1)->unique()->comment('รหัสเหล่า 1 หลัก (1-9)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('code', 'idx_branches_code');
            $table->index('is_active', 'idx_branches_is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
