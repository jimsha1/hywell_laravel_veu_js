<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createRole('superadmin');
        $this->createRole('customer');
        $this->createRole('sales_man');
        $this->createRole('sales_executive');

    }
    private function createRole($name){
        $role = Role::updateOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo(Permission::all());

    }
}
