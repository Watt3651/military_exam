<div class="py-12">
    @php /** @var \Illuminate\Pagination\LengthAwarePaginator $quotas */ $quotas = $this->quotas; @endphp
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-lg bg-red-50 border border-red-200 p-4 text-red-700 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-900">
                    {{ $editingId ? 'แก้ไขอัตราที่เปิดสอบ' : 'เพิ่มอัตราที่เปิดสอบ' }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">เลือก รอบสอบ + ตำแหน่ง + จำนวนอัตรา</p>
            </div>

            <form wire:submit="save" class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="exam_session_id" class="block text-sm font-medium text-gray-700">รอบสอบ</label>
                    <select id="exam_session_id" wire:model.live="exam_session_id"
                            class="mt-1 block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="">-- เลือกรอบสอบ --</option>
                        @foreach ($this->examSessions as $session)
                            <option value="{{ $session->id }}">
                                {{ $session->display_name }} ({{ $session->is_active ? 'Active' : 'Inactive' }})
                            </option>
                        @endforeach
                    </select>
                    @error('exam_session_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="position_name" class="block text-sm font-medium text-gray-700">ตำแหน่ง</label>
                    <input id="position_name" type="text" wire:model.live="position_name"
                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                           placeholder="เช่น ผบ.หมู่">
                    @error('position_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="quota_count" class="block text-sm font-medium text-gray-700">จำนวนอัตรา</label>
                    <input id="quota_count" type="number" min="0" wire:model.live="quota_count"
                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                           placeholder="เช่น 50">
                    @error('quota_count') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-3 flex items-center justify-end gap-2 pt-2">
                    @if ($editingId)
                        <button type="button" wire:click="cancelEdit"
                                class="px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            ยกเลิกแก้ไข
                        </button>
                    @endif
                    <button type="submit"
                            class="px-4 py-2 rounded-md bg-primary-600 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'บันทึกการแก้ไข' : 'เพิ่มอัตรา' }}</span>
                        <span wire:loading wire:target="save">กำลังบันทึก...</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-100">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between gap-3 flex-wrap">
                <h3 class="text-lg font-semibold text-gray-900">อัตราปัจจุบัน</h3>
                <input type="text" wire:model.live.debounce.300ms="search"
                       class="w-full sm:w-80 rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 text-sm"
                       placeholder="ค้นหาจากตำแหน่ง">
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รอบสอบ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ตำแหน่ง</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">อัตรา</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">สมัครแล้ว</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">คงเหลือ</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($quotas as $quota)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $quota->examSession?->display_name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $quota->position_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">{{ $quota->quota_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">{{ $quota->registered_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">
                                    {{ max(0, $quota->quota_count - $quota->registered_count) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" wire:click="edit({{ $quota->id }})"
                                                class="px-3 py-1.5 rounded-md bg-amber-100 text-amber-800 hover:bg-amber-200 text-xs font-medium">
                                            แก้ไข
                                        </button>
                                        <button type="button"
                                                wire:click="delete({{ $quota->id }})"
                                                wire:confirm="ยืนยันการลบอัตราตำแหน่งนี้?"
                                                class="px-3 py-1.5 rounded-md bg-red-100 text-red-700 hover:bg-red-200 text-xs font-medium">
                                            ลบ
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                    ไม่พบข้อมูลอัตราที่เปิดสอบ
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-100">
                {{ $quotas->links() }}
            </div>
        </div>
    </div>
</div>
