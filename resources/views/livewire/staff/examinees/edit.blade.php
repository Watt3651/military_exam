<div class="py-12">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-700 text-sm">
                {{ session('success') }}
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

                    <div>
                        <x-input-label for="suspended_years" value="ปีที่ถูกงดบำเหน็จ" />
                        <x-text-input id="suspended_years" wire:model="suspended_years" type="number" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('suspended_years')" class="mt-2" />
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
                            <option value="pending">รอดำเนินการ</option>
                            <option value="confirmed">ยืนยันแล้ว</option>
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
                    <a href="{{ route('staff.examinees.index') }}" wire:navigate
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                        กลับหน้ารายการ
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-primary-700 disabled:opacity-50"
                            wire:loading.attr="disabled">
                        บันทึกการแก้ไข
                    </button>
                </div>
            </form>
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
    </div>
</div>
