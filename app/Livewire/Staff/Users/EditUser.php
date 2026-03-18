<?php

namespace App\Livewire\Staff\Users;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('แก้ไขผู้ใช้งานเจ้าหน้าที่')]
class EditUser extends Component
{
    /**
     * หมายเลขประจำตัว 13 หลัก
     */
    public string $national_id = '';

    /**
     * ยศ
     */
    public string $rank = '';

    /**
     * ชื่อ
     */
    public string $first_name = '';

    /**
     * นามสกุล
     */
    public string $last_name = '';

    /**
     * Email (optional)
     */
    public string $email = '';

    /**
     * Role (staff/commander/password_support)
     */
    public string $role = 'staff';

    /**
     * User ID for editing
     */
    public int $userId;

    public string $reset_password = '';

    public string $reset_password_confirmation = '';

    public bool $auto_generate_password = false;

    public ?string $generated_password = null;

    /**
     * Mount component - load user data
     */
    public function mount($id): void
    {
        // Only staff can access
        if (! Auth::check() || Auth::user()?->role !== 'staff') {
            abort(403, 'เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        $this->userId = $id;
        $user = User::findOrFail($id);

        // Load user data
        $this->national_id = $user->national_id;
        $this->rank = $user->rank;
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email ?? '';
        $this->role = $user->role;
    }

    /**
     * Validation rules ตาม Section 2.1.3
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'national_id' => [
                'required',
                'string',
                'digits:13',
                Rule::unique('users', 'national_id')->ignore($this->userId),
            ],
            'rank' => [
                'required',
                'string',
                'max:100',
            ],
            'first_name' => [
                'required',
                'string',
                'max:255',
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->userId),
            ],
            'role' => [
                'required',
                'string',
                Rule::in(['staff', 'commander', 'password_support']),
            ],
        ];
    }

    /**
     * Custom validation messages (ภาษาไทย)
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'national_id.required' => 'กรุณากรอกหมายเลขประจำตัว',
            'national_id.digits' => 'หมายเลขประจำตัวต้องเป็นตัวเลข 13 หลัก',
            'national_id.unique' => 'หมายเลขประจำตัวนี้ถูกใช้งานแล้ว',
            'rank.required' => 'กรุณากรอกยศ',
            'first_name.required' => 'กรุณากรอกชื่อ',
            'last_name.required' => 'กรุณากรอกนามสกุล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'role.required' => 'กรุณาเลือกบทบาท',
            'role.in' => 'บทบาทไม่ถูกต้อง',
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

    protected function generatePassword(): string
    {
        return Str::password(12);
    }

    /**
     * Update staff/commander user
     */
    public function updateUser()
    {
        // Validate input
        $validated = $this->validate();

        // Find and update user
        $user = User::findOrFail($this->userId);
        $user->update([
            'national_id' => $validated['national_id'],
            'rank' => $validated['rank'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'] ?: null,
            'role' => $validated['role'],
        ]);

        // Sync Spatie role
        $user->syncRoles([$validated['role']]);

        // Flash success message
        session()->flash('success', 'แก้ไขผู้ใช้งานสำเร็จ');

        // Redirect to index
        return redirect()->route('staff.users.index');
    }

    public function resetPassword(): void
    {
        $rules = [];

        if (! $this->auto_generate_password) {
            $rules['reset_password'] = [
                'required',
                'string',
                'confirmed',
                'min:8',
                Password::defaults(),
            ];
        }

        if ($rules !== []) {
            $this->validate($rules);
        }

        $password = $this->auto_generate_password
            ? $this->generatePassword()
            : $this->reset_password;

        $user = User::findOrFail($this->userId);
        $user->update([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ]);

        $this->generated_password = $password;
        $this->reset_password = '';
        $this->reset_password_confirmation = '';
        $this->resetValidation(['reset_password', 'reset_password_confirmation']);

        session()->flash('success', 'รีเซ็ตรหัสผ่านผู้ใช้งานเรียบร้อย');
    }

    /**
     * Render the component
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.staff.users.edit-user');
    }
}
