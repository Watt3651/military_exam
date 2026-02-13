<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * RolePermissionSeeder — Roles & Permissions (Section 3.3)
 *
 * 3 Roles: examinee, staff, commander
 * Staff ได้ permissions ทั้งหมด (full control)
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        /*
        |----------------------------------------------------------------------
        | Create all permissions
        |----------------------------------------------------------------------
        */
        $permissions = [
            // Authentication
            'self_register',
            'create_staff_account',
            'create_commander_account',

            // Dashboard
            'view_own_dashboard',
            'view_staff_dashboard',
            'view_commander_dashboard',

            // Examinee Management
            'view_own_profile',
            'edit_own_profile',
            'view_all_examinees',
            'edit_examinee',
            'delete_examinee',

            // Exam Registration
            'register_exam',
            'cancel_exam_registration',
            'add_late_examinee',

            // Exam Session
            'view_exam_sessions',
            'create_exam_session',
            'edit_exam_session',
            'archive_exam_session',

            // Border Area
            'view_border_areas',
            'create_border_area',
            'edit_border_area',
            'delete_border_area',
            'set_special_scores',
            'view_border_area_history',

            // Test Location
            'view_test_locations',
            'create_test_location',
            'edit_test_location',
            'delete_test_location',

            // Branch
            'view_branches',
            'create_branch',
            'edit_branch',
            'delete_branch',

            // Position Quota
            'view_position_quotas',
            'manage_position_quotas',

            // Exam Numbers
            'generate_exam_numbers',
            'view_own_exam_number',

            // Reports
            'view_reports',
            'export_examinee_list',
            'export_statistics',
            'print_exam_card',

            // History
            'view_own_exam_history',
            'view_all_exam_history',

            // Audit
            'view_activity_log',
            'view_edit_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        /*
        |----------------------------------------------------------------------
        | Create roles & assign permissions
        |----------------------------------------------------------------------
        */

        // ── Examinee ──
        $examineeRole = Role::firstOrCreate(
            ['name' => 'examinee'],
            ['guard_name' => 'web']
        );
        $examineeRole->syncPermissions([
            'self_register',
            'view_own_dashboard',
            'view_own_profile',
            'edit_own_profile',
            'register_exam',
            'cancel_exam_registration',
            'view_border_areas',
            'view_exam_sessions',
            'view_test_locations',
            'view_branches',
            'view_position_quotas',
            'view_own_exam_number',
            'view_own_exam_history',
            'print_exam_card',
        ]);

        // ── Staff (full control) ──
        $staffRole = Role::firstOrCreate(
            ['name' => 'staff'],
            ['guard_name' => 'web']
        );
        $staffRole->syncPermissions($permissions); // ได้ทุก permission

        // ── Commander (read-only + reports) ──
        $commanderRole = Role::firstOrCreate(
            ['name' => 'commander'],
            ['guard_name' => 'web']
        );
        $commanderRole->syncPermissions([
            'view_commander_dashboard',
            'view_all_examinees',
            'view_border_areas',
            'view_border_area_history',
            'view_exam_sessions',
            'view_test_locations',
            'view_branches',
            'view_position_quotas',
            'view_reports',
            'export_examinee_list',
            'export_statistics',
            'view_all_exam_history',
            'view_activity_log',
            'view_edit_logs',
        ]);

        $this->command->info('✅ Roles seeded: examinee (' . $examineeRole->permissions->count() . '), staff (' . $staffRole->permissions->count() . '), commander (' . $commanderRole->permissions->count() . ')');
        $this->command->info('✅ Permissions seeded: ' . count($permissions) . ' permissions');
    }
}
