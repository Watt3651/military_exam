<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Section 5.2.5 - test_locations
     * สถานที่สอบ
     *
     * code: ตัวเลข 1 หลัก (1-9) ใช้เป็นส่วนหนึ่งของหมายเลขสอบ
     * เช่น สถานที่ code = '2' ในเหล่า code = '3' → หมายเลขสอบเริ่มด้วย 32xxx
     * capacity: จำนวนที่นั่งสอบสูงสุดของสถานที่นั้น
     */
    public function up(): void
    {
        Schema::create('test_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('ชื่อสถานที่สอบ');
            $table->char('code', 1)->unique()->comment('รหัสสถานที่ 1 หลัก (1-9)');
            $table->text('address')->nullable()->comment('ที่อยู่');
            $table->integer('capacity')->default(0)->comment('จำนวนที่นั่งสอบ');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('code', 'idx_test_locations_code');
            $table->index('is_active', 'idx_test_locations_is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_locations');
    }
};
