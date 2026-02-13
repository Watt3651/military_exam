<?php

namespace App\Livewire\Staff\TestLocations;

use App\Models\TestLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.staff')]
#[Title('จัดการสถานที่สอบ')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function locations(): LengthAwarePaginator
    {
        $query = TestLocation::query()
            ->withCount('examRegistrations')
            ->orderBy('code');

        if ($this->search !== '') {
            $keyword = trim($this->search);
            $query->where(function ($q) use ($keyword): void {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        return $query->paginate($this->perPage);
    }

    public function confirmDelete(int $id): void
    {
        $location = TestLocation::findOrFail($id);
        $this->confirmDeleteId = $location->id;
        $this->confirmDeleteName = "{$location->code} - {$location->name}";
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

        $location = TestLocation::query()
            ->withCount('examRegistrations')
            ->findOrFail($this->confirmDeleteId);

        // ตามเอกสาร: ลบได้เมื่อไม่มีผู้ใช้
        if ($location->exam_registrations_count > 0) {
            session()->flash('error', 'ไม่สามารถลบได้ เนื่องจากมีผู้สมัครใช้งานสถานที่สอบนี้อยู่');
            $this->cancelDelete();
            return;
        }

        $location->delete();
        session()->flash('success', 'ลบสถานที่สอบเรียบร้อย');
        $this->cancelDelete();
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.staff.test-locations.index');
    }
}
