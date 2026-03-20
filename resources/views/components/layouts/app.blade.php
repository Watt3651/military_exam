<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=sarabun:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @php
            $user = auth()->user();
            $role = $user?->role ?? 'guest';
            
            // Navigation configuration based on role
            $navConfig = [
                'staff' => [
                    'bg' => 'bg-blue-500',
                    'border' => 'border-primary-700',
                    'text' => 'text-black',
                    'hoverText' => 'hover:text-white',
                    'hoverBorder' => 'border-blue-300',
                    'logoText' => 'เจ้าหน้าที่',
                    'dashboardRoute' => 'staff.dashboard',
                    'menu' => [
                        ['route' => 'staff.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                        ['route' => 'staff.examinees.index', 'label' => 'ผู้สมัคร', 'icon' => 'users', 'pattern' => 'staff.examinees.*'],
                        ['route' => 'staff.border-areas.index', 'label' => 'พื้นที่ชายแดน', 'icon' => 'calendar', 'pattern' => 'staff.border-areas.*'],
                        ['route' => 'staff.exam-sessions.create', 'label' => 'รอบสอบ', 'icon' => 'document', 'pattern' => 'staff.exam-sessions.*'],
                        ['route' => 'staff.position-quotas.manage', 'label' => 'อัตราที่เปิดสอบ', 'icon' => 'document', 'pattern' => 'staff.position-quotas.*'],
                        ['route' => 'staff.test-locations.index', 'label' => 'สถานที่สอบ', 'icon' => 'location', 'pattern' => 'staff.test-locations.*'],
                        ['route' => 'staff.branches.index', 'label' => 'เหล่า', 'icon' => 'menu', 'pattern' => 'staff.branches.*'],
                        ['route' => 'staff.units.index', 'label' => 'สังกัด', 'icon' => 'building', 'pattern' => 'staff.units.*'],
                        ['route' => 'staff.reports.index', 'label' => 'รายงาน', 'icon' => 'chart', 'pattern' => 'staff.reports.*'],
                        ['route' => 'staff.users.index', 'label' => 'ผู้ใช้งาน', 'icon' => 'users', 'pattern' => 'staff.users.*'],
                    ],
                ],
                'password_support' => [
                    'bg' => 'bg-blue-500',
                    'border' => 'border-primary-700',
                    'text' => 'text-black',
                    'hoverText' => 'hover:text-white',
                    'hoverBorder' => 'border-blue-300',
                    'logoText' => 'ช่วยรีเซ็ตรหัสผ่าน',
                    'dashboardRoute' => 'staff.password-support.index',
                    'menu' => [
                        ['route' => 'staff.password-support.index', 'label' => 'ช่วยรีเซ็ตรหัสผ่าน', 'icon' => 'key', 'pattern' => 'staff.password-support.*'],
                        ['route' => 'staff.password-support.history', 'label' => 'ประวัติรีเซ็ตรหัสผ่าน', 'icon' => 'clock', 'pattern' => 'staff.password-support.*'],
                    ],
                ],
                'examinee' => [
                    'bg' => 'bg-blue-500',
                    'border' => 'border-primary-700',
                    'text' => 'text-black',
                    'hoverText' => 'hover:text-white',
                    'hoverBorder' => 'border-blue-300',
                    'logoText' => 'ผู้เข้าสอบ',
                    'dashboardRoute' => 'examinee.dashboard',
                    'menu' => [
                        ['route' => 'examinee.dashboard', 'label' => 'หน้าหลัก', 'icon' => 'home'],
                        ['route' => 'examinee.register-exam', 'label' => 'ลงทะเบียนสอบ', 'icon' => 'document'],
                        ['route' => 'examinee.profile', 'label' => 'ข้อมูลส่วนตัว', 'icon' => 'user'],
                        ['route' => 'examinee.history', 'label' => 'ประวัติการสอบ', 'icon' => 'clock'],
                    ],
                ],
                'commander' => [
                    'bg' => 'bg-blue-500',
                    'border' => 'border-primary-700',
                    'text' => 'text-black',
                    'hoverText' => 'hover:text-white',
                    'hoverBorder' => 'border-blue-300',
                    'logoText' => 'ผู้บังคับบัญชา',
                    'dashboardRoute' => 'commander.dashboard',
                    'menu' => [
                        ['route' => 'commander.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                        ['route' => 'commander.reports.index', 'label' => 'รายงาน', 'icon' => 'chart', 'pattern' => 'commander.reports.*'],
                    ],
                ],
            ];
            
            $config = $navConfig[$role] ?? $navConfig['examinee'];
            
            // SVG icons mapping
            $icons = [
                'home' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'users' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'calendar' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                'document' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a3 3 0 006 0M9 5a3 3 0 016 0M9 12h6M9 16h6',
                'location' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z',
                'menu' => 'M4 7h16M4 12h16M4 17h16',
                'building' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                'chart' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'key' => 'M15 7a2 2 0 11-4 0 2 2 0 014 0zM7 14a2 2 0 012-2h7l2 2-2 2h-2v2h-2v-2H9a2 2 0 01-2-2z',
                'user' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            ];
        @endphp

        @if($role !== 'guest')
            {{-- Main Navigation --}}
            <nav x-data="{ open: false }" class="menu-nav {{ $config['bg'] }} border-b {{ $config['border'] }} shadow-lg">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <a href="{{ route($config['dashboardRoute']) }}" class="flex items-center">
                                    <x-application-logo class="block h-9 w-auto fill-current {{ $config['text'] }}" />
                                    <span class="ml-3 {{ $config['text'] }} font-bold text-lg hidden lg:block">{{ $config['logoText'] }}</span>
                                </a>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-2 sm:-my-px sm:ms-10 sm:flex">
                                @foreach($config['menu'] as $item)
                                    @php
                                        $pattern = $item['pattern'] ?? $item['route'];
                                        $isActive = request()->routeIs($pattern);
                                    @endphp
                                    <x-nav-link :href="route($item['route'])" :active="$isActive"
                                        class="{{ $config['text'] }} {{ $config['hoverText'] }} {{ $config['hoverBorder'] }}">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="{{ $icons[$item['icon']] }}" />
                                        </svg>
                                        {{ $item['label'] }}
                                    </x-nav-link>
                                @endforeach
                            </div>
                        </div>

                        <!-- Settings Dropdown -->
                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <form method="POST" action="{{ route('logout') }}" class="mr-3">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium {{ in_array($role, ['staff', 'password_support'], true) ? 'text-gray-700 bg-white hover:bg-red-100' : 'text-black hover:text-blue-600 bg-white/90 hover:bg-white' }} transition duration-150 ease-in-out">
                                    ออกจากระบบ
                                </button>
                            </form>
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md {{ $config['text'] }} {{ $config['hoverText'] }} focus:outline-none transition ease-in-out duration-150">
                                        <div>{{ $user->full_name }}</div>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('profile')">
                                        <svg class="w-4 h-4 inline mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        ข้อมูลส่วนตัว
                                    </x-dropdown-link>

                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                            <svg class="w-4 h-4 inline mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            ออกจากระบบ
                                        </x-dropdown-link>
                                    </form>
                                </x-slot>
                            </x-dropdown>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="open = ! open"
                                class="inline-flex items-center justify-center p-2 rounded-md {{ $config['text'] }} {{ $config['hoverText'] }} hover:bg-primary-700 focus:outline-none transition duration-150 ease-in-out">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        @foreach($config['menu'] as $item)
                            @php
                                $pattern = $item['pattern'] ?? $item['route'];
                                $isActive = request()->routeIs($pattern);
                            @endphp
                            <x-responsive-nav-link :href="route($item['route'])" :active="$isActive"
                                class="{{ $config['text'] }} hover:bg-primary-700">
                                {{ $item['label'] }}
                            </x-responsive-nav-link>
                        @endforeach
                    </div>

                    <div class="pt-4 pb-1 border-t {{ $config['border'] }}">
                        <div class="px-4">
                            <div class="font-medium text-base {{ $config['text'] }}">{{ $user->full_name }}</div>
                            <div class="font-medium text-sm text-secondary-200">{{ $user->national_id }}</div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <x-responsive-nav-link :href="route('profile')" class="{{ $config['text'] }} hover:bg-primary-700">
                                ข้อมูลส่วนตัว
                            </x-responsive-nav-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();"
                                    class="{{ $config['text'] }} hover:bg-primary-700">
                                    ออกจากระบบ
                                </x-responsive-nav-link>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>
        @endif

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
        
        {{-- Notification Alert for Examinees --}}
        @if(strtolower($role) === 'examinee')
            <livewire:notification-alert />
        @endif
    </div>
</body>

</html>
