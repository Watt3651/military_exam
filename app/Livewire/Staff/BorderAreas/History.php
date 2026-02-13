<?php

namespace App\Livewire\Staff\BorderAreas;

use App\Models\BorderAreaScoreHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('ประวัติคะแนนพื้นที่ชายแดน')]
class History extends Component
{
    public Collection $rows;

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isStaff()) {
            abort(403, 'เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        $this->rows = BorderAreaScoreHistory::query()
            ->with(['borderArea', 'changedBy'])
            ->latestFirst()
            ->limit(200)
            ->get();
    }

    public function render()
    {
        return view('livewire.staff.border-areas.history');
    }
}
