<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| RBAC Test — ทดสอบระบบ Role-Based Access Control
|--------------------------------------------------------------------------
|
| 1. Login / Authentication
| 2. Role-based Dashboard Redirect
| 3. Middleware Protection (unauthorized access)
| 4. Spatie Permission Matrix
|
*/

// ─────────────────────────────────────────────────────────
// Helper: Seed roles & permissions + create user with role
// ─────────────────────────────────────────────────────────

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function createUserWithRole(string $role): User
{
    $user = User::factory()->{$role}()->create();
    $user->assignRole($role);

    return $user;
}

// ═════════════════════════════════════════════════════════
// 1. Authentication — Login Tests
// ═════════════════════════════════════════════════════════

describe('Authentication', function () {

    test('guest สามารถเข้าหน้า login ได้', function () {
        $this->get('/login')->assertStatus(200);
    });

    test('guest สามารถเข้าหน้า register ได้', function () {
        $this->get('/register')->assertStatus(200);
    });

    test('guest ไม่สามารถเข้า /dashboard ได้ → redirect to login', function () {
        $this->get('/dashboard')->assertRedirect('/login');
    });

    test('examinee สามารถ login ด้วย national_id ได้ (Volt)', function () {
        $user = createUserWithRole('examinee');

        $component = Volt::test('pages.auth.login')
            ->set('form.national_id', $user->national_id)
            ->set('form.password', 'password')
            ->call('login');

        $component->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    });

    test('staff สามารถ login ด้วย national_id ได้ (Volt)', function () {
        $user = createUserWithRole('staff');

        $component = Volt::test('pages.auth.login')
            ->set('form.national_id', $user->national_id)
            ->set('form.password', 'password')
            ->call('login');

        $component->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    });

    test('commander สามารถ login ด้วย national_id ได้ (Volt)', function () {
        $user = createUserWithRole('commander');

        $component = Volt::test('pages.auth.login')
            ->set('form.national_id', $user->national_id)
            ->set('form.password', 'password')
            ->call('login');

        $component->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    });

    test('login ด้วย password ผิด → ไม่ผ่าน', function () {
        $user = createUserWithRole('examinee');

        $component = Volt::test('pages.auth.login')
            ->set('form.national_id', $user->national_id)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $this->assertGuest();
    });

    test('login ด้วย national_id ที่ไม่มีอยู่ → ไม่ผ่าน', function () {
        $component = Volt::test('pages.auth.login')
            ->set('form.national_id', '9999999999999')
            ->set('form.password', 'password');

        $component->call('login');

        $this->assertGuest();
    });

    test('user สามารถ logout ได้', function () {
        $user = createUserWithRole('examinee');

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    });
});

// ═════════════════════════════════════════════════════════
// 2. Role-based Dashboard Redirect
// ═════════════════════════════════════════════════════════

describe('Dashboard Redirect', function () {

    test('examinee → /dashboard → redirect ไป /examinee/dashboard', function () {
        $user = createUserWithRole('examinee');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('examinee.dashboard'));
    });

    test('staff → /dashboard → redirect ไป /staff/dashboard', function () {
        $user = createUserWithRole('staff');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('staff.dashboard'));
    });

    test('commander → /dashboard → redirect ไป /commander/dashboard', function () {
        $user = createUserWithRole('commander');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('commander.dashboard'));
    });

    test('examinee สามารถเข้า /examinee/dashboard ได้', function () {
        $user = createUserWithRole('examinee');

        $this->actingAs($user)
            ->get('/examinee/dashboard')
            ->assertStatus(200);
    });

    test('staff สามารถเข้า /staff/dashboard ได้', function () {
        $user = createUserWithRole('staff');

        $this->actingAs($user)
            ->get('/staff/dashboard')
            ->assertStatus(200);
    });

    test('commander สามารถเข้า /commander/dashboard ได้', function () {
        $user = createUserWithRole('commander');

        $this->actingAs($user)
            ->get('/commander/dashboard')
            ->assertStatus(200);
    });
});

// ═════════════════════════════════════════════════════════
// 3. Middleware Protection — Unauthorized Access
// ═════════════════════════════════════════════════════════

