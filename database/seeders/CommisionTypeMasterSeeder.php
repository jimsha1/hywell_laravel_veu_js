<?php

namespace Database\Seeders;

use App\Models\CommisionTypeMaster;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommisionTypeMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createCommission(1, 'Wholesale', 'executive', '0.01');
        $this->createCommission(2, 'Wholesale', 'sales_man', '0.01');
        $this->createCommission(3, 'Retail', 'executive', '0.01');
        $this->createCommission(4, 'Retail', 'sales_man', '0.01');
        $this->createCommission(5, 'Office', 'sales_man', '0.01');
    }
    private function createCommission($id, $name, $type, $commission)
    {
        CommisionTypeMaster::updateOrCreate([
            'id' => $id
        ], [
            'name' => $name,
            'type' => $type,
            'commision' => $commission,
        ]);
    }
}
