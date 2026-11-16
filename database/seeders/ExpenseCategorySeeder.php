<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Salaries & Wages',
                'code' => 'SAL',
                'account_code' => '9100',
                'description' => 'Employee salaries and wages',
            ],
            [
                'name' => 'Rent',
                'code' => 'RENT',
                'account_code' => '9200',
                'description' => 'Office or facility rent',
            ],
            [
                'name' => 'Utilities',
                'code' => 'UTIL',
                'account_code' => '9300',
                'description' => 'Electricity, water, internet, etc.',
            ],
            [
                'name' => 'Marketing & Advertising',
                'code' => 'MKTG',
                'account_code' => '9400',
                'description' => 'Marketing and advertising expenses',
            ],
            [
                'name' => 'Office Supplies',
                'code' => 'OFFC',
                'account_code' => '9000',
                'description' => 'Office supplies and stationery',
            ],
            [
                'name' => 'Travel & Transportation',
                'code' => 'TRVL',
                'account_code' => '9000',
                'description' => 'Travel and transportation expenses',
            ],
            [
                'name' => 'Maintenance & Repairs',
                'code' => 'MAINT',
                'account_code' => '9000',
                'description' => 'Equipment and facility maintenance',
            ],
            [
                'name' => 'Insurance',
                'code' => 'INS',
                'account_code' => '9000',
                'description' => 'Insurance premiums',
            ],
            [
                'name' => 'Professional Fees',
                'code' => 'PROF',
                'account_code' => '9000',
                'description' => 'Legal, accounting, consulting fees',
            ],
            [
                'name' => 'Miscellaneous',
                'code' => 'MISC',
                'account_code' => '9000',
                'description' => 'Other miscellaneous expenses',
            ],
        ];

        foreach ($categories as $categoryData) {
            $accountCode = $categoryData['account_code'];
            unset($categoryData['account_code']);

            // Find the account
            $account = Account::where('code', $accountCode)->first();

            ExpenseCategory::updateOrCreate(
                ['code' => $categoryData['code']],
                array_merge($categoryData, [
                    'account_id' => $account ? $account->id : null,
                    'is_active' => true,
                ])
            );
        }
    }
}




