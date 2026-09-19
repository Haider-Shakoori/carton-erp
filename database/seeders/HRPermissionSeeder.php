<?php
// database/seeders/HRPermissionSeeder.php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class HRPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // HR Dashboard
            ['name' => 'view hr dashboard', 'guard_name' => 'web', 'module' => 'hr'],

            // Employee Management
            ['name' => 'view employees', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'create employees', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'edit employees', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'delete employees', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'view departments', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'create departments', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'edit departments', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'delete departments', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'view designations', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'create designations', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'edit designations', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'delete designations', 'guard_name' => 'web', 'module' => 'hr'],

            // Attendance Management
            ['name' => 'view attendance', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'create attendance', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'edit attendance', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'delete attendance', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'view attendance report', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'export attendance', 'guard_name' => 'web', 'module' => 'hr'],

            // Leave Management
            ['name' => 'view leave requests', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'create leave requests', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'edit leave requests', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'delete leave requests', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'approve leave requests', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'reject leave requests', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'view leave types', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'create leave types', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'edit leave types', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'delete leave types', 'guard_name' => 'web', 'module' => 'hr'],

            // Advances & Loans
            ['name' => 'view employee advances', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'create employee advances', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'edit employee advances', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'delete employee advances', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'approve employee advances', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'reject employee advances', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'deduct employee advances', 'guard_name' => 'web', 'module' => 'hr'],

            // Payroll Management
            ['name' => 'view payroll', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'create payroll', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'edit payroll', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'delete payroll', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'process payroll', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'pay payroll', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'export payroll', 'guard_name' => 'web', 'module' => 'hr'],

            // HR Reports
            ['name' => 'view hr reports', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'view employee reports', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'view attendance reports', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'view payroll reports', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'view leave reports', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'view advances reports', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'export reports', 'guard_name' => 'web', 'module' => 'hr'],

            // HR Settings
            ['name' => 'view hr settings', 'guard_name' => 'web', 'module' => 'hr'],
            ['name' => 'edit hr settings', 'guard_name' => 'web', 'module' => 'hr'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']]
            );
        }
    }
}
