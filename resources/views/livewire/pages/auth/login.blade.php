<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <!-- Page Header -->
    <div class="mb-6 text-center">
        <h2 class="text-3xl font-extrabold text-primary-600">
            เข้าสู่ระบบ
        </h2>
        <p class="mt-2 text-sm text-gray-600">
            ระบบสอบเลื่อนฐานะทหาร
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-6">
        <!-- National ID -->
        <div>
            <x-input-label for="national_id" value="หมายเลขประจำตัว 13 หลัก" />
            <x-text-input 
                wire:model="form.national_id" 
                id="national_id" 
                class="block mt-1 w-full" 
                type="text" 
                name="national_id" 
                required 
                autofocus 
                autocomplete="username"
                maxlength="13"
                placeholder="กรอกหมายเลขประจำตัว 13 หลัก" />
            <x-input-error :messages="$errors->get('form.national_id')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="รหัสผ่าน" />

            <x-text-input 
                wire:model="form.password" 
                id="password" 
                class="block mt-1 w-full"
                type="password"
                name="password"
                required 
                autocomplete="current-password"
                placeholder="กรอกรหัสผ่าน" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label for="remember" class="inline-flex items-center">
                <input 
                    wire:model="form.remember" 
                    id="remember" 
                    type="checkbox" 
                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500" 
                    name="remember">
                <span class="ms-2 text-sm text-gray-600">จดจำการเข้าสู่ระบบ</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-primary-600 hover:text-primary-700 underline" href="{{ route('password.request') }}" wire:navigate>
                    ลืมรหัสผ่าน?
                </a>
            @endif
        </div>

        <div>
            <x-primary-button class="w-full justify-center">
                เข้าสู่ระบบ
            </x-primary-button>
        </div>

        <!-- Register Link -->
        <div class="text-center text-sm text-gray-600">
            ยังไม่มีบัญชี?
            <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:text-primary-700 underline" wire:navigate>
                สมัครสมาชิก (ผู้เข้าสอบ)
            </a>
        </div>
    </form>
</div>