describe('Middleware: Examinee routes blocked for other roles', function () {

    test('staff ไม่สามารถเข้า /examinee/dashboard ได้ → 403', function () {
        $user = createUserWithRole('staff');

        $this->actingAs($user)
            ->get('/examinee/dashboard')
            ->assertStatus(403);
    });

    test('commander ไม่สามารถเข้า /examinee/dashboard ได้ → 403', function () {
        $user = createUserWithRole('commander');

        $this->actingAs($user)
            ->get('/examinee/dashboard')
            ->assertStatus(403);
    });

    test('examinee ไม่สามารถเข้า /examinee/register-exam ได้ (ไม่อยู่ช่วงลงทะเบียน) → redirect', function () {
        $user = createUserWithRole('examinee');

        $this->actingAs($user)
            ->get('/examinee/register-exam')
            ->assertRedirect(route('examinee.dashboard'));
    });
});

describe('Middleware: Staff routes blocked for other roles', function () {

    test('examinee ไม่สามารถเข้า /staff/dashboard ได้ → 403', function () {
        $user = createUserWithRole('examinee');

        $this->actingAs($user)
            ->get('/staff/dashboard')
            ->assertStatus(403);
    });

    test('commander ไม่สามารถเข้า /staff/dashboard ได้ → 403', function () {
        $user = createUserWithRole('commander');

        $this->actingAs($user)
            ->get('/staff/dashboard')
            ->assertStatus(403);
    });

    test('examinee ไม่สามารถเข้า /staff/users ได้ → 403', function () {
        $user = createUserWithRole('examinee');

        $this->actingAs($user)
            ->get('/staff/users')
            ->assertStatus(403);
    });

    test('commander ไม่สามารถเข้า /staff/examinees ได้ → 403', function () {
        $user = createUserWithRole('commander');

        $this->actingAs($user)
            ->get('/staff/examinees')
            ->assertStatus(403);
    });
});

describe('Middleware: Commander routes blocked for other roles', function () {

    test('examinee ไม่สามารถเข้า /commander/dashboard ได้ → 403', function () {
        $user = createUserWithRole('examinee');

        $this->actingAs($user)
            ->get('/commander/dashboard')
            ->assertStatus(403);
    });

    test('staff ไม่สามารถเข้า /commander/dashboard ได้ → 403', function () {
        $user = createUserWithRole('staff');

        $this->actingAs($user)
            ->get('/commander/dashboard')
            ->assertStatus(403);
    });

    test('examinee ไม่สามารถเข้า /commander/reports ได้ → 403', function () {
        $user = createUserWithRole('examinee');

        $this->actingAs($user)
            ->get('/commander/reports')
            ->assertStatus(403);
    });
});

describe('Middleware: Guest routes blocked for authenticated', function () {

    test('authenticated user ไม่สามารถเข้า /login ได้ → redirect', function () {
        $user = createUserWithRole('examinee');

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('/dashboard');
    });

    test('authenticated user ไม่สามารถเข้า /register ได้ → redirect', function () {
        $user = createUserWithRole('examinee');

        $this->actingAs($user)
            ->get('/register')
            ->assertRedirect('/dashboard');
    });
});

// ═════════════════════════════════════════════════════════
// 4. Spatie Permission Matrix
// ═════════════════════════════════════════════════════════

