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
            <div class="p-6 border-b border-gray-200 flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">จัดการพื้นที่ชายแดน</h2>
                    <p class="mt-1 text-sm text-gray-500">รายการพื้นที่ชายแดนทั้งหมด พร้อมคะแนนพิเศษและสถานะการใช้งาน
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('staff.border-areas.history') }}" wire:navigate
                        class="inline-flex items-center px-4 py-2 bg-secondary-100 border border-secondary-200 rounded-md text-xs font-semibold text-yellow-800 uppercase tracking-widest hover:bg-secondary-200">
                        ประวัติการเปลี่ยนแปลง
                    </a>
                    <a href="{{ route('staff.border-areas.create') }}" wire:navigate
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                        เพิ่มพื้นที่ใหม่
                    </a>
                </div>
            </div>

            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-xs font-medium text-gray-500 mb-1">ค้นหา</label>
                        <input id="search" type="text" wire:model.live.debounce.300ms="search"
                            placeholder="ค้นหาจากรหัสพื้นที่หรือชื่อพื้นที่"
                            class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 text-sm">
                    </div>
                    <div>
                        <label for="statusFilter" class="block text-xs font-medium text-gray-500 mb-1">สถานะ</label>
                        <select id="statusFilter" wire:model.live="statusFilter"
                            class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 text-sm">
                            <option value="all">ทั้งหมด</option>
                            <option value="active">เปิดใช้งาน</option>
                            <option value="inactive">ปิดใช้งาน</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                รหัสพื้นที่</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ชื่อพื้นที่</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                คะแนนพิเศษ</th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                สถานะ (เปิด/ปิด)</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($areas as $area)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    {{ $area->code }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <p class="font-medium">{{ $area->name }}</p>
                                    @if ($area->description)
                                        <p class="text-xs text-gray-500 mt-1">{{ $area->description }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">
                                    {{ number_format((float) $area->special_score, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if ($area->is_active)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            เปิดใช้งาน
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                            ปิดใช้งาน
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('staff.border-areas.edit', $area->id) }}" wire:navigate
                                            class="px-3 py-1.5 rounded-md bg-amber-100 text-amber-800 hover:bg-amber-200 text-xs font-medium">
                                            แก้ไข
                                        </a>
                                        <button wire:click="confirmDelete({{ $area->id }})" type="button"
                                            class="px-3 py-1.5 rounded-md bg-red-100 text-red-700 hover:bg-red-200 text-xs font-medium">
                                            ลบ
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                    ยังไม่มีข้อมูลพื้นที่ชายแดน
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($confirmDeleteId)
            <div class="fixed inset-0 z-40 flex items-center justify-center p-4 bg-black/40">
                <div class="w-full max-w-md rounded-xl bg-white shadow-xl border border-gray-200">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900">ยืนยันการลบพื้นที่ชายแดน</h3>
                    </div>
                    <div class="p-5">
                        <p class="text-sm text-gray-700">
                            คุณต้องการลบพื้นที่
                            <span class="font-semibold text-gray-900">{{ $confirmDeleteName }}</span>
                            ใช่หรือไม่?
                        </p>
                        <p class="mt-2 text-xs text-red-600">
                            หมายเหตุ: หากมีผู้สมัครใช้งานพื้นที่นี้อยู่ ระบบจะไม่อนุญาตให้ลบ
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