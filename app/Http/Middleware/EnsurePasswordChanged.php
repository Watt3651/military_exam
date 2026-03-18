<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs('profile') || $request->routeIs('logout')) {
            return $next($request);
        }

        return redirect()->route('profile')
            ->with('warning', 'กรุณาเปลี่ยนรหัสผ่านก่อนใช้งานระบบต่อ เนื่องจากบัญชีนี้ถูกรีเซ็ตรหัสผ่านโดยเจ้าหน้าที่');
    }
}
