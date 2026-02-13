<?php

use App\Livewire\Staff\BorderAreas\Create as BorderAreaCreate;
use App\Livewire\Staff\BorderAreas\Edit as BorderAreaEdit;
use App\Livewire\Staff\BorderAreas\Index as BorderAreaIndex;
use App\Livewire\Staff\BorderAreas\ScoreHistory as BorderAreaScoreHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| ระบบสอบเลื่อนฐานะทหาร (Military Promotion Exam System)
|
| Route Structure ตาม Section 8.1:
| 1. Public routes     → guest middleware (login, register)
| 2. Examinee routes   → auth + role:examinee
| 3. Staff routes      → auth + role:staff
| 4. Commander routes  → auth + role:commander
|
*/

// ─────────────────────────────────────────────────────────
// Public: Welcome Page
// ─────────────────────────────────────────────────────────
Route::view('/', 'welcome');

// ─────────────────────────────────────────────────────────
// Authenticated: Dashboard Redirect (Role-based)
// ─────────────────────────────────────────────────────────
Route::get('dashboard', function () {
    $user = Auth::user();

    // Redirect to role-specific dashboard
    return match ($user->role) {
        'staff'     => redirect()->route('staff.dashboard'),
        'commander' => redirect()->route('commander.dashboard'),
        'examinee'  => redirect()->route('examinee.dashboard'),
        default     => redirect('/'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

// ─────────────────────────────────────────────────────────
// Authenticated: Profile (ทุก role เข้าถึงได้)
// ─────────────────────────────────────────────────────────
Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// ─────────────────────────────────────────────────────────
// Auth Routes (Login, Register, Logout, Password Reset)
// ─────────────────────────────────────────────────────────
require __DIR__.'/auth.php';

// ─────────────────────────────────────────────────────────
// Examinee Routes (auth + role:examinee)
// ─────────────────────────────────────────────────────────
require __DIR__.'/examinee.php';

// ─────────────────────────────────────────────────────────
// Staff: Border Area Management (auth + role:staff)
// ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:staff'])
    ->prefix('staff')
    ->group(function () {
        Route::prefix('border-areas')->group(function () {
            Route::get('/', BorderAreaIndex::class)->name('staff.border-areas.index');
            Route::get('/create', BorderAreaCreate::class)->name('staff.border-areas.create');
            Route::get('/{id}/edit', BorderAreaEdit::class)->name('staff.border-areas.edit');
            Route::get('/history', BorderAreaScoreHistory::class)->name('staff.border-areas.history');
        });
    });

// ─────────────────────────────────────────────────────────
// Staff Routes (auth + role:staff)
// ─────────────────────────────────────────────────────────
require __DIR__.'/staff.php';

// ─────────────────────────────────────────────────────────
// Commander Routes (auth + role:commander)
// ─────────────────────────────────────────────────────────
require __DIR__.'/commander.php';