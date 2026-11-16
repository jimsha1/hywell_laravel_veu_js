<?php

namespace Database\Seeders;

use App\Models\AutoIncrementMaster;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AutoIncrementMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createAutoIncrement(1,'AI_SUPPLIER_CODE', 'supplier code', 'SUP', 101);
        $this->createAutoIncrement(2,'AI_CUSTOMER_CODE', 'customer code', 'CUS', 101);
        $this->createAutoIncrement(3,'AI_PRODUCT_CODE', 'product code', 'PRO', 101);
        $this->createAutoIncrement(4,'AI_VEHICLE_CODE', 'vehicle code', 'VEH', 101);
        $this->createAutoIncrement(5,'AI_CATEGORY_CODE', 'category code', 'CAT', 101);
        $this->createAutoIncrement(6,'AI_V_STOCK_CODE', 'Vstock code', 'VSK', 101);
        $this->createAutoIncrement(7,'AI_ORDER_CODE', 'order code', 'ORD', 1001);
        $this->createAutoIncrement(8,'AI_SALES_RETURN_CODE', 'sales return code', 'SR', 1001);
        $this->createAutoIncrement(16,'AI_PURCHASE_RETURN_CODE', 'purchase return code', 'PR', 1001);
        // $this->createAutoIncrement(9, 'AI_CUSTOMER_LEDGER_CODE', 'customer ledger code', 'CL', 1400100);
        // $this->createAutoIncrement(10, 'AI_SUPPLIER_LEDGER_CODE', 'supplier ledger code', 'SL', 3100100);

        // $this->createAutoIncrement(11, 'AI_EMPLOYEE_LEDGER_CODE', 'employee ledger code', 'EL', 9100100);

        //  $this->createAutoIncrement(12,'AI_EMPLOYEE_CODE', 'employee code', 'EMP', 101);

        //  $this->createAutoIncrement(13,'AI_EMPLOYEE_COMMISION_CODE', 'employee commission code', 'CMN', 12000101);

        // $this->createAutoIncrement(13, 'AI_EXPENSE_CODE', 'expense code', 'EXP', 101);
        // $this->createAutoIncrement(15, 'AI_VOUCHER_CODE', 'voucher code', 'VOU', 101);
        // $this->createAutoIncrement(14, 'AI_LEDGER_CODE', 'normal ledger code', 'A', 1001);
    }
    private function createAutoIncrement($id, $auto_code, $auto_name, $auto_prefix, $start_value)
    {
        $result = AutoIncrementMaster::updateOrCreate([
            'id' => $id
        ], [
            'auto_code' => $auto_code,
            'auto_name' => $auto_name,
            'auto_prefix' => $auto_prefix,
            'auto_increment_general_value' => $start_value,
        ]);
    }
}
