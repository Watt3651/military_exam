<?php

namespace App\Livewire\Staff\Examinees;

use App\Models\Branch;
use App\Models\Examinee;
use App\Models\TestLocation;
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
    public string $examNumberFilter = '';
    public int $perPage = 10;

    public ?int $confirmDeleteId = null;
    public ?string $confirmDeleteName = null;

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
        $query = Examinee::query()
            ->with([
                'user',
                'branch',
                'examRegistrations' => function ($q): void {
                    $q->with('testLocation')->orderByDesc('registered_at');
                },
            ])
            ->orderByDesc('id');

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

        if ($this->examNumberFilter !== '') {
            $keyword = trim($this->examNumberFilter);
            $query->whereHas('examRegistrations', function ($q) use ($keyword): void {
                $q->where('exam_number', 'like', "%{$keyword}%");
            });
        }

        return $query->paginate($this->perPage);
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

    public function delete(): void
    {
        if (! $this->confirmDeleteId) {
            return;
        }

        $examinee = Examinee::findOrFail($this->confirmDeleteId);
        $examinee->delete();

        session()->flash('success', 'ลบข้อมูลผู้เข้าสอบเรียบร้อย');
        $this->cancelDelete();
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.staff.examinees.index');
    }
}
