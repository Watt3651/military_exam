<div>
    {{-- Page Header --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">ข้อมูลส่วนตัว</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ═══════════════════════════════════════════
                 Flash Messages
                 ═══════════════════════════════════════════ --}}
            @if (session('success'))
                <div class="rounded-lg bg-green-50 border border-green-200 p-4">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @error('general')
                <div class="rounded-lg bg-red-50 border border-red-200 p-4">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-red-700">{{ $message }}</p>
                    </div>
                </div>
            @enderror

            {{-- ═══════════════════════════════════════════
                 Read-only Info Card
                 ═══════════════════════════════════════════ --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                        </svg>
                        ข้อมูลที่ไม่สามารถแก้ไขได้
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- หมายเลขประจำตัว --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">หมายเลขประจำตัว</label>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <span class="text-lg font-mono font-semibold text-gray-800 tracking-wider">{{ $national_id }}</span>
                            </div>
                        </div>

                        {{-- หมายเลขสอบ --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">หมายเลขสอบ</label>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                @if ($examNumber)
                                    <span class="text-lg font-mono font-semibold text-[#2D6A4F] tracking-wider">{{ $examNumber }}</span>
                                @else
                                    <span class="text-sm text-gray-400 italic">ยังไม่ได้รับหมายเลขสอบ</span>
                                @endif
                            </div>
                        </div>

                        {{-- สถานะการลงทะเบียน --}}
                        @if ($hasRegistration)
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">สถานะการลงทะเบียน</label>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    {{ $registrationStatus === 'ยืนยันการสมัครแล้ว' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $registrationStatus === 'รอยืนยันการสมัคร' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $registrationStatus === 'ยกเลิก' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ $registrationStatus }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════
                 Read-only Form (Locked After Confirmation)
                 ═══════════════════════════════════════════ --}}
            @if ($isLockedAfterConfirmation)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-red-50 border-b border-red-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-red-800 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            ข้อมูลถูกล็อค
                        </h3>
                        <p class="text-sm text-red-700 mt-1">การสมัครของคุณได้รับการยืนยันแล้ว — ไม่สามารถแก้ไขข้อมูลใดๆ ได้</p>
                    </div>
                    <div class="p-6 space-y-8">

                        {{-- ─── Section 1: ข้อมูลทั่วไป (Read-only) ─── --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                ข้อมูลทั่วไป
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                {{-- ยศ --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">ยศ</label>
                                    <div class="px-3 py-2 bg-gray-100 rounded-lg border border-gray-200">
                                        <span class="text-sm text-gray-700">{{ $rank }}</span>
                                    </div>
                                </div>

                                {{-- ชื่อ --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">ชื่อ</label>
                                    <div class="px-3 py-2 bg-gray-100 rounded-lg border border-gray-200">
                                        <span class="text-sm text-gray-700">{{ $first_name }}</span>
                                    </div>
                                </div>

                                {{-- นามสกุล --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">นามสกุล</label>
                                    <div class="px-3 py-2 bg-gray-100 rounded-lg border border-gray-200">
                                        <span class="text-sm text-gray-700">{{ $last_name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-200">

                        {{-- ─── Section 2: ข้อมูลผู้เข้าสอบ (Read-only) ─── --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                ข้อมูลผู้เข้าสอบ
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- สังกัดปัจจุบัน --}}
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-500 mb-1">สังกัดปัจจุบัน</label>
                                    <div class="px-3 py-2 bg-gray-100 rounded-lg border border-gray-200">
                                        <span class="text-sm text-gray-700">{{ $currentUnitName ?? '-' }}</span>
                                    </div>
                                </div>

                                {{-- ระดับที่สอบ --}}
                                @if($exam_level)
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-500 mb-3">ระดับที่สอบ</label>
                                    <div class="px-3 py-2 bg-gray-100 rounded-lg border border-gray-200">
                                        <span class="text-sm text-gray-700">
                                            {{ $exam_level === 'sergeant_major' ? 'จ่าเอก (Sergeant Major)' : 'พันจ่าเอก (Master Sergeant)' }}
                                        </span>
                                    </div>
                                </div>
                                @endif

                                {{-- ตำแหน่งที่สอบ --}}
                                @if($positions && count($positions) > 0)
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-500 mb-1">ตำแหน่งที่สอบ</label>
                                    <div class="px-3 py-2 bg-gray-100 rounded-lg border border-gray-200">
                                        <span class="text-sm text-gray-700">{{ implode(', ', $positions) }}</span>
                                    </div>
                                </div>
                                @endif

                                {{-- เหล่า --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">เหล่า</label>
                                    <div class="px-3 py-2 bg-gray-100 rounded-lg border border-gray-200">
                                        <span class="text-sm text-gray-700">{{ $currentBranchName }}</span>
                                    </div>
                                </div>

                                {{-- อายุ --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">อายุ (ปี)</label>
                                    <div class="px-3 py-2 bg-gray-100 rounded-lg border border-gray-200">
                                        <span class="text-sm text-gray-700">{{ $age }}</span>
                                    </div>
                                </div>

                                {{-- ปีที่มีสิทธิ์สอบ --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">ปีที่มีสิทธิ์สอบ (พ.ศ.)</label>
                                    <div class="px-3 py-2 bg-gray-100 rounded-lg border border-gray-200">
                                        <span class="text-sm text-gray-700">{{ $eligible_year }}</span>
                                    </div>
                                </div>

                                {{-- ปีที่ถูกงดบำเหน็จ --}}
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-500 mb-2">ปีที่ถูกงดบำเหน็จ</label>
                                    @if(count($suspended_years) > 0)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($suspended_years as $year)
                                                <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm">{{ $year }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="px-3 py-2 bg-gray-100 rounded-lg border border-gray-200">
                                            <span class="text-sm text-gray-500">ไม่มีปีที่ถูกงดบำเหน็จ</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-200">

                        {{-- ─── Section 3: ข้อมูลการสอบ (Read-only) ─── --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                สถานที่สอบ / พื้นที่ชายแดน
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- พื้นที่ชายแดน --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">พื้นที่ราชการชายแดน</label>
                                    <div class="px-3 py-2 bg-gray-100 rounded-lg border border-gray-200">
                                        <span class="text-sm text-gray-700">{{ $currentBorderAreaName ?? 'ไม่ได้ระบุ' }}</span>
                                    </div>
                                </div>

                                {{-- สถานที่สอบ --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">สถานที่สอบ</label>
                                    <div class="px-3 py-2 bg-gray-100 rounded-lg border border-gray-200">
                                        <span class="text-sm text-gray-700">{{ $currentTestLocationName ?? 'ยังไม่ได้ลงทะเบียน' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-200">

                        {{-- ─── Section 4: คะแนน (Read-only) ─── --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                คะแนน (คำนวณอัตโนมัติ)
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {{-- คะแนนค้างบรรจุ --}}
                                <div class="bg-blue-50 rounded-xl border border-blue-100 p-4 text-center">
                                    <p class="text-xs font-medium text-blue-600 mb-1">คะแนนค้างบรรจุ</p>
                                    <p class="text-2xl font-bold text-blue-800">
                                        {{ number_format($pendingScore, 2) }}
                                    </p>
                                </div>

                                {{-- คะแนนพิเศษ --}}
                                <div class="bg-amber-50 rounded-xl border border-amber-100 p-4 text-center">
                                    <p class="text-xs font-medium text-amber-600 mb-1">คะแนนพิเศษ</p>
                                    <p class="text-2xl font-bold text-amber-800">
                                        {{ number_format($specialScore, 2) }}
                                    </p>
                                </div>

                                {{-- คะแนนรวม --}}
                                <div class="bg-green-50 rounded-xl border border-green-200 p-4 text-center ring-2 ring-green-200">
                                    <p class="text-xs font-medium text-green-600 mb-1">คะแนนรวม</p>
                                    <p class="text-2xl font-bold text-green-800">
                                        {{ number_format($totalScore, 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 border-t border-gray-200 px-6 py-4">
                        <p class="text-center text-gray-500 text-sm">
                            หากต้องการแก้ไขข้อมูล กรุณาติดต่อเจ้าหน้าที่ผู้ดูแลระบบ
                        </p>
                    </div>
                </div>
            @else
                <form wire:submit="save">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

                    {{-- Form Header --}}
                    <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            แก้ไขข้อมูลส่วนตัว
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">กรอกข้อมูลที่ต้องการเปลี่ยนแปลง แล้วกดบันทึก</p>
                    </div>

                    <div class="p-6 space-y-8">

                        {{-- ─── Section 1: ข้อมูลทั่วไป (User) ─── --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                ข้อมูลทั่วไป
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                {{-- ยศ --}}
                                <div>
                                    <label for="rank" class="block text-sm font-medium text-gray-700 mb-1">
                                        ยศ <span class="text-red-500">*</span>
                                    </label>
                                    <select id="rank"
                                            wire:model="rank"
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2D6A4F] focus:ring-[#2D6A4F] text-sm">
                                        <option value="">-- เลือกยศ --</option>
                                        @foreach ($rankOptions as $r)
                                            <option value="{{ $r }}">{{ $r }}</option>
                                        @endforeach
                                    </select>
                                    @error('rank')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- ชื่อ --}}
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        ชื่อ <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="first_name"
                                           wire:model="first_name"
                                           placeholder="ชื่อ"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2D6A4F] focus:ring-[#2D6A4F] text-sm">
                                    @error('first_name')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- นามสกุล --}}
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        นามสกุล <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="last_name"
                                           wire:model="last_name"
                                           placeholder="นามสกุล"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2D6A4F] focus:ring-[#2D6A4F] text-sm">
                                    @error('last_name')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-200">

                        {{-- ─── Section 2: ข้อมูลผู้เข้าสอบ (Examinee) ─── --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                ข้อมูลผู้เข้าสอบ
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- สังกัดปัจจุบัน --}}
                                <div class="md:col-span-2">
                                    <label for="unit_id" class="block text-sm font-medium text-gray-700 mb-1">
                                        สังกัดปัจจุบัน <span class="text-red-500">*</span>
                                    </label>
                                    <select id="unit_id"
                                            wire:model.live="unit_id"
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2D6A4F] focus:ring-[#2D6A4F] text-sm">
                                        <option value="">เลือกสังกัด</option>
                                        @foreach ($units->where('is_active', true) as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                    {{-- Debug: แสดงค่า unit_id ปัจจุบัน --}}
                                    <p class="mt-1 text-xs text-gray-500">Current unit_id: {{ $unit_id }}</p>
                                    @error('unit_id')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Exam Level (only if registration is open) --}}
                                @if($canEditRegistrationFields)
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-3">
                                        ระดับที่สอบ
                                    </label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <label class="relative flex items-center gap-3 p-4 rounded-lg border-2 cursor-pointer transition-all
                                               {{ $exam_level === 'sergeant_major' ? 'border-[#2D6A4F] bg-green-50 ring-1 ring-[#2D6A4F]' : 'border-gray-200 hover:border-gray-300' }}">
                                            <input type="radio" wire:model.live="exam_level" value="sergeant_major"
                                                   class="text-[#2D6A4F] focus:ring-[#2D6A4F]">
                                            <div>
                                                <p class="font-medium text-gray-800">จ่าเอก</p>
                                                <p class="text-xs text-gray-500">Sergeant Major</p>
                                            </div>
                                        </label>
                                        <label class="relative flex items-center gap-3 p-4 rounded-lg border-2 cursor-pointer transition-all
                                               {{ $exam_level === 'master_sergeant' ? 'border-[#2D6A4F] bg-green-50 ring-1 ring-[#2D6A4F]' : 'border-gray-200 hover:border-gray-300' }}">
                                            <input type="radio" wire:model.live="exam_level" value="master_sergeant"
                                                   class="text-[#2D6A4F] focus:ring-[#2D6A4F]">
                                            <div>
                                                <p class="font-medium text-gray-800">พันจ่าเอก</p>
                                                <p class="text-xs text-gray-500">Master Sergeant</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                {{-- ตำแหน่งที่สอบ (only if registration is open) --}}
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        ตำแหน่งที่สอบ
                                        <span class="text-gray-400 text-xs font-normal">(เลือกได้ไม่เกิน 3 ตำแหน่ง)</span>
                                    </label>
                                    
                                    <!-- แสดงจำนวนที่เลือก -->
                                    <div class="mb-2 text-sm text-gray-600">
                                        เลือกแล้ว {{ count($positions) }}/3 ตำแหน่ง
                                    </div>
                                    
                                    @if($exam_level && $positionQuotas->count() > 0)
                                        <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                                            @foreach($positionQuotas as $quota)
                                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ in_array($quota->position_name, $positions) ? 'bg-green-50 border-green-300' : 'border-gray-200' }}">
                                                    <input type="checkbox" 
                                                           wire:model.live="positions" 
                                                           value="{{ $quota->position_name }}"
                                                           class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                                                           {{ count($positions) >= 3 && !in_array($quota->position_name, $positions) ? 'disabled' : '' }}>
                                                    <div class="ml-3 flex-1">
                                                        <p class="font-medium text-gray-800">{{ $quota->position_name }}</p>
                                                        <p class="text-xs text-gray-500">สามารถเลือกได้ทุกคน</p>
                                                    </div>
                                                    @if(in_array($quota->position_name, $positions))
                                                        <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">เลือกแล้ว</span>
                                                    @endif
                                                </label>
                                            @endforeach
                                        </div>
                                        
                                        @if(count($positions) >= 3)
                                            <p class="mt-2 text-xs text-orange-600 bg-orange-50 p-2 rounded">
                                                เลือกตำแหน่งครบ 3 ตำแหน่งแล้ว ไม่สามารถเลือกเพิ่มได้
                                            </p>
                                        @endif
                                    @else
                                        <input type="text" 
                                               placeholder="กรุณาเลือกระดับที่สอบก่อน"
                                               class="w-full rounded-lg border-gray-200 bg-gray-50 text-sm" readonly>
                                    @endif
                                    @error('positions')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                    @error('positions.*')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                @endif

                                {{-- เหล่า --}}
                                <div>
                                    <label for="branch_id" class="block text-sm font-medium text-gray-700 mb-1">
                                        เหล่า <span class="text-red-500">*</span>
                                    </label>
                                    <select id="branch_id"
                                            wire:model="branch_id"
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2D6A4F] focus:ring-[#2D6A4F] text-sm">
                                        <option value="">-- เลือกเหล่า --</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->code }} - {{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('branch_id')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- อายุ --}}
                                <div>
                                    <label for="age" class="block text-sm font-medium text-gray-700 mb-1">
                                        อายุ (ปี) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="age"
                                           wire:model="age"
                                           min="18" max="60"
                                           placeholder="อายุปัจจุบัน"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2D6A4F] focus:ring-[#2D6A4F] text-sm">
                                    @error('age')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- ปีที่มีสิทธิ์สอบ --}}
                                <div>
                                    <label for="eligible_year" class="block text-sm font-medium text-gray-700 mb-1">
                                        ปีที่มีสิทธิ์สอบ (พ.ศ.) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="eligible_year"
                                           wire:model.live.debounce.300ms="eligible_year"
                                           min="2500" max="2600"
                                           placeholder="เช่น {{ date('Y') + 543 }}"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2D6A4F] focus:ring-[#2D6A4F] text-sm">
                                    @error('eligible_year')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- ปีที่ถูกงดบำเหน็จ --}}
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        ปีที่ถูกงดบำเหน็จ <span class="text-gray-400 text-xs font-normal">(เลือกปีที่ถูกงด)</span>
                                    </label>
                                    @if(count($availableSuspendedYears) > 0)
                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
                                            @foreach($availableSuspendedYears as $yearInfo)
                                                <label class="flex items-center p-2 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ in_array($yearInfo['year'], $suspended_years) ? 'bg-red-50 border-red-300' : 'border-gray-200' }}">
                                                    <input type="checkbox" 
                                                           wire:model.live="suspended_years" 
                                                           value="{{ $yearInfo['year'] }}"
                                                           class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                                    <span class="ml-2 text-sm">
                                                        {{ $yearInfo['year'] }}
                                                        <span class="text-xs text-gray-500 block">(หัก {{ $yearInfo['points'] }} คะแนน)</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500">
                                            เลือกปีที่ถูกงดบำเหน็จ ระบบจะหักคะแนนตามเกณฑ์ขั้นบันได
                                        </p>
                                    @else
                                        <p class="text-sm text-gray-400">กรุณาระบุปีที่มีสิทธิ์สอบก่อน</p>
                                    @endif
                                    @error('suspended_years')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                    @error('suspended_years.*')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-200">

                        {{-- ─── Section 3: ข้อมูลการสอบ (Conditional) ─── --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                สถานที่สอบ / พื้นที่ชายแดน
                            </h4>

                            @if (!$canEditRegistrationFields)
                                <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 mb-4">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        <p class="text-xs text-amber-700">
                                            ไม่สามารถแก้ไขข้อมูลการสอบได้ — เนื่องจากการสมัครได้รับการยืนยันแล้ว หรือไม่อยู่ในช่วงเปิดรับสมัคร
                                        </p>
                                    </div>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- พื้นที่ชายแดน --}}
                                <div>
                                    <label for="border_area_id" class="block text-sm font-medium text-gray-700 mb-1">
                                        พื้นที่ราชการชายแดน
                                        <span class="text-gray-400 text-xs font-normal">(ถ้ามี)</span>
                                    </label>
                                    @if ($canEditRegistrationFields)
                                        <select id="border_area_id"
                                                wire:model.live="border_area_id"
                                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2D6A4F] focus:ring-[#2D6A4F] text-sm">
                                            <option value="">-- ไม่ได้ประจำพื้นที่ชายแดน --</option>
                                            @foreach ($borderAreas as $area)
                                                <option value="{{ $area->id }}">{{ $area->code }} - {{ $area->name }} ({{ number_format($area->special_score, 2) }})</option>
                                            @endforeach
                                        </select>
                                        @error('border_area_id')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    @else
                                        <div class="flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-lg border border-gray-200">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            <span class="text-sm text-gray-600">{{ $currentBorderAreaName ?? 'ไม่ได้ระบุ' }}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- สถานที่สอบ --}}
                                <div>
                                    <label for="test_location_id" class="block text-sm font-medium text-gray-700 mb-1">
                                        สถานที่สอบ
                                    </label>
                                    @if ($canEditRegistrationFields && $hasRegistration)
                                        <select id="test_location_id"
                                                wire:model="test_location_id"
                                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2D6A4F] focus:ring-[#2D6A4F] text-sm">
                                            <option value="">-- เลือกสถานที่สอบ --</option>
                                            @foreach ($testLocations as $location)
                                                <option value="{{ $location->id }}">{{ $location->code }} - {{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('test_location_id')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    @else
                                        <div class="flex items-center gap-2 px-3 py-2.5 bg-gray-100 rounded-lg border border-gray-200">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            <span class="text-sm text-gray-600">{{ $currentTestLocationName ?? 'ยังไม่ได้ลงทะเบียน' }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-200">

                        {{-- ─── Section 4: คะแนน (Real-time) ─── --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                คะแนน (คำนวณอัตโนมัติ)
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {{-- คะแนนค้างบรรจุ --}}
                                <div class="bg-blue-50 rounded-xl border border-blue-100 p-4 text-center">
                                    <p class="text-xs font-medium text-blue-600 mb-1">คะแนนค้างบรรจุ</p>
                                    <p class="text-2xl font-bold text-blue-800" wire:loading.class="opacity-50">
                                        {{ number_format($pendingScore, 2) }}
                                    </p>
                                </div>

                                {{-- คะแนนพิเศษ --}}
                                <div class="bg-amber-50 rounded-xl border border-amber-100 p-4 text-center">
                                    <p class="text-xs font-medium text-amber-600 mb-1">คะแนนพิเศษ</p>
                                    <p class="text-2xl font-bold text-amber-800" wire:loading.class="opacity-50">
                                        {{ number_format($specialScore, 2) }}
                                    </p>
                                </div>

                                {{-- คะแนนรวม --}}
                                <div class="bg-green-50 rounded-xl border border-green-200 p-4 text-center ring-2 ring-green-200">
                                    <p class="text-xs font-medium text-green-600 mb-1">คะแนนรวม</p>
                                    <p class="text-2xl font-bold text-green-800" wire:loading.class="opacity-50">
                                        {{ number_format($totalScore, 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Form Footer --}}
                    <div class="bg-gray-50 border-t border-gray-200 px-6 py-4 flex items-center justify-between flex-wrap gap-3">
                        <a href="{{ route('examinee.dashboard') }}" wire:navigate
                           class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            กลับ Dashboard
                        </a>

                        <button type="button"
                                wire:click="save"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-75 cursor-not-allowed"
                                class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-[#2D6A4F] rounded-lg hover:bg-[#1B4332] focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] focus:ring-offset-2 transition-colors shadow-sm">
                            <span wire:loading.remove wire:target="save">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                            <span wire:loading wire:target="save">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                            </span>
                            <span wire:loading.remove wire:target="save">บันทึกข้อมูล</span>
                            <span wire:loading wire:target="save">กำลังบันทึก...</span>
                        </button>
                    </div>
                </div>
            </form>
            @endif

        </div>
    </div>
</div>
