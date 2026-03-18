<?php

namespace App\Livewire\Staff\PasswordSupport;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('ช่วยรีเซ็ตรหัสผ่าน')]
class Index extends Component
{
    public string $search = '';
    public string $reason = '';
    public string $reset_password = '';
    public string $reset_password_confirmation = '';
    public bool $auto_generate_password = true;
    public ?string $generated_password = null;
    public ?int $selectedUserId = null;

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->canSupportPasswords()) {
            abort(403, 'ไม่มีสิทธิ์เข้าถึงหน้านี้');
        }
    }

    protected function rules(): array
    {
        $rules = [
            'selectedUserId' => ['required', 'exists:users,id'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ];

        if (! $this->auto_generate_password) {
            $rules['reset_password'] = ['required', 'string', 'confirmed', 'min:8', Password::defaults()];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'selectedUserId.required' => 'กรุณาเลือกผู้ใช้ที่ต้องการรีเซ็ตรหัสผ่าน',
            'selectedUserId.exists' => 'ไม่พบผู้ใช้ที่เลือก',
            'reason.required' => 'กรุณาระบุเหตุผลในการรีเซ็ตรหัสผ่าน',
            'reason.min' => 'เหตุผลต้องมีอย่างน้อย 5 ตัวอักษร',
            'reset_password.required' => 'กรุณากรอกรหัสผ่านใหม่',
            'reset_password.min' => 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร',
            'reset_password.confirmed' => 'ยืนยันรหัสผ่านใหม่ไม่ตรงกัน',
        ];
    }

    public function updatedAutoGeneratePassword($value): void
    {
        if ($value) {
            $this->reset_password = '';
            $this->reset_password_confirmation = '';
        }
    }

    public function selectUser(int $userId): void
    {
        $this->selectedUserId = $userId;
        $this->generated_password = null;
        $this->resetValidation(['selectedUserId']);
    }

    protected function generatePassword(): string
    {
        return Str::password(12);
    }

    public function resetPassword(): void
    {
        $validated = $this->validate();

        /** @var User $user */
        $user = User::query()->findOrFail($validated['selectedUserId']);
        $password = $this->auto_generate_password ? $this->generatePassword() : $this->reset_password;

        $user->update([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ]);

        activity()
            ->causedBy(Auth::user())
            ->performedOn($user)
            ->withProperties([
                'reason' => $validated['reason'],
                'reset_by_role' => Auth::user()?->role,
            ])
            ->log('password_reset_by_support');

        $this->generated_password = $password;
        $this->reason = '';
        $this->reset_password = '';
        $this->reset_password_confirmation = '';

        session()->flash('success', 'รีเซ็ตรหัสผ่านเรียบร้อย และบังคับให้ผู้ใช้เปลี่ยนรหัสผ่านหลังเข้าสู่ระบบ');
    }

    public function getUsersProperty(): EloquentCollection
    {
        return User::query()
            ->when($this->search !== '', function ($query): void {
                $term = trim($this->search);
                $query->where(function ($subQuery) use ($term): void {
                    $subQuery->where('national_id', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"]);
                });
            })
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public function render()
    {
        return view('livewire.staff.password-support.index', [
            'users' => $this->users,
            'selectedUser' => $this->selectedUserId ? User::find($this->selectedUserId) : null,
        ]);
    }
}
