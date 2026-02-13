<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('สมัครสมาชิก - ระบบสอบเลื่อนฐานะทหาร')]
class Register extends Component
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
     * รหัสผ่าน
     */
    public string $password = '';

    /**
     * ยืนยันรหัสผ่าน
     */
    public string $password_confirmation = '';

    /**
     * Validation rules ตาม Section 2.1.2
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
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                Password::defaults(),
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
            'rank.max' => 'ยศต้องไม่เกิน 100 ตัวอักษร',
            'first_name.required' => 'กรุณากรอกชื่อ',
            'first_name.max' => 'ชื่อต้องไม่เกิน 255 ตัวอักษร',
            'last_name.required' => 'กรุณากรอกนามสกุล',
            'last_name.max' => 'นามสกุลต้องไม่เกิน 255 ตัวอักษร',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
            'password.confirmed' => 'รหัสผ่านไม่ตรงกัน',
        ];
    }

    /**
     * Custom attribute names (ภาษาไทย)
     *
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'national_id' => 'หมายเลขประจำตัว',
            'rank' => 'ยศ',
            'first_name' => 'ชื่อ',
            'last_name' => 'นามสกุล',
            'password' => 'รหัสผ่าน',
            'password_confirmation' => 'ยืนยันรหัสผ่าน',
        ];
    }

    /**
     * Handle registration request
     * Section 2.1.2: Register สำหรับผู้เข้าสอบ
     *
     * Process:
     * 1. Validate input
     * 2. Hash password (bcrypt)
     * 3. Create user with role = 'examinee'
     * 4. Assign 'examinee' role (Spatie)
     * 5. Trigger Registered event
     * 6. Auto-login
     * 7. Redirect to dashboard
     */
    public function register()
    {
        // Validate input
        $validated = $this->validate();

        // Create user
        $user = User::create([
            'national_id' => $validated['national_id'],
            'rank' => $validated['rank'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'password' => Hash::make($validated['password']), // Hash password (bcrypt)
            'role' => 'examinee', // Set role
            'is_active' => true, // Active by default
        ]);

        // Assign Spatie role
        $user->assignRole('examinee');

        // Fire registered event (for email verification, etc.)
        event(new Registered($user));

        // Auto-login after successful registration
        Auth::login($user);

        // Flash success message
        session()->flash('success', 'สมัครสมาชิกสำเร็จ! ยินดีต้อนรับเข้าสู่ระบบสอบเลื่อนฐานะทหาร');

        // Redirect to dashboard
        return $this->redirect(route('dashboard'), navigate: true);
    }

    /**
     * Render the component
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.auth.register');
    }
}
