<?php

namespace App\Livewire\Staff\Examinees;

use App\Models\Branch;
use App\Models\Examinee;
use App\Models\ExamRegistration;
use App\Models\TestLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.staff')]
#[Title('จัดการผู้เข้าสอบ')]
class Index extends Component
{
    use WithPagination;

    public string $searchName = '';
    public string $branchFilter = '';
    public string $testLocationFilter = '';
    public string $examLevelFilter = '';
    public string $examNumberFilter = '';
    public int $perPage = 10;
    public bool $bulkConfirmPendingOnly = true;

    public ?int $confirmDeleteId = null;
    public ?string $confirmDeleteName = null;
    public ?string $bulkActionToConfirm = null;
    public int $confirmBulkCount = 0;
    public string $bulkDeleteConfirmText = '';

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isStaff()) {
            abort(403, 'เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }
    }

    public function updatedSearchName(): void
    {
        $this->resetPage();
    }

    public function updatedBranchFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTestLocationFilter(): void
    {
        $this->resetPage();
    }

    public function updatedExamLevelFilter(): void
    {
        $this->resetPage();
    }

    public function updatedExamNumberFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function branches(): Collection
    {
        return Branch::query()->orderBy('code')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function testLocations(): Collection
    {
        return TestLocation::query()->orderBy('code')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function examinees(): LengthAwarePaginator
    {
        return $this->filteredExamineesQuery()
            ->with([
                'user',
                'branch',
                'unit', // เพิ่ม unit relationship
                'examRegistrations' => function ($q): void {
                    $q->with('testLocation')->orderByDesc('registered_at');
                },
            ])
            ->paginate($this->perPage);
    }

    private function filteredExamineesQuery(): Builder
    {
        $query = Examinee::query()->orderByDesc('id');

        if ($this->searchName !== '') {
            $keyword = trim($this->searchName);
            $query->whereHas('user', function ($q) use ($keyword): void {
                $q->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$keyword}%"]);
            });
        }

        if ($this->branchFilter !== '') {
            $query->where('branch_id', (int) $this->branchFilter);
        }

        if ($this->testLocationFilter !== '') {
            $query->whereHas('examRegistrations', function ($q): void {
                $q->where('test_location_id', (int) $this->testLocationFilter);
            });
        }

        if ($this->examLevelFilter !== '') {
            $query->whereHas('examRegistrations', function ($q): void {
                $q->where('exam_level', $this->examLevelFilter);
            });
        }

        if ($this->examNumberFilter !== '') {
            $keyword = trim($this->examNumberFilter);
            $query->whereHas('examRegistrations', function ($q) use ($keyword): void {
                $q->where('exam_number', 'like', "%{$keyword}%");
            });
        }

        return $query;
    }

    public function confirmDelete(int $id): void
    {
        $examinee = Examinee::query()->with('user')->findOrFail($id);
        $this->confirmDeleteId = $examinee->id;
        $this->confirmDeleteName = $examinee->user?->full_name ?? "ID {$id}";
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
        $this->confirmDeleteName = null;
    }

    public function promptBulkAction(string $action): void
    {
        if (! in_array($action, ['confirm', 'delete'], true)) {
            return;
        }

        $this->bulkActionToConfirm = $action;
        $this->bulkDeleteConfirmText = '';
        $this->confirmBulkCount = $action === 'confirm'
            ? $this->countTargetRegistrationsForBulkConfirm()
            : (int) $this->filteredExamineesQuery()->count();

        if ($this->confirmBulkCount === 0) {
            $this->cancelBulkAction();
            session()->flash('error', $action === 'confirm'
                ? 'ไม่พบรายการที่สามารถยืนยันได้จากเงื่อนไขที่เลือก'
                : 'ไม่พบผู้เข้าสอบสำหรับลบจากเงื่อนไขที่เลือก');
        }
    }

    public function cancelBulkAction(): void
    {
        $this->bulkActionToConfirm = null;
        $this->confirmBulkCount = 0;
        $this->bulkDeleteConfirmText = '';
    }

    public function executeBulkAction(): void
    {
        if ($this->bulkActionToConfirm === 'confirm') {
            $this->confirmAllRegistrations();
            return;
        }

        if ($this->bulkActionToConfirm === 'delete') {
            if ($this->bulkDeleteConfirmText !== 'DELETE') {
                session()->flash('error', 'กรุณาพิมพ์คำว่า DELETE เพื่อยืนยันการลบทั้งหมด');
                return;
            }

            $this->deleteAllExaminees();
        }
    }

    public function delete(): void
    {
        if (! $this->confirmDeleteId) {
            return;
        }

        $examinee = Examinee::findOrFail($this->confirmDeleteId);
        $examinee->forceDelete();

        session()->flash('success', 'ลบข้อมูลผู้เข้าสอบเรียบร้อย');
        $this->cancelDelete();
        $this->resetPage();
    }

    public function confirmRegistration(int $registrationId): void
    {
        $registration = ExamRegistration::findOrFail($registrationId);
        $registration->update(['status' => ExamRegistration::STATUS_CONFIRMED]);

        session()->flash('success', 'ยืนยันการสมัครเรียบร้อย');
    }

    private function countTargetRegistrationsForBulkConfirm(): int
    {
        $count = 0;

        $examinees = $this->filteredExamineesQuery()
            ->with(['examRegistrations' => fn ($q) => $q->orderByDesc('registered_at')->orderByDesc('id')])
            ->get();

        foreach ($examinees as $examinee) {
            $latestRegistration = $examinee->examRegistrations->first();
            if (! $latestRegistration) {
                continue;
            }

            if ($this->bulkConfirmPendingOnly && $latestRegistration->status === ExamRegistration::STATUS_CONFIRMED) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    private function confirmAllRegistrations(): void
    {
        $processedCount = 0;
        $newlyConfirmedCount = 0;

        $examinees = $this->filteredExamineesQuery()
            ->with(['examRegistrations' => fn ($q) => $q->orderByDesc('registered_at')->orderByDesc('id')])
            ->get();

        foreach ($examinees as $examinee) {
            $latestRegistration = $examinee->examRegistrations->first();
            if (! $latestRegistration) {
                continue;
            }

            if ($this->bulkConfirmPendingOnly && $latestRegistration->status === ExamRegistration::STATUS_CONFIRMED) {
                continue;
            }

            $processedCount++;

            if ($latestRegistration->status !== ExamRegistration::STATUS_CONFIRMED) {
                $latestRegistration->update(['status' => ExamRegistration::STATUS_CONFIRMED]);
                $newlyConfirmedCount++;
            }
        }

        if ($processedCount === 0) {
            session()->flash('error', 'ไม่พบรายการที่สามารถยืนยันได้จากเงื่อนไขที่เลือก');
        } elseif ($this->bulkConfirmPendingOnly) {
            session()->flash('success', "ยืนยันการสมัครเรียบร้อย {$newlyConfirmedCount} รายการ");
        } else {
            session()->flash('success', "ดำเนินการยืนยันทั้งหมด {$processedCount} รายการ (ยืนยันใหม่ {$newlyConfirmedCount} รายการ)");
        }

        $this->cancelBulkAction();
        $this->resetPage();
    }

    private function deleteAllExaminees(): void
    {
        $ids = $this->filteredExamineesQuery()->pluck('id');

        if ($ids->isEmpty()) {
            session()->flash('error', 'ไม่พบผู้เข้าสอบสำหรับลบจากเงื่อนไขที่เลือก');
            $this->cancelBulkAction();
            return;
        }

        $deletedCount = Examinee::query()->whereIn('id', $ids)->forceDelete();
        session()->flash('success', "ลบข้อมูลผู้เข้าสอบเรียบร้อย {$deletedCount} รายการ");

        $this->cancelBulkAction();
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.staff.examinees.index');
    }
}
