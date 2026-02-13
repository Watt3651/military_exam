<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Commander\Dashboard as CommanderDashboard;

/*
|--------------------------------------------------------------------------
| Commander Routes (ผู้บังคับบัญชา)
|--------------------------------------------------------------------------
|
| Middleware: auth + role:commander
| Prefix: /commander
| Name prefix: commander.
|
| Section 8.1 - Route Protection:
|   GET /commander/dashboard    → commander.dashboard
|   GET /commander/reports      → commander.reports.index
|
| Note: Commander มีสิทธิ์ Read-only เท่านั้น
|       เหมือน Staff Dashboard แต่ไม่สามารถแก้ไขข้อมูลได้
|
*/

Route::middleware(['auth', 'role:commander'])
    ->prefix('commander')
    ->name('commander.')
    ->group(function () {

        // ═════════════════════════════════════════
        // Dashboard ผู้บังคับบัญชา (Section 2.6.3)
        // URL: /commander/dashboard
        // แสดง: เหมือน Staff Dashboard แต่ Read-only
        // ═════════════════════════════════════════
        Route::get('/dashboard', CommanderDashboard::class)->name('dashboard');

        // ═════════════════════════════════════════
        // รายงาน (Read-only)
        // URL: /commander/reports
        // ═════════════════════════════════════════
        Route::prefix('reports')->name('reports.')->group(function () {
            // หน้ารายงานรวม
            Route::get('/', function () {
                return view('commander.reports.index');
            })->name('index');
        });
    });
