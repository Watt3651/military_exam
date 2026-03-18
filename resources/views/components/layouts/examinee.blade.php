{{--
    Examinee Layout - Backward Compatible Wrapper
    
    This file now delegates to the unified app layout.
    All role-specific logic has been moved to the centralized app.blade.php layout
    which determines navigation based on auth()->user()->role
    
    @see resources/views/components/layouts/app.blade.php
--}}
<x-layouts.app>
    {{ $slot }}
</x-layouts.app>