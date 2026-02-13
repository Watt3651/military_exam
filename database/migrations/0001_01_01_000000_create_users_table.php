<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Section 5.2.1 - users
     * ตาราง users สำหรับระบบสอบเลื่อนฐานะนายทหารประทวน
     *
     * Roles: examinee (ผู้เข้าสอบ), staff (เจ้าหน้าที่), commander (ผู้บังคับบัญชา)
     * Authentication: ใช้ national_id (13 หลัก) + password
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('national_id', 13)->unique()->comment('หมายเลขประจำตัว 13 หลัก');
            $table->string('rank', 100)->comment('ยศ');
            $table->string('first_name')->comment('ชื่อ');
            $table->string('last_name')->comment('นามสกุล');
            $table->string('email')->nullable()->unique()->comment('Email (Staff/Commander)');
            $table->string('password')->comment('bcrypt hash');
            $table->enum('role', ['examinee', 'staff', 'commander'])->default('examinee')->comment('บทบาท');
            $table->boolean('is_active')->default(true)->comment('สถานะใช้งาน');
            $table->unsignedBigInteger('created_by')->nullable()->comment('ผู้สร้างบัญชี (FK users.id)');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('national_id', 'idx_users_national_id');
            $table->index('role', 'idx_users_role');
            $table->index('is_active', 'idx_users_is_active');

            // Self-referencing FK
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
