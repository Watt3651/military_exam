{{--
Public Register Form for Examinee
Section 2.1.2: Register สำหรับผู้เข้าสอบ

Fields:
- หมายเลขประจำตัว 13 หลัก (unique)
- ยศ
- ชื่อ
- นามสกุล
- รหัสผ่าน
- ยืนยันรหัสผ่าน
--}}

<div class="w-full">
    {{-- Page Header --}}
    <div class="mb-8 text-center">
        <div class="flex justify-center mb-4">
            <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
        </div>

        <h2 class="text-3xl font-extrabold text-primary-600 mb-2">
            สมัครสมาชิก
        </h2>
        <p class="text-sm text-gray-600">
            สำหรับผู้เข้าสอบเลื่อนฐานะทหาร
        </p>
        <p class="mt-1 text-xs text-gray-500">
            กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง
        </p>
    </div>

    {{-- Registration Form --}}
    <form wire:submit="register" class="space-y-5">

        {{-- National ID --}}
        <div>
            <x-input-label for="national_id" value="หมายเลขประจำตัว 13 หลัก" class="required" />
            <x-text-input wire:model="national_id" id="national_id" class="block mt-1 w-full" type="text"
                name="national_id" required autofocus maxlength="13" pattern="[0-9]{13}" inputmode="numeric"
                placeholder="กรอกหมายเลขประจำตัว 13 หลัก" />
            <x-input-error :messages="$errors->get('national_id')" class="mt-2" />
            <p class="mt-1 text-xs text-gray-500">
                <svg class="inline w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clip-rule="evenodd" />
                </svg>
                กรอกเฉพาะตัวเลข 13 หลักเท่านั้น
            </p>
        </div>

        {{-- Rank --}}
        <div>
            <x-input-label for="rank" value="ยศ" class="required" />
            <x-text-input wire:model="rank" id="rank" class="block mt-1 w-full" type="text" name="rank" required
                placeholder="เช่น น.อ., ร.อ.,​ พ.จ.อ., จ.อ." />
            <x-input-error :messages="$errors->get('rank')" class="mt-2" />
        </div>

        {{-- First Name & Last Name in Grid --}}
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            {{-- First Name --}}
            <div>
                <x-input-label for="first_name" value="ชื่อ" class="required" />
                <x-text-input wire:model="first_name" id="first_name" class="block mt-1 w-full" type="text"
                    name="first_name" required placeholder="กรอกชื่อ" />
                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
            </div>

            {{-- Last Name --}}
            <div>
                <x-input-label for="last_name" value="นามสกุล" class="required" />
                <x-text-input wire:model="last_name" id="last_name" class="block mt-1 w-full" type="text"
                    name="last_name" required placeholder="กรอกนามสกุล" />
                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
            </div>
        </div>

        {{-- Divider --}}
        <div class="relative py-2">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-xs">
                <span class="px-2 bg-white text-gray-500">ตั้งรหัสผ่าน</span>
            </div>
        </div>

        {{-- Password --}}
        <div>
            <x-input-label for="password" value="รหัสผ่าน" class="required" />
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" name="password"
                required autocomplete="new-password" placeholder="ตั้งรหัสผ่าน (อย่างน้อย 8 ตัวอักษร)" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
            <p class="mt-1 text-xs text-gray-500">
                <svg class="inline w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                        clip-rule="evenodd" />
                </svg>
                รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร
            </p>
        </div>

        {{-- Confirm Password --}}
        <div>
            <x-input-label for="password_confirmation" value="ยืนยันรหัสผ่าน" class="required" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                type="password" name="password_confirmation" required autocomplete="new-password"
                placeholder="กรอกรหัสผ่านอีกครั้ง" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        {{-- Terms Notice --}}
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-xs text-gray-600">
                <svg class="inline w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clip-rule="evenodd" />
                </svg>
                การสมัครสมาชิกถือว่าคุณได้อ่านและยอมรับเงื่อนไขการใช้งานระบบสอบเลื่อนฐานะทหาร
                ข้อมูลที่กรอกจะถูกเก็บเป็นความลับและใช้เพื่อการสอบเท่านั้น
            </p>
        </div>

        {{-- Submit Button --}}
        <div class="pt-2">
            <x-primary-button class="w-full justify-center py-3 text-base font-semibold" wire:loading.attr="disabled"
                wire:target="register">
                <span wire:loading.remove wire:target="register">
                    <svg class="inline w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    สมัครสมาชิก
                </span>
                <span wire:loading wire:target="register">
                    <svg class="inline animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    กำลังสมัครสมาชิก...
                </span>
            </x-primary-button>
        </div>

        {{-- Login Link --}}
        <div class="relative py-4">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-4 bg-white text-gray-600">
                    มีบัญชีอยู่แล้ว?
                    <a href="{{ route('login') }}"
                        class="font-medium text-primary-600 hover:text-primary-700 underline transition-colors"
                        wire:navigate>
                        เข้าสู่ระบบ
                    </a>
                </span>
            </div>
        </div>
    </form>
    {{-- Inline style for required label --}}
    <style>
        .required::after {
            content: " *";
            color: #ef4444;
        }
    </style>
</div>