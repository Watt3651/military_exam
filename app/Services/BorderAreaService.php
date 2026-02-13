<?php

namespace App\Services;

use App\Models\BorderArea;
use App\Models\BorderAreaScoreHistory;
use Illuminate\Support\Facades\DB;

/**
 * BorderAreaService (Section 9.3)
 *
 * จัดการ business logic ของพื้นที่ชายแดน โดยเฉพาะการแก้ไขคะแนนพิเศษ
 * และบันทึกประวัติการเปลี่ยนแปลงคะแนนลงตาราง audit
 * `border_area_score_history`
 */
class BorderAreaService
{
    /**
     * อัปเดตข้อมูล BorderArea และบันทึกประวัติถ้าคะแนนพิเศษเปลี่ยน
     *
     * Logic:
     * 1) เก็บค่า special_score เดิมก่อนแก้ไข
     * 2) อัปเดตข้อมูลพื้นที่ชายแดน
     * 3) ตั้งค่า updated_by เป็นผู้ที่แก้ไข
     * 4) ถ้า special_score เปลี่ยนจริง -> insert log ลง border_area_score_history
     *
     * @param  BorderArea   $borderArea  พื้นที่ชายแดนที่ต้องการแก้ไข
     * @param  array<string, mixed> $data ข้อมูลที่จะอัปเดต
     * @param  int          $userId      ผู้แก้ไข (users.id)
     * @param  string|null  $reason      เหตุผลในการเปลี่ยนคะแนน (optional)
     * @return BorderArea   ข้อมูล BorderArea หลังอัปเดตล่าสุด
     */
    public function updateWithHistory(
        BorderArea $borderArea,
        array $data,
        int $userId,
        ?string $reason = null,
    ): BorderArea {
        return DB::transaction(function () use ($borderArea, $data, $userId, $reason): BorderArea {
            $oldScore = $borderArea->special_score !== null
                ? (float) $borderArea->special_score
                : null;

            // อัปเดตข้อมูลหลักของพื้นที่ชายแดน
            $borderArea->fill($data);
            $borderArea->updated_by = $userId;
            $borderArea->save();

            $newScore = (float) $borderArea->special_score;

            // บันทึก history เฉพาะเมื่อ special_score เปลี่ยนจริง
            $scoreChanged = $oldScore === null || ((float) $oldScore !== $newScore);
            if ($scoreChanged) {
                BorderAreaScoreHistory::create([
                    'border_area_id' => $borderArea->id,
                    'old_score' => $oldScore,
                    'new_score' => $newScore,
                    'changed_by' => $userId,
                    'reason' => $reason,
                    'changed_at' => now(),
                ]);
            }

            return $borderArea->fresh();
        });
    }
}
