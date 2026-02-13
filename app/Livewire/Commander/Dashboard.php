<?php

namespace App\Livewire\Commander;

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

#[Layout('components.layouts.commander')]
#[Title('Dashboard - ผู้บังคับบัญชา')]
class Dashboard extends Component
{
    public string $yearFilter = '';
    public string $testLocationFilter = '';
    public string $branchFilter = '';

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isCommander()) {
            abort(403, 'เฉพาะผู้บังคับบัญชา (Commander) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
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
        if (! $session) {
            return collect();
        }

        return ExamRegistration::query()
            ->where('exam_session_id', $session->id)
            ->where('status', '!=', ExamRegistration::STATUS_CANCELLED)
            ->when($this->testLocationFilter !== '', fn ($q) => $q->where('test_location_id', (int) $this->testLocationFilter))
            ->when($this->branchFilter !== '', fn ($q) => $q->whereHas('examinee', fn ($subQ) => $subQ->where('branch_id', (int) $this->branchFilter)))
            ->with([
                'testLocation:id,name,code',
                'examinee:id,user_id,branch_id',
                'examinee.branch:id,name,code',
                'examSession:id,year,exam_level',
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
            'pending' => $registrations->where('status', ExamRegistration::STATUS_PENDING)->count(),
        ];
    }

    /**
     * @return array{
     *   current_year:int,
     *   previous_year:int,
     *   total:array{current:int,previous:int,change:int,percent:float},
     *   confirmed:array{current:int,previous:int,change:int,percent:float},
     *   pending:array{current:int,previous:int,change:int,percent:float}
     * }
     */
    #[Computed]
    public function yoy(): array
    {
        $currentYear = $this->yearFilter !== '' ? (int) $this->yearFilter : (int) (ExamSession::query()->max('year') ?? date('Y'));
        $previousYear = $currentYear - 1;

        $current = $this->yearSummary($currentYear);
        $previous = $this->yearSummary($previousYear);

        return [
            'current_year' => $currentYear,
            'previous_year' => $previousYear,
            'total' => $this->buildYoyMetric($current['total'], $previous['total']),
            'confirmed' => $this->buildYoyMetric($current['confirmed'], $previous['confirmed']),
            'pending' => $this->buildYoyMetric($current['pending'], $previous['pending']),
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
            ->when($this->testLocationFilter !== '', fn ($q) => $q->where('test_location_id', (int) $this->testLocationFilter))
            ->when($this->branchFilter !== '', fn ($q) => $q->whereHas('examinee', fn ($subQ) => $subQ->where('branch_id', (int) $this->branchFilter)))
            ->with('examSession:id,exam_level')
            ->get();

        $labelsMap = [
            ExamSession::LEVEL_SERGEANT_MAJOR => ExamSession::LEVEL_LABELS[ExamSession::LEVEL_SERGEANT_MAJOR],
            ExamSession::LEVEL_MASTER_SERGEANT => ExamSession::LEVEL_LABELS[ExamSession::LEVEL_MASTER_SERGEANT],
        ];

        $counts = collect($labelsMap)->map(fn () => 0)->all();
        foreach ($registrations as $registration) {
            $level = $registration->examSession?->exam_level;
            if ($level && array_key_exists($level, $counts)) {
                $counts[$level]++;
            }
        }

        return [
            'labels' => array_values($labelsMap),
            'series' => array_values($counts),
        ];
    }

    /**
     * @return array{total:int,confirmed:int,pending:int}
     */
    private function yearSummary(int $year): array
    {
        $rows = ExamRegistration::query()
            ->where('status', '!=', ExamRegistration::STATUS_CANCELLED)
            ->whereHas('examSession', fn ($q) => $q->where('year', $year))
            ->when($this->testLocationFilter !== '', fn ($q) => $q->where('test_location_id', (int) $this->testLocationFilter))
            ->when($this->branchFilter !== '', fn ($q) => $q->whereHas('examinee', fn ($subQ) => $subQ->where('branch_id', (int) $this->branchFilter)))
            ->get(['id', 'status']);

        return [
            'total' => $rows->count(),
            'confirmed' => $rows->where('status', ExamRegistration::STATUS_CONFIRMED)->count(),
            'pending' => $rows->where('status', ExamRegistration::STATUS_PENDING)->count(),
        ];
    }

    /**
     * @return array{current:int,previous:int,change:int,percent:float}
     */
    private function buildYoyMetric(int $current, int $previous): array
    {
        $change = $current - $previous;
        $percent = $previous > 0
            ? round(($change / $previous) * 100, 2)
            : ($current > 0 ? 100.0 : 0.0);

        return [
            'current' => $current,
            'previous' => $previous,
            'change' => $change,
            'percent' => $percent,
        ];
    }

    public function render()
    {
        return view('livewire.commander.dashboard');
    }
}
