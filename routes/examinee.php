<?php

use App\Livewire\Examinee\Dashboard;
use App\Livewire\Examinee\DownloadExamCard;
use App\Livewire\Examinee\ExamRegistration;
use App\Livewire\Examinee\History;
use App\Livewire\Examinee\Profile;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Examinee Routes (ผู้เข้าสอบ)
|--------------------------------------------------------------------------
|
| Middleware: auth + role:examinee
| Prefix: /examinee
| Name prefix: examinee.
|
| Section 8.1 - Route Protection:
|   GET /examinee/dashboard      → examinee.dashboard
|   GET /examinee/register-exam  → examinee.register-exam (+ check.registration.period)
|   GET /examinee/profile        → examinee.profile
|   GET /examinee/history        → examinee.history
|
*/

Route::middleware(['auth', 'role:examinee'])
    ->prefix('examinee')
    ->name('examinee.')
    ->group(function () {

        // ─────────────────────────────────────────
        // Dashboard ผู้เข้าสอบ (Section 2.6.1)
        // URL: /examinee/dashboard
        // แสดง: สถานะการลงทะเบียน, เลขที่สอบ, สถานที่สอบ
        // ─────────────────────────────────────────
        Route::get('/dashboard', Dashboard::class)->name('dashboard');

        // ─────────────────────────────────────────
        // ลงทะเบียนสอบ (Section 2.2)
        // URL: /examinee/register-exam
        // Middleware: check.registration.period
        // เปิดเฉพาะช่วงที่อยู่ใน registration_start - registration_end
        // ─────────────────────────────────────────
        Route::get('/register-exam', ExamRegistration::class)
          ->middleware('check.registration.period')
          ->name('register-exam');

        // ─────────────────────────────────────────
        // ข้อมูลส่วนตัว (Examinee Profile)
        // URL: /examinee/profile
        // แสดง/แก้ไข: ยศ, ชื่อ-นามสกุล, เหล่า, สังกัด, พื้นที่ชายแดน
        // ─────────────────────────────────────────
        Route::get('/profile', Profile::class)->name('profile');

        // ─────────────────────────────────────────
        // ประวัติการสอบ (Section 2.6.1)
        // URL: /examinee/history
        // แสดง: ประวัติการสอบทั้งหมดของผู้เข้าสอบ
        // ─────────────────────────────────────────
        Route::get('/history', History::class)->name('history');

        // ─────────────────────────────────────────
        // ดาวน์โหลดบัตรประจำตัวสอบ
        // URL: /examinee/download-exam-card
        // ─────────────────────────────────────────
        Route::get('/download-exam-card', DownloadExamCard::class)->name('download-exam-card');
    });
