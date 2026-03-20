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
        // ลบ table ที่มีปัญหา
        Schema::dropIfExists('data_review_logs');
        
        // สร้างใหม่ถูกต้อง
        Schema::create('data_review_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examinee_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('users')->onDelete('cascade');
            $table->text('message');
            $table->enum('status', ['pending', 'reviewed', 'ignored'])->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['examinee_id', 'status']);
            $table->index(['staff_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_review_logs');
    }
};
