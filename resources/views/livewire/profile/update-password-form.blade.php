<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            เปลี่ยนรหัสผ่าน
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            ตั้งรหัสผ่านที่มีความปลอดภัยและยาวพอเพื่อความปลอดภัยของบัญชี
        </p>
    </header>

    <form wire:submit="updatePassword" class="mt-6 space-y-6">
        <div>
            <x-input-label for="update_password_current_password" value="รหัสผ่านปัจจุบัน" />
            <x-text-input 
                wire:model="current_password" 
                id="update_password_current_password" 
                name="current_password" 
                type="password" 
                class="mt-1 block w-full" 
                autocomplete="current-password"
                placeholder="กรอกรหัสผ่านปัจจุบัน" />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="รหัสผ่านใหม่" />
            <x-text-input 
                wire:model="password" 
                id="update_password_password" 
                name="password" 
                type="password" 
                class="mt-1 block w-full" 
                autocomplete="new-password"
                placeholder="กรอกรหัสผ่านใหม่ (อย่างน้อย 8 ตัวอักษร)" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="ยืนยันรหัสผ่านใหม่" />
            <x-text-input 
                wire:model="password_confirmation" 
                id="update_password_password_confirmation" 
                name="password_confirmation" 
                type="password" 
                class="mt-1 block w-full" 
                autocomplete="new-password"
                placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>บันทึก</x-primary-button>

            <x-action-message class="me-3" on="password-updated">
                บันทึกสำเร็จ
            </x-action-message>
        </div>
    </form>
</section>
