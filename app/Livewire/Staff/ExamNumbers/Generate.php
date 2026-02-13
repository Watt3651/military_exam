<?php

namespace App\Livewire\Staff\ExamNumbers;

use App\Models\ExamRegistration;
use App\Models\ExamSession;
use App\Services\ExamNumberGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('สร้างหมายเลขสอบ')]
class Generate extends Component
{
    public ?int $exam_session_id = null;
    public int $lastGeneratedCount = 0;

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isStaff()) {
            abort(403, 'เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        $defaultSession = ExamSession::query()
            ->where('is_archived', false)
            ->orderByDesc('year')
            ->orderBy('exam_level')
            ->first();

        if ($defaultSession) {
            $this->exam_session_id = $defaultSession->id;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'exam_session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'exam_session_id.required' => 'กรุณาเลือกรอบสอบ',
            'exam_session_id.exists' => 'ไม่พบรอบสอบที่เลือก',
        ];
    }

    #[Computed]
    public function examSessions(): Collection
    {
        return ExamSession::query()
            ->orderByDesc('year')
            ->orderBy('exam_level')
            ->get();
    }

    #[Computed]
    public function pendingCount(): int
    {
        if (! $this->exam_session_id) {
            return 0;
        }

        return ExamRegistration::query()
            ->where('exam_session_id', $this->exam_session_id)
            ->where('status', ExamRegistration::STATUS_PENDING)
            ->count();
    }

    /**
     * ตัวอย่างหมายเลขสอบล่วงหน้า (ไม่บันทึกจริง)
     *
     * แสดงสูงสุด 10 รายการแรก เพื่อให้ staff เห็นรูปแบบเลข XYZNN
     *
     * @return Collection<int, array{name:string, exam_number:string, location_code:string, branch_code:string}>
     */
    #[Computed]
    public function previewItems(): Collection
    {
        if (! $this->exam_session_id) {
            return collect();
        }

        $pendingRegistrations = ExamRegistration::query()
            ->where('exam_session_id', $this->exam_session_id)
            ->where('status', ExamRegistration::STATUS_PENDING)
            ->with([
                'testLocation:id,code',
                'examinee:id,user_id,branch_id',
                'examinee.branch:id,code',
                'examinee.user:id,first_name,last_name',
            ])
            ->get();

        if ($pendingRegistrations->isEmpty()) {
            return collect();
        }

        $grouped = $pendingRegistrations->groupBy(function (ExamRegistration $registration): string {
            $locationCode = (string) optional($registration->testLocation)->code;
            $branchCode = (string) optional(optional($registration->examinee)->branch)->code;

            return "{$locationCode}|{$branchCode}";
        });

        $items = collect();

        /** @var Collection<string, Collection<int, ExamRegistration>> $grouped */
        foreach ($grouped as $groupKey => $registrations) {
            [$locationCode, $branchCode] = explode('|', $groupKey);

            if (! $this->isOneDigitCode($locationCode) || ! $this->isOneDigitCode($branchCode)) {
                continue;
            }

            $sequence = $this->getCurrentMaxSequence($this->exam_session_id, $locationCode, $branchCode);

            $sorted = $registrations->sortBy(
                fn (ExamRegistration $registration): string => (string) optional(optional($registration->examinee)->user)->first_name
            )->values();

            foreach ($sorted as $registration) {
                $sequence++;

                if ($sequence > 999) {
                    break;
                }

                $user = optional($registration->examinee)->user;
                $fullName = trim(((string) optional($user)->first_name) . ' ' . ((string) optional($user)->last_name));

                $items->push([
                    'name' => $fullName !== '' ? $fullName : '-',
                    'exam_number' => sprintf('%s%s%03d', $locationCode, $branchCode, $sequence),
                    'location_code' => $locationCode,
                    'branch_code' => $branchCode,
                ]);
            }
        }

        return $items->take(10)->values();
    }

    public function generate(ExamNumberGenerator $generator): void
    {
        $validated = $this->validate();

        $generatedCount = $generator->generate((int) $validated['exam_session_id']);
        $this->lastGeneratedCount = $generatedCount;

        if ($generatedCount > 0) {
            session()->flash('success', "สร้างหมายเลขสอบสำเร็จ {$generatedCount} รายการ");
        } else {
            session()->flash('error', 'ไม่พบผู้สมัครสถานะรอออกหมายเลขในรอบสอบที่เลือก');
        }

        unset($this->pendingCount, $this->previewItems);
    }

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

    private function isOneDigitCode(string $code): bool
    {
        return (bool) preg_match('/^[1-9]$/', $code);
    }

    public function render()
    {
        return view('livewire.staff.exam-numbers.generate');
    }
}
