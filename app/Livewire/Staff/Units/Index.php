<?php

namespace App\Livewire\Staff\Units;

use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('จัดการสังกัด')]
class Index extends Component
{
    public Collection $units;

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isStaff()) {
            abort(403, 'เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        $this->loadUnits();
    }

    private function loadUnits(): void
    {
        $this->units = Unit::ordered()
            ->get();
    }

    public function deleteUnit(int $unitId): void
    {
        $unit = Unit::find($unitId);
        if (!$unit) {
            return;
        }

        // Check if has examinees
        if ($unit->examinees()->count() > 0) {
            $this->dispatch('error-message', 'ไม่สามารถลบสังกัดนี้ได้ เนื่องจากมีผู้สอบอยู่ในสังกัดนี้');
            return;
        }

        $unit->delete();
        $this->loadUnits();
        
        $this->dispatch('success-message', 'ลบสังกัดเรียบร้อยแล้ว');
    }

    public function render()
    {
        return view('livewire.staff.units.index');
    }
}
