{{--
Staff Create User Form
Section 2.1.3: Register สำหรับ Staff/Commander (Admin Only)

Only Staff can access this page
URL: /staff/users/create
--}}

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

        @if ($user_created && $created_user_data)
            {{-- Success Message Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                สร้างผู้ใช้งานสำเร็จ!
                            </h3>
                            <p class="text-sm text-gray-600">
                                กรุณาบันทึกข้อมูลการเข้าสู่ระบบด้านล่างนี้
                            </p>
                        </div>
                    </div>

                    {{-- User Credentials Display --}}
                    <div class="bg-gray-50 border-2 border-green-200 rounded-lg p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500">หมายเลขประจำตัว (Username)</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <p class="text-lg font-mono font-semibold text-gray-900">
                                        {{ $created_user_data['national_id'] }}</p>
                                    <button type="button"
                                        onclick="navigator.clipboard.writeText('{{ $created_user_data['national_id'] }}')"
                                        class="text-gray-500 hover:text-gray-700" title="คัดลอก">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            @if ($generated_password)
                                <div>
                                    <label class="block text-xs font-medium text-gray-500">รหัสผ่าน (Password)</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <p class="text-lg font-mono font-semibold text-red-600">{{ $generated_password }}</p>
                                        <button type="button"
                                            onclick="navigator.clipboard.writeText('{{ $generated_password }}')"
                                            class="text-gray-500 hover:text-gray-700" title="คัดลอก">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="mt-1 text-xs text-red-600">⚠️ รหัสผ่านนี้จะแสดงเพียงครั้งเดียว กรุณาบันทึกไว้</p>
                                </div>
                            @endif
                        </div>

                        <div class="border-t border-gray-200 pt-4">
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt class="font-medium text-gray-500">ชื่อ-นามสกุล</dt>
                                    <dd class="mt-1 text-gray-900">{{ $created_user_data['full_name'] }}</dd>
                                </div>
                                @if ($created_user_data['email'])
                                    <div>
                                        <dt class="font-medium text-gray-500">อีเมล</dt>
                                        <dd class="mt-1 text-gray-900">{{ $created_user_data['email'] }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="font-medium text-gray-500">บทบาท</dt>
                                    <dd class="mt-1">
                                        @if ($created_user_data['role'] === 'staff')
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                เจ้าหน้าที่
                                            </span>
                                        @else
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                                ผู้บังคับบัญชา
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-6 flex gap-3">
                        <button wire:click="createAnother" type="button"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            สร้างผู้ใช้งานอีก
                        </button>
                        <a href="{{ route('staff.users.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            รายการผู้ใช้งาน
                        </a>
                    </div>
                </div>
            </div>
        @else
            {{-- Create User Form --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                                <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-2xl font-bold text-gray-900">
                                สร้างผู้ใช้งาน Staff/Commander
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถสร้างบัญชีผู้ใช้งานได้
                            </p>
                        </div>
                    </div>
                </div>

                <form wire:submit="createUser" class="p-6 space-y-6">

                    {{-- Personal Information Section --}}
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            ข้อมูลส่วนตัว
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- National ID --}}
                            <div class="md:col-span-2">
                                <x-input-label for="national_id" value="หมายเลขประจำตัว 13 หลัก" class="required" />
                                <x-text-input wire:model="national_id" id="national_id" class="block mt-1 w-full"
                                    type="text" required autofocus maxlength="13" pattern="[0-9]{13}" inputmode="numeric"
                                    placeholder="กรอกหมายเลขประจำตัว 13 หลัก" />
                                <x-input-error :messages="$errors->get('national_id')" class="mt-2" />
                            </div>

                            {{-- Rank --}}
                            <div>
                                <x-input-label for="rank" value="ยศ" class="required" />
                                <x-text-input wire:model="rank" id="rank" class="block mt-1 w-full" type="text" required
                                    placeholder="เช่น พ.อ., พ.ท., ร.อ." />
                                <x-input-error :messages="$errors->get('rank')" class="mt-2" />
                            </div>

                            {{-- Role --}}
                            <div>
                                <x-input-label for="role" value="บทบาท" class="required" />
                                <select wire:model="role" id="role"
                                    class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                                    required>
                                    <option value="staff">เจ้าหน้าที่ (Staff)</option>
                                    <option value="commander">ผู้บังคับบัญชา (Commander)</option>
                                </select>
                                <x-input-error :messages="$errors->get('role')" class="mt-2" />
                            </div>

                            {{-- First Name --}}
                            <div>
                                <x-input-label for="first_name" value="ชื่อ" class="required" />
                                <x-text-input wire:model="first_name" id="first_name" class="block mt-1 w-full" type="text"
                                    required placeholder="กรอกชื่อ" />
                                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                            </div>

                            {{-- Last Name --}}
                            <div>
                                <x-input-label for="last_name" value="นามสกุล" class="required" />
                                <x-text-input wire:model="last_name" id="last_name" class="block mt-1 w-full" type="text"
                                    required placeholder="กรอกนามสกุล" />
                                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                            </div>

                            {{-- Email (Optional) --}}
                            <div class="md:col-span-2">
                                <x-input-label for="email" value="อีเมล (ไม่บังคับ)" />
                                <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email"
                                    placeholder="example@email.com" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">
                                    แนะนำให้กรอกอีเมลเพื่อรับข้อมูลการเข้าสู่ระบบ
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Divider --}}
                    <div class="border-t border-gray-200"></div>

                    {{-- Password Section --}}
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            ตั้งรหัสผ่าน
                        </h3>

                        {{-- Auto-generate Password Toggle --}}
                        <div class="mb-4">
                            <label class="inline-flex items-center">
                                <input type="checkbox" wire:model.live="auto_generate_password"
                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    สร้างรหัสผ่านอัตโนมัติ (แนะนำ)
                                </span>
                            </label>
                            <p class="mt-1 ml-6 text-xs text-gray-500">
                                ระบบจะสร้างรหัสผ่านที่ปลอดภัยให้อัตโนมัติ
                            </p>
                        </div>

                        @if (!$auto_generate_password)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Manual Password --}}
                                <div>
                                    <x-input-label for="password" value="รหัสผ่าน" class="required" />
                                    <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password"
                                        required autocomplete="new-password"
                                        placeholder="ตั้งรหัสผ่าน (อย่างน้อย 8 ตัวอักษร)" />
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                </div>

                                {{-- Confirm Password --}}
                                <div>
                                    <x-input-label for="password_confirmation" value="ยืนยันรหัสผ่าน" class="required" />
                                    <x-text-input wire:model="password_confirmation" id="password_confirmation"
                                        class="block mt-1 w-full" type="password" required autocomplete="new-password"
                                        placeholder="กรอกรหัสผ่านอีกครั้ง" />
                                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                                </div>
                            </div>
                        @else
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            ระบบจะสร้างรหัสผ่านที่ปลอดภัย 12 ตัวอักษรให้อัตโนมัติ
                                            <br>
                                            รหัสผ่านจะแสดงหลังจากสร้างผู้ใช้งานสำเร็จ
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Info Box --}}
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>หมายเหตุ:</strong> กรุณาบันทึกข้อมูลการเข้าสู่ระบบและแจ้งให้ผู้ใช้งานทราบ
                                    รหัสผ่านที่สร้างอัตโนมัติจะแสดงเพียงครั้งเดียว
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Submit Buttons --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('staff.dashboard') }}"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            ยกเลิก
                        </a>

                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                            wire:loading.attr="disabled" wire:target="createUser">
                            <span wire:loading.remove wire:target="createUser">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                สร้างผู้ใช้งาน
                            </span>
                            <span wire:loading wire:target="createUser">
                                <svg class="animate-spin h-4 w-4 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                กำลังสร้าง...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
    {{-- Inline style for required label --}}
    <style>
        .required::after {
            content: " *";
            color: #ef4444;
        }
    </style>
</div>