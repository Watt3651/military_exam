<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $national_id = '';
    public string $rank = '';
    public string $first_name = '';
    public string $last_name = '';
    public string $password = '';
    public string $password_confirmation = '';

    // Rank Options - same as Examinee/Profile
    public array $rankOptions = [
        'จ.ต.', 'จ.ท.', 'จ.อ.',
        'พ.จ.ต.', 'พ.จ.ท.', 'พ.จ.อ.',
        'ร.ต.', 'ร.ท.', 'ร.อ.',
        'น.ต.', 'น.ท.', 'น.อ.',
        'พล.ต.', 'พล.ท.', 'พล.อ.',
    ];

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'national_id' => ['required', 'string', 'digits:13', 'unique:'.User::class.',national_id'],
            'rank' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', 'min:8', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'national_id' => $validated['national_id'],
            'rank' => $validated['rank'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'password' => Hash::make($validated['password']),
            'role' => 'examinee',
            'is_active' => true,
        ]);

        // Assign examinee role
        $user->assignRole('examinee');

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <!-- Page Header -->
    <div class="mb-6 text-center">
        <h2 class="text-3xl font-extrabold text-primary-600">
            สมัครสมาชิก
        </h2>
        <p class="mt-2 text-sm text-gray-600">
            สำหรับผู้เข้าสอบเลื่อนฐานะทหาร
        </p>
    </div>

    <form wire:submit="register" class="space-y-6">
        <!-- National ID -->
        <div>
            <x-input-label for="national_id" value="หมายเลขประจำตัว 13 หลัก" />
            <x-text-input 
                wire:model="national_id" 
                id="national_id" 
                class="block mt-1 w-full" 
                type="text" 
                name="national_id" 
                required 
                autofocus
                maxlength="13"
                placeholder="กรอกหมายเลขประจำตัว 13 หลัก" />
            <x-input-error :messages="$errors->get('national_id')" class="mt-2" />
        </div>

        <!-- Rank -->
        {{-- <div>
            <x-input-label for="rank" value="ยศ" />
            <x-text-input 
                wire:model="rank" 
                id="rank" 
                class="block mt-1 w-full" 
                type="text" 
                name="rank" 
                required
                placeholder="เช่น พ.อ., ร.ต., ร.อ." />
            <x-input-error :messages="$errors->get('rank')" class="mt-2" />
        </div> --}}

        <!-- Rnak Options -->
        <div>
            <x-input-label for="rank" class="block text-sm font-medium text-gray-700 mb-1">
                ยศ <span class="text-red-500">*</span>
            </x-input-label>
            <select id="rank"
              wire:model="rank"
              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2D6A4F] focus:ring-[#2D6A4F] text-sm">
                <option value="">-- เลือกยศ --</option>
                @foreach ($rankOptions as $r)
                    <option value="{{ $r }}">{{ $r }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('rank')" class="mt-2" />
        </div>

        <!-- First Name -->
        <div>
            <x-input-label for="first_name" value="ชื่อ" />
            <x-text-input 
                wire:model="first_name" 
                id="first_name" 
                class="block mt-1 w-full" 
                type="text" 
                name="first_name" 
                required
                placeholder="กรอกชื่อ" />
            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
        </div>

        <!-- Last Name -->
        <div>
            <x-input-label for="last_name" value="นามสกุล" />
            <x-text-input 
                wire:model="last_name" 
                id="last_name" 
                class="block mt-1 w-full" 
                type="text" 
                name="last_name" 
                required
                placeholder="กรอกนามสกุล" />
            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="รหัสผ่าน" />

            <x-text-input 
                wire:model="password" 
                id="password" 
                class="block mt-1 w-full"
                type="password"
                name="password"
                required 
                autocomplete="new-password"
                placeholder="ตั้งรหัสผ่าน (อย่างน้อย 8 ตัวอักษร)" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" value="ยืนยันรหัสผ่าน" />

            <x-text-input 
                wire:model="password_confirmation" 
                id="password_confirmation" 
                class="block mt-1 w-full"
                type="password"
                name="password_confirmation" 
                required 
                autocomplete="new-password"
                placeholder="กรอกรหัสผ่านอีกครั้ง" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div>
            <x-primary-button class="w-full justify-center">
                สมัครสมาชิก
            </x-primary-button>
        </div>

        <!-- Login Link -->
        <div class="text-center text-sm text-gray-600">
            มีบัญชีอยู่แล้ว?
            <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-700 underline" wire:navigate>
                เข้าสู่ระบบ
            </a>
        </div>
    </form>
</div>
