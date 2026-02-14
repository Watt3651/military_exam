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
            <div class="p-6 border-b border-gray-200 flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">จัดการเหล่า</h2>
                    <p class="mt-1 text-sm text-gray-500">CRUD ข้อมูลเหล่า (รหัส 1 หลัก)</p>
                </div>
                <a href="{{ route('staff.branches.create') }}" wire:navigate
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    เพิ่มเหล่าใหม่
                </a>
            </div>

            <div class="p-6 border-b border-gray-100 bg-gray-50 grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-500 mb-1">ค้นหา</label>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 text-sm"
                        placeholder="ค้นหาจากชื่อเหล่าหรือรหัสเหล่า">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">สถานะ</label>
                    <select wire:model.live="statusFilter"
                        class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 text-sm">
                        <option value="all">ทั้งหมด</option>
                        <option value="active">เปิดใช้งาน</option>
                        <option value="inactive">ปิดใช้งาน</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                รหัสเหล่า</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ชื่อเหล่า</th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                จำนวนผู้ใช้งาน</th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                สถานะ</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($this->branches as $branch)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    {{ $branch->code }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $branch->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">
                                    {{ $branch->examinees_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if ($branch->is_active)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">เปิดใช้งาน</span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">ปิดใช้งาน</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('staff.branches.edit', $branch->id) }}" wire:navigate
                                            class="px-3 py-1.5 rounded-md bg-amber-100 text-amber-800 hover:bg-amber-200 text-xs font-medium">
                                            แก้ไข
                                        </a>
                                        <button type="button" wire:click="confirmDelete({{ $branch->id }})"
                                            class="px-3 py-1.5 rounded-md bg-red-100 text-red-700 hover:bg-red-200 text-xs font-medium">
                                            ลบ
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">ไม่พบข้อมูลเหล่า</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-100">
                {{ $this->branches->links() }}
            </div>
        </div>

        @if ($confirmDeleteId)
            <div class="fixed inset-0 z-40 flex items-center justify-center p-4 bg-black/40">
                <div class="w-full max-w-md rounded-xl bg-white shadow-xl border border-gray-200">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900">ยืนยันการลบเหล่า</h3>
                    </div>
                    <div class="p-5">
                        <p class="text-sm text-gray-700">
                            คุณต้องการลบ
                            <span class="font-semibold text-gray-900">{{ $confirmDeleteName }}</span>
                            ใช่หรือไม่?
                        </p>
                        <p class="mt-2 text-xs text-red-600">ถ้ามีผู้เข้าสอบใช้งานอยู่ ระบบจะไม่อนุญาตให้ลบ</p>
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