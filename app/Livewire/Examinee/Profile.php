<?php

namespace App\Livewire\Examinee;

use App\Models\BorderArea;
use App\Models\Branch;
use App\Models\Examinee;
use App\Models\ExamRegistration;
use App\Models\ExamSession;
use App\Models\TestLocation;
use App\Services\ScoreCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Examinee Profile (Section 2.2.3)
 *
 * แสดง/แก้ไขข้อมูลส่วนตัวของผู้เข้าสอบ
 *
 * ฟิลด์ที่แก้ไขได้:
 *   - ยศ, ชื่อ, นามสกุล (User model)
 *   - ตำแหน่ง, เหล่า, อายุ (Examinee model)
 *   - สถานที่สอบ, พื้นที่ชายแดน (ก่อนปิดรับสมัครเท่านั้น)
 *
 * ห้ามแก้ไข:
 *   - หมายเลขประจำตัว (national_id)
 *   - หมายเลขสอบ (exam_number)
 */
#[Layout('components.layouts.examinee')]
#[Title('ข้อมูลส่วนตัว - ระบบสอบเลื่อนฐานะทหาร')]
class Profile extends Component
{
    // ─── User Fields ───
    public string $rank = '';
    public string $first_name = '';
    public string $last_name = '';

    // ─── Examinee Fields ───
    public string $position = '';
    public string $branch_id = '';
    public string $age = '';
    public string $eligible_year = '';
    public string $suspended_years = '0';
    public string $border_area_id = '';

    // ─── Registration Fields ───
    public string $test_location_id = '';

    // ─── Read-only Display ───
    public string $national_id = '';
    public ?string $examNumber = null;
    public ?string $currentBranchName = null;
    public ?string $currentBorderAreaName = null;
    public ?string $currentTestLocationName = null;
    public ?string $registrationStatus = null;

    // ─── Score Display ───
    public float $pendingScore = 0;
    public float $specialScore = 0;
    public float $totalScore = 0;

    // ─── State ───
    public bool $hasExamineeProfile = false;
    public bool $hasRegistration = false;
    public bool $isRegistrationOpen = false;
    public bool $canEditRegistrationFields = false;

    // ─── Dropdown Data ───
    public Collection $branches;
    public Collection $borderAreas;
    public Collection $testLocations;

    // ─── Rank Options ───
    public array $rankOptions = [
        'พลฯ',
        'ส.ท.',
        'ส.อ.',
        'จ.ส.ต.',
        'จ.ส.ท.',
        'จ.ส.อ.',
    ];

    public function mount(): void
    {
        $user = Auth::user();

        // ── User data (read-only display + editable) ──
        $this->national_id = $user->national_id;
        $this->rank = $user->rank ?? '';
        $this->first_name = $user->first_name ?? '';
        $this->last_name = $user->last_name ?? '';

        // ── Dropdown data ──
        $this->branches = Branch::where('is_active', true)->orderBy('code')->get();
        $this->borderAreas = BorderArea::where('is_active', true)->orderBy('code')->get();
        $this->testLocations = TestLocation::where('is_active', true)->orderBy('code')->get();

        // ── Check registration period ──
        $activeSession = ExamSession::registrationOpen()->first();
        $this->isRegistrationOpen = $activeSession !== null;

        // ── Examinee data ──
        $examinee = $user->examinee;

        if ($examinee) {
            $this->hasExamineeProfile = true;
            $examinee->load(['branch', 'borderArea']);

            $this->position = $examinee->position ?? '';
            $this->branch_id = (string) ($examinee->branch_id ?? '');
            $this->age = (string) ($examinee->age ?? '');
            $this->eligible_year = (string) ($examinee->eligible_year ?? '');
            $this->suspended_years = (string) ($examinee->suspended_years ?? '0');
            $this->border_area_id = (string) ($examinee->border_area_id ?? '');

            $this->currentBranchName = $examinee->branch?->name;
            $this->currentBorderAreaName = $examinee->borderArea?->name;

            // ── Scores ──
            $this->pendingScore = (float) $examinee->pending_score;
            $this->specialScore = (float) $examinee->special_score;
            $this->totalScore = $examinee->total_score;

            // ── Latest registration ──
            $latestReg = ExamRegistration::where('examinee_id', $examinee->id)
                ->with('testLocation')
                ->notCancelled()
                ->orderByDesc('registered_at')
                ->first();

            if ($latestReg) {
                $this->hasRegistration = true;
                $this->examNumber = $latestReg->exam_number;
                $this->test_location_id = (string) ($latestReg->test_location_id ?? '');
                $this->currentTestLocationName = $latestReg->testLocation?->name;
                $this->registrationStatus = $latestReg->status_label;
            }
        }

        // ── สถานที่สอบ/พื้นที่ชายแดน แก้ได้เฉพาะก่อนปิดรับสมัคร ──
        $this->canEditRegistrationFields = $this->isRegistrationOpen;
    }

    /*
    |--------------------------------------------------------------------------
    | Real-time Score Calculation
    |--------------------------------------------------------------------------
    */

    public function updatedEligibleYear(): void
    {
        $this->recalculatePreview();
    }

    public function updatedSuspendedYears(): void
    {
        $this->recalculatePreview();
    }

    public function updatedBorderAreaId(): void
    {
        $this->recalculatePreview();
    }

