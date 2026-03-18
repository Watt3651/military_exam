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
        Schema::table('examinees', function (Blueprint $table) {
            // เปลี่ยนจาก integer เป็น JSON array เก็บปี พ.ศ. ที่ถูกงดบำเหน็จ
            $table->json('suspended_years')->nullable()->comment('ปี พ.ศ. ที่ถูกงดบำเหน็จ [2566, 2567, ...]')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('examinees', function (Blueprint $table) {
            // เปลี่ยนกลับเป็น integer (จำนวนปี)
            $table->integer('suspended_years')->default(0)->comment('ปีที่ถูกงดบำเหน็จ')->change();
        });
    }
};
