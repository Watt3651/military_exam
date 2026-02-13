<?php

namespace App\Livewire\Examinee;

use App\Models\Examinee;
use App\Models\ExamRegistration;
use App\Models\ExamSession;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Examinee Dashboard (Section 2.6.1)
 *
 * แสดง:
 *   - สถานะการสมัคร (Widget)
 *   - หมายเลขประจำตัวสอบ
 *   - สถานที่สอบ / วันที่สอบ
 *   - คะแนนรวม (pending + special)
 *   - ปุ่ม Actions
 */
#[Layout('components.layouts.examinee')]
#[Title('หน้าหลัก - ผู้เข้าสอบ')]
class Dashboard extends Component
{
    // ─── Examinee Data ───
    public ?Examinee $examinee = null;
    public bool $hasExamineeProfile = false;

    // ─── Registration Data ───
    public ?ExamRegistration $latestRegistration = null;
    public bool $hasRegistration = false;

    // ─── Active Session ───
    public ?ExamSession $activeSession = null;
    public bool $isRegistrationOpen = false;

    // ─── Score Data ───
    public float $pendingScore = 0;
    public float $specialScore = 0;
    public float $totalScore = 0;

    // ─── Extra Info ───
    public ?string $branchName = null;
    public ?string $borderAreaName = null;
    public ?string $testLocationName = null;
    public ?string $examNumber = null;
    public ?string $examDate = null;
    public ?string $registrationStatus = null;
    public ?string $registrationStatusColor = null;
    public ?string $examSessionName = null;

    public function mount(): void
    {
        $user = Auth::user();

        // ─── Load Examinee Profile ───
        $this->examinee = $user->examinee;
        $this->hasExamineeProfile = $this->examinee !== null;

        if ($this->hasExamineeProfile) {
            $this->examinee->load(['branch', 'borderArea']);

            // Scores
            $this->pendingScore = (float) $this->examinee->pending_score;
            $this->specialScore = (float) $this->examinee->special_score;
            $this->totalScore = $this->examinee->total_score;

            // Branch & Border Area
            $this->branchName = $this->examinee->branch?->name;
            $this->borderAreaName = $this->examinee->borderArea
                ? "{$this->examinee->borderArea->name} ({$this->examinee->borderArea->code})"
                : null;

            // ─── Latest Registration ───
            $this->latestRegistration = ExamRegistration::where('examinee_id', $this->examinee->id)
                ->with(['examSession', 'testLocation'])
                ->orderByDesc('registered_at')
                ->first();

            if ($this->latestRegistration) {
                $this->hasRegistration = true;
                $this->examNumber = $this->latestRegistration->exam_number;
                $this->testLocationName = $this->latestRegistration->testLocation?->name;
                $this->registrationStatus = $this->latestRegistration->status_label;
                $this->registrationStatusColor = match ($this->latestRegistration->status) {
                    ExamRegistration::STATUS_PENDING   => 'yellow',
                    ExamRegistration::STATUS_CONFIRMED => 'green',
                    ExamRegistration::STATUS_CANCELLED => 'red',
                    default => 'gray',
                };

                // Exam session info
                if ($this->latestRegistration->examSession) {
                    $session = $this->latestRegistration->examSession;
                    $this->examSessionName = $session->display_name;
                    $this->examDate = $session->exam_date?->format('d/m/Y');
                }
            }
        }

        // ─── Active Session (เปิดรับสมัครอยู่?) ───
        $this->activeSession = ExamSession::registrationOpen()->first();
        $this->isRegistrationOpen = $this->activeSession !== null;
    }

    public function render()
    {
        return view('livewire.examinee.dashboard');
    }
}
