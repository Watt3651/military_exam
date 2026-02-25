<div>
    {{-- Page Header --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">หน้าหลัก</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ═══════════════════════════════════════════
                 Flash Messages
                 ═══════════════════════════════════════════ --}}
            @if (session('error'))
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            @endif
            @if (session('success'))
                <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            {{-- ═══════════════════════════════════════════
                 Welcome Banner
                 ═══════════════════════════════════════════ --}}
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-8 sm:px-8 sm:py-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-blue-500 mb-1">
                                ยินดีต้อนรับ, {{ auth()->user()->full_name }}
                            </h3>
                            <p class="text-primary-100 text-sm">ระบบสอบเลื่อนฐานะทหาร</p>
                        </div>
                        <div class="hidden sm:block">
                            <div class="w-16 h-16 bg-black rounded-full flex items-center justify-center">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Registration period notification --}}
                    @if ($isRegistrationOpen && $activeSession)
                        <div class="mt-4 bg-white/10 rounded-lg px-4 py-3 border border-white/20">
                            <div class="flex items-center text-blue-500">
                                <svg class="w-5 h-5 mr-2 text-secondary-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm">
                                    กำลังเปิดรับสมัคร: <strong>{{ $activeSession->display_name }}</strong>
                                    (ถึง {{ $activeSession->registration_end->format('d/m/Y') }})
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═══════════════════════════════════════════
                 No Examinee Profile Warning
                 ═══════════════════════════════════════════ --}}
            @if (!$hasExamineeProfile)
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-yellow-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <h4 class="text-lg font-semibold text-yellow-800">ยังไม่มีข้อมูลผู้เข้าสอบ</h4>
                            <p class="mt-1 text-sm text-yellow-700">
                                กรุณากรอกข้อมูลส่วนตัวให้ครบถ้วนก่อนจึงจะสามารถลงทะเบียนสอบได้
                            </p>
                            <a href="{{ route('examinee.profile') }}"
                               class="mt-3 inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                กรอกข้อมูลส่วนตัว
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═══════════════════════════════════════════
                 Section 1: Registration Status Widget
                 ═══════════════════════════════════════════ --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        สถานะการสมัครสอบ
                    </h4>
                </div>

                @if ($hasRegistration)
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            {{-- Status --}}
                            <div class="text-center p-4 bg-{{ $registrationStatusColor }}-50 rounded-xl border border-{{ $registrationStatusColor }}-200">
                                <p class="text-xs font-medium text-gray-500 mb-1">สถานะ</p>
                                <span class="inline-flex px-3 py-1 text-sm font-bold rounded-full bg-{{ $registrationStatusColor }}-100 text-{{ $registrationStatusColor }}-800">
                                    {{ $registrationStatus }}
                                </span>
                            </div>

                            {{-- Exam Number --}}
                            <div class="text-center p-4 bg-gray-50 rounded-xl border border-gray-200">
                                <p class="text-xs font-medium text-gray-500 mb-1">หมายเลขสอบ</p>
                                @if ($examNumber)
                                    <p class="text-2xl font-mono font-bold text-primary-700">{{ $examNumber }}</p>
                                @else
                                    <p class="text-sm text-gray-400">รอออกหมายเลข</p>
                                @endif
                            </div>

                            {{-- Test Location --}}
                            <div class="text-center p-4 bg-gray-50 rounded-xl border border-gray-200">
                                <p class="text-xs font-medium text-gray-500 mb-1">สถานที่สอบ</p>
                                <p class="text-lg font-semibold text-gray-800">{{ $testLocationName ?? '-' }}</p>
                            </div>

                            {{-- Exam Date --}}
                            <div class="text-center p-4 bg-gray-50 rounded-xl border border-gray-200">
                                <p class="text-xs font-medium text-gray-500 mb-1">วันที่สอบ</p>
                                <p class="text-lg font-semibold text-gray-800">{{ $examDate ?? '-' }}</p>
                            </div>
                        </div>

                        {{-- Exam Session Name --}}
                        @if ($examSessionName)
                            <div class="mt-4 text-center text-sm text-gray-500">
                                {{ $examSessionName }}
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-8 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <p class="text-gray-500 mb-1">ยังไม่มีการลงทะเบียนสอบ</p>
                        @if ($isRegistrationOpen)
                            <p class="text-sm text-primary-600">ขณะนี้เปิดรับสมัครอยู่ กดปุ่มด้านล่างเพื่อลงทะเบียน</p>
                        @else
                            <p class="text-sm text-gray-400">รอประกาศเปิดรับสมัครรอบถัดไป</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ═══════════════════════════════════════════
                 Section 2: Score Summary
                 ═══════════════════════════════════════════ --}}
            @if ($hasExamineeProfile)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                            คะแนนรวม
                        </h4>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            {{-- Pending Score --}}
                            <div class="text-center p-5 bg-blue-50 rounded-xl border border-blue-200">
                                <p class="text-xs font-medium text-blue-600 uppercase tracking-wider mb-2">คะแนนค้างบรรจุ</p>
                                <p class="text-4xl font-bold text-blue-700">{{ number_format($pendingScore, 2) }}</p>
                                <p class="text-xs text-blue-500 mt-1">
                                    (ปีปัจจุบัน - ปีมีสิทธิ์สอบ) - ปีงดบำเหน็จ
                                </p>
                            </div>

                            {{-- Special Score --}}
                            <div class="text-center p-5 bg-amber-50 rounded-xl border border-amber-200">
                                <p class="text-xs font-medium text-amber-600 uppercase tracking-wider mb-2">คะแนนพิเศษ (ชายแดน)</p>
                                <p class="text-4xl font-bold text-amber-700">{{ number_format($specialScore, 2) }}</p>
                                @if ($borderAreaName)
                                    <p class="text-xs text-amber-500 mt-1">{{ $borderAreaName }}</p>
                                @else
                                    <p class="text-xs text-gray-400 mt-1">ไม่ได้อยู่ในพื้นที่ชายแดน</p>
                                @endif
                            </div>

                            {{-- Total Score --}}
                            <div class="text-center p-5 bg-primary-50 rounded-xl border-2 border-primary-300">
                                <p class="text-xs font-medium text-primary-600 uppercase tracking-wider mb-2">คะแนนรวมทั้งหมด</p>
                                <p class="text-4xl font-bold text-primary-700">{{ number_format($totalScore, 2) }}</p>
                                <p class="text-xs text-primary-500 mt-1">pending + special</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═══════════════════════════════════════════
                 Section 3: Personal Info Summary
                 ═══════════════════════════════════════════ --}}
            @if ($hasExamineeProfile)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            ข้อมูลผู้เข้าสอบ
                        </h4>
                    </div>

                    <div class="p-6">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                            <div>
                                <dt class="text-xs font-medium text-gray-500">หมายเลขประจำตัว</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ auth()->user()->national_id }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">ยศ-ชื่อ-นามสกุล</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ auth()->user()->full_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">ตำแหน่ง</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $examinee->position ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">เหล่า</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $branchName ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">อายุ</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $examinee->age ?? '-' }} ปี</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">ปีที่มีสิทธิ์สอบ (พ.ศ.)</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $examinee->eligible_year ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">ปีที่ถูกงดบำเหน็จ</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $examinee->suspended_years ?? 0 }} ปี</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">พื้นที่ชายแดน</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $borderAreaName ?? 'ไม่มี' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @endif

            {{-- ═══════════════════════════════════════════
                 Section 4: Actions
                 ═══════════════════════════════════════════ --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        เมนูลัด
                    </h4>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                        {{-- Register Exam --}}
                        <a href="{{ route('examinee.register-exam') }}"
                           class="group flex flex-col items-center p-5 rounded-xl border-2 transition-all duration-200
                                  {{ $isRegistrationOpen
                                      ? 'border-primary-200 bg-primary-50 hover:border-primary-400 hover:shadow-md'
                                      : 'border-gray-200 bg-gray-50 opacity-60 pointer-events-none' }}">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center mb-3
                                        {{ $isRegistrationOpen ? 'bg-primary-100 text-primary-600' : 'bg-gray-200 text-gray-400' }}">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold {{ $isRegistrationOpen ? 'text-primary-700' : 'text-gray-400' }}">
                                ลงทะเบียนสอบ
                            </span>
                            <span class="text-xs mt-1 {{ $isRegistrationOpen ? 'text-primary-500' : 'text-gray-400' }}">
                                {{ $isRegistrationOpen ? 'เปิดรับสมัคร' : 'ยังไม่เปิดรับสมัคร' }}
                            </span>
                        </a>

                        {{-- Download Exam Card --}}
                        <button type="button"
                                @if (!$examNumber) disabled @endif
                                class="group flex flex-col items-center p-5 rounded-xl border-2 transition-all duration-200
                                       {{ $examNumber
                                           ? 'border-blue-200 bg-blue-50 hover:border-blue-400 hover:shadow-md cursor-pointer'
                                           : 'border-gray-200 bg-gray-50 opacity-60 cursor-not-allowed' }}">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center mb-3
                                        {{ $examNumber ? 'bg-blue-100 text-blue-600' : 'bg-gray-200 text-gray-400' }}">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold {{ $examNumber ? 'text-blue-700' : 'text-gray-400' }}">
                                บัตรประจำตัวสอบ
                            </span>
                            <span class="text-xs mt-1 {{ $examNumber ? 'text-blue-500' : 'text-gray-400' }}">
                                {{ $examNumber ? 'ดาวน์โหลด PDF' : 'รอออกหมายเลขสอบ' }}
                            </span>
                        </button>

                        {{-- Exam History --}}
                        <a href="{{ route('examinee.history') }}"
                           class="group flex flex-col items-center p-5 rounded-xl border-2 border-purple-200 bg-purple-50 hover:border-purple-400 hover:shadow-md transition-all duration-200">
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mb-3 text-purple-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-purple-700">ประวัติการสอบ</span>
                            <span class="text-xs mt-1 text-purple-500">ดูผลสอบทั้งหมด</span>
                        </a>

                        {{-- Edit Profile --}}
                        <a href="{{ route('examinee.profile') }}"
                           class="group flex flex-col items-center p-5 rounded-xl border-2 border-amber-200 bg-amber-50 hover:border-amber-400 hover:shadow-md transition-all duration-200">
                            <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center mb-3 text-amber-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-amber-700">แก้ไขข้อมูลส่วนตัว</span>
                            <span class="text-xs mt-1 text-amber-500">ยศ, ชื่อ, เหล่า, ที่อยู่</span>
                        </a>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
