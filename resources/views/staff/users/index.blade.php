<x-layouts.staff>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                จัดการผู้ใช้งาน
            </h2>
            <a href="{{ route('staff.users.create') }}"
                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                สร้างผู้ใช้งาน
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6 flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">รายการผู้ใช้งาน</h3>
                            <p class="mt-1 text-sm text-gray-500">จัดการผู้ใช้งาน และเข้าถึงเครื่องมือช่วยรีเซ็ตรหัสผ่านได้จากส่วนนี้</p>
                        </div>
                        <a href="{{ route('staff.users.create') }}"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            สร้างผู้ใช้งาน
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ยศ ชื่อ ชื่อสกุล
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ตำแหน่ง
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        สิทธิ์
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        การกระทำ
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $user->full_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $user->rank }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <x-role-badge :role="$user->role" />
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('staff.users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">แก้ไข</a>
                                            <form method="POST" action="{{ route('staff.users.destroy', $user) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('คุณต้องการลบผู้ใช้นี้หรือไม่?')">ลบ</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            ไม่พบข้อมูลผู้ใช้งาน
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($users->hasPages())
                        <div class="mt-4">
                            {{ $users->links() }}
                        </div>
                    @endif

                    <div class="mt-8 border-t border-gray-200 pt-6">
                        <h4 class="text-base font-semibold text-gray-900">เครื่องมือรีเซ็ตรหัสผ่าน</h4>
                        <p class="mt-1 text-sm text-gray-500">เข้าถึงหน้ารีเซ็ตรหัสผ่านและตรวจสอบประวัติการดำเนินการ</p>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <a href="{{ route('staff.password-support.index') }}"
                                class="group rounded-xl border border-blue-200 bg-blue-50 p-5 hover:border-blue-300 hover:bg-blue-100/70 transition">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 7a2 2 0 11-4 0 2 2 0 014 0zM7 14a2 2 0 012-2h7l2 2-2 2h-2v2h-2v-2H9a2 2 0 01-2-2z" />
                                            </svg>
                                        </span>
                                        <div>
                                            <p class="text-sm font-semibold text-blue-900">ช่วยรีเซ็ตรหัสผ่าน</p>
                                            <p class="mt-1 text-xs text-blue-700">ค้นหาและรีเซ็ตรหัสผ่านให้ผู้ใช้ได้ทันที</p>
                                        </div>
                                    </div>
                                    <svg class="h-5 w-5 text-blue-500 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </a>

                            <a href="{{ route('staff.password-support.history') }}"
                                class="group rounded-xl border border-amber-200 bg-amber-50 p-5 hover:border-amber-300 hover:bg-amber-100/70 transition">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </span>
                                        <div>
                                            <p class="text-sm font-semibold text-amber-900">ประวัติรีเซ็ตรหัสผ่าน</p>
                                            <p class="mt-1 text-xs text-amber-700">ตรวจสอบรายการรีเซ็ตรหัสผ่านย้อนหลัง</p>
                                        </div>
                                    </div>
                                    <svg class="h-5 w-5 text-amber-500 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.staff>