<?php

namespace App\Livewire\Staff\PasswordSupport;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('components.layouts.staff')]
#[Title('ประวัติการรีเซ็ตรหัสผ่าน')]
class History extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->canSupportPasswords()) {
            abort(403, 'ไม่มีสิทธิ์เข้าถึงหน้านี้');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = Activity::query()
            ->where('description', 'password_reset_by_support')
            ->with(['causer', 'subject'])
            ->when($this->search !== '', function ($query): void {
                $term = trim($this->search);
                $query->where(function ($subQuery) use ($term): void {
                    $subQuery->whereHas('causer', function ($userQ) use ($term): void {
                        $userQ->where('national_id', 'like', "%{$term}%")
                            ->orWhere('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%");
                    })->orWhereHas('subject', function ($userQ) use ($term): void {
                        $userQ->where('national_id', 'like', "%{$term}%")
                            ->orWhere('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%");
                    });
                });
            })
            ->latest()
            ->paginate(20);

        return view('livewire.staff.password-support.history', [
            'logs' => $logs,
        ]);
    }
}
