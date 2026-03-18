<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">ช่วยรีเซ็ตรหัสผ่าน</h2>
                    <p class="mt-1 text-sm text-gray-500">ค้นหาผู้ใช้และรีเซ็ตรหัสผ่านได้โดยไม่ต้องเข้าสู่หน้าแก้ไขข้อมูลหลัก</p>
                </div>
                <a href="{{ route('staff.password-support.history') }}"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    ดูประวัติการรีเซ็ตรหัสผ่าน
                </a>
            </div>

            <div class="p-6 space-y-6">
                <div>
                    <x-input-label for="search" value="ค้นหาจากหมายเลขประจำตัว หรือชื่อ-นามสกุล" />
                    <x-text-input id="search" wire:model.live.debounce.300ms="search" type="text" class="block mt-1 w-full"
                        placeholder="เช่น 1234567890123 หรือ สมชาย" />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-sm font-medium text-gray-700">
                            ผลการค้นหา
                        </div>
                        <div class="divide-y divide-gray-100 max-h-[32rem] overflow-y-auto">
                            @forelse ($users as $user)
                                <button type="button" wire:click="selectUser({{ $user->id }})"
                                    class="w-full px-4 py-3 text-left hover:bg-gray-50 {{ $selectedUser && $selectedUser->id === $user->id ? 'bg-blue-50' : 'bg-white' }}">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $user->full_name }}</p>
                                            <p class="mt-1 text-sm text-gray-500">{{ $user->national_id }}</p>
                                        </div>
                                        <x-role-badge :role="$user->role" />
                                    </div>
                                </button>
                            @empty
                                <div class="px-4 py-8 text-center text-sm text-gray-500">ไม่พบผู้ใช้ตามเงื่อนไขค้นหา</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-lg">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-sm font-medium text-gray-700">
                            รีเซ็ตรหัสผ่าน
                        </div>
                        <form wire:submit="resetPassword" class="p-6 space-y-6">
                            @if ($selectedUser)
                                <div class="rounded-lg bg-blue-50 border border-blue-200 p-4 text-sm text-blue-800">
                                    <div class="font-semibold">ผู้ใช้ที่เลือก</div>
                                    <div class="mt-1">{{ $selectedUser->full_name }} ({{ $selectedUser->national_id }})</div>
                                    <div class="mt-1">บทบาท:
                                        <x-role-badge :role="$selectedUser->role" class="ml-1" />
                                    </div>
                                </div>
                            @else
                                <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-800">
                                    กรุณาเลือกผู้ใช้จากรายการทางซ้ายก่อนรีเซ็ตรหัสผ่าน
                                </div>
                            @endif

                            <x-input-error :messages="$errors->get('selectedUserId')" class="mt-2" />

                            @if ($generated_password)
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                    <p class="text-sm font-medium text-amber-800">รหัสผ่านชั่วคราวใหม่</p>
                                    <div class="mt-2 flex items-center gap-3">
                                        <p class="text-lg font-mono font-semibold text-amber-900">{{ $generated_password }}</p>
                                        <button type="button"
                                            onclick="navigator.clipboard.writeText('{{ $generated_password }}')"
                                            class="text-sm text-amber-700 underline hover:text-amber-900">
                                            คัดลอก
                                        </button>
                                    </div>
                                    <p class="mt-2 text-xs text-amber-700">ผู้ใช้จะถูกบังคับให้เปลี่ยนรหัสผ่านหลังเข้าสู่ระบบครั้งถัดไป</p>
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
                                        <x-input-label for="reset_password" value="รหัสผ่านใหม่" />
                                        <x-text-input id="reset_password" wire:model="reset_password" type="password" class="block mt-1 w-full" autocomplete="new-password" />
                                        <x-input-error :messages="$errors->get('reset_password')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="reset_password_confirmation" value="ยืนยันรหัสผ่านใหม่" />
                                        <x-text-input id="reset_password_confirmation" wire:model="reset_password_confirmation" type="password" class="block mt-1 w-full" autocomplete="new-password" />
                                        <x-input-error :messages="$errors->get('reset_password_confirmation')" class="mt-2" />
                                    </div>
                                </div>
                            @else
                                <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 text-sm text-gray-600">
                                    ระบบจะสร้างรหัสผ่านชั่วคราวแบบสุ่มให้อัตโนมัติเมื่อกดรีเซ็ตรหัสผ่าน
                                </div>
                            @endif

                            <div>
                                <x-input-label for="reason" value="เหตุผลในการรีเซ็ตรหัสผ่าน" />
                                <textarea id="reason" wire:model="reason" rows="3"
                                    class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    placeholder="เช่น ผู้ใช้ยืนยันตัวตนแล้วและแจ้งว่าลืมรหัสผ่าน"></textarea>
                                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                            </div>

                            <div class="flex items-center justify-end border-t border-gray-200 pt-6">
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                                    wire:loading.attr="disabled" wire:target="resetPassword">
                                    <span wire:loading.remove wire:target="resetPassword">รีเซ็ตรหัสผ่าน</span>
                                    <span wire:loading wire:target="resetPassword">กำลังรีเซ็ต...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
