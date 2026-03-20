<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">จัดการสังกัด</h2>
                    <p class="mt-1 text-sm text-gray-500">แสดงรายการสังกัดทหารทั้งหมด พร้อมสถานะการใช้งาน</p>
                </div>
                <a href="{{ route('staff.units.create') }}" wire:navigate
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-green-100">
                    สร้างสังกัดใหม่
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                รหัสสังกัด</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ชื่อสังกัด</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                สถานะ</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($units as $unit)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    {{ $unit->code }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ $unit->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if ($unit->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            เปิดใช้งาน
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            ปิดใช้งาน
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                    <a href="{{ route('staff.units.edit', $unit) }}" wire:navigate
                                       class="inline-flex items-center px-3 py-1.5 rounded-md bg-blue-100 text-blue-800 hover:bg-blue-200 text-xs font-medium mr-2">
                                        แก้ไข
                                    </a>
                                    <button wire:click="deleteUnit({{ $unit->id }})"
                                            wire:confirm="คุณต้องการลบสังกัดนี้ใช่หรือไม่?"
                                            class="inline-flex items-center px-3 py-1.5 rounded-md bg-red-100 text-red-800 hover:bg-red-200 text-xs font-medium">
                                        ลบ
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
                                    ยังไม่มีข้อมูลสังกัด
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    @this.on('success-message', (message) => {
        alert(message);
        window.location.reload();
    });
    
    @this.on('error-message', (message) => {
        alert(message);
    });
</script>
