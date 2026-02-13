<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4 text-red-700 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">จัดการผู้เข้าสอบ</h2>
                <p class="mt-1 text-sm text-gray-500">ค้นหา กรอง และแก้ไขข้อมูลผู้เข้าสอบทั้งหมด</p>
            </div>

            <div class="p-6 border-b border-gray-100 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ชื่อ</label>
                        <input type="text" wire:model.live.debounce.300ms="searchName"
                               class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 text-sm"
                               placeholder="ชื่อ/นามสกุล">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">เหล่า</label>
                        <select wire:model.live="branchFilter"
                                class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 text-sm">
                            <option value="">ทั้งหมด</option>
                            @foreach ($this->branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->code }} - {{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">สถานที่สอบ</label>
                        <select wire:model.live="testLocationFilter"
                                class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 text-sm">
                            <option value="">ทั้งหมด</option>
                            @foreach ($this->testLocations as $location)
                                <option value="{{ $location->id }}">{{ $location->code }} - {{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">หมายเลขสอบ</label>
                        <input type="text" wire:model.live.debounce.300ms="examNumberFilter"
                               class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 text-sm"
                               placeholder="เช่น 11045">
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้เข้าสอบ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เหล่า</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานที่สอบ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หมายเลขสอบ</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">คะแนนรวม</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($this->examinees as $examinee)
                            @php
                                $latestReg = $examinee->examRegistrations->first();
                            @endphp
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900">{{ $examinee->user?->full_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $examinee->user?->national_id }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $examinee->branch?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $latestReg?->testLocation?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 font-mono">{{ $latestReg?->exam_number ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900">{{ number_format($examinee->total_score, 2) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('staff.examinees.edit', $examinee->id) }}" wire:navigate
                                           class="px-3 py-1.5 rounded-md bg-amber-100 text-amber-800 hover:bg-amber-200 text-xs font-medium">
                                            แก้ไข
                                        </a>
                                        <button type="button" wire:click="confirmDelete({{ $examinee->id }})"
                                                class="px-3 py-1.5 rounded-md bg-red-100 text-red-700 hover:bg-red-200 text-xs font-medium">
                                            ลบ
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                    ไม่พบข้อมูลผู้เข้าสอบ
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-100">
                {{ $this->examinees->links() }}
            </div>
        </div>

        @if ($confirmDeleteId)
            <div class="fixed inset-0 z-40 flex items-center justify-center p-4 bg-black/40">
                <div class="w-full max-w-md rounded-xl bg-white shadow-xl border border-gray-200">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900">ยืนยันการลบผู้เข้าสอบ</h3>
                    </div>
                    <div class="p-5">
                        <p class="text-sm text-gray-700">
                            คุณต้องการลบผู้เข้าสอบ
                            <span class="font-semibold text-gray-900">{{ $confirmDeleteName }}</span>
                            ใช่หรือไม่?
                        </p>
                    </div>
                    <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-end gap-2">
                        <button type="button" wire:click="cancelDelete"
                                class="px-4 py-2 rounded-md border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                            ยกเลิก
                        </button>
                        <button type="button" wire:click="delete"
                                class="px-4 py-2 rounded-md bg-red-600 text-xs font-semibold text-white hover:bg-red-700">
                            ยืนยันลบ
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
