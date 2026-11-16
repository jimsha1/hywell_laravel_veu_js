<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // "authorization"
        $this->createPermission(1, 'authorization', 'create_user', 'Create user');
        $this->createPermission(2, 'authorization', 'edit_user', 'Edit user');
        $this->createPermission(3, 'authorization', 'delete_user', 'Delete user');
        $this->createPermission(4, 'authorization', 'create_role', 'Create role');
        $this->createPermission(5, 'authorization', 'delete_role', 'Delete role');
        // "master_data"
        $this->createPermission(6, 'master_data', 'suppliers', 'Suppliers');
        $this->createPermission(7, 'master_data', 'product_category', 'Product Category');
        $this->createPermission(8, 'master_data', 'products', 'Product');
        $this->createPermission(9, 'master_data', 'vehicles', 'Vehicles');
        $this->createPermission(10, 'master_data', 'special_price', 'Special Price');
        // "customer"
        $this->createPermission(11, 'customer', 'customers', 'Customers');
        // "purchase"
        $this->createPermission(12, 'purchases', 'purchase', 'Purchase');
        // "approve load"
        $this->createPermission(13, 'approve_load', 'approve_load', 'Approve Load');

        // "Reports"
        $this->createPermission(14, 'report', 'open_close_report', 'Open Close Report');
        $this->createPermission(15, 'report', 'purchase_report', 'Purchase Report');
        $this->createPermission(16, 'report', 'order_report', 'Order Report');
        $this->createPermission(17, 'report', 'sales_report', 'Sales Report');
        $this->createPermission(18, 'report', 'stock_report', 'Stock Report');
        $this->createPermission(19, 'report', 'commission_report', 'Commission Report');

        // "return load"
        $this->createPermission(20, 'return_load', 'return_load', 'Return Load');
        // "open stock"
        $this->createPermission(21, 'open_stock', 'open_stock', 'Open Stock');
    }
    private function createPermission($id, $group, $name, $description)
    {
        Permission::updateOrCreate([
            'id' => $id
        ], [
            'name' => $name,
            'description' => $description,
            'group' => $group,
        ]);
    }
}
