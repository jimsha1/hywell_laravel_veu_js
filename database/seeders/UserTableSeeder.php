<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserTableSeeder extends Seeder
{
    private $created_role_id = '';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create system defined roles
        // $this->createRole(1, 'superadmin', 'Super Admin');

        // // Create super admin
        // $this->createSuperAdmin(1, 'Super Admin', 'superadmin', 'admin@admin.com', 'admin');
        $this->changePassword('oxionwaterpoint@gmail.com', 'Aqua@63Oxion#26');
    }

    private function createRole($id, $name, $description)
    {
        $role = Role::updateOrCreate([
            'id' => $id
        ], [
            'name' => $name,
            'description' => $description,
            'system_defined' => 1,
        ]);

        $permissions = Permission::pluck('id', 'id')->all();

        $role->syncPermissions($permissions);

        $this->created_role_id = $role->id;
    }
    private function changePassword($email, $password)
    {
        $user = User::find(1);
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->save();
    }

    private function createSuperAdmin($id, $name, $user_name, $email, $password)
    {

        $user = User::updateOrCreate([
            'id' => $id
        ], [
            'name' => $name,
            'username' => $user_name,
            'email' => $email,
            'user_type' => $user_name,
            'password' => Hash::make($password),
            'password_without_hash' => $password
        ]);

        $user->assignRole('superadmin');
    }
}
