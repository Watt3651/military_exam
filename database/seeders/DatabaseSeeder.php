<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder — Main Seeder
 *
 * ลำดับการ seed เรียงตาม dependencies:
 *
 * 1. Master Data (Foundation tables — ไม่มี deps)
 *    - BranchSeeder       → 9 เหล่าทหาร
 *    - BorderAreaSeeder   → 6 พื้นที่ชายแดน
 *    - TestLocationSeeder → 5 สถานที่สอบ
 *
 * 2. Roles & Permissions (ต้อง seed ก่อน users — ใช้ syncRoles)
 *    - RolePermissionSeeder → 3 roles, 35 permissions
 *
 * 3. Default Users (ต้อง seed หลัง roles + master data)
 *    - DefaultUserSeeder → Staff, Commander, Examinee ทดสอบ
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('🚀 ระบบสอบเลื่อนฐานะนายทหารประทวน — Seeding...');
        $this->command->info(str_repeat('─', 50));

        $this->call([
            // 1. Master Data (Foundation tables)
            BranchSeeder::class,
            BorderAreaSeeder::class,
            TestLocationSeeder::class,

            // 2. Roles & Permissions
            RolePermissionSeeder::class,

            // 3. Default Users (ต้อง seed หลัง roles + master data)
            DefaultUserSeeder::class,
        ]);

        $this->command->info(str_repeat('─', 50));
        $this->command->info('✅ Seeding completed!');
        $this->command->info('');
    }
}
