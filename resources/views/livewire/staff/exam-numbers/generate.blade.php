<div class="py-12">
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
                <h2 class="text-xl font-semibold text-gray-900">สร้างหมายเลขสอบ</h2>
                <p class="mt-1 text-sm text-gray-500">
                    ระบบจะสร้างเลขรูปแบบ XYZNN ตามกลุ่มสถานที่สอบ + เหล่า และเรียงชื่อผู้สมัครก่อนยืนยันเลข
                </p>
            </div>

            <form wire:submit="generate" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label for="exam_session_id" class="block text-sm font-medium text-gray-700">เลือกรอบสอบ</label>
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

                    <div class="md:col-span-1">
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3">
                            <p class="text-xs text-blue-700">ผู้สมัครที่รอออกหมายเลข</p>
                            <p class="text-2xl font-bold text-blue-900">{{ $this->pendingCount }}</p>
                        </div>
                    </div>
                </div>

                @if ($lastGeneratedCount > 0)
                    <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                        <p class="text-sm text-green-800">
                            ผลลัพธ์ล่าสุด: สร้างหมายเลขสอบสำเร็จ
                            <span class="font-semibold">{{ $lastGeneratedCount }}</span> รายการ
                        </p>
                    </div>
                @endif

                <div wire:loading wire:target="generate" class="space-y-2">
                    <div class="h-2.5 rounded-full bg-gray-200 overflow-hidden">
                        <div class="h-full bg-primary-600 animate-pulse" style="width: 100%;"></div>
                    </div>
                    <p class="text-xs text-gray-500">กำลังสร้างหมายเลขสอบ กรุณารอสักครู่...</p>
                </div>

                <div class="flex items-center justify-end">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                        wire:loading.attr="disabled" @disabled($this->pendingCount === 0)>
                        <span wire:loading.remove wire:target="generate">สร้างหมายเลขสอบ</span>
                        <span wire:loading wire:target="generate">กำลังดำเนินการ...</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-100">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Preview ตัวอย่างหมายเลข</h3>
                    <p class="mt-1 text-sm text-gray-500">แสดงตัวอย่าง 10 รายการแรก (ยังไม่บันทึกจริง)</p>
                </div>
                <span class="text-xs text-gray-500">รูปแบบ: XYZNN</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ผู้สมัคร</th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Location (X)</th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Branch (Y)</th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ตัวอย่างหมายเลข (XYZNN)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($this->previewItems as $item)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item['name'] }}</td>
                                <td class="px-6 py-4 text-center text-sm text-gray-700">{{ $item['location_code'] }}</td>
                                <td class="px-6 py-4 text-center text-sm text-gray-700">{{ $item['branch_code'] }}</td>
                                <td class="px-6 py-4 text-center text-sm font-semibold text-primary-700">
                                    {{ $item['exam_number'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
                                    ไม่มีข้อมูล preview (อาจยังไม่มีผู้สมัครสถานะ pending ในรอบสอบที่เลือก)
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>