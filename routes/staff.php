<?php

use App\Livewire\Staff\Users\CreateUser;
use App\Livewire\Staff\Dashboard as StaffDashboard;
use App\Livewire\Staff\Reports\Index as StaffReportsIndex;
use App\Livewire\Staff\Branches\Create as CreateBranch;
use App\Livewire\Staff\Branches\Edit as EditBranch;
use App\Livewire\Staff\Branches\Index as BranchIndex;
use App\Livewire\Staff\Examinees\Edit as EditExaminee;
use App\Livewire\Staff\Examinees\Index as ExamineeIndex;
use App\Livewire\Staff\ExamSessions\Create as CreateExamSession;
use App\Livewire\Staff\ExamSessions\Index as ExamSessionIndex;
use App\Livewire\Staff\TestLocations\Create as CreateTestLocation;
use App\Livewire\Staff\TestLocations\Edit as EditTestLocation;
use App\Livewire\Staff\TestLocations\Index as TestLocationIndex;
use App\Livewire\Staff\PositionQuotas\Manage as PositionQuotaManage;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Staff Routes (เจ้าหน้าที่)
|--------------------------------------------------------------------------
|
| Middleware: auth + role:staff
| Prefix: /staff
| Name prefix: staff.
|
| Section 8.1 - Route Protection:
|   GET /staff/dashboard                → staff.dashboard
|   --- Examinee Management ---
|   GET /staff/examinees                → staff.examinees.index
|   GET /staff/examinees/{id}           → staff.examinees.show
|   GET /staff/examinees/{id}/edit      → staff.examinees.edit
|   --- Border Areas ---
|   GET /staff/border-areas             → staff.border-areas.index
|   GET /staff/border-areas/create      → staff.border-areas.create
|   GET /staff/border-areas/{id}/edit   → staff.border-areas.edit
|   GET /staff/border-areas/history     → staff.border-areas.history
|   --- Exam Sessions ---
|   GET /staff/exam-sessions            → staff.exam-sessions.index
|   GET /staff/exam-sessions/create     → staff.exam-sessions.create
|   GET /staff/exam-sessions/{id}       → staff.exam-sessions.show
|   GET /staff/exam-sessions/{id}/edit  → staff.exam-sessions.edit
|   --- Exam Registrations ---
|   GET /staff/registrations            → staff.registrations.index
|   GET /staff/registrations/{id}       → staff.registrations.show
|   --- Reports ---
|   GET /staff/reports                  → staff.reports.index
|   GET /staff/reports/export           → staff.reports.export
|   --- User Management ---
|   GET /staff/users                    → staff.users.index
|   GET /staff/users/create             → staff.users.create
|
*/

Route::middleware(['auth', 'role:staff'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {

        // ═════════════════════════════════════════
        // Dashboard (Section 2.6.2)
        // URL: /staff/dashboard
        // แสดง: สถิติผู้สมัคร, รอบสอบ, พื้นที่ชายแดน
        // ═════════════════════════════════════════
        Route::get('/dashboard', StaffDashboard::class)->name('dashboard');

        // ═════════════════════════════════════════
        // จัดการผู้สมัคร (Section 2.4)
        // URL: /staff/examinees
        // ═════════════════════════════════════════
        Route::prefix('examinees')->name('examinees.')->group(function () {
            // รายการผู้สมัครทั้งหมด
            Route::get('/', ExamineeIndex::class)->name('index');

            // ดูข้อมูลผู้สมัคร
            Route::get('/{id}', function ($id) {
                return view('staff.examinees.show', ['id' => $id]);
            })->name('show');

            // แก้ไขข้อมูลผู้สมัคร
            Route::get('/{id}/edit', EditExaminee::class)->name('edit');
        });

        // ═════════════════════════════════════════
        // รอบสอบ (Section 2.5)
        // URL: /staff/exam-sessions
        // ═════════════════════════════════════════
        Route::prefix('exam-sessions')->name('exam-sessions.')->group(function () {
            // รายการรอบสอบทั้งหมด
            Route::get('/', ExamSessionIndex::class)->name('index');

            // สร้างรอบสอบใหม่
            Route::get('/create', CreateExamSession::class)->name('create');

            // ดูรายละเอียดรอบสอบ
            Route::get('/{id}', function ($id) {
                return view('staff.exam-sessions.show', ['id' => $id]);
            })->name('show');

            // แก้ไขรอบสอบ
            Route::get('/{id}/edit', function ($id) {
                return view('staff.exam-sessions.edit', ['id' => $id]);
            })->name('edit');
        });

        // ═════════════════════════════════════════
        // อัตราที่เปิดสอบ (Section 2.3.2)
        // URL: /staff/position-quotas
        // ═════════════════════════════════════════
        Route::prefix('position-quotas')->name('position-quotas.')->group(function () {
            Route::get('/', PositionQuotaManage::class)->name('manage');
        });

        // ═════════════════════════════════════════
        // สถานที่สอบ (Section 2.3.3)
        // URL: /staff/test-locations
        // ═════════════════════════════════════════
        Route::prefix('test-locations')->name('test-locations.')->group(function () {
            Route::get('/', TestLocationIndex::class)->name('index');
            Route::get('/create', CreateTestLocation::class)->name('create');
            Route::get('/{id}/edit', EditTestLocation::class)->name('edit');
        });

        // ═════════════════════════════════════════
        // เหล่า (Section 2.3.4)
        // URL: /staff/branches
        // ═════════════════════════════════════════
        Route::prefix('branches')->name('branches.')->group(function () {
            Route::get('/', BranchIndex::class)->name('index');
            Route::get('/create', CreateBranch::class)->name('create');
            Route::get('/{id}/edit', EditBranch::class)->name('edit');
        });

        // ═════════════════════════════════════════
        // จัดการการลงทะเบียนสอบ
        // URL: /staff/registrations
        // ═════════════════════════════════════════
        Route::prefix('registrations')->name('registrations.')->group(function () {
            // รายการลงทะเบียนทั้งหมด
            Route::get('/', function () {
                return view('staff.registrations.index');
            })->name('index');

            // ดูรายละเอียดการลงทะเบียน
            Route::get('/{id}', function ($id) {
                return view('staff.registrations.show', ['id' => $id]);
            })->name('show');
        });

        // ═════════════════════════════════════════
        // รายงาน (Section 2.6.2)
        // URL: /staff/reports
        // ═════════════════════════════════════════
        Route::prefix('reports')->name('reports.')->group(function () {
            // หน้ารายงานรวม
            Route::get('/', StaffReportsIndex::class)->name('index');

            // Export รายงาน (PDF/Excel)
            Route::get('/export', function () {
                return view('staff.reports.export');
            })->name('export');
        });

        // ═════════════════════════════════════════
        // จัดการผู้ใช้งาน (Section 2.1.3)
        // URL: /staff/users
        // ═════════════════════════════════════════
        Route::prefix('users')->name('users.')->group(function () {
            // รายการผู้ใช้งานทั้งหมด
            Route::get('/', function () {
                return view('staff.users.index');
            })->name('index');

            // สร้างผู้ใช้งาน (Staff/Commander)
            Route::get('/create', CreateUser::class)->name('create');
        });
    });
