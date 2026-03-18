{{--
Staff Edit User Form
Section 2.1.3: Edit สำหรับ Staff/Commander/Password Support (Admin Only)

Only Staff can access this page
URL: /staff/users/{id}/edit
--}}

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Edit User Form --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                            <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-2xl font-bold text-gray-900">
                            แก้ไขผู้ใช้งานเจ้าหน้าที่
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถแก้ไขบัญชีผู้ใช้งานได้
                        </p>
                    </div>
                </div>
            </div>

            <form wire:submit="updateUser" class="p-6 space-y-6">

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
                                    placeholder="เช่น น.อ., น.ท., ร.อ." />
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
                                    <option value="password_support">เจ้าหน้าที่ช่วยรีเซ็ตรหัสผ่าน</option>
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

                    {{-- Info Box --}}
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>หมายเหตุ:</strong> การแก้ไขข้อมูลผู้ใช้งานจะไม่เปลี่ยนแปลงรหัสผ่าน
                                    หากต้องการเปลี่ยนรหัสผ่าน ให้ใช้ส่วนรีเซ็ตรหัสผ่านด้านล่าง
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
                            wire:loading.attr="disabled" wire:target="updateUser">
                            <span wire:loading.remove wire:target="updateUser">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                แก้ไขผู้ใช้งาน
                            </span>
                            <span wire:loading wire:target="updateUser">
                                <svg class="animate-spin h-4 w-4 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                กำลังแก้ไข...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">รีเซ็ตรหัสผ่าน</h3>
                <p class="mt-1 text-sm text-gray-500">เจ้าหน้าที่สามารถตั้งรหัสผ่านใหม่ให้บัญชีนี้ได้ และควรแจ้งรหัสผ่านใหม่ให้ผู้ใช้ทันที</p>
            </div>

            <form wire:submit="resetPassword" class="p-6 space-y-6">
                @if ($generated_password)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-medium text-amber-800">รหัสผ่านใหม่</p>
                        <div class="mt-2 flex items-center gap-3">
                            <p class="text-lg font-mono font-semibold text-amber-900">{{ $generated_password }}</p>
                            <button type="button"
                                onclick="navigator.clipboard.writeText('{{ $generated_password }}')"
                                class="text-sm text-amber-700 underline hover:text-amber-900">
                                คัดลอก
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-amber-700">รหัสผ่านนี้จะแสดงในหน้านี้หลังรีเซ็ตเท่านั้น กรุณาบันทึกหรือแจ้งผู้ใช้ทันที</p>
                    </div>
                @endif

                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" wire:model.live="auto_generate_password"
                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">สร้างรหัสผ่านอัตโนมัติ</span>
                    </label>
                </div>

                @if (! $auto_generate_password)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="reset_password" value="รหัสผ่านใหม่" class="required" />
                            <x-text-input wire:model="reset_password" id="reset_password" class="block mt-1 w-full"
                                type="password" autocomplete="new-password" placeholder="ตั้งรหัสผ่านใหม่" />
                            <x-input-error :messages="$errors->get('reset_password')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="reset_password_confirmation" value="ยืนยันรหัสผ่านใหม่" class="required" />
                            <x-text-input wire:model="reset_password_confirmation" id="reset_password_confirmation"
                                class="block mt-1 w-full" type="password" autocomplete="new-password"
                                placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" />
                            <x-input-error :messages="$errors->get('reset_password_confirmation')" class="mt-2" />
                        </div>
                    </div>
                @else
                    <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 text-sm text-gray-600">
                        ระบบจะสร้างรหัสผ่านแบบสุ่มให้อัตโนมัติเมื่อกดปุ่มรีเซ็ต
                    </div>
                @endif

                <div class="flex items-center justify-end pt-4 border-t border-gray-200">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                        wire:loading.attr="disabled" wire:target="resetPassword">
                        <span wire:loading.remove wire:target="resetPassword">รีเซ็ตรหัสผ่าน</span>
                        <span wire:loading wire:target="resetPassword">กำลังรีเซ็ต...</span>
                    </button>
                </div>
            </form>
        </div>
    {{-- Inline style for required label --}}
    <style>
        .required::after {
            content: " *";
            color: #ef4444;
        }
    </style>
</div>