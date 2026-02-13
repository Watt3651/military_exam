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
                'name' => 'กรุงเทพมหานคร',
                'code' => '1',
                'address' => 'ศูนย์สอบส่วนกลาง กรุงเทพมหานคร',
                'capacity' => 500,
                'is_active' => true,
            ],
            [
                'name' => 'จ.เชียงใหม่',
                'code' => '2',
                'address' => 'ศูนย์สอบภาคเหนือ จ.เชียงใหม่',
                'capacity' => 300,
                'is_active' => true,
            ],
            [
                'name' => 'จ.นครราชสีมา',
                'code' => '3',
                'address' => 'ศูนย์สอบภาคตะวันออกเฉียงเหนือ จ.นครราชสีมา',
                'capacity' => 400,
                'is_active' => true,
            ],
            [
                'name' => 'จ.ขอนแก่น',
                'code' => '4',
                'address' => 'ศูนย์สอบภาคตะวันออกเฉียงเหนือ จ.ขอนแก่น',
                'capacity' => 350,
                'is_active' => true,
            ],
            [
                'name' => 'จ.สงขลา',
                'code' => '5',
                'address' => 'ศูนย์สอบภาคใต้ จ.สงขลา',
                'capacity' => 300,
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
