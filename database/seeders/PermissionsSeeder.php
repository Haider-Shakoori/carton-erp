<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Define modules and permissions
        $modules = [
            // ============================================================
            // DASHBOARD
            // ============================================================
            'dashboard' => ['view'],

            // ============================================================
            // SALES
            // ============================================================
            'customers' => ['view', 'create', 'update', 'delete', 'print', 'send-whatsapp'],
            'sales' => ['view', 'create', 'update', 'delete'],
            'sale returns' => ['view', 'create', 'update', 'delete'],

            // ============================================================
            // PRODUCTION (NEW)
            // ============================================================
            'bom' => ['view', 'create', 'update', 'delete'],
            'production planning' => ['view'],
            'production orders' => ['view', 'create', 'update', 'delete'],
            'work orders' => ['view', 'create', 'update', 'delete'],
            'quality' => ['view', 'create', 'update', 'delete'],

            // ============================================================
            // INVENTORY & PURCHASING
            // ============================================================
            'suppliers' => ['view', 'create', 'update', 'delete', 'print', 'send-whatsapp'],
            'purchase orders' => ['view', 'create', 'update', 'delete', 'print', 'send-whatsapp'],
            'stock' => ['view', 'create', 'update', 'delete'],
            'products' => ['view', 'create', 'update', 'delete', 'print', 'send-whatsapp'],
            'stock movements' => ['view'], // NEW
            'stock reconciliations' => ['view', 'create', 'update', 'submit', 'approve', 'post', 'cancel'],

            // ============================================================
            // FINANCE
            // ============================================================
            'transactions' => ['view', 'create', 'update', 'delete', 'export', 'print'],
            'expenses' => ['view', 'create', 'update', 'delete'],
            'cost analysis' => ['view'], // NEW

            // ============================================================
            // EXCHANGE & REMITTANCES
            // ============================================================
            'remittances' => ['view', 'create', 'update', 'delete', 'approve'],
            'exchange rates' => ['view', 'create', 'update', 'delete'],
            'exchange purchase' => ['view', 'create', 'update', 'delete', 'approve', 'print'],
            'exchange sales' => ['view', 'create', 'update', 'delete', 'print'],

            // ============================================================
            // PARTNERS
            // ============================================================
            'agents' => ['view', 'create', 'update', 'delete', 'print', 'send-whatsapp'],
            'sarafs' => ['view', 'create', 'update', 'delete'],

            // ============================================================
            // REPORTS (NEW)
            // ============================================================
            'production reports' => ['view'],
            'inventory reports' => ['view'],
            'financial reports' => ['view'],
            'reports' => ['view', 'create', 'update', 'delete'],

            // ============================================================
            // ACCOUNTING
            // ============================================================
            'journal' => ['view', 'delete', 'export', 'print', 'send-whatsapp'],
            'creditors' => ['view', 'create', 'update', 'delete'],
            'debtors' => ['view', 'create', 'update', 'delete'],
            'account categories' => ['view', 'create', 'update', 'delete'],
            'account sub categories' => ['view', 'create', 'update', 'delete'],

            // ============================================================
            // ADMINISTRATION
            // ============================================================
            'users' => ['view', 'create', 'update', 'delete'],
            'roles' => ['view', 'create', 'update', 'delete'],
            'permissions' => ['view', 'create', 'update', 'delete'],
            'currencies' => ['view', 'create', 'update', 'delete'],
            'settings' => ['view', 'update'],
            'audit logs' => ['view'],

            // ============================================================
            // APP SETTINGS (NEW/Expanded)
            // ============================================================
            'app settings' => ['view'],
            'company settings' => ['view', 'update'],
            'categories' => ['view', 'create', 'update', 'delete'],
            'units' => ['view', 'create', 'update', 'delete'],
            'invoice templates' => ['view', 'create', 'update', 'delete', 'print'],
            'bom settings' => ['view', 'update'], // NEW

            // ============================================================
            // PROFIT & COST
            // ============================================================
            'profit' => ['view'],
            'cost rate' => ['view'],
            'customers reports' => ['view'],

            // ============================================================
            // ADDITIONAL PERMISSIONS FOR NEW MENU ITEMS
            // ============================================================
            // These are specific permissions needed for the new sidebar items
            'exchange rates' => ['view', 'create', 'update', 'delete'],
            'quality control' => ['view'],
            'saraf' => ['view'],
        ];

        // Create permissions per module and action
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action} {$module}"]);
            }
        }

        // Create admin user
        $admin = User::updateOrCreate(
            ['username' => 'superadmin'],
            [
                'id' => 1,
                'name' => 'Hasibullah',
                'account_type' => 'admin',
                'created_by' => 1,
                'password' => Hash::make('password123'), // change in prod
            ]
        );

        $admin->assignRole($adminRole);
        $user = User::find(1);
        $user->syncPermissions(Permission::all());

        // Create default settings
        Setting::create([
            'company_name' => 'Rahe Arya Transit Company',
            'logo' => 'logo.png',
            'contact' => '+93777209514',
            'email' => 'info@rahe-arya.com',
            'address' => 'Kabul, Afghanistan',
            'note_en' => 'Please check and confirm the sent balance.',
            'note_fa' => 'لطف نموده بیلانس را بررسي و از صحت آن اطمینان حاصل نمایید.',
            'note_ps' => 'مهرباني وکړئ د لېږدول شوي بیلانس د صحت په اړه خپل اطمینان حاصل کړئ.',
            'default_language' => 'en',
            'currency' => 'USD',
        ]);

        $this->command->info('All permissions and roles seeded successfully!');
    }
}
