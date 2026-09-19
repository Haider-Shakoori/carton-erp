<?

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;

class AccountSubCategorySeeder extends Seeder
{
    public function run()
    {
        // Example subcategories for the "Customer" category
        $customerCategory = AccountCategory::where('name', 'Client')->first();

        AccountSubCategory::create([
            'name' => 'Customer',
            'account_category_id' => $customerCategory->id,
        ]);
        AccountSubCategory::create([
            'name' => 'Saraf',
            'account_category_id' => $customerCategory->id,
        ]);
        AccountSubCategory::create([
            'name' => 'Bank',
            'account_category_id' => $customerCategory->id,
        ]);

        // Example subcategories for the "Expense" category
        $expenseCategory = AccountCategory::where('name', 'Cash')->first();

        AccountSubCategory::create([
            'name' => 'Safe',
            'account_category_id' => $expenseCategory->id,
        ]);

        // Example subcategories for the "Employee" category
        $employeeCategory = AccountCategory::where('name', 'Employee')->first();

        AccountSubCategory::create([
            'name' => 'Office Employee',
            'account_category_id' => $employeeCategory->id,
        ]);
    }
}
