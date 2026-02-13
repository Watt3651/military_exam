<?php

namespace App\Services;

use App\Models\BorderArea;
use App\Models\Examinee;

/**
 * ScoreCalculator Service — คำนวณคะแนนสอบเลื่อนฐานะ
 *
 * Section 9.2
 *
 * สูตร:
 *   คะแนนค้างบรรจุ (Pending)  = (ปีปัจจุบัน พ.ศ. − ปีที่มีสิทธิ์สอบ) − ปีที่ถูกงดบำเหน็จ
 *   คะแนนพิเศษ     (Special)  = border_areas.special_score ของพื้นที่ที่เลือก
 *   คะแนนรวม       (Total)    = Pending + Special
 *
 * Usage:
 *   $calc = new ScoreCalculator();
 *   $pending = $calc->calculatePendingScore(2565, 0);        // e.g. 4.00
 *   $special = $calc->getSpecialScore($borderAreaId);         // e.g. 5.00
 *   $total   = $calc->calculateTotalScore($pending, $special); // e.g. 9.00
 *
 *   // หรือคำนวณทั้งหมดในครั้งเดียว
 *   $scores  = $calc->calculateAll(2565, 0, $borderAreaId);
 *   // => ['pending_score' => 4.00, 'special_score' => 5.00, 'total_score' => 9.00]
 */
class ScoreCalculator
{
    /*
    |--------------------------------------------------------------------------
    | Core Calculations
    |--------------------------------------------------------------------------
    */

    /**
     * คำนวณคะแนนค้างบรรจุ (Pending Score)
     *
     * Formula: (CurrentYear พ.ศ. − EligibleYear) − SuspendedYears
     * ผลลัพธ์ต้องไม่ติดลบ (minimum 0)
     *
     * @param  int       $eligibleYear    ปีที่มีสิทธิ์สอบ (พ.ศ.)
     * @param  int       $suspendedYears  จำนวนปีที่ถูกงดบำเหน็จ
     * @param  int|null  $currentYear     ปีปัจจุบัน พ.ศ. (null = คำนวณอัตโนมัติ)
     * @return float     คะแนนค้างบรรจุ (≥ 0)
     */
    public function calculatePendingScore(
        int $eligibleYear,
        int $suspendedYears = 0,
        ?int $currentYear = null,
    ): float {
        $currentYear = $currentYear ?? $this->getCurrentBuddhistYear();

        $yearsDiff = $currentYear - $eligibleYear;
        $pendingScore = $yearsDiff - $suspendedYears;

        return (float) max(0, $pendingScore);
    }

    /**
     * คำนวณคะแนนรวม (Total Score)
     *
     * Formula: PendingScore + SpecialScore
     *
     * @param  float  $pendingScore  คะแนนค้างบรรจุ
     * @param  float  $specialScore  คะแนนพิเศษ (จากพื้นที่ชายแดน)
     * @return float  คะแนนรวม
     */
    public function calculateTotalScore(float $pendingScore, float $specialScore): float
    {
        return $pendingScore + $specialScore;
    }

    /*
    |--------------------------------------------------------------------------
    | Special Score Lookup
    |--------------------------------------------------------------------------
    */

    /**
     * ดึงคะแนนพิเศษจาก border_area
     *
     * ถ้าไม่ได้เลือกพื้นที่ชายแดน (null) จะได้ 0.00
     *
     * @param  int|null  $borderAreaId  FK border_areas.id
     * @return float     คะแนนพิเศษ (≥ 0)
     */
    public function getSpecialScore(?int $borderAreaId): float
    {
        if (! $borderAreaId) {
            return 0.00;
        }

        $borderArea = BorderArea::find($borderAreaId);

        return $borderArea ? (float) $borderArea->special_score : 0.00;
    }

    /*
    |--------------------------------------------------------------------------
    | All-in-One Calculation
    |--------------------------------------------------------------------------
    */

    /**
     * คำนวณคะแนนทั้งหมดในครั้งเดียว
     *
     * @param  int       $eligibleYear    ปีที่มีสิทธิ์สอบ (พ.ศ.)
     * @param  int       $suspendedYears  จำนวนปีที่ถูกงดบำเหน็จ
     * @param  int|null  $borderAreaId    FK border_areas.id (null = ไม่มีพื้นที่ชายแดน)
     * @param  int|null  $currentYear     ปีปัจจุบัน พ.ศ. (null = คำนวณอัตโนมัติ)
     * @return array{pending_score: float, special_score: float, total_score: float}
     */
    public function calculateAll(
        int $eligibleYear,
        int $suspendedYears = 0,
        ?int $borderAreaId = null,
        ?int $currentYear = null,
    ): array {
        $pendingScore = $this->calculatePendingScore($eligibleYear, $suspendedYears, $currentYear);
        $specialScore = $this->getSpecialScore($borderAreaId);
        $totalScore = $this->calculateTotalScore($pendingScore, $specialScore);

        return [
            'pending_score' => $pendingScore,
            'special_score' => $specialScore,
            'total_score'   => $totalScore,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Examinee-specific Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * คำนวณและบันทึกคะแนนลง Examinee model
     *
     * @param  Examinee  $examinee     Model ผู้เข้าสอบ
     * @param  int|null  $currentYear  ปีปัจจุบัน พ.ศ. (null = คำนวณอัตโนมัติ)
     * @return array{pending_score: float, special_score: float, total_score: float}
     */
    public function recalculateForExaminee(Examinee $examinee, ?int $currentYear = null): array
    {
        $scores = $this->calculateAll(
            eligibleYear: $examinee->eligible_year,
            suspendedYears: $examinee->suspended_years,
            borderAreaId: $examinee->border_area_id,
            currentYear: $currentYear,
        );

        $examinee->update([
            'pending_score' => $scores['pending_score'],
            'special_score' => $scores['special_score'],
        ]);

        return $scores;
    }

    /**
     * คำนวณคะแนนสำหรับ Examinee ทั้งหมดในรอบสอบ
     *
     * ใช้ตอนต้องการ batch recalculate เช่น เมื่อ border_area score เปลี่ยน
     *
     * @param  int|null  $currentYear  ปีปัจจุบัน พ.ศ. (null = คำนวณอัตโนมัติ)
     * @return int       จำนวน examinee ที่คำนวณใหม่
     */
    public function recalculateAll(?int $currentYear = null): int
    {
        $count = 0;

        Examinee::with('borderArea')
            ->chunk(100, function (\Illuminate\Database\Eloquent\Collection $examinees) use ($currentYear, &$count) {
                /** @var Examinee $examinee */
                foreach ($examinees as $examinee) {
                    $this->recalculateForExaminee($examinee, $currentYear);
                    $count++;
                }
            });

        return $count;
    }

    /*
    |--------------------------------------------------------------------------
    | Utility
    |--------------------------------------------------------------------------
    */

    /**
     * ปีปัจจุบัน (พ.ศ.)
     *
     * @return int เช่น 2569
     */
    public function getCurrentBuddhistYear(): int
    {
        return (int) date('Y') + 543;
    }
}
