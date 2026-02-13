<?php

namespace Database\Seeders;

use App\Models\Examinee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DefaultUserSeeder — บัญชีทดสอบเริ่มต้น (Section 5.3.4)
 *
 * สร้างบัญชีทดสอบ 3 บทบาท:
 *   1. Staff (Admin)  — จัดการระบบทั้งหมด
 *   2. Commander       — ดูรายงาน read-only
 *   3. Examinee        — ผู้เข้าสอบทดสอบ (พร้อมข้อมูล examinee)
 *
 * ⚠️  ควรเปลี่ยนรหัสผ่านหลังจาก deploy ขึ้น production
 */
class DefaultUserSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |----------------------------------------------------------------------
        | 1. Default Staff Account (Admin)
        |----------------------------------------------------------------------
        */
        $staff = User::updateOrCreate(
            ['national_id' => '1000000000001'],
            [
                'rank' => 'จ.ส.อ.',
                'first_name' => 'Admin',
                'last_name' => 'System',
                'email' => 'admin@exam.military.th',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'is_active' => true,
            ],
        );
        $staff->syncRoles('staff');

        /*
        |----------------------------------------------------------------------
        | 2. Default Commander Account
        |----------------------------------------------------------------------
        */
        $commander = User::updateOrCreate(
            ['national_id' => '1000000000002'],
            [
                'rank' => 'พ.อ.',
                'first_name' => 'Commander',
                'last_name' => 'System',
                'email' => 'commander@exam.military.th',
                'password' => Hash::make('password'),
                'role' => 'commander',
                'is_active' => true,
            ],
        );
        $commander->syncRoles('commander');

        /*
        |----------------------------------------------------------------------
        | 3. Default Examinee Account (ผู้เข้าสอบทดสอบ)
        |----------------------------------------------------------------------
        */
        $examineeUser = User::updateOrCreate(
            ['national_id' => '1234567890123'],
            [
                'rank' => 'ส.อ.',
                'first_name' => 'สมชาย',
                'last_name' => 'ทดสอบ',
                'email' => null,
                'password' => Hash::make('password'),
                'role' => 'examinee',
                'is_active' => true,
                'created_by' => $staff->id,
            ],
        );
        $examineeUser->syncRoles('examinee');

        // สร้างข้อมูล examinee (ข้อมูลผู้เข้าสอบ)
        Examinee::updateOrCreate(
            ['user_id' => $examineeUser->id],
            [
                'position' => 'ผบ.หมู่ ร้อย.อาวุธเบา',
                'branch_id' => 1, // ทหารราบ (code: 1)
                'age' => 28,
                'eligible_year' => 2567,
                'suspended_years' => 0,
                'pending_score' => 2.00, // (2569 - 2567) - 0 = 2
                'special_score' => 5.00, // จ.นราธิวาส (BA01)
                'border_area_id' => 1, // BA01: จ.นราธิวาส
            ],
        );

        /*
        |----------------------------------------------------------------------
        | Output
        |----------------------------------------------------------------------
        */
        $this->command->info('✅ Default users seeded:');
        $this->command->info('');
        $this->command->info('   ┌─────────────┬─────────────────┬──────────┬───────────┐');
        $this->command->info('   │ Role        │ National ID     │ Password │ Name      │');
        $this->command->info('   ├─────────────┼─────────────────┼──────────┼───────────┤');
        $this->command->info('   │ Staff       │ 1000000000001   │ password │ Admin     │');
        $this->command->info('   │ Commander   │ 1000000000002   │ password │ Commander │');
        $this->command->info('   │ Examinee    │ 1234567890123   │ password │ สมชาย      │');
        $this->command->info('   └─────────────┴─────────────────┴──────────┴───────────┘');
    }
}
