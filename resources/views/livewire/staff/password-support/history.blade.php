<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">ประวัติการรีเซ็ตรหัสผ่าน</h2>
                    <p class="mt-1 text-sm text-gray-500">บันทึกการรีเซ็ตรหัสผ่านจากเจ้าหน้าที่และเจ้าหน้าที่ช่วยรีเซ็ตรหัสผ่าน</p>
                </div>
                <a href="{{ route('staff.password-support.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    กลับหน้าช่วยรีเซ็ตรหัสผ่าน
                </a>
            </div>

            <div class="p-6 space-y-6">
                <div>
                    <x-input-label for="search" value="ค้นหาจากผู้รีเซ็ต หรือผู้ที่ถูกรีเซ็ต" />
                    <x-text-input id="search" wire:model.live.debounce.300ms="search" type="text" class="block mt-1 w-full"
                        placeholder="เช่น 1000000000001 หรือ สมชาย" />
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เวลา</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้รีเซ็ต</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้ถูกรีเซ็ต</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เหตุผล</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ $log->created_at?->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        @if ($log->causer)
                                            <div>{{ $log->causer->full_name }}</div>
                                            <div class="text-xs text-gray-500">
                                                {{ $log->causer->national_id }}
                                                <x-role-badge :role="$log->causer->role" class="ml-1" />
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        @if ($log->subject)
                                            <div>{{ $log->subject->full_name }}</div>
                                            <div class="text-xs text-gray-500">
                                                {{ $log->subject->national_id }}
                                                <x-role-badge :role="$log->subject->role" class="ml-1" />
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ $log->properties['reason'] ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">ยังไม่มีประวัติการรีเซ็ตรหัสผ่าน</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
