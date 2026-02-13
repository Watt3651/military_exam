<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Section 5.2.7 - border_areas 🔥
     * พื้นที่ชายแดน (ใช้คำนวณคะแนนพิเศษ)
     *
     * code: เช่น BA01, BA02 — รหัสพื้นที่ชายแดน
     * special_score: คะแนนพิเศษที่จะบวกเพิ่มให้ผู้สมัครที่อยู่ในพื้นที่นี้
     *
     * Dependencies: users (created_by, updated_by)
     */
    public function up(): void
    {
        Schema::create('border_areas', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('รหัสพื้นที่ เช่น BA01, BA02');
            $table->string('name')->comment('ชื่อพื้นที่ เช่น จ.นราธิวาส');
            $table->decimal('special_score', 5, 2)->default(0)->comment('คะแนนพิเศษ');
            $table->text('description')->nullable()->comment('รายละเอียดเพิ่มเติม');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable()->comment('ผู้สร้าง (FK users)');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('ผู้แก้ไขล่าสุด (FK users)');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('code', 'idx_border_areas_code');
            $table->index('is_active', 'idx_border_areas_is_active');

            // Foreign Keys
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('border_areas');
    }
};
