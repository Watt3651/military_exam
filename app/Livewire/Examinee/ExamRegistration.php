<?php

namespace App\Livewire\Examinee;

use App\Models\BorderArea;
use App\Models\Branch;
use App\Models\Examinee;
use App\Models\ExamRegistration as ExamRegistrationModel;
use App\Models\ExamSession;
use App\Models\PositionQuota;
use App\Models\TestLocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Exam Registration (Section 2.2.1)
 *
 * ลงทะเบียนสอบเลื่อนฐานะทหาร
 *
 * Business Rules:
 *   - ต้องอยู่ในช่วง registration_start - registration_end (middleware)
 *   - 1 คน สมัครได้ 1 รอบสอบเท่านั้น (unique examinee_id + exam_session_id)
 *   - บันทึก status = 'pending'
 *   - คำนวณคะแนนอัตโนมัติ (pending_score + special_score)
 */
#[Layout('components.layouts.examinee')]
#[Title('ลงทะเบียนสอบ - ระบบสอบเลื่อนฐานะ นย.')]
class ExamRegistration extends Component
{
    // ─── Form Fields (Section 2.2.1) ───
    public string $position = '';
    public string $branch_id = '';
    public string $age = '';
    public string $eligible_year = '';
    public array $suspended_years = []; // เปลี่ยนเป็น array เก็บปี พ.ศ.
    public string $border_area_id = '';
    public string $test_location_id = '';
    public string $exam_level = '';

    // ─── Calculated Scores ───
    public float $calculatedPendingScore = 0;
    public float $calculatedSpecialScore = 0;
    public float $calculatedTotalScore = 0;

    // ─── State ───
    public ?ExamSession $activeSession = null;
    public bool $alreadyRegistered = false;
    public bool $registrationSuccess = false;
    public ?string $registrationMessage = null;

    // ─── Dropdown Data ───
    public Collection $branches;
    public Collection $borderAreas;
    public Collection $testLocations;
    public Collection $positionQuotas;

    // ─── Suspended Years Options ───
    public array $availableSuspendedYears = [];

    public function mount(): void
    {
        $user = Auth::user();

        // ─── Active Session (injected by middleware) ───
        $this->activeSession = ExamSession::registrationOpen()->first();

        if (!$this->activeSession) {
            return;
        }

        // ─── Check if already registered ───
        $examinee = $user->examinee;
        if ($examinee) {
            $exists = ExamRegistrationModel::where('examinee_id', $examinee->id)
                ->where('exam_session_id', $this->activeSession->id)
                ->notCancelled()
                ->exists();

            if ($exists) {
                $this->alreadyRegistered = true;
                return;
            }

            // ─── Load existing examinee data (if any) ───
            $this->branch_id = (string) ($examinee->branch_id ?? '');
            $this->age = (string) ($examinee->age ?? '');
            $this->eligible_year = (string) ($examinee->eligible_year ?? '');
            // แปลง suspended_years เป็น array
            $suspendedYears = $examinee->suspended_years ?? [];
            $this->suspended_years = is_array($suspendedYears) ? $suspendedYears : [];
            $this->border_area_id = (string) ($examinee->border_area_id ?? '');

            // Generate available suspended years options
            $this->generateAvailableSuspendedYears();
        }

        // ─── Load Dropdown Data ───
        $this->branches = Branch::where('is_active', true)->orderBy('code')->get();
        $this->borderAreas = BorderArea::where('is_active', true)->orderBy('code')->get();
        $this->testLocations = TestLocation::where('is_active', true)->orderBy('code')->get();
        $this->positionQuotas = collect(); // Will be loaded based on exam level

        // ─── Initial calculation ───
        $this->recalculate();
    }

    /**
     * สร้างรายการปีที่สามารถเลือกเป็นปีงดบำเหน็จได้
     */
    public function generateAvailableSuspendedYears(): void
    {
        $currentYear = (int) date('Y') + 543;
        $eligibleYear = (int) $this->eligible_year;

        $this->availableSuspendedYears = [];
        if ($eligibleYear > 0 && $eligibleYear <= $currentYear) {
            for ($year = $eligibleYear; $year <= $currentYear; $year++) {
                $yearIndex = $year - $eligibleYear + 1;
                $points = $this->getTierPoints($yearIndex);
                $this->availableSuspendedYears[] = [
                    'year' => $year,
                    'index' => $yearIndex,
                    'points' => $points,
                ];
            }
        }
    }

