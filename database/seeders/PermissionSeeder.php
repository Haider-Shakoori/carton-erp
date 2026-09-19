<?php
// database/seeders/PermissionSeeder.php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // ============================================================
            // DASHBOARD
            // ============================================================
            'view dashboard',

            // ============================================================
            // SALES
            // ============================================================
            'view sales',
            'create sales',
            'edit sales',
            'delete sales',
            'view sale-returns',
            'create sale-returns',
            'edit sale-returns',
            'delete sale-returns',
            'view customers',
            'create customers',
            'edit customers',
            'delete customers',

            // ============================================================
            // PRODUCTION (NEW)
            // ============================================================
            'view bom',
            'create bom',
            'edit bom',
            'delete bom',
            'view production-planning',
            'view production-orders',
            'create production-orders',
            'edit production-orders',
            'delete production-orders',
            'view work-orders',
            'create work-orders',
            'edit work-orders',
            'delete work-orders',
            'view quality',
            'create quality',
            'edit quality',
            'delete quality',

            // ============================================================
            // INVENTORY & PURCHASING
            // ============================================================
            'view purchase-orders',
            'create purchase-orders',
            'edit purchase-orders',
            'delete purchase-orders',
            'view stock',
            'view products',
            'create products',
            'edit products',
            'delete products',
            'view stock-movements', // NEW

            // ============================================================
            // FINANCE
            // ============================================================
            'view transactions',
            'create transactions',
            'edit transactions',
            'delete transactions',
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',
            'view cost-analysis', // NEW

            // ============================================================
            // PARTNERS
            // ============================================================
            'view suppliers',
            'create suppliers',
            'edit suppliers',
            'delete suppliers',
            'view agents',
            'create agents',
            'edit agents',
            'delete agents',
            'view sarafs',
            'create sarafs',
            'edit sarafs',
            'delete sarafs',

            // ============================================================
            // REPORTS (NEW)
            // ============================================================
            'view production-reports',
            'view inventory-reports',
            'view financial-reports',

            // ============================================================
            // HR MODULE PERMISSIONS (NEW)
            // ============================================================
            // Employees
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'export employees',
            'import employees',

            // Attendance
            'view attendance',
            'create attendance',
            'edit attendance',
            'delete attendance',
            'export attendance',

            // Leave Types
            'view leave-types',
            'create leave-types',
            'edit leave-types',
            'delete leave-types',

            // Leave Requests
            'view leave-requests',
            'create leave-requests',
            'edit leave-requests',
            'delete leave-requests',
            'approve leave-requests',
            'reject leave-requests',

            // Employee Advances
            'view employee-advances',
            'create employee-advances',
            'edit employee-advances',
            'delete employee-advances',
            'approve employee-advances',
            'reject employee-advances',

            // Payroll
            'view payroll',
            'create payroll',
            'edit payroll',
            'delete payroll',
            'process payroll',
            'export payroll',

            // HR Reports
            'view hr-reports',
            'export hr-reports',

            // ============================================================
            // ADMINISTRATION
            // ============================================================
            'view users',
            'create users',
            'edit users',
            'delete users',
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',
            'view currencies',
            'create currencies',
            'edit currencies',
            'delete currencies',
            'view account-categories',
            'create account-categories',
            'edit account-categories',
            'delete account-categories',
            'view account-sub-categories',
            'create account-sub-categories',
            'edit account-sub-categories',
            'delete account-sub-categories',
            'view settings',
            'edit settings',
            'view audit',

            // ============================================================
            // APP SETTINGS (NEW/Expanded)
            // ============================================================
            'view app-settings',
            'view company',
            'edit company',
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            'view units',
            'create units',
            'edit units',
            'delete units',
            'view invoice-templates',
            'create invoice-templates',
            'edit invoice-templates',
            'delete invoice-templates',
            'view bom-settings',
            'edit bom-settings',
        ];

        // Insert permissions if they don't exist
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
        }

        $this->command->info('Permissions seeded successfully!');
    }
}
