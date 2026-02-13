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
            {{-- Staff Navigation --}}
            <nav x-data="{ open: false }" class="menu-nav bg-primary-600 border-b border-primary-700 shadow-lg">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <a href="{{ route('staff.dashboard') }}" class="flex items-center">
                                    <x-application-logo class="block h-9 w-auto fill-current text-white" />
                                    <span class="ml-3 text-white font-bold text-lg hidden lg:block">เจ้าหน้าที่</span>
                                </a>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                                {{-- Dashboard --}}
                                <x-nav-link :href="route('staff.dashboard')" :active="request()->routeIs('staff.dashboard')" class="text-white hover:text-secondary-200 border-secondary-300">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                    Dashboard
                                </x-nav-link>
                                
                                {{-- จัดการผู้สมัคร --}}
                                <x-nav-link :href="route('staff.examinees.index')" :active="request()->routeIs('staff.examinees.*')" class="text-white hover:text-secondary-200 border-secondary-300">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    ผู้สมัคร
                                </x-nav-link>
                                
                                {{-- พื้นที่ชายแดน --}}
                                <x-nav-link :href="route('staff.border-areas.index')" :active="request()->routeIs('staff.border-areas.*')" class="text-white hover:text-secondary-200 border-secondary-300">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    พื้นที่ชายแดน
                                </x-nav-link>
                                
                                {{-- รอบสอบ --}}
                                <x-nav-link :href="route('staff.exam-sessions.index')" :active="request()->routeIs('staff.exam-sessions.*')" class="text-white hover:text-secondary-200 border-secondary-300">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    รอบสอบ
                                </x-nav-link>

                                {{-- อัตราที่เปิดสอบ --}}
                                <x-nav-link :href="route('staff.position-quotas.manage')" :active="request()->routeIs('staff.position-quotas.*')" class="text-white hover:text-secondary-200 border-secondary-300">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a3 3 0 006 0M9 5a3 3 0 016 0M9 12h6M9 16h6" />
                                    </svg>
                                    อัตราที่เปิดสอบ
                                </x-nav-link>

                                {{-- สถานที่สอบ --}}
                                <x-nav-link :href="route('staff.test-locations.index')" :active="request()->routeIs('staff.test-locations.*')" class="text-white hover:text-secondary-200 border-secondary-300">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    สถานที่สอบ
                                </x-nav-link>

                                {{-- เหล่า --}}
                                <x-nav-link :href="route('staff.branches.index')" :active="request()->routeIs('staff.branches.*')" class="text-white hover:text-secondary-200 border-secondary-300">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                                    </svg>
                                    เหล่า
                                </x-nav-link>
                                
                                {{-- รายงาน --}}
                                <x-nav-link :href="route('staff.reports.index')" :active="request()->routeIs('staff.reports.*')" class="text-white hover:text-secondary-200 border-secondary-300">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    รายงาน
                                </x-nav-link>
                                
                                {{-- ผู้ใช้งาน --}}
                                <x-nav-link :href="route('staff.users.index')" :active="request()->routeIs('staff.users.*')" class="text-white hover:text-secondary-200 border-secondary-300">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    ผู้ใช้งาน
                                </x-nav-link>
                            </div>
                        </div>

                        <!-- Settings Dropdown -->
                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <span class="text-secondary-100 text-sm mr-4">
                                <span class="px-2 py-1 bg-blue-500 text-white text-xs font-semibold rounded-full">
                                    เจ้าหน้าที่
                                </span>
                            </span>
                            <form method="POST" action="{{ route('logout') }}" class="mr-3">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-black hover:text-blue-600 bg-white/90 hover:bg-white transition duration-150 ease-in-out">
                                    ออกจากระบบ
                                </button>
                            </form>
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white hover:text-secondary-200 focus:outline-none transition ease-in-out duration-150">
                                        <div>{{ auth()->user()->full_name }}</div>

                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('profile')">
                                        <svg class="w-4 h-4 inline mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        ข้อมูลส่วนตัว
                                    </x-dropdown-link>

                                    <!-- Authentication -->
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <x-dropdown-link :href="route('logout')"
                                                onclick="event.preventDefault();
                                                        this.closest('form').submit();">
                                            <svg class="w-4 h-4 inline mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            ออกจากระบบ
                                        </x-dropdown-link>
                                    </form>
                                </x-slot>
                            </x-dropdown>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-white hover:text-secondary-200 hover:bg-primary-700 focus:outline-none focus:bg-primary-700 transition duration-150 ease-in-out">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <x-responsive-nav-link :href="route('staff.dashboard')" :active="request()->routeIs('staff.dashboard')" class="text-white hover:bg-primary-700">
                            Dashboard
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('staff.examinees.index')" :active="request()->routeIs('staff.examinees.*')" class="text-white hover:bg-primary-700">
                            จัดการผู้สมัคร
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('staff.border-areas.index')" :active="request()->routeIs('staff.border-areas.*')" class="text-white hover:bg-primary-700">
                            พื้นที่ชายแดน
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('staff.exam-sessions.index')" :active="request()->routeIs('staff.exam-sessions.*')" class="text-white hover:bg-primary-700">
                            รอบสอบ
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('staff.position-quotas.manage')" :active="request()->routeIs('staff.position-quotas.*')" class="text-white hover:bg-primary-700">
                            อัตราที่เปิดสอบ
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('staff.test-locations.index')" :active="request()->routeIs('staff.test-locations.*')" class="text-white hover:bg-primary-700">
                            สถานที่สอบ
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('staff.branches.index')" :active="request()->routeIs('staff.branches.*')" class="text-white hover:bg-primary-700">
                            เหล่า
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('staff.reports.index')" :active="request()->routeIs('staff.reports.*')" class="text-white hover:bg-primary-700">
                            รายงาน
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('staff.users.index')" :active="request()->routeIs('staff.users.*')" class="text-white hover:bg-primary-700">
                            ผู้ใช้งาน
                        </x-responsive-nav-link>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-primary-700">
                        <div class="px-4">
                            <div class="font-medium text-base text-white">{{ auth()->user()->full_name }}</div>
                            <div class="font-medium text-sm text-secondary-200">{{ auth()->user()->national_id }}</div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <x-responsive-nav-link :href="route('profile')" class="text-white hover:bg-primary-700">
                                ข้อมูลส่วนตัว
                            </x-responsive-nav-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-responsive-nav-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                this.closest('form').submit();"
                                        class="text-white hover:bg-primary-700">
                                    ออกจากระบบ
                                </x-responsive-nav-link>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>

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
        </div>
    </body>
</html>
