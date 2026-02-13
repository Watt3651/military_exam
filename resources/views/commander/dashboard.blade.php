<x-layouts.commander>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard - ผู้บังคับบัญชา
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Welcome --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-primary-600 mb-2">
                        ยินดีต้อนรับ, {{ auth()->user()->full_name }}
                    </h3>
                    <p class="text-gray-600">Dashboard สำหรับผู้บังคับบัญชา (Read-only)</p>
                </div>
            </div>

            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-sm font-medium text-gray-500">ผู้สมัครทั้งหมด</h4>
                        <p class="text-3xl font-bold text-gray-900 mt-2">-</p>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-sm font-medium text-gray-500">รอบสอบที่เปิด</h4>
                        <p class="text-3xl font-bold text-gray-900 mt-2">-</p>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-sm font-medium text-gray-500">พื้นที่ชายแดน</h4>
                        <p class="text-3xl font-bold text-gray-900 mt-2">-</p>
                    </div>
                </div>
            </div>

            {{-- Auth Debug Info --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">
                        ข้อมูล Authentication (Debug)
                    </h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="py-2 pr-4 font-medium text-gray-500 w-48">User ID</td>
                                    <td class="py-2 text-gray-900">{{ auth()->user()->id }}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 pr-4 font-medium text-gray-500">National ID</td>
                                    <td class="py-2 text-gray-900">{{ auth()->user()->national_id }}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 pr-4 font-medium text-gray-500">ยศ-ชื่อ-นามสกุล</td>
                                    <td class="py-2 text-gray-900">{{ auth()->user()->full_name }}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 pr-4 font-medium text-gray-500">Role (column)</td>
                                    <td class="py-2">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                            {{ auth()->user()->role }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2 pr-4 font-medium text-gray-500">Spatie Roles</td>
                                    <td class="py-2 text-gray-900">{{ auth()->user()->getRoleNames()->implode(', ') ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 pr-4 font-medium text-gray-500">Email</td>
                                    <td class="py-2 text-gray-900">{{ auth()->user()->email ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 pr-4 font-medium text-gray-500">Active</td>
                                    <td class="py-2 text-gray-900">{{ auth()->user()->is_active ? 'Yes' : 'No' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 pr-4 font-medium text-gray-500">Created At</td>
                                    <td class="py-2 text-gray-900">{{ auth()->user()->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-layouts.commander>
