<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">แก้ไขพื้นที่ชายแดน</h2>
                <p class="mt-1 text-sm text-gray-500">
                    อัปเดตข้อมูลพื้นที่ชายแดนและคะแนนพิเศษ โดยระบบจะบันทึกประวัติอัตโนมัติเมื่อคะแนนเปลี่ยน
                </p>
            </div>

            <form wire:submit="save" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="code" value="รหัสพื้นที่" />
                        <x-text-input id="code" wire:model="code" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="name" value="ชื่อพื้นที่" />
                        <x-text-input id="name" wire:model="name" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="special_score" value="คะแนนพิเศษ" />
                        <x-text-input id="special_score" wire:model="special_score" class="block mt-1 w-full" type="number" min="0" max="99.99" step="0.01" />
                        <x-input-error :messages="$errors->get('special_score')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="is_active" value="สถานะใช้งาน" />
                        <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-700">
                            <input id="is_active" type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            เปิดใช้งาน
                        </label>
                        <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="description" value="รายละเอียด" />
                        <textarea id="description" wire:model="description" rows="3"
                                  class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                                  placeholder="รายละเอียดเพิ่มเติม (ถ้ามี)"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="reason" value="เหตุผลในการเปลี่ยนแปลงคะแนน (จำเป็นเมื่อมีการเปลี่ยนคะแนน)" />
                        <textarea id="reason" wire:model="reason" rows="2"
                                  class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                                  placeholder="ระบุเหตุผลเมื่อมีการปรับคะแนนพิเศษ"></textarea>
                        <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6">
                    <a href="{{ route('staff.border-areas.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                        ย้อนกลับ
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-primary-700 disabled:opacity-50"
                            wire:loading.attr="disabled">
                        บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">ประวัติการแก้ไขคะแนน</h3>
                <p class="mt-1 text-sm text-gray-500">แสดงรายการล่าสุดของพื้นที่นี้ (สูงสุด 20 รายการ)</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">คะแนนเดิม</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">คะแนนใหม่</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้แก้ไข</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เหตุผล</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($scoreLogs as $log)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $log->changed_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right">
                                    {{ $log->old_score !== null ? number_format((float) $log->old_score, 2) : 'ใหม่' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">
                                    {{ number_format((float) $log->new_score, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $log->changedBy?->full_name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ $log->reason ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                    ยังไม่มีประวัติการแก้ไขคะแนน
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