    private function recalculatePreview(): void
    {
        $calc = new ScoreCalculator();

        $currentYear = $calc->getCurrentBuddhistYear();
        $eligibleYear = (int) $this->eligible_year;
        $suspendedYears = (int) $this->suspended_years;

        if ($eligibleYear > 0 && $eligibleYear <= $currentYear) {
            $this->pendingScore = $calc->calculatePendingScore($eligibleYear, $suspendedYears);
        } else {
            $this->pendingScore = 0;
        }

        $this->specialScore = $calc->getSpecialScore(
            $this->border_area_id ? (int) $this->border_area_id : null
        );

        $this->totalScore = $calc->calculateTotalScore($this->pendingScore, $this->specialScore);
    }

    /*
    |--------------------------------------------------------------------------
    | Save Profile
    |--------------------------------------------------------------------------
    */

    public function save(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // ── Base validation (always editable) ──
        $rules = [
            'rank'            => ['required', 'string', 'max:50'],
            'first_name'      => ['required', 'string', 'max:255'],
            'last_name'       => ['required', 'string', 'max:255'],
            'position'        => ['required', 'string', 'max:255'],
            'branch_id'       => ['required', 'exists:branches,id'],
            'age'             => ['required', 'integer', 'min:18', 'max:60'],
            'eligible_year'   => ['required', 'integer', 'min:2500', 'max:2600'],
            'suspended_years' => ['required', 'integer', 'min:0', 'max:20'],
        ];

        // ── Conditional: editable only during registration period ──
        if ($this->canEditRegistrationFields) {
            $rules['border_area_id'] = ['nullable', 'exists:border_areas,id'];
            $rules['test_location_id'] = ['nullable', 'exists:test_locations,id'];
        }

        $validated = $this->validate($rules, [
            'rank.required'            => 'กรุณาเลือกยศ',
            'first_name.required'      => 'กรุณาระบุชื่อ',
            'last_name.required'       => 'กรุณาระบุนามสกุล',
            'position.required'        => 'กรุณาระบุตำแหน่ง',
            'branch_id.required'       => 'กรุณาเลือกเหล่า',
            'branch_id.exists'         => 'เหล่าที่เลือกไม่ถูกต้อง',
            'age.required'             => 'กรุณาระบุอายุ',
            'age.min'                  => 'อายุต้องไม่น้อยกว่า 18 ปี',
            'age.max'                  => 'อายุต้องไม่เกิน 60 ปี',
            'eligible_year.required'   => 'กรุณาระบุปีที่มีสิทธิ์สอบ',
            'eligible_year.min'        => 'ปีที่มีสิทธิ์สอบไม่ถูกต้อง',
            'suspended_years.required' => 'กรุณาระบุปีที่ถูกงดบำเหน็จ',
            'suspended_years.min'      => 'ปีที่ถูกงดบำเหน็จต้องไม่น้อยกว่า 0',
            'border_area_id.exists'    => 'พื้นที่ชายแดนที่เลือกไม่ถูกต้อง',
            'test_location_id.exists'  => 'สถานที่สอบที่เลือกไม่ถูกต้อง',
        ]);

        try {
            DB::transaction(function () use ($user, $validated) {
                $calc = new ScoreCalculator();

                // 1. Update User (ยศ, ชื่อ, นามสกุล)
                $user->update([
                    'rank'       => $validated['rank'],
                    'first_name' => $validated['first_name'],
                    'last_name'  => $validated['last_name'],
                ]);

                // 2. Calculate scores
                $borderAreaId = ! empty($validated['border_area_id'])
                    ? (int) $validated['border_area_id']
                    : ($this->hasExamineeProfile ? Auth::user()->examinee?->border_area_id : null);

                $scores = $calc->calculateAll(
                    (int) $validated['eligible_year'],
                    (int) $validated['suspended_years'],
                    $borderAreaId,
                );

                // 3. Update or Create Examinee
                $examineeData = [
                    'position'        => $validated['position'],
                    'branch_id'       => $validated['branch_id'],
                    'age'             => $validated['age'],
                    'eligible_year'   => $validated['eligible_year'],
                    'suspended_years' => $validated['suspended_years'],
                    'pending_score'   => $scores['pending_score'],
                    'special_score'   => $scores['special_score'],
                ];

                // border_area editable only during registration
                if ($this->canEditRegistrationFields) {
                    $examineeData['border_area_id'] = ! empty($validated['border_area_id'])
                        ? $validated['border_area_id']
                        : null;
                }

                $examinee = Examinee::updateOrCreate(
                    ['user_id' => $user->id],
                    $examineeData,
                );

                // 4. Update test_location on latest registration (if registration is open)
                if (
                    $this->canEditRegistrationFields
                    && ! empty($validated['test_location_id'])
                    && $this->hasRegistration
                ) {
                    $latestReg = ExamRegistration::where('examinee_id', $examinee->id)
                        ->notCancelled()
                        ->orderByDesc('registered_at')
                        ->first();

                    if ($latestReg && $latestReg->isPending()) {
                        $latestReg->update([
                            'test_location_id' => $validated['test_location_id'],
                        ]);
                    }
                }

                // 5. Refresh local state
                $this->pendingScore = $scores['pending_score'];
                $this->specialScore = $scores['special_score'];
                $this->totalScore = $scores['total_score'];
                $this->hasExamineeProfile = true;

                if ($this->canEditRegistrationFields) {
                    $borderArea = $this->borderAreas->firstWhere('id', (int) ($validated['border_area_id'] ?? 0));
                    $this->currentBorderAreaName = $borderArea?->name;

                    if (! empty($validated['test_location_id'])) {
                        $location = $this->testLocations->firstWhere('id', (int) $validated['test_location_id']);
                        $this->currentTestLocationName = $location?->name;
                    }
                }

                $branch = $this->branches->firstWhere('id', (int) $validated['branch_id']);
                $this->currentBranchName = $branch?->name;
            });

            session()->flash('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            $this->addError('general', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.examinee.profile');
    }
}
