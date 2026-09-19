<?

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Muhammad',
            'Abdullah',
            'Ali',
            'Omar',
            'Uthman',
            'Abu Bakr',
            'Hamza',
            'Hassan',
            'Hussein',
            'Ahmad'
        ];

        $types = [
            'customer' => ['prefix' => '#', 'start' => 100, 'count' => 1, 'sub_category' => 'Customer'],
            'saraf' => ['prefix' => 'SA', 'start' => 100, 'count' => 1, 'sub_category' => 'Sarafi'],
            'bank' => ['prefix' => 'BA', 'start' => 100, 'count' => 1, 'sub_category' => 'Bank'],
            'cash' => ['prefix' => 'EX', 'start' => 100, 'count' => 1, 'sub_category' => 'Daily Expense'],
            'employee' => ['prefix' => 'EM', 'start' => 100, 'count' => 1, 'sub_category' => 'Employee'],
        ];

        // Loop through each account type and seed the data
        $index = 0;

        $cashAccountCode = $this->generateUniqueCode('CASH');
        Account::create([
            'name' => 'Main Cash',
            'code' => $cashAccountCode, // Use the unique code here
            'contact' => '077777777',
            'address' => 'Kabul, Afghanistan',
            'company' => null,
            'account_sub_category_id' => AccountSubCategory::where('name', 'Safe')->first()->id, // Example subcategory
            'is_active' => true,
            'created_by' => 1,
            'is_safe' => true,
            'bi_icon' => 'bi-cash', // Bootstrap icon for cash
            'bi_icon_color' => 'success', // Example color
            'profile_bg' => '#ffffff', // Background color
        ]);

        // foreach ($types as $type => $data) {
        //     // Fetch the appropriate subcategory for each type
        //     $accountSubCategory = AccountSubCategory::where('name', $data['sub_category'])->first();

        //     if (!$accountSubCategory) {
        //         // Optionally, you can create the subcategory if it doesn't exist
        //         $accountSubCategory = AccountSubCategory::create([
        //             'name' => $data['sub_category'],
        //             'account_category_id' => AccountCategory::where('name', ucfirst($type))->first()->id,
        //         ]);
        //     }

        //     // Generate unique account codes and create accounts
        //     for ($i = 0; $i < rand(1, $data['count']); $i++) {
        //         $uniqueCode = $this->generateUniqueCode($data['prefix']);

        //         Account::create([
        //             'name' => $names[$index % count($names)],
        //             'code' => $uniqueCode, // Use the unique code here
        //             'contact' => '0784006200',
        //             'address' => 'Kabul, Afghanistan',
        //             'company' => null,
        //             'account_sub_category_id' => $accountSubCategory->id, // Assign subcategory ID
        //             'created_by' => 1,
        //             'is_active' => true,
        //             'is_safe' => false,
        //             'bi_icon' => 'bi-person', // Optionally set Bootstrap icon (you can adjust this as needed)
        //             'bi_icon_color' => 'primary', // Example color for the icon
        //             'profile_bg' => '#f0f0f0', // Example background color
        //         ]);
        //         $index++;
        //     }
        // }


    }

    /**
     * Generate a unique code for the given prefix.
     *
     * @param string $prefix
     * @return string
     */
    private function generateUniqueCode($prefix)
    {
        // Find the highest code with the given prefix
        $maxCode = Account::where('code', 'LIKE', $prefix . '%')
            ->orderBy('code', 'desc')
            ->pluck('code')
            ->first();

        // Extract the numerical part of the code and increment it
        if ($maxCode) {
            $numericPart = (int) str_replace($prefix, '', $maxCode);
            $newNumericPart = $numericPart + 1;
            $newCode = $prefix . $newNumericPart;
        } else {
            // If no such code exists, start with the first number (100)
            $newCode = $prefix . '100';
        }

        return $newCode;
    }
}
