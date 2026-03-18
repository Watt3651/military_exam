<?php

namespace Database\Seeders;

use App\Models\TestLocation;
use Illuminate\Database\Seeder;

/**
 * TestLocationSeeder — ข้อมูลสถานที่สอบ (Section 5.3.3)
 *
 * code ใช้เป็นหลักที่ 2 (Y) ของหมายเลขสอบ 5 หลัก (XYZNN)
 * capacity = จำนวนที่นั่งสอบสูงสุด
 */
class TestLocationSeeder extends Seeder
{
    public function run(): void
    {
        $testLocations = [
            [
                'name' => 'ศฝ.นย.',
                'code' => '1',
                'address' => 'ศูนย์สอบพื้นที่สัตหีบ',
                'capacity' => 500,
                'is_active' => true,
            ],
            [
                'name' => 'กรม ร.3 พล.นย.',
                'code' => '2',
                'address' => 'ศูนย์สอบภาคใต้ จว.นราธิวาส',
                'capacity' => 500,
                'is_active' => true,
            ],
            [
                'name' => 'พัน.ร.8 กรม ร.3 พล.นย.',
                'code' => '3',
                'address' => 'ศูนย์สอบภาคใต้ จว.สงขลา',
                'capacity' => 500,
                'is_active' => true,
            ],
            [
                'name' => 'กรม รปภ.ฐท.พง.',
                'code' => '4',
                'address' => 'ศูนย์สอบภาคใต้ จว.พังงา',
                'capacity' => 500,
                'is_active' => true,
            ],
            [
                'name' => 'กอง รปภ.กท.กรม รปภ.นย.',
                'code' => '5',
                'address' => 'ศูนย์สอบภาคกลาง กทม.',
                'capacity' => 500,
                'is_active' => true,
            ],
            [
                'name' => 'พัน.ร.7 กรม ร.3 พล.นย.',
                'code' => '6',
                'address' => 'ศูนย์สอบภาคตะวันออก จว.ระยอง',
                'capacity' => 500,
                'is_active' => true,
            ],
            [
                'name' => 'กรม ทพ.นย.',
                'code' => '7',
                'address' => 'ศูนย์สอบภาคกตะวันออก กรมทหารพราน นย.',
                'capacity' => 500,
                'is_active' => true,
            ],
            [
                'name' => 'กปช.จต.',
                'code' => '8',
                'address' => 'ศูนย์สอบภาคกตะวันออก จว.จันทบุรีั',
                'capacity' => 500,
                'is_active' => true,
            ],
            [
                'name' => 'ฉก.นย.ตราด',
                'code' => '9',
                'address' => 'ศูนย์สอบภาคกตะวันออก จว.ตราด',
                'capacity' => 500,
                'is_active' => true,
            ],
            [
                'name' => 'นรข.',
                'code' => '0',
                'address' => 'ศูนย์สอบภาคตะวันออกเฉียงเหนือ จว.นครพนม',
                'capacity' => 500,
                'is_active' => true,
            ],

        ];

        foreach ($testLocations as $location) {
            TestLocation::updateOrCreate(
                ['code' => $location['code']],
                $location,
            );
        }

        $this->command->info('✅ Test Locations seeded: ' . count($testLocations) . ' สถานที่');
    }
}
