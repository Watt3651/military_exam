<?php

namespace App\Http\Middleware;

use App\Models\ExamSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRegistrationPeriod Middleware (Section 8.2)
 *
 * ตรวจสอบว่าอยู่ในช่วงเวลาลงทะเบียนสอบหรือไม่
 * ใช้กับ route: /examinee/register-exam
 *
 * Logic:
 *   1. ค้นหา ExamSession ที่ is_active = true
 *   2. ตรวจว่า registration_start <= now() <= registration_end
 *   3. ถ้าอยู่ในช่วง → inject activeSession เข้า request แล้วผ่าน
 *   4. ถ้าไม่อยู่ในช่วง → redirect กลับ examinee.dashboard พร้อม error message
 *
 * Usage in routes:
 *   Route::get('/register-exam', ...)->middleware('check.registration.period');
 */
class CheckRegistrationPeriod
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ค้นหารอบสอบที่เปิดรับลงทะเบียนอยู่
        $activeSession = ExamSession::where('is_active', true)
            ->whereDate('registration_start', '<=', now())
            ->whereDate('registration_end', '>=', now())
            ->first();

        if (!$activeSession) {
            return redirect()->route('examinee.dashboard')
                ->with('error', 'ไม่อยู่ในช่วงเวลาลงทะเบียนสอบ');
        }

        // Inject active session เข้า request เพื่อให้ controller/component ใช้ได้เลย
        $request->merge(['activeExamSession' => $activeSession]);

        return $next($request);
    }
}
