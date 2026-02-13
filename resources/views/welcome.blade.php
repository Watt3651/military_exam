<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>ระบบสอบเลื่อนฐานะ นย.</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=sarabun:400,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans">
        <div class="relative min-h-screen bg-blue-100">
            @if (Route::has('login'))
                <div class="absolute right-4 top-4 z-10 sm:right-6 sm:top-6">
                    <livewire:welcome.navigation />
                </div>
            @endif

            <main class="mx-auto flex min-h-screen w-full max-w-5xl flex-col items-center justify-center px-6 py-10 text-center">
                <h1 class="text-4xl font-bold tracking-tight text-blue-900 sm:text-5xl md:text-6xl">
                    ระบบสอบเลื่อนฐานะ นย.
                </h1>

                <div class="mt-8 w-full max-w-xl rounded-2xl bg-white/60 p-3 shadow-lg backdrop-blur-sm">
                    <img
                        src="{{ asset('images/home-hero.png') }}"
                        alt="อนุสาวรีย์หน้าแรกระบบสอบเลื่อนฐานะ นย."
                        class="mx-auto w-full rounded-xl object-cover"
                    >
                </div>
            </main>
        </div>
    </body>
</html>