describe('Permission Matrix: Examinee', function () {

    test('examinee มี permissions ที่ถูกต้อง', function () {
        $user = createUserWithRole('examinee');

        // ต้องมี
        expect($user->hasPermissionTo('self_register'))->toBeTrue();
        expect($user->hasPermissionTo('view_own_dashboard'))->toBeTrue();
        expect($user->hasPermissionTo('view_own_profile'))->toBeTrue();
        expect($user->hasPermissionTo('edit_own_profile'))->toBeTrue();
        expect($user->hasPermissionTo('register_exam'))->toBeTrue();
        expect($user->hasPermissionTo('cancel_exam_registration'))->toBeTrue();
        expect($user->hasPermissionTo('view_own_exam_number'))->toBeTrue();
        expect($user->hasPermissionTo('view_own_exam_history'))->toBeTrue();
        expect($user->hasPermissionTo('print_exam_card'))->toBeTrue();
        expect($user->hasPermissionTo('view_border_areas'))->toBeTrue();
        expect($user->hasPermissionTo('view_exam_sessions'))->toBeTrue();
    });

    test('examinee ไม่มี permissions ของ staff', function () {
        $user = createUserWithRole('examinee');

        // ต้องไม่มี
        expect($user->hasPermissionTo('create_staff_account'))->toBeFalse();
        expect($user->hasPermissionTo('view_staff_dashboard'))->toBeFalse();
        expect($user->hasPermissionTo('view_all_examinees'))->toBeFalse();
        expect($user->hasPermissionTo('edit_examinee'))->toBeFalse();
        expect($user->hasPermissionTo('create_exam_session'))->toBeFalse();
        expect($user->hasPermissionTo('create_border_area'))->toBeFalse();
        expect($user->hasPermissionTo('generate_exam_numbers'))->toBeFalse();
        expect($user->hasPermissionTo('view_activity_log'))->toBeFalse();
    });
});

describe('Permission Matrix: Staff', function () {

    test('staff มีทุก permissions (full control)', function () {
        $user = createUserWithRole('staff');
        $allPermissions = \Spatie\Permission\Models\Permission::all();

        expect($user->getAllPermissions()->count())->toBe($allPermissions->count());
    });

    test('staff สามารถจัดการผู้สมัครได้', function () {
        $user = createUserWithRole('staff');

        expect($user->hasPermissionTo('view_all_examinees'))->toBeTrue();
        expect($user->hasPermissionTo('edit_examinee'))->toBeTrue();
        expect($user->hasPermissionTo('delete_examinee'))->toBeTrue();
        expect($user->hasPermissionTo('add_late_examinee'))->toBeTrue();
    });

    test('staff สามารถจัดการรอบสอบได้', function () {
        $user = createUserWithRole('staff');

        expect($user->hasPermissionTo('create_exam_session'))->toBeTrue();
        expect($user->hasPermissionTo('edit_exam_session'))->toBeTrue();
        expect($user->hasPermissionTo('archive_exam_session'))->toBeTrue();
    });

    test('staff สามารถจัดการพื้นที่ชายแดนได้', function () {
        $user = createUserWithRole('staff');

        expect($user->hasPermissionTo('create_border_area'))->toBeTrue();
        expect($user->hasPermissionTo('edit_border_area'))->toBeTrue();
        expect($user->hasPermissionTo('delete_border_area'))->toBeTrue();
        expect($user->hasPermissionTo('set_special_scores'))->toBeTrue();
    });

    test('staff สามารถสร้างบัญชีผู้ใช้ได้', function () {
        $user = createUserWithRole('staff');

        expect($user->hasPermissionTo('create_staff_account'))->toBeTrue();
        expect($user->hasPermissionTo('create_commander_account'))->toBeTrue();
    });
});

describe('Permission Matrix: Commander', function () {

    test('commander มี read-only permissions เท่านั้น', function () {
        $user = createUserWithRole('commander');

        // ต้องมี (read-only)
        expect($user->hasPermissionTo('view_commander_dashboard'))->toBeTrue();
        expect($user->hasPermissionTo('view_all_examinees'))->toBeTrue();
        expect($user->hasPermissionTo('view_border_areas'))->toBeTrue();
        expect($user->hasPermissionTo('view_border_area_history'))->toBeTrue();
        expect($user->hasPermissionTo('view_reports'))->toBeTrue();
        expect($user->hasPermissionTo('export_examinee_list'))->toBeTrue();
        expect($user->hasPermissionTo('export_statistics'))->toBeTrue();
        expect($user->hasPermissionTo('view_activity_log'))->toBeTrue();
    });

    test('commander ไม่มี write permissions', function () {
        $user = createUserWithRole('commander');

        // ต้องไม่มี (write/modify)
        expect($user->hasPermissionTo('create_staff_account'))->toBeFalse();
        expect($user->hasPermissionTo('edit_examinee'))->toBeFalse();
        expect($user->hasPermissionTo('delete_examinee'))->toBeFalse();
        expect($user->hasPermissionTo('create_exam_session'))->toBeFalse();
        expect($user->hasPermissionTo('edit_exam_session'))->toBeFalse();
        expect($user->hasPermissionTo('create_border_area'))->toBeFalse();
        expect($user->hasPermissionTo('edit_border_area'))->toBeFalse();
        expect($user->hasPermissionTo('generate_exam_numbers'))->toBeFalse();
    });
});

