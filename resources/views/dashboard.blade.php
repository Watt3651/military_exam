<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            หน้าหลัก
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Welcome Message -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-primary-600 mb-2">
                        ยินดีต้อนรับ, {{ auth()->user()->full_name }}
                    </h3>
                    <p class="text-gray-600">
                        ระบบสอบเลื่อนฐานะ นย.
                    </p>
                </div>
            </div>

            <!-- User Info Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">ข้อมูลผู้ใช้งาน</h4>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">หมายเลขประจำตัว</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ auth()->user()->national_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ยศ</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ auth()->user()->rank }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ชื่อ-นามสกุล</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ auth()->user()->short_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">บทบาท</dt>
                            <dd class="mt-1">
                                @if(auth()->user()->role === 'examinee')
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">ผู้เข้าสอบ</span>
                                @elseif(auth()->user()->role === 'staff')
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">เจ้าหน้าที่</span>
                                @elseif(auth()->user()->role === 'commander')
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">ผู้บังคับบัญชา</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>