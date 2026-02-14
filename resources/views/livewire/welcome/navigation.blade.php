<nav class="inline-flex items-center gap-2 rounded-xl bg-white/85 px-2 py-1.5 shadow-md ring-1 ring-blue-200 backdrop-blur-sm">
    @auth
        <span class="mr-2 text-sm font-medium text-blue-900">
            {{ auth()->user()->full_name }}
        </span>
        <a
            href="{{ url('/dashboard') }}"
            class="rounded-md px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 hover:text-blue-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
        >
            หน้าหลัก
        </a>
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button
                type="submit"
                class="rounded-md px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 hover:text-blue-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
            >
                ออกจากระบบ
            </button>
        </form>
    @else
        <a
            href="{{ route('login') }}"
            class="rounded-md px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 hover:text-blue-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
        >
            เข้าสู่ระบบ
        </a>

        @if (Route::has('register'))
            <a
                href="{{ route('register') }}"
                class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"
            >
                สมัครสมาชิก
            </a>
        @endif
    @endauth
</nav>
