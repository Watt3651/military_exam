<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">สร้างรอบสอบใหม่</h2>
                <p class="mt-1 text-sm text-gray-500">กรอกข้อมูลรอบสอบตามเงื่อนไขวันเวลาและปี/ระดับที่ไม่ซ้ำกัน</p>
            </div>

            <form wire:submit="save" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="year" value="ปีการสอบ (พ.ศ.)" />
                        <x-text-input id="year" wire:model="year" type="number" class="block mt-1 w-full" placeholder="เช่น 2569" />
                        <x-input-error :messages="$errors->get('year')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="exam_level" value="ระดับการสอบ" />
                        <select id="exam_level" wire:model="exam_level"
                                class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="sergeant_major">จ่าเอก</option>
                            <option value="master_sergeant">พันจ่าเอก</option>
                        </select>
                        <x-input-error :messages="$errors->get('exam_level')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="registration_start" value="วันเริ่มรับสมัคร" />
                        <x-text-input id="registration_start" wire:model="registration_start" type="date" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('registration_start')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="registration_end" value="วันปิดรับสมัคร" />
                        <x-text-input id="registration_end" wire:model="registration_end" type="date" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('registration_end')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="exam_date" value="วันสอบ" />
                        <x-text-input id="exam_date" wire:model="exam_date" type="date" class="block mt-1 w-full" />
                        <x-input-error :messages="$errors->get('exam_date')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="is_active" value="สถานะ" />
                        <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-700">
                            <input id="is_active" type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            เปิดใช้งาน
                        </label>
                        <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6">
                    <a href="{{ route('staff.exam-sessions.index') }}" wire:navigate
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                        กลับหน้ารายการ
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-primary-700 disabled:opacity-50"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">บันทึกข้อมูล</span>
                        <span wire:loading wire:target="save">กำลังบันทึก...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
