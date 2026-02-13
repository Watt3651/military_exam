<div>
    {{-- Page Header --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">ลงทะเบียนสอบ</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ═══════════════════════════════════════════
                 Flash Messages
                 ═══════════════════════════════════════════ --}}
            @if (session('info'))
                <div class="rounded-lg bg-blue-50 border border-blue-200 p-4">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-blue-700">{{ session('info') }}</p>
                    </div>
                </div>
            @endif

            {{-- ═══════════════════════════════════════════
                 No Active Session
                 ═══════════════════════════════════════════ --}}
            @if (!$activeSession)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-10 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">ไม่อยู่ในช่วงเปิดรับสมัคร</h3>
                    <p class="text-gray-500 mb-6">ขณะนี้ไม่มีรอบสอบที่เปิดรับสมัคร กรุณารอประกาศจากหน่วยงาน</p>
                    <a href="{{ route('examinee.dashboard') }}" wire:navigate
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#2D6A4F] text-white text-sm font-medium rounded-lg hover:bg-[#1B4332] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        กลับหน้าหลัก
                    </a>
                </div>

            {{-- ═══════════════════════════════════════════
                 Already Registered
                 ═══════════════════════════════════════════ --}}
            @elseif ($alreadyRegistered)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-10 text-center">
                    <svg class="mx-auto h-16 w-16 text-green-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">คุณลงทะเบียนสอบรอบนี้แล้ว</h3>
                    <p class="text-gray-500 mb-6">ไม่สามารถลงทะเบียนซ้ำได้ กรุณาตรวจสอบสถานะที่หน้า Dashboard</p>
                    <a href="{{ route('examinee.dashboard') }}" wire:navigate
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#2D6A4F] text-white text-sm font-medium rounded-lg hover:bg-[#1B4332] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        กลับหน้าหลัก
                    </a>
                </div>

            {{-- ═══════════════════════════════════════════
                 Registration Success
                 ═══════════════════════════════════════════ --}}
            @elseif ($registrationSuccess)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] px-6 py-10 text-center">
                        <svg class="mx-auto h-20 w-20 text-white mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-2xl font-bold text-white mb-2">ลงทะเบียนสอบสำเร็จ!</h3>
                        <p class="text-green-100">{{ $registrationMessage }}</p>
                    </div>

                    <div class="p-6 space-y-4">
                        {{-- Score Summary --}}
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-blue-50 rounded-lg p-4 text-center">
                                <p class="text-xs text-blue-600 font-medium mb-1">คะแนนค้างบรรจุ</p>
                                <p class="text-2xl font-bold text-blue-700">{{ number_format($calculatedPendingScore, 2) }}</p>
                            </div>
                            <div class="bg-amber-50 rounded-lg p-4 text-center">
                                <p class="text-xs text-amber-600 font-medium mb-1">คะแนนพิเศษ</p>
                                <p class="text-2xl font-bold text-amber-700">{{ number_format($calculatedSpecialScore, 2) }}</p>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4 text-center">
                                <p class="text-xs text-green-600 font-medium mb-1">คะแนนรวม</p>
                                <p class="text-2xl font-bold text-green-700">{{ number_format($calculatedTotalScore, 2) }}</p>
                            </div>
                        </div>

                        <div class="flex justify-center pt-4">
                            <button wire:click="goToDashboard"
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-[#2D6A4F] text-white font-medium rounded-lg hover:bg-[#1B4332] transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                กลับหน้า Dashboard
                            </button>
                        </div>
                    </div>
                </div>

            {{-- ═══════════════════════════════════════════
                 Registration Form
                 ═══════════════════════════════════════════ --}}
            @else
                {{-- Session Info Banner --}}
                <div class="bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h3 class="text-lg font-bold">{{ $activeSession->display_name }}</h3>
                            <p class="text-green-100 text-sm mt-1">
                                ช่วงรับสมัคร:
                                {{ $activeSession->registration_start->format('d/m/Y') }}
                                -
                                {{ $activeSession->registration_end->format('d/m/Y') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-green-100 text-xs">วันสอบ</p>
                            <p class="text-xl font-bold">{{ $activeSession->exam_date->format('d/m/Y') }}</p>
                        </div>
                    </div>
                </div>

                {{-- General Error --}}
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

                {{-- Main Form Card --}}
                <form wire:submit="register">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

                        {{-- Form Header --}}
                        <div class="bg-gray-50 border-b border-gray-200 px-6 py-4">
                            <div class="flex items-center justify-between flex-wrap gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">แบบฟอร์มลงทะเบียนสอบ</h3>
                                    <p class="text-sm text-gray-500">กรอกข้อมูลให้ครบถ้วนก่อนกดยืนยัน</p>
                                </div>
                                <button type="button" wire:click="loadPreviousData"
                                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-[#2D6A4F] bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    ใช้ข้อมูลปีที่แล้ว
                                </button>
                            </div>
                        </div>

                        {{-- Form Body --}}
                        <div class="p-6 space-y-8">

                            {{-- ─── Section 1: ข้อมูลส่วนตัว ─── --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    ข้อมูลส่วนตัว
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {{-- ตำแหน่ง --}}
                                    <div class="md:col-span-2">
                                        <label for="position" class="block text-sm font-medium text-gray-700 mb-1">
                                            ตำแหน่ง <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="position"
                                               wire:model="position"
                                               placeholder="เช่น พลทหาร กองร้อยที่ 1"
                                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2D6A4F] focus:ring-[#2D6A4F] text-sm">
                                        @error('position')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

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
                                </div>
                            </div>

                            {{-- Divider --}}
                            <hr class="border-gray-200">

                            {{-- ─── Section 2: ข้อมูลการสอบ ─── --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    ข้อมูลการสอบ
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                    <div>
                                        <label for="suspended_years" class="block text-sm font-medium text-gray-700 mb-1">
                                            ปีที่ถูกงดบำเหน็จ <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" id="suspended_years"
                                               wire:model.live.debounce.300ms="suspended_years"
                                               min="0" max="20"
                                               placeholder="0"
                                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2D6A4F] focus:ring-[#2D6A4F] text-sm">
                                        <p class="mt-1 text-xs text-gray-400">ถ้าไม่มี ให้ระบุ 0</p>
                                        @error('suspended_years')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- ระดับที่สอบ --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-3">
                                            ระดับที่สอบ <span class="text-red-500">*</span>
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
                                        @error('exam_level')
                                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- สถานที่สอบ --}}
                                    <div>
                                        <label for="test_location_id" class="block text-sm font-medium text-gray-700 mb-1">
                                            สถานที่สอบ <span class="text-red-500">*</span>
                                        </label>
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
                                    </div>

                                    {{-- พื้นที่ราชการชายแดน --}}
                                    <div>
                                        <label for="border_area_id" class="block text-sm font-medium text-gray-700 mb-1">
                                            พื้นที่ราชการชายแดน
                                            <span class="text-gray-400 text-xs font-normal">(ถ้ามี)</span>
                                        </label>
                                        <select id="border_area_id"
                                                wire:model.live="border_area_id"
                                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-[#2D6A4F] focus:ring-[#2D6A4F] text-sm">
                                            <option value="">-- ไม่ได้ประจำพื้นที่ชายแดน --</option>
                                            @foreach ($borderAreas as $area)
                                                <option value="{{ $area->id }}">{{ $area->code }} - {{ $area->name }} (คะแนนพิเศษ: {{ number_format($area->special_score, 2) }})</option>
                                            @endforeach
                                        </select>
                                        @error('border_area_id')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Divider --}}
                            <hr class="border-gray-200">

                            {{-- ─── Section 3: คะแนนที่คำนวณอัตโนมัติ ─── --}}
                            <div>
                                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    คะแนนที่คำนวณอัตโนมัติ
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    {{-- คะแนนค้างบรรจุ --}}
                                    <div class="bg-blue-50 rounded-xl border border-blue-100 p-5 text-center">
                                        <div class="flex items-center justify-center gap-2 mb-2">
                                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <p class="text-sm font-medium text-blue-700">คะแนนค้างบรรจุ</p>
                                        </div>
                                        <p class="text-3xl font-bold text-blue-800" wire:loading.class="opacity-50">
                                            {{ number_format($calculatedPendingScore, 2) }}
                                        </p>
                                        <p class="text-xs text-blue-500 mt-2">
                                            (ปีปัจจุบัน - ปีที่มีสิทธิ์สอบ) - ปีงดบำเหน็จ
                                        </p>
                                    </div>

                                    {{-- คะแนนพิเศษ --}}
                                    <div class="bg-amber-50 rounded-xl border border-amber-100 p-5 text-center">
                                        <div class="flex items-center justify-center gap-2 mb-2">
                                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                            </svg>
                                            <p class="text-sm font-medium text-amber-700">คะแนนพิเศษ</p>
                                        </div>
                                        <p class="text-3xl font-bold text-amber-800" wire:loading.class="opacity-50">
                                            {{ number_format($calculatedSpecialScore, 2) }}
                                        </p>
                                        <p class="text-xs text-amber-500 mt-2">
                                            จากพื้นที่ราชการชายแดน
                                        </p>
                                    </div>

                                    {{-- คะแนนรวม --}}
                                    <div class="bg-green-50 rounded-xl border border-green-200 p-5 text-center ring-2 ring-green-200">
                                        <div class="flex items-center justify-center gap-2 mb-2">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                            </svg>
                                            <p class="text-sm font-medium text-green-700">คะแนนรวม</p>
                                        </div>
                                        <p class="text-3xl font-bold text-green-800" wire:loading.class="opacity-50">
                                            {{ number_format($calculatedTotalScore, 2) }}
                                        </p>
                                        <p class="text-xs text-green-500 mt-2">
                                            ค้างบรรจุ + คะแนนพิเศษ
                                        </p>
                                    </div>
                                </div>

                                {{-- Score Calculation Explanation --}}
                                <div class="mt-4 bg-gray-50 rounded-lg p-4 text-xs text-gray-500">
                                    <p class="font-medium text-gray-600 mb-1">สูตรการคำนวณ:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li><span class="font-medium">คะแนนค้างบรรจุ</span> = (ปีปัจจุบัน พ.ศ. {{ date('Y') + 543 }} - ปีที่มีสิทธิ์สอบ) - ปีที่ถูกงดบำเหน็จ</li>
                                        <li><span class="font-medium">คะแนนพิเศษ</span> = ดึงจากพื้นที่ราชการชายแดนที่เลือก</li>
                                        <li><span class="font-medium">คะแนนรวม</span> = คะแนนค้างบรรจุ + คะแนนพิเศษ</li>
                                    </ul>
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
                                ยกเลิก
                            </a>

                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-75 cursor-not-allowed"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-[#2D6A4F] rounded-lg hover:bg-[#1B4332] focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] focus:ring-offset-2 transition-colors shadow-sm">
                                <span wire:loading.remove wire:target="register">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                                <span wire:loading wire:target="register">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                    </svg>
                                </span>
                                <span wire:loading.remove wire:target="register">ยืนยันลงทะเบียนสอบ</span>
                                <span wire:loading wire:target="register">กำลังบันทึก...</span>
                            </button>
                        </div>
                    </div>
                </form>
            @endif

        </div>
    </div>
</div>
