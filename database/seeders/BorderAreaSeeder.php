<?php

namespace Database\Seeders;

use App\Models\BorderArea;
use Illuminate\Database\Seeder;

/**
 * BorderAreaSeeder — ข้อมูลพื้นที่ชายแดน (Section 5.3.2)
 *
 * special_score = คะแนนพิเศษที่จะบวกให้ผู้เข้าสอบที่ประจำอยู่ในพื้นที่
 */
class BorderAreaSeeder extends Seeder
{
    public function run(): void
    {
        $borderAreas = [
            [
                'code' => 'BA01',
                'name' => 'จ.นราธิวาส',
                'special_score' => 5.00,
                'description' => 'พื้นที่จังหวัดชายแดนภาคใต้ — ระดับความเสี่ยงสูงสุด',
                'is_active' => true,
            ],
            [
                'code' => 'BA02',
                'name' => 'จ.สงขลา',
                'special_score' => 5.00,
                'description' => 'พื้นที่จังหวัดชายแดนภาคใต้ — ระดับความเสี่ยงสูง',
                'is_active' => true,
            ],
            [
                'code' => 'BA03',
                'name' => 'จ.พังงา',
                'special_score' => 3.00,
                'description' => 'พื้นที่จังหวัดชายแดนภาคใต้ — ระดับความเสี่ยงสูง',
                'is_active' => true,
            ],
            [
                'code' => 'BA04',
                'name' => 'จ.จันทบุรี',
                'special_score' => 3.00,
                'description' => 'เฉพาะอำเภอที่อยู่ในพื้นที่เสี่ยง',
                'is_active' => true,
            ],
            [
                'code' => 'BA05',
                'name' => 'จ.ตราด',
                'special_score' => 3.00,
                'description' => 'เฉพาะอำเภอที่อยู่ในพื้นที่เสี่ยง',
                'is_active' => true,
            ],
            [
                'code' => 'BA06',
                'name' => 'นรข.',
                'special_score' => 3.00,
                'description' => 'พื้นที่ชายแดนภาคตะวันออกเฉียงเหนือ',
                'is_active' => true,
            ],
        ];

        foreach ($borderAreas as $area) {
            BorderArea::updateOrCreate(
                ['code' => $area['code']],
                $area,
            );
        }

        $this->command->info('✅ Border Areas seeded: ' . count($borderAreas) . ' พื้นที่');
    }
}
