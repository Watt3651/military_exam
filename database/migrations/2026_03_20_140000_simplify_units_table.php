<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // ลบ foreign key ก่อน
            $table->dropForeign(['parent_id']);
            // ลบฟิลด์ที่ไม่ต้องการ
            $table->dropColumn(['type', 'parent_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // เพิ่มฟิลด์กลับมา
            $table->string('type')->after('code'); // regiment, battalion, company, etc.
            $table->unsignedBigInteger('parent_id')->nullable()->after('type');
            $table->integer('level')->default(1)->after('parent_id');
            
            // เพิ่ม foreign key กลับ
            $table->foreign('parent_id')->references('id')->on('units')->onDelete('cascade');
        });
    }
};
