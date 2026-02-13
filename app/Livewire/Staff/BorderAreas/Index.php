<?php

namespace App\Livewire\Staff\BorderAreas;

use App\Models\BorderArea;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('จัดการพื้นที่ชายแดน')]
class Index extends Component
{
    public Collection $areas;
    public string $search = '';
    public string $statusFilter = 'all';
    public ?int $confirmDeleteId = null;
    public ?string $confirmDeleteName = null;

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isStaff()) {
            abort(403, 'เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        $this->loadAreas();
    }

    public function updatedSearch(): void
    {
        $this->loadAreas();
    }

    public function updatedStatusFilter(): void
    {
        $this->loadAreas();
    }

    public function toggleActive(int $id): void
    {
        $area = BorderArea::findOrFail($id);
        $area->update([
            'is_active' => ! $area->is_active,
            'updated_by' => Auth::id(),
        ]);

        session()->flash('success', 'อัปเดตสถานะพื้นที่เรียบร้อย');
        $this->loadAreas();
    }

    public function confirmDelete(int $id): void
    {
        $area = BorderArea::findOrFail($id);

        $this->confirmDeleteId = $area->id;
        $this->confirmDeleteName = $area->display_name;
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

        $area = BorderArea::query()
            ->withCount('examinees')
            ->findOrFail($this->confirmDeleteId);

        // ตามเอกสาร: ห้ามลบถ้ามีผู้สมัครใช้อยู่
        if ($area->examinees_count > 0) {
            session()->flash('error', 'ไม่สามารถลบได้ เนื่องจากมีผู้สมัครใช้งานพื้นที่นี้อยู่');
            $this->cancelDelete();
            return;
        }

        $area->update(['updated_by' => Auth::id()]);
        $area->delete();

        session()->flash('success', 'ลบพื้นที่ชายแดนเรียบร้อย');
        $this->cancelDelete();
        $this->loadAreas();
    }

    private function loadAreas(): void
    {
        $query = BorderArea::query()
            ->withCount('examinees')
            ->orderBy('code');

        if ($this->search !== '') {
            $keyword = trim($this->search);
            $query->where(function ($q) use ($keyword): void {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $this->areas = $query->get();
    }

    public function render()
    {
        return view('livewire.staff.border-areas.index');
    }
}
