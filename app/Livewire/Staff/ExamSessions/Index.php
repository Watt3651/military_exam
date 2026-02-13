<?php

namespace App\Livewire\Staff\ExamSessions;

use App\Models\ExamSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('จัดการรอบสอบ')]
class Index extends Component
{
    public Collection $sessions;

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isStaff()) {
            abort(403, 'เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        $this->loadSessions();
    }

    private function loadSessions(): void
    {
        $this->sessions = ExamSession::query()
            ->orderByDesc('year')
            ->orderBy('exam_level')
            ->get();
    }

    public function render()
    {
        return view('livewire.staff.exam-sessions.index');
    }
}
