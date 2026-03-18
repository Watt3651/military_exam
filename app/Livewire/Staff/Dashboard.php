<?php

namespace App\Livewire\Staff;

use App\Models\Branch;
use App\Models\ExamRegistration;
use App\Models\ExamSession;
use App\Models\TestLocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('Dashboard - เจ้าหน้าที่')]
class Dashboard extends Component
{
    public string $yearFilter = '';
    public string $examLevelFilter = '';
    public string $testLocationFilter = '';
    public string $branchFilter = '';

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isStaff()) {
            abort(403, 'เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        $latestYear = ExamSession::query()->max('year');
        if ($latestYear) {
            $this->yearFilter = (string) $latestYear;
        }
    }

    #[Computed]
    public function years(): Collection
    {
        return ExamSession::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');
    }

    #[Computed]
    public function testLocations(): Collection
    {
        return TestLocation::query()->ordered()->get(['id', 'name', 'code']);
    }

    #[Computed]
    public function branches(): Collection
    {
        return Branch::query()->ordered()->get(['id', 'name', 'code']);
    }

    #[Computed]
    public function currentSession(): ?ExamSession
    {
        return ExamSession::query()
            ->when($this->yearFilter !== '', fn ($q) => $q->where('year', (int) $this->yearFilter))
            ->when($this->examLevelFilter !== '', fn ($q) => $q->where('exam_level', $this->examLevelFilter))
            ->where('is_archived', false)
            ->orderByDesc('is_active')
            ->orderByDesc('year')
            ->orderBy('exam_level')
            ->first();
    }

    /**
     * @return Collection<int, ExamRegistration>
     */
    #[Computed]
    public function currentSessionRegistrations(): Collection
    {
        $session = $this->currentSession;
        if (! $session && $this->examLevelFilter === '') {
            return collect();
        }

        return ExamRegistration::query()
            ->where('status', '!=', ExamRegistration::STATUS_CANCELLED)
            ->when($this->yearFilter !== '', function ($q): void {
                $year = (int) $this->yearFilter;
                $q->whereHas('examSession', fn ($subQ) => $subQ->where('year', $year));
            })
            ->when($this->examLevelFilter !== '', function ($q): void {
                $level = $this->examLevelFilter;
                $q->where(function ($subQ) use ($level): void {
                    $subQ->where('exam_level', $level)
                        ->orWhereHas('positionQuota', fn ($quotaQ) => $quotaQ->where('exam_level', $level))
                        ->orWhereHas('examSession', fn ($sessionQ) => $sessionQ->where('exam_level', $level));
                });
            }, fn ($q) => $q->where('exam_session_id', $session->id))
            ->when($this->testLocationFilter !== '', fn ($q) => $q->where('test_location_id', (int) $this->testLocationFilter))
            ->when($this->branchFilter !== '', fn ($q) => $q->whereHas('examinee', fn ($subQ) => $subQ->where('branch_id', (int) $this->branchFilter)))
            ->with([
                'testLocation:id,name,code',
                'examinee:id,user_id,branch_id',
                'examinee.branch:id,name,code',
                'examSession:id,year,exam_level',
                'positionQuota:id,exam_level',
            ])
            ->get();
    }

    /**
     * @return array{total:int, confirmed:int, pending:int}
     */
    #[Computed]
    public function summary(): array
    {
        $registrations = $this->currentSessionRegistrations;

        return [
            'total' => $registrations->count(),
            'confirmed' => $registrations->where('status', ExamRegistration::STATUS_CONFIRMED)->count(),
            'pending' => $registrations->filter(fn (ExamRegistration $registration): bool => $registration->status === ExamRegistration::STATUS_CONFIRMED && empty($registration->exam_number))->count(),
        ];
    }

    /**
     * @return array{categories:array<int,string>, series:array<int,int>}
     */
    #[Computed]
    public function locationChart(): array
    {
        $grouped = $this->currentSessionRegistrations
            ->groupBy(fn (ExamRegistration $registration) => (string) $registration->testLocation?->id)
            ->map(function (Collection $items): array {
                /** @var ExamRegistration $first */
                $first = $items->first();
                $label = $first->testLocation
                    ? "{$first->testLocation->name} ({$first->testLocation->code})"
                    : 'ไม่ระบุสถานที่สอบ';

                return ['label' => $label, 'count' => $items->count()];
            })
            ->values();

        return [
            'categories' => $grouped->pluck('label')->values()->all(),
            'series' => $grouped->pluck('count')->values()->all(),
        ];
    }

    /**
     * @return array{labels:array<int,string>, series:array<int,int>}
     */
    #[Computed]
    public function branchChart(): array
    {
        $grouped = $this->currentSessionRegistrations
            ->groupBy(fn (ExamRegistration $registration) => (string) $registration->examinee?->branch?->id)
            ->map(function (Collection $items): array {
                /** @var ExamRegistration $first */
                $first = $items->first();
                $label = $first->examinee?->branch
                    ? "{$first->examinee->branch->name} ({$first->examinee->branch->code})"
                    : 'ไม่ระบุเหล่า';

                return ['label' => $label, 'count' => $items->count()];
            })
            ->values();

        return [
            'labels' => $grouped->pluck('label')->values()->all(),
            'series' => $grouped->pluck('count')->values()->all(),
        ];
    }

    /**
     * @return array{labels:array<int,string>, series:array<int,int>}
     */
    #[Computed]
    public function levelChart(): array
    {
        $registrations = ExamRegistration::query()
            ->where('status', '!=', ExamRegistration::STATUS_CANCELLED)
            ->when($this->yearFilter !== '', function ($q): void {
                $year = (int) $this->yearFilter;
                $q->whereHas('examSession', fn ($subQ) => $subQ->where('year', $year));
            })
            ->when($this->examLevelFilter !== '', function ($q): void {
                $level = $this->examLevelFilter;
                $q->where(function ($subQ) use ($level): void {
                    $subQ->where('exam_level', $level)
                        ->orWhereHas('positionQuota', fn ($quotaQ) => $quotaQ->where('exam_level', $level))
                        ->orWhereHas('examSession', fn ($sessionQ) => $sessionQ->where('exam_level', $level));
                });
            })
            ->when($this->testLocationFilter !== '', fn ($q) => $q->where('test_location_id', (int) $this->testLocationFilter))
            ->when($this->branchFilter !== '', fn ($q) => $q->whereHas('examinee', fn ($subQ) => $subQ->where('branch_id', (int) $this->branchFilter)))
            ->with(['examSession:id,exam_level', 'positionQuota:id,exam_level'])
            ->get();

        $labelsMap = [
            ExamSession::LEVEL_SERGEANT_MAJOR => ExamSession::LEVEL_LABELS[ExamSession::LEVEL_SERGEANT_MAJOR],
            ExamSession::LEVEL_MASTER_SERGEANT => ExamSession::LEVEL_LABELS[ExamSession::LEVEL_MASTER_SERGEANT],
        ];

        $counts = collect($labelsMap)->map(fn () => 0)->all();

        foreach ($registrations as $registration) {
            $level = $registration->exam_level
                ?? $registration->positionQuota?->exam_level
                ?? $registration->examSession?->exam_level;
            if ($level && array_key_exists($level, $counts)) {
                $counts[$level]++;
            }
        }

        return [
            'labels' => array_values($labelsMap),
            'series' => array_values($counts),
        ];
    }

    public function render()
    {
        return view('livewire.staff.dashboard');
    }
}
