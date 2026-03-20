<div class="py-12">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('notification_sent'))
            <div class="rounded-lg bg-blue-50 border border-blue-200 p-4 text-blue-700 text-sm">
                ✅ {{ session('notification_sent') }}
            </div>
        @endif

        <!-- Livewire Alert -->
        @if ($this->alertSuccess ?? false)
            <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-700 text-sm">
                ✅ ส่งแจ้งเตือนสำเร็จแล้ว
            </div>
        @endif

        {{-- Simple Test Button --}}
        <div class="fixed bottom-4 left-4 bg-purple-500 text-white p-2 rounded text-xs z-50">
            Debug: <button wire:click="$set('alertSuccess', true)" class="bg-white text-purple-500 px-2 py-1 rounded text-xs">Test Alert</button>
            <button wire:click="openNotificationModal" class="bg-white text-purple-500 px-2 py-1 rounded text-xs ml-2">Test Modal</button>
        </div>

        {{-- Notification Modal --}}
        @if ($showNotificationModal)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">แจ้งเตือนตรวจสอบข้อมูล</h3>
                        <div class="mt-2 px-7 py-3">
                            <p class="text-sm text-gray-500">
                                ส่งแจ้งเตือนให้ผู้สมัครตรวจสอบและแก้ไขข้อมูล
                            </p>
                            <textarea wire:model="notificationMessage" 
                                      class="mt-3 w-full border-gray-300 rounded-md shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                                      rows="4"
                                      placeholder="กรุณาระบุข้อความแจ้งเตือน..."></textarea>
                            @error('notificationMessage')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="items-center px-4 py-3">
                            <button wire:click="sendNotification"
                                    class="px-4 py-2 bg-yellow-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                ส่งแจ้งเตือน
                            </button>
                            <button wire:click="closeNotificationModal"
                                    class="mt-3 px-4 py-2 bg-white text-gray-700 text-base font-medium rounded-md w-full shadow-sm border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                ยกเลิก
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">แก้ไขข้อมูลผู้เข้าสอบ</h2>
                <p class="mt-1 text-sm text-gray-500">สามารถแก้ไขได้ทุกฟิลด์ ยกเว้นหมายเลขประจำตัว</p>
            </div>

            <form wire:submit="save" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <x-input-label for="national_id" value="หมายเลขประจำตัว (แก้ไขไม่ได้)" />
                        <x-text-input id="national_id" wire:model="national_id" type="text" class="block mt-1 w-full bg-gray-100" readonly disabled />
                    </div>
                    <div>
                        <x-input-label for="rank" value="ยศ" />
                        <x-text-input id="rank" wire:model="rank" type="text" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('rank')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" value="อีเมล" />
                        <x-text-input id="email" wire:model="email" type="email" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="first_name" value="ชื่อ" />
                        <x-text-input id="first_name" wire:model="first_name" type="text" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="last_name" value="นามสกุล" />
                        <x-text-input id="last_name" wire:model="last_name" type="text" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="position" value="ตำแหน่ง" />
                        <x-text-input id="position" wire:model="position" type="text" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('position')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="branch_id" value="เหล่า" />
                        <select id="branch_id" wire:model="branch_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">เลือกเหล่า</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->code }} - {{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="age" value="อายุ" />
                        <x-text-input id="age" wire:model="age" type="number" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('age')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="eligible_year" value="ปีที่มีสิทธิ์สอบ" />
                        <x-text-input id="eligible_year" wire:model="eligible_year" type="number" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('eligible_year')" class="mt-2" />
                    </div>

                    <div class="md:col-span-3">
                        <x-input-label value="ปีที่ถูกงดบำเหน็จ" />
                        @if(count($availableSuspendedYears) > 0)
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 mt-2">
                                @foreach($availableSuspendedYears as $yearInfo)
                                    <label class="flex items-center p-2 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ in_array($yearInfo['year'], $suspended_years) ? 'bg-red-50 border-red-300' : 'border-gray-200' }}">
                                        <input type="checkbox" 
                                               wire:model="suspended_years" 
                                               value="{{ $yearInfo['year'] }}"
                                               class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                        <span class="ml-2 text-sm">
                                            {{ $yearInfo['year'] }}
                                            <span class="text-xs text-gray-500 block">(หัก {{ $yearInfo['points'] }} คะแนน)</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs text-gray-500">เลือกปีที่ถูกงดบำเหน็จ ระบบจะหักคะแนนตามเกณฑ์ขั้นบันได</p>
                        @else
                            <p class="mt-2 text-sm text-gray-400">กรุณาระบุปีที่มีสิทธิ์สอบก่อน</p>
                        @endif
                        <x-input-error :messages="$errors->get('suspended_years')" class="mt-2" />
                        <x-input-error :messages="$errors->get('suspended_years.*')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="border_area_id" value="พื้นที่ชายแดน" />
                        <select id="border_area_id" wire:model="border_area_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">ไม่ระบุ</option>
                            @foreach ($borderAreas as $area)
                                <option value="{{ $area->id }}">{{ $area->code }} - {{ $area->name }} (+{{ number_format((float) $area->special_score, 2) }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('border_area_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="test_location_id" value="สถานที่สอบ" />
                        <select id="test_location_id" wire:model="test_location_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">ไม่ระบุ</option>
                            @foreach ($testLocations as $location)
                                <option value="{{ $location->id }}">{{ $location->code }} - {{ $location->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('test_location_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="exam_number" value="หมายเลขสอบ" />
                        <x-text-input id="exam_number" wire:model="exam_number" type="text" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('exam_number')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="registration_status" value="สถานะการลงทะเบียน" />
                        <select id="registration_status" wire:model="registration_status" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="pending">รอยืนยันการสมัคร</option>
                            <option value="confirmed">ยืนยันการสมัครแล้ว</option>
                            <option value="cancelled">ยกเลิก</option>
                        </select>
                        <x-input-error :messages="$errors->get('registration_status')" class="mt-2" />
                    </div>
                    <div class="md:col-span-3">
                        <x-input-label for="reason" value="เหตุผลในการแก้ไข (บังคับ)" />
                        <textarea id="reason" wire:model="reason" rows="3"
                                  class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                  placeholder="ระบุเหตุผลในการแก้ไขข้อมูลครั้งนี้"></textarea>
                        <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-green-100"
                            wire:loading.attr="disabled">
                        บันทึกการแก้ไข
                    </button>
                </div>
            </form>

            <div class="p-6 border-t border-gray-200">
                <div class="flex items-center justify-end gap-3">
                    <button type="button" wire:click="openNotificationModal"
                            class="inline-flex items-center px-4 py-2 bg-yellow-500 border border-yellow-600 rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        แจ้งเตือนตรวจสอบข้อมูล
                    </button>
                    <a href="{{ route('staff.examinees.index') }}" wire:navigate
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                        กลับหน้ารายการ
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Edit Logs</h3>
                <p class="mt-1 text-sm text-gray-500">ประวัติการแก้ไขข้อมูลล่าสุด (จาก examinee_edit_logs)</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่แก้ไข</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ฟิลด์</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ค่าเดิม</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ค่าใหม่</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้แก้ไข</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เหตุผล</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($editLogs as $log)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $log->edited_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $log->field_label }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $log->old_value ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $log->new_value ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $log->editedBy?->full_name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $log->reason ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">ยังไม่มีประวัติการแก้ไข</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">รีเซ็ตรหัสผ่านผู้สมัครสอบ</h3>
                <p class="mt-1 text-sm text-gray-500">เจ้าหน้าที่สามารถตั้งรหัสผ่านใหม่ให้ผู้สมัครสอบรายนี้ได้โดยตรง</p>
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
                        <p class="mt-2 text-xs text-amber-700">รหัสผ่านนี้จะแสดงหลังรีเซ็ตเท่านั้น กรุณาบันทึกหรือแจ้งผู้สมัครสอบทันที</p>
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
                        ระบบจะสร้างรหัสผ่านแบบสุ่มให้อัตโนมัติเมื่อกดปุ่มรีเซ็ตรหัสผ่าน
                    </div>
                @endif

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

    {{-- Notification Modal --}}
    @if ($showNotificationModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeNotificationModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    แจ้งเตือนตรวจสอบข้อมูล
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        ส่งแจ้งเตือนให้ผู้สมัครตรวจสอบและแก้ไขข้อมูลให้ถูกต้อง
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white px-4 py-3 sm:px-6 sm:flex sm:flex-row">
                        <div class="w-full">
                            <label for="notificationMessage" class="block text-sm font-medium text-gray-700 mb-2">
                                ข้อความแจ้งเตือน
                            </label>
                            <textarea id="notificationMessage" wire:model="notificationMessage" rows="3"
                                      class="w-full border-gray-300 rounded-md shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                                      placeholder="ระบุข้อความแจ้งเตือน..."></textarea>
                            @error('notificationMessage')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="sendNotification"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm">
                            ส่งแจ้งเตือน
                        </button>
                        <button type="button" wire:click="closeNotificationModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            ยกเลิก
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
