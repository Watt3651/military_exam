<?php

namespace App\Livewire\Staff\BorderAreas;

use App\Models\BorderArea;
use App\Models\BorderAreaScoreHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('ประวัติการเปลี่ยนคะแนนพื้นที่ชายแดน')]
class ScoreHistory extends Component
{
    public string $borderAreaFilter = '';

    /**
     * @var Collection<int, object>
     */
    public Collection $rows;

    /**
     * @var Collection<int, BorderArea>
     */
    public Collection $areas;

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isStaff()) {
            abort(403, 'เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        $this->areas = BorderArea::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $this->loadRows();
    }

    public function updatedBorderAreaFilter(): void
    {
        $this->loadRows();
    }

    private function loadRows(): void
    {
        $query = BorderAreaScoreHistory::query()
            ->from('border_area_score_history as bsh')
            ->leftJoin('border_areas as ba', 'ba.id', '=', 'bsh.border_area_id')
            ->leftJoin('users as u', 'u.id', '=', 'bsh.changed_by')
            ->select([
                'bsh.id',
                'bsh.old_score',
                'bsh.new_score',
                'bsh.reason',
                'bsh.changed_at',
                'ba.code as border_area_code',
                'ba.name as border_area_name',
                'u.rank as changed_by_rank',
                'u.first_name as changed_by_first_name',
                'u.last_name as changed_by_last_name',
            ])
            ->orderByDesc('bsh.changed_at');

        if ($this->borderAreaFilter !== '') {
            $query->where('bsh.border_area_id', (int) $this->borderAreaFilter);
        }

        $this->rows = $query->limit(300)->get();
    }

    public function render()
    {
        return view('livewire.staff.border-areas.score-history');
    }
}
