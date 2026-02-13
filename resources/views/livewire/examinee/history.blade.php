<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">ประวัติการสอบ</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-base font-semibold text-gray-800">ประวัติการสอบทั้งหมดของผู้เข้าสอบ</h3>
                    <p class="text-sm text-gray-500 mt-1">แสดงข้อมูลตามรอบสอบที่เคยลงทะเบียน</p>
                </div>

                @if ($hasHistory)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ปีที่สอบ</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ระดับ</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">สถานที่สอบ</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">หมายเลขสอบ</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">คะแนนรวม</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach ($historyRows as $row)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-medium">{{ $row['year'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $row['level'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $row['test_location'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">{{ $row['exam_number'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">
                                            {{ number_format($row['total_score'], 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @php
                                                $statusClass = match ($row['result_status']) {
                                                    'ผ่าน' => 'bg-green-100 text-green-800',
                                                    'ไม่ผ่าน' => 'bg-red-100 text-red-700',
                                                    default => 'bg-amber-100 text-amber-700',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                                {{ $row['result_status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V9m-5-4h5m0 0v5m0-5L10 14"/>
                        </svg>
                        <h4 class="mt-3 text-base font-semibold text-gray-700">ยังไม่มีประวัติการสอบ</h4>
                        <p class="mt-1 text-sm text-gray-500">เมื่อมีการลงทะเบียนสอบ ข้อมูลจะแสดงในตารางนี้</p>
                        <a href="{{ route('examinee.register-exam') }}" wire:navigate
                           class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-[#2D6A4F] text-white text-sm font-medium rounded-lg hover:bg-[#1B4332] transition-colors">
                            ลงทะเบียนสอบ
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
