<?php

namespace App\Livewire\Examinee;

use App\Models\ExamRegistration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Examinee History (Section 2.8.1)
 *
 * แสดงประวัติการสอบทั้งหมดของผู้เข้าสอบ:
 *   - ปีที่สอบ
 *   - ระดับ
 *   - สถานที่สอบ
 *   - หมายเลขสอบ
 *   - คะแนนรวม
 *   - สถานะ (รอผล/ผ่าน/ไม่ผ่าน)
 */
#[Layout('components.layouts.examinee')]
#[Title('ประวัติการสอบ - ผู้เข้าสอบ')]
class History extends Component
{
    /**
     * @var Collection<int, array{
     *     year: int|string,
     *     level: string,
     *     test_location: string,
     *     exam_number: string,
     *     total_score: float,
     *     result_status: string
     * }>
     */
    public Collection $historyRows;

    public bool $hasHistory = false;

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $this->historyRows = collect();

        $examinee = $user->examinee;
        if (! $examinee) {
            return;
        }

        $rows = ExamRegistration::query()
            ->where('examinee_id', $examinee->id)
            ->with(['examSession', 'testLocation'])
            ->orderByDesc('registered_at')
            ->get()
            ->map(function (ExamRegistration $registration) use ($examinee): array {
                return [
                    'year' => $registration->examSession?->year ?? '-',
                    'level' => $registration->examSession?->exam_level_label ?? '-',
                    'test_location' => $registration->testLocation?->name ?? '-',
                    'exam_number' => $registration->exam_number ?: '-',
                    'total_score' => (float) $examinee->total_score,
                    'result_status' => $this->resolveResultStatus($registration),
                ];
            })
            ->values();

        $this->historyRows = $rows;
        $this->hasHistory = $rows->isNotEmpty();
    }

    private function resolveResultStatus(ExamRegistration $registration): string
    {
        $resultRaw = (string) ($registration->getAttribute('result_status') ?? $registration->getAttribute('exam_result') ?? '');
        $normalized = strtolower(trim($resultRaw));

        if (in_array($normalized, ['pass', 'passed', 'ผ่าน'], true)) {
            return 'ผ่าน';
        }

        if (in_array($normalized, ['fail', 'failed', 'ไม่ผ่าน'], true)) {
            return 'ไม่ผ่าน';
        }

        if ($registration->status === ExamRegistration::STATUS_CANCELLED) {
            return 'ไม่ผ่าน';
        }

        return 'รอผล';
    }

    public function render()
    {
        return view('livewire.examinee.history');
    }
}
