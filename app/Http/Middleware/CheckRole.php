<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRole Middleware (Section 8.1)
 *
 * ตรวจสอบว่า user มี role ที่กำหนดหรือไม่
 * รองรับทั้ง column `role` ใน users table และ Spatie HasRoles trait
 *
 * Usage in routes:
 *   Route::middleware('role:staff')         → เฉพาะ staff
 *   Route::middleware('role:staff,commander') → staff หรือ commander
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  รายชื่อ role ที่อนุญาต (คั่นด้วย comma ใน route definition)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // ถ้ายังไม่ได้ login → redirect ไปหน้า login
        if (!$request->user()) {
            return redirect()->route('login');
        }

        $user = $request->user();

        // ตรวจสอบ role จาก column `role` ใน users table
        foreach ($roles as $role) {
            if ($user->role === $role) {
                return $next($request);
            }
        }

        // Fallback: ตรวจสอบผ่าน Spatie HasRoles trait (ถ้ามี)
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($roles)) {
            return $next($request);
        }

        // ไม่มีสิทธิ์เข้าถึง
        abort(403, 'ไม่มีสิทธิ์เข้าถึงหน้านี้');
    }
}
