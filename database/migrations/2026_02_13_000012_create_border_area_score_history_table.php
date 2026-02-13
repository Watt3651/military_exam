<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Section 5.2.8 - border_area_score_history 🔥
     * ประวัติการเปลี่ยนแปลงคะแนนพิเศษของพื้นที่ชายแดน
     *
     * Dependencies: border_areas, users
     *
     * เก็บทุกครั้งที่ Staff เปลี่ยน special_score ของ border_area
     * old_score = NULL หมายถึงสร้างใหม่ (ยังไม่เคยมีคะแนน)
     */
    public function up(): void
    {
        Schema::create('border_area_score_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('border_area_id')->comment('FK border_areas — พื้นที่ชายแดน');
            $table->decimal('old_score', 5, 2)->nullable()->comment('คะแนนเดิม (NULL = สร้างใหม่)');
            $table->decimal('new_score', 5, 2)->comment('คะแนนใหม่');
            $table->unsignedBigInteger('changed_by')->comment('FK users — เจ้าหน้าที่ที่เปลี่ยน');
            $table->text('reason')->nullable()->comment('เหตุผลที่เปลี่ยน');
            $table->timestamp('changed_at')->useCurrent()->comment('วันเวลาที่เปลี่ยน');
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('border_area_id', 'idx_score_history_border_area');
            $table->index('changed_by', 'idx_score_history_changed_by');
            $table->index('changed_at', 'idx_score_history_changed_at');

            // Foreign Keys
            $table->foreign('border_area_id')
                ->references('id')
                ->on('border_areas')
                ->onDelete('cascade');

            $table->foreign('changed_by')
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
        Schema::dropIfExists('border_area_score_history');
    }
};