// ═════════════════════════════════════════════════════════
// 5. User Model Helper Methods
// ═════════════════════════════════════════════════════════

describe('User Model Helpers', function () {

    test('isExaminee() ทำงานถูกต้อง', function () {
        $user = User::factory()->examinee()->create();

        expect($user->isExaminee())->toBeTrue();
        expect($user->isStaff())->toBeFalse();
        expect($user->isCommander())->toBeFalse();
    });

    test('isStaff() ทำงานถูกต้อง', function () {
        $user = User::factory()->staff()->create();

        expect($user->isStaff())->toBeTrue();
        expect($user->isExaminee())->toBeFalse();
        expect($user->isCommander())->toBeFalse();
    });

    test('isCommander() ทำงานถูกต้อง', function () {
        $user = User::factory()->commander()->create();

        expect($user->isCommander())->toBeTrue();
        expect($user->isExaminee())->toBeFalse();
        expect($user->isStaff())->toBeFalse();
    });

    test('full_name accessor ทำงานถูกต้อง', function () {
        $user = User::factory()->create([
            'rank' => 'ส.อ.',
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
        ]);

        expect($user->full_name)->toBe('ส.อ. สมชาย ใจดี');
    });

    test('short_name accessor ทำงานถูกต้อง', function () {
        $user = User::factory()->create([
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
        ]);

        expect($user->short_name)->toBe('สมชาย ใจดี');
    });

    test('scopeActive กรองเฉพาะ active users', function () {
        User::factory()->create(['is_active' => true]);
        User::factory()->create(['is_active' => false]);

        expect(User::active()->count())->toBe(1);
    });

    test('scopeByRole กรองตาม role ได้', function () {
        User::factory()->examinee()->create();
        User::factory()->examinee()->create();
        User::factory()->staff()->create();

        expect(User::byRole('examinee')->count())->toBe(2);
        expect(User::byRole('staff')->count())->toBe(1);
        expect(User::byRole('commander')->count())->toBe(0);
    });
});

// ═════════════════════════════════════════════════════════
// 6. Cross-role All Staff Routes Access Matrix
// ═════════════════════════════════════════════════════════

describe('Staff route access for all routes', function () {

    test('staff สามารถเข้าถึง staff routes ทั้งหมดได้ (Blade views)', function () {
        $user = createUserWithRole('staff');

        // Routes ที่ return Blade views (ไม่ใช่ Livewire full-page components)
        $routes = [
            '/staff/dashboard',
            '/staff/examinees',
            '/staff/border-areas',
            '/staff/border-areas/create',
            '/staff/border-areas/history',
            '/staff/exam-sessions',
            '/staff/exam-sessions/create',
            '/staff/registrations',
            '/staff/reports',
            '/staff/reports/export',
            '/staff/users',
        ];

        foreach ($routes as $route) {
            $this->actingAs($user)
                ->get($route)
                ->assertStatus(200, "Failed asserting $route returns 200");
        }
    });

    test('staff สามารถเข้า /staff/users/create ได้ (Livewire component)', function () {
        $user = createUserWithRole('staff');

        $this->actingAs($user)
            ->get('/staff/users/create')
            ->assertStatus(200);
    });
});

describe('Examinee route access for all routes', function () {

    test('examinee สามารถเข้าถึง examinee routes ทั้งหมดได้ (ยกเว้น register-exam)', function () {
        $user = createUserWithRole('examinee');

        $routes = [
            '/examinee/dashboard',
            '/examinee/profile',
            '/examinee/history',
        ];

        foreach ($routes as $route) {
            $this->actingAs($user)
                ->get($route)
                ->assertStatus(200, "Failed asserting $route returns 200");
        }
    });
});
