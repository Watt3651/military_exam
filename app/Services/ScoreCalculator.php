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
 *   คะแนนค้างบรรจุ (Pending) = คำนวณตามขั้นบันได แล้วหักคะแนนตามปีที่ถูกงดบำเหน็จ
 *   ปีที่ 1-5:   ได้ปีละ 2 คะแนน (หัก 2 คะแนนต่อปีที่ถูกงด)
 *   ปีที่ 6-10:  ได้ปีละ 3 คะแนน (หัก 3 คะแนนต่อปีที่ถูกงด)
 *   ปีที่ 11-15: ได้ปีละ 4 คะแนน (หัก 4 คะแนนต่อปีที่ถูกงด)
 *   ปีที่ 16+:   ได้ปีละ 5 คะแนน (หัก 5 คะแนนต่อปีที่ถูกงด)
 *   คะแนนสูงสุด: 50 คะแนน
 *
 *   คะแนนพิเศษ (Special) = border_areas.special_score
 *   คะแนนรวม (Total)    = Pending + Special
 *
 * Usage:
 *   $calc = new ScoreCalculator();
 *   $suspendedYears = [2566, 2567]; // ปี พ.ศ. ที่ถูกงด
 *   $pending = $calc->calculatePendingScore(2565, $suspendedYears);
 *   $special = $calc->getSpecialScore($borderAreaId);
 *   $total   = $calc->calculateTotalScore($pending, $special);
 */
class ScoreCalculator
{
    /*
    |--------------------------------------------------------------------------
    | Core Calculations
    |--------------------------------------------------------------------------
    */

    /**
     * คำนวณคะแนนค้างบรรจุ (Pending Score) แบบขั้นบันได พร้อมหักตามปีที่ถูกงดบำเหน็จ
     *
     * Formula:
     *   1. คำนวณคะแนนรวมจากปีที่มีสิทธิ์ถึงปีปัจจุบัน ตามขั้นบันได
     *   2. หักคะแนนสำหรับแต่ละปีที่ถูกงดบำเหน็จ ตาม tier ของปีนั้น
     *
     * Tier:
     *   ปีที่ 1-5:   2 คะแนน/ปี (หัก 2 คะแนน/ปีที่งด)
     *   ปีที่ 6-10:  3 คะแนน/ปี (หัก 3 คะแนน/ปีที่งด)
     *   ปีที่ 11-15: 4 คะแนน/ปี (หัก 4 คะแนน/ปีที่งด)
     *   ปีที่ 16+:   5 คะแนน/ปี (หัก 5 คะแนน/ปีที่งด)
     *
     *   คะแนนสูงสุด: 50 คะแนน
     *
     * @param  int           $eligibleYear     ปีที่มีสิทธิ์สอบ (พ.ศ.)
     * @param  array<int>    $suspendedYears   ปี พ.ศ. ที่ถูกงดบำเหน็จ [2566, 2567, ...]
     * @param  int|null      $currentYear      ปีปัจจุบัน พ.ศ. (null = คำนวณอัตโนมัติ)
     * @return float        คะแนนค้างบรรจุ (0-50)
     */
    public function calculatePendingScore(
        int $eligibleYear,
        array $suspendedYears = [],
        ?int $currentYear = null,
    ): float {
        $currentYear = $currentYear ?? $this->getCurrentBuddhistYear();

        // คะแนนรวมก่อนหัก (คำนวณจากปีที่มีสิทธิ์ถึงปีปัจจุบัน)
        $totalYears = max(0, $currentYear - $eligibleYear);
        $grossScore = $this->calculateTieredScore($totalYears);

        // คำนวณคะแนนที่ต้องหัก (ตามปีที่ถูกงดบำเหน็จ)
        $deductionScore = 0;
        foreach ($suspendedYears as $suspendedYear) {
            // ตรวจสอบว่าปีที่ถูกงดอยู่ในช่วงที่มีสิทธิ์หรือไม่
            if ($suspendedYear >= $eligibleYear && $suspendedYear <= $currentYear) {
                // หาว่าปีที่ถูกงดอยู่ใน tier ไหน
                $yearIndex = $suspendedYear - $eligibleYear + 1; // ปีที่ 1, 2, 3, ...
                $deductionScore += $this->getTierPoints($yearIndex);
            }
        }

        // คะแนนสุทธิ = คะแนนรวม - คะแนนที่หัก (ไม่ต่ำกว่า 0)
        $netScore = max(0, $grossScore - $deductionScore);

        // คะแนนสูงสุด 50 คะแนน
        return (float) min(50, $netScore);
    }

    /**
     * คำนวณคะแนนตามขั้นบันไดจากจำนวนปี
     *
     * @param  int  $years  จำนวนปี
     * @return float คะแนนรวม
     */
    private function calculateTieredScore(int $years): float
    {
        $score = 0;

        if ($years > 0) {
            // ปีที่ 1-5: ปีละ 2 คะแนน
            $yearsInTier1 = min($years, 5);
            $score += $yearsInTier1 * 2;
        }

        if ($years > 5) {
            // ปีที่ 6-10: ปีละ 3 คะแนน
            $yearsInTier2 = min($years - 5, 5);
            $score += $yearsInTier2 * 3;
        }

        if ($years > 10) {
            // ปีที่ 11-15: ปีละ 4 คะแนน
            $yearsInTier3 = min($years - 10, 5);
            $score += $yearsInTier3 * 4;
        }

        if ($years > 15) {
            // ปีที่ 16+: ปีละ 5 คะแนน
            $yearsInTier4 = $years - 15;
            $score += $yearsInTier4 * 5;
        }

        return (float) $score;
    }

    /**
     * ดึงคะแนนตาม tier ของปี
     *
     * @param  int  $yearIndex  ลำดับปี (1, 2, 3, ...)
     * @return float คะแนนที่ต้องหัก
     */
    private function getTierPoints(int $yearIndex): float
    {
        if ($yearIndex <= 5) {
            return 2; // ปีที่ 1-5: หัก 2 คะแนน
        } elseif ($yearIndex <= 10) {
            return 3; // ปีที่ 6-10: หัก 3 คะแนน
        } elseif ($yearIndex <= 15) {
            return 4; // ปีที่ 11-15: หัก 4 คะแนน
        } else {
            return 5; // ปีที่ 16+: หัก 5 คะแนน
        }
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
     * @param  int           $eligibleYear     ปีที่มีสิทธิ์สอบ (พ.ศ.)
     * @param  array<int>    $suspendedYears   ปี พ.ศ. ที่ถูกงดบำเหน็จ [2566, 2567, ...]
     * @param  int|null      $borderAreaId     FK border_areas.id (null = ไม่มีพื้นที่ชายแดน)
     * @param  int|null      $currentYear      ปีปัจจุบัน พ.ศ. (null = คำนวณอัตโนมัติ)
     * @return array{pending_score: float, special_score: float, total_score: float}
     */
    public function calculateAll(
        int $eligibleYear,
        array $suspendedYears = [],
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
        // แปลง suspended_years เป็น array (รองรับทั้ง old int และ new json)
        $suspendedYears = $examinee->suspended_years ?? [];
        if (! is_array($suspendedYears)) {
            $suspendedYears = [];
        }

        $scores = $this->calculateAll(
            eligibleYear: $examinee->eligible_year,
            suspendedYears: $suspendedYears,
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
