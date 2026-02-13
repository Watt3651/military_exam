<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">ประวัติการเปลี่ยนคะแนนพื้นที่ชายแดน</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        ข้อมูลจากตาราง border_area_score_history (เรียงจากล่าสุด)
                    </p>
                </div>
                <a href="{{ route('staff.border-areas.index') }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    กลับหน้ารายการ
                </a>
            </div>

            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <div class="max-w-sm">
                    <label for="borderAreaFilter" class="block text-xs font-medium text-gray-500 mb-1">กรองตามพื้นที่</label>
                    <select id="borderAreaFilter" wire:model.live="borderAreaFilter"
                            class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 text-sm">
                        <option value="">ทั้งหมด</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}">{{ $area->code }} - {{ $area->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่เปลี่ยน</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อพื้นที่</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">คะแนนเดิม → คะแนนใหม่</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้เปลี่ยน</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เหตุผล</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ \Illuminate\Support\Carbon::parse($row->changed_at)->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                    {{ $row->border_area_code ? "{$row->border_area_code} - {$row->border_area_name}" : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">
                                    {{ $row->old_score !== null ? number_format((float) $row->old_score, 2) : 'ใหม่' }}
                                    <span class="text-gray-400">→</span>
                                    {{ number_format((float) $row->new_score, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ trim(($row->changed_by_rank ?? '') . ' ' . ($row->changed_by_first_name ?? '') . ' ' . ($row->changed_by_last_name ?? '')) ?: '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ $row->reason ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                    ยังไม่มีประวัติการเปลี่ยนแปลงคะแนน
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
