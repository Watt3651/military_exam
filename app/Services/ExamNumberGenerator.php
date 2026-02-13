<?php

namespace App\Services;

use App\Models\ExamRegistration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * ExamNumberGenerator Service — สร้างหมายเลขสอบอัตโนมัติ
 *
 * Section 9.1 / Algorithm from Section 2.5.3
 *
 * รูปแบบหมายเลขสอบ: XYZNN
 * - X   = test_location.code (1 หลัก)
 * - Y   = branch.code (1 หลัก)
 * - ZNN = running sequence 3 หลัก (001-999)
 */
class ExamNumberGenerator
{
    /**
     * Generate หมายเลขสอบให้ผู้สมัครที่อยู่สถานะ pending ของรอบสอบที่กำหนด
     *
     * Algorithm:
     * 1) ดึง exam_registrations ที่ status = pending ของ exam_session ที่ระบุ
     * 2) Group ตาม (test_location.code, branch.code)
     * 3) Sort ตาม user.first_name ASC ภายในแต่ละ group
     * 4) สร้างหมายเลขสอบรูปแบบ XYZNN
     * 5) อัปเดต exam_number + เปลี่ยนสถานะเป็น confirmed
     * 6) คืนค่าจำนวนรายการที่อัปเดต
     *
     * @param  int  $examSessionId  id ของรอบสอบที่ต้องการ generate เลข
     * @return int  จำนวนผู้สมัครที่ถูก generate เลขและยืนยันสถานะแล้ว
     */
    public function generate(int $examSessionId): int
    {
        return DB::transaction(function () use ($examSessionId): int {
            // Step 1: ดึงรายการ pending พร้อม relation ที่ใช้ใน algorithm
            $pendingRegistrations = ExamRegistration::query()
                ->where('exam_session_id', $examSessionId)
                ->where('status', ExamRegistration::STATUS_PENDING)
                ->with([
                    'testLocation:id,code',
                    'examinee:id,user_id,branch_id',
                    'examinee.branch:id,code',
                    'examinee.user:id,first_name',
                ])
                ->get();

            if ($pendingRegistrations->isEmpty()) {
                return 0;
            }

            // Step 2: Group by (test_location.code, branch.code)
            $grouped = $pendingRegistrations->groupBy(function (ExamRegistration $registration): string {
                $locationCode = (string) optional($registration->testLocation)->code;
                $branchCode = (string) optional(optional($registration->examinee)->branch)->code;
                return "{$locationCode}|{$branchCode}";
            });

            $updatedCount = 0;

            /** @var Collection<string, Collection<int, ExamRegistration>> $grouped */
            foreach ($grouped as $groupKey => $registrations) {
                [$locationCode, $branchCode] = explode('|', $groupKey);

                // ถ้าข้อมูลหลักไม่ครบ (เช่น ไม่มี code) ให้ข้ามเพื่อไม่สร้างเลขผิดรูปแบบ
                if (! $this->isOneDigitCode($locationCode) || ! $this->isOneDigitCode($branchCode)) {
                    continue;
                }

                // Step 3: Sort ตาม user.first_name ASC ภายในกลุ่ม
                $sorted = $registrations->sortBy(
                    fn (ExamRegistration $registration): string => (string) optional(optional($registration->examinee)->user)->first_name
                )->values();

                // หา sequence ล่าสุดของ prefix เดิมในรอบสอบนี้ เพื่อกันเลขซ้ำเมื่อรันหลายครั้ง
                $sequence = $this->getCurrentMaxSequence($examSessionId, $locationCode, $branchCode);

                // Step 4 + 5: Generate หมายเลข XYZNN แล้ว update exam_number + status=confirmed
                foreach ($sorted as $registration) {
                    $sequence++;

                    if ($sequence > 999) {
                        throw new RuntimeException(
                            "Sequence overflow for prefix {$locationCode}{$branchCode} in exam_session_id={$examSessionId}"
                        );
                    }

                    $examNumber = sprintf('%s%s%03d', $locationCode, $branchCode, $sequence);

                    $registration->update([
                        'exam_number' => $examNumber,
                        'status' => ExamRegistration::STATUS_CONFIRMED,
                    ]);

                    $updatedCount++;
                }
            }

            // Step 6: Return จำนวนที่อัปเดตทั้งหมด
            return $updatedCount;
        });
    }

    /**
     * ดึง sequence ล่าสุดของ prefix (XY) ในรอบสอบที่กำหนด
     *
     * เช่น prefix 12 และเลขล่าสุด 12057 -> return 57
     * ถ้ายังไม่พบ -> return 0 (เพื่อให้เริ่มที่ 001)
     */
    private function getCurrentMaxSequence(int $examSessionId, string $locationCode, string $branchCode): int
    {
        $prefix = $locationCode . $branchCode;

        $maxExamNumber = (string) ExamRegistration::query()
            ->where('exam_session_id', $examSessionId)
            ->whereNotNull('exam_number')
            ->where('exam_number', 'like', $prefix . '%')
            ->max('exam_number');

        if ($maxExamNumber === '' || strlen($maxExamNumber) < 5) {
            return 0;
        }

        return (int) substr($maxExamNumber, -3);
    }

    /**
     * ตรวจว่าเป็นรหัส 1 หลัก ช่วง 1-9 หรือไม่
     */
    private function isOneDigitCode(string $code): bool
    {
        return (bool) preg_match('/^[1-9]$/', $code);
    }
}
