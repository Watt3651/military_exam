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
#[Title('สร้างผู้ใช้งาน Staff/Commander')]
class CreateUser extends Component
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
     * Role (staff/commander)
     */
    public string $role = 'staff';

    /**
     * รหัสผ่าน (manual input)
     */
    public string $password = '';

    /**
     * ยืนยันรหัสผ่าน
     */
    public string $password_confirmation = '';

    /**
     * Auto-generate password flag
     */
    public bool $auto_generate_password = false;

    /**
     * Generated password (to show to staff)
     */
    public ?string $generated_password = null;

    /**
     * Success message flag
     */
    public bool $user_created = false;

    /**
     * Created user data
     */
    public ?array $created_user_data = null;

    /**
     * Mount component - check authorization
     */
    public function mount(): void
    {
        // Only staff can access
        if (!Auth::check() || !Auth::user()->isStaff()) {
            abort(403, 'เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }
    }

    /**
     * Validation rules ตาม Section 2.1.3
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $rules = [
            'national_id' => [
                'required',
                'string',
                'digits:13',
                'unique:users,national_id',
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
                'unique:users,email',
            ],
            'role' => [
                'required',
                'string',
                Rule::in(['staff', 'commander']),
            ],
        ];

        // Password validation only if not auto-generating
        if (!$this->auto_generate_password) {
            $rules['password'] = [
                'required',
                'string',
                'confirmed',
                'min:8',
                Password::defaults(),
            ];
        }

        return $rules;
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
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
            'password.confirmed' => 'รหัสผ่านไม่ตรงกัน',
        ];
    }

    /**
     * Toggle auto-generate password
     */
    public function updatedAutoGeneratePassword($value): void
    {
        if ($value) {
            // Clear manual password fields
            $this->password = '';
            $this->password_confirmation = '';
        }
    }

    /**
     * Generate random secure password
     *
     * @return string
     */
    protected function generatePassword(): string
    {
        return Str::password(12); // Generate 12-character password
    }

    /**
     * Create new staff/commander user
     * Section 2.1.3: Register สำหรับ Staff/Commander (Admin Only)
     *
     * Process:
     * 1. Validate input
     * 2. Generate password if auto-generate enabled
     * 3. Create user with role = staff/commander
     * 4. Assign Spatie role
     * 5. Save created_by = current staff user_id
     * 6. Show success with credentials
     */
    public function createUser()
    {
        // Validate input
        $validated = $this->validate();

        // Generate password if needed
        $password = $this->auto_generate_password 
            ? $this->generatePassword() 
            : $validated['password'];

        // Create user
        $user = User::create([
            'national_id' => $validated['national_id'],
            'rank' => $validated['rank'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'] ?: null,
            'password' => Hash::make($password),
            'role' => $validated['role'],
            'is_active' => true,
            'created_by' => Auth::id(), // Save creator staff_id
        ]);

        // Assign Spatie role
        $user->assignRole($validated['role']);

        // Store generated password to show (only if auto-generated)
        if ($this->auto_generate_password) {
            $this->generated_password = $password;
        }

        // Store created user data
        $this->created_user_data = [
            'national_id' => $user->national_id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'role' => $user->role,
            'password' => $this->auto_generate_password ? $password : '(กำหนดโดยผู้สร้าง)',
        ];

        // Set success flag
        $this->user_created = true;

        // Flash success message
        session()->flash('success', 'สร้างผู้ใช้งานสำเร็จ');

        // TODO: Send email with credentials (optional - future enhancement)
        // if ($user->email) {
        //     Mail::to($user->email)->send(new UserCredentials($user, $password));
        // }
    }

    /**
     * Reset form for creating another user
     */
    public function createAnother(): void
    {
        $this->reset([
            'national_id',
            'rank',
            'first_name',
            'last_name',
            'email',
            'role',
            'password',
            'password_confirmation',
            'auto_generate_password',
            'generated_password',
            'user_created',
            'created_user_data',
        ]);

        $this->role = 'staff'; // Reset to default
    }

    /**
     * Render the component
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.staff.users.create-user');
    }
}
