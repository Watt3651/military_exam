<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Section 5.2.2 - examinees
     * ข้อมูลผู้เข้าสอบ (ขยายจาก users)
     *
     * Dependencies: users, branches, border_areas
     *
     * คะแนนค้างบรรจุ = (ปีปัจจุบัน - eligible_year) - suspended_years
     * คะแนนพิเศษ     = ดึงจาก border_areas.special_score
     */
    public function up(): void
    {
        Schema::create('examinees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique()->comment('FK users — 1 user = 1 examinee');
            $table->string('position')->comment('ตำแหน่ง');
            $table->unsignedBigInteger('branch_id')->comment('FK branches — เหล่า');
            $table->integer('age')->comment('อายุ');
            $table->unsignedSmallInteger('eligible_year')->comment('ปีที่มีสิทธิ์สอบ (พ.ศ.)');
            $table->integer('suspended_years')->default(0)->comment('ปีที่ถูกงดบำเหน็จ');
            $table->decimal('pending_score', 5, 2)->default(0)->comment('คะแนนค้างบรรจุ (auto-calculated)');
            $table->decimal('special_score', 5, 2)->default(0)->comment('คะแนนพิเศษ (จาก border_area)');
            $table->unsignedBigInteger('border_area_id')->nullable()->comment('FK border_areas — พื้นที่ชายแดน');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id', 'idx_examinees_user_id');
            $table->index('branch_id', 'idx_examinees_branch_id');
            $table->index('border_area_id', 'idx_examinees_border_area_id');
            $table->index('eligible_year', 'idx_examinees_eligible_year');

            // Foreign Keys
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('restrict');

            $table->foreign('border_area_id')
                ->references('id')
                ->on('border_areas')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examinees');
    }
};
