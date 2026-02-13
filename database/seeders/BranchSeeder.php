<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

/**
 * BranchSeeder — ข้อมูลเหล่าทหาร (Section 5.3.1)
 *
 * code ใช้เป็นหลักแรก (X) ของหมายเลขสอบ 5 หลัก (XYZNN)
 */
class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['name' => 'ทหารราบ', 'code' => '1', 'is_active' => true],
            ['name' => 'ทหารปืนใหญ่', 'code' => '2', 'is_active' => true],
            ['name' => 'ทหารช่าง', 'code' => '3', 'is_active' => true],
            ['name' => 'ทหารสื่อสาร', 'code' => '4', 'is_active' => true],
            ['name' => 'ทหารขนส่ง', 'code' => '5', 'is_active' => true],
            ['name' => 'ทหารม้า', 'code' => '6', 'is_active' => true],
            ['name' => 'ทหารสรรพาวุธ', 'code' => '7', 'is_active' => true],
            ['name' => 'ทหารพลาธิการ', 'code' => '8', 'is_active' => true],
            ['name' => 'ทหารการเงิน', 'code' => '9', 'is_active' => true],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(
                ['code' => $branch['code']],
                $branch,
            );
        }

        $this->command->info('✅ Branches seeded: ' . count($branches) . ' เหล่า');
    }
}
