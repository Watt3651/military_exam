<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('examinee', 'staff', 'commander', 'password_support') NOT NULL DEFAULT 'examinee' COMMENT 'บทบาท'");

        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('must_change_password')
                ->default(false)
                ->after('is_active')
                ->comment('บังคับเปลี่ยนรหัสผ่านหลัง login');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('must_change_password');
        });

        DB::statement("ALTER TABLE users MODIFY role ENUM('examinee', 'staff', 'commander') NOT NULL DEFAULT 'examinee' COMMENT 'บทบาท'");
    }
};