    /**
     * ดึงคะแนนตาม tier ของปี
     */
    private function getTierPoints(int $yearIndex): int
    {
        if ($yearIndex <= 5) {
            return 2;
        } elseif ($yearIndex <= 10) {
            return 3;
        } elseif ($yearIndex <= 15) {
            return 4;
        } else {
            return 5;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Real-time Score Calculation
    |--------------------------------------------------------------------------
    */

    /**
     * เมื่อ eligible_year เปลี่ยน → คำนวณ pending score ใหม่
     */
    public function updatedEligibleYear(): void
    {
        $this->generateAvailableSuspendedYears();
        $this->recalculate();
    }

    /**
     * เมื่อ suspended_years เปลี่ยน → คำนวณ pending score ใหม่
     */
    public function updatedSuspendedYears(): void
    {
        $this->recalculate();
    }

    /**
     * เมื่อ border_area_id เปลี่ยน → คำนวณ special score ใหม่
     */
    public function updatedBorderAreaId(): void
    {
        $this->recalculate();
    }

    /**
     * คำนวณคะแนนทั้งหมด (real-time)
     */
    private function recalculate(): void
    {
        $calc = new \App\Services\ScoreCalculator();
        $currentYear = $calc->getCurrentBuddhistYear();
        $eligibleYear = (int) $this->eligible_year;

        if ($eligibleYear > 0 && $eligibleYear <= $currentYear) {
            $this->calculatedPendingScore = $calc->calculatePendingScore(
                $eligibleYear,
                $this->suspended_years,
                $currentYear
            );
        } else {
            $this->calculatedPendingScore = 0;
        }

        // Special Score = จาก border_areas.special_score
        if ($this->border_area_id) {
            $borderArea = $this->borderAreas->firstWhere('id', (int) $this->border_area_id);
            $this->calculatedSpecialScore = $borderArea ? (float) $borderArea->special_score : 0;
        } else {
            $this->calculatedSpecialScore = 0;
        }

        // Total
        $this->calculatedTotalScore = $this->calculatedPendingScore + $this->calculatedSpecialScore;
    }

    /**
     * Load position quotas based on selected exam level
     */
    public function loadPositionQuotas(): void
    {
        if (!$this->activeSession || !$this->exam_level) {
            $this->positionQuotas = collect();
            return;
        }

        // Find the correct session for this exam level
        $correctSession = ExamSession::registrationOpen()
            ->where('exam_level', $this->exam_level)
            ->first();
            
        if (!$correctSession) {
            $this->positionQuotas = collect();
            return;
        }

        $this->positionQuotas = PositionQuota::where('exam_session_id', $correctSession->id)
            ->where('exam_level', $this->exam_level)
            ->orderBy('position_name')
            ->get();
            
        // Debug: แสดงจำนวน position quotas ที่โหลดได้
        Log::info('Position quotas loaded', [
            'exam_session_id' => $correctSession->id,
            'exam_level' => $this->exam_level,
            'count' => $this->positionQuotas->count()
        ]);
    }

    /**
     * Updated exam_level property
     */
    public function updatedExamLevel(): void
    {
        $this->loadPositionQuotas();
        $this->position = ''; // Reset position when exam level changes
    }

    /*
    |--------------------------------------------------------------------------
    | Feature: ใช้ข้อมูลปีที่แล้ว (Section 2.2.2)
    |--------------------------------------------------------------------------
    */

    public function loadPreviousData(): void
    {
        $user = Auth::user();
        $examinee = $user->examinee;

        if (!$examinee) {
            $this->addError('general', 'ไม่พบข้อมูลผู้เข้าสอบ');
            return;
        }

        // ดึง registration ล่าสุดจากปีก่อน
        $previous = ExamRegistrationModel::where('examinee_id', $examinee->id)
            ->with(['testLocation'])
            ->orderByDesc('registered_at')
            ->first();

        if (!$previous) {
            $this->addError('general', 'ไม่พบข้อมูลการสมัครก่อนหน้า');
            return;
        }

        // Pre-fill from previous registration
        $this->test_location_id = (string) ($previous->test_location_id ?? '');

        // Pre-fill from examinee data (latest)
        $this->branch_id = (string) ($examinee->branch_id ?? $this->branch_id);
        $this->age = (string) ($examinee->age ?? $this->age);
        $this->border_area_id = (string) ($examinee->border_area_id ?? $this->border_area_id);

        $this->recalculate();

        session()->flash('info', 'โหลดข้อมูลจากการสมัครครั้งก่อนเรียบร้อย กรุณาตรวจสอบและแก้ไขก่อนยืนยัน');
    }

    /*
    |--------------------------------------------------------------------------
    | Submit Registration
    |--------------------------------------------------------------------------
    */

    public function register()
    {
        $validated = $this->validate([
            'position'        => ['required', 'string', 'max:255'],
            'branch_id'       => ['required', 'exists:branches,id'],
            'age'             => ['required', 'integer', 'min:18', 'max:60'],
            'eligible_year'   => ['required', 'integer', 'min:2500', 'max:2600'],
            'suspended_years' => ['nullable', 'array'], // เปลี่ยนเป็น array
            'suspended_years.*' => ['integer', 'min:2500', 'max:2600'],
            'border_area_id'  => ['nullable', 'exists:border_areas,id'],
            'test_location_id' => ['required', 'exists:test_locations,id'],
            'exam_level'      => ['required', 'in:sergeant_major,master_sergeant'],
        ], [
            'position.required'        => 'กรุณาระบุตำแหน่ง',
            'branch_id.required'       => 'กรุณาเลือกเหล่า',
            'branch_id.exists'         => 'เหล่าที่เลือกไม่ถูกต้อง',
            'age.required'             => 'กรุณาระบุอายุ',
            'age.min'                  => 'อายุต้องไม่น้อยกว่า 18 ปี',
            'age.max'                  => 'อายุต้องไม่เกิน 60 ปี',
            'eligible_year.required'   => 'กรุณาระบุปีที่มีสิทธิ์สอบ',
            'eligible_year.min'        => 'ปีที่มีสิทธิ์สอบไม่ถูกต้อง',
            'suspended_years.array'    => 'ข้อมูลปีที่ถูกงดบำเหน็จไม่ถูกต้อง',
            'suspended_years.*.integer' => 'ปีที่ถูกงดบำเหน็จต้องเป็นตัวเลข',
            'suspended_years.*.min'    => 'ปีที่ถูกงดบำเหน็จไม่ถูกต้อง',
            'test_location_id.required' => 'กรุณาเลือกสถานที่สอบ',
            'test_location_id.exists'  => 'สถานที่สอบที่เลือกไม่ถูกต้อง',
            'exam_level.required'      => 'กรุณาเลือกระดับที่สอบ',
            'exam_level.in'            => 'ระดับที่สอบไม่ถูกต้อง',
        ]);

        $user = Auth::user();

        // ── Final recalculate ──
        $this->recalculate();

        try {
            DB::transaction(function () use ($user, $validated) {
                $selectedSession = ExamSession::registrationOpen()
                    ->where('exam_level', $validated['exam_level'])
                    ->first();

                if (! $selectedSession) {
                    throw new \Exception('ไม่พบรอบสอบที่เปิดรับสมัครสำหรับระดับที่เลือก');
                }

                $this->activeSession = $selectedSession;

                // 1. Create or Update Examinee profile (keep current position)
                $examinee = Examinee::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'branch_id'       => $validated['branch_id'],
                        'age'             => $validated['age'],
                        'eligible_year'   => $validated['eligible_year'],
                        'suspended_years' => $validated['suspended_years'] ?? [], // เก็บเป็น array
                        'border_area_id'  => $validated['border_area_id'] ?: null,
                        'pending_score'   => $this->calculatedPendingScore,
                        'special_score'   => $this->calculatedSpecialScore,
                    ]
                );

                // 2. Check duplicate registration
                $exists = ExamRegistrationModel::where('examinee_id', $examinee->id)
                    ->where('exam_session_id', $selectedSession->id)
                    ->notCancelled()
                    ->exists();

                if ($exists) {
                    throw new \Exception('คุณได้ลงทะเบียนสอบรอบนี้แล้ว');
                }

                // 3. Create ExamRegistration with position quota
                $positionQuota = PositionQuota::where('exam_session_id', $selectedSession->id)
                    ->where('exam_level', $validated['exam_level'])
                    ->where('position_name', $validated['position'])
                    ->first();

                ExamRegistrationModel::create([
                    'examinee_id'     => $examinee->id,
                    'exam_session_id' => $selectedSession->id,
                    'exam_level'      => $validated['exam_level'],
                    'test_location_id' => $validated['test_location_id'],
                    'position_quota_id' => $positionQuota?->id, // Link to position quota
                    'exam_position'   => $validated['position'],
                    'status'          => ExamRegistrationModel::STATUS_PENDING,
                    'registered_at'   => now(),
                ]);
            });

            $this->registrationSuccess = true;
            $this->registrationMessage = 'ลงทะเบียนสอบสำเร็จ! กรุณารอการออกหมายเลขสอบ';

        } catch (\Exception $e) {
            $this->addError('general', $e->getMessage());
        }
    }

    public function goToDashboard()
    {
        return $this->redirect(route('examinee.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.examinee.exam-registration');
    }
}
