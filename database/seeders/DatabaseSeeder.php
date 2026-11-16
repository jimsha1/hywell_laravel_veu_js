<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    public function run(): void
    {
        // $this->call(PermissionSeeder::class);
        // $this->call(UserTableSeeder::class);
        $this->call(AutoIncrementMasterSeeder::class);


        // User::factory()->create([ 
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Seed accounting module
        $this->call([
            AccountTypeSeeder::class,
            AccountSeeder::class,
            // ExpenseCategorySeeder::class,
        ]);
    }
}
