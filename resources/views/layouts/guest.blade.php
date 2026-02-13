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
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-gray-50 to-gray-100">
            {{-- Logo --}}
            <div class="mb-6">
                <a href="/" wire:navigate class="block">
                    <div class="flex items-center justify-center">
                        <x-application-logo class="w-20 h-20 fill-current text-primary-600" />
                    </div>
                    <div class="mt-3 text-center">
                        <h1 class="text-2xl font-bold text-primary-700">ระบบสอบเลื่อนฐานะทหาร</h1>
                        <p class="text-sm text-gray-600 mt-1">Military Promotion Exam System</p>
                    </div>
                </a>
            </div>

            {{-- Content Card --}}
            <div class="w-full sm:max-w-md px-6 py-6 bg-white shadow-lg overflow-hidden sm:rounded-xl border border-gray-100">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600">
                    © {{ date('Y') }} ระบบสอบเลื่อนฐานะทหาร
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    พัฒนาโดย กองทัพไทย
                </p>
            </div>
        </div>
    </body>
</html>
