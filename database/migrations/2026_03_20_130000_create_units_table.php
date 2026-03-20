<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Section 5.2.7 - units
     * ตารางสังกัดทหาร (หน่วยทหาร, กรมทหาร, กองพัน, กองร้อย, etc.)
     *
     * เช่น กรมทหารราบที่ 1, กองพันทหารราบที่ 1, กองร้อยทหารราบที่ 1
     * ใช้เก็บข้อมูลสังกัดปัจจุบันของผู้สอบแทนตำแหน่ง (position)
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('ชื่อสังกัด เช่น กรมทหารราบที่ 1, กองพันทหารราบที่ 1');
            $table->string('code')->unique()->comment('รหัสสังกัด เช่น รบ.1, พัน.รบ.1, ร้อย.รบ.1');
            $table->string('type')->default('battalion')->comment('ประเภทสังกัด: regiment, battalion, company, etc.');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('สังกัดแม่ (ถ้ามี)');
            $table->integer('level')->default(1)->comment('ระดับชั้น: 1=กรม, 2=กองพัน, 3=กองร้อย, 4=หมวด');
            $table->boolean('is_active')->default(true)->comment('สถานะใช้งาน');
            $table->text('description')->nullable()->comment('รายละเอียดเพิ่มเติม');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('code', 'idx_units_code');
            $table->index('type', 'idx_units_type');
            $table->index('level', 'idx_units_level');
            $table->index('is_active', 'idx_units_is_active');
            $table->index('parent_id', 'idx_units_parent_id');

            // Foreign Keys
            $table->foreign('parent_id')
                ->references('id')
                ->on('units')
                ->onDelete('set null');
        });

        // Add FK to examinees table
        Schema::table('examinees', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('position')->comment('FK units - สังกัดปัจจุบัน');
            
            $table->index('unit_id', 'idx_examinees_unit_id');
            $table->foreign('unit_id')
                ->references('id')
                ->on('units')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('examinees', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropIndex('idx_examinees_unit_id');
            $table->dropColumn('unit_id');
        });

        Schema::dropIfExists('units');
    }
};
