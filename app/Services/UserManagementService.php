<?php

namespace App\Services;

use App\Mail\MailVerifyOtp;
use App\Models\Account;
use App\Models\AutoIncrementMaster;
use App\Models\PasswordReset;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;


class UserManagementService
{
    // Role Section
    public function rolesIndex()
    {
        return view('admin.pages.user_management.role.index');
    }
    public function roleStore($request)
    {
        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request['name'],
                'guard_name' => 'web',
                'status' => $request['status']
            ]);
            DB::commit();
            return successResponse($role, 'Role Created successfully.', 0, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function roleUpdate($request)
    {

        try {
            DB::beginTransaction();

            $role = Role::find($request['role_id']);
            if (!$role) {
                return apiResponse(1, 'Role not found', null);
            }
            $role->update([
                'name' => $request['name'],
                'guard_name' => 'web',
                'status' => $request['status']
            ]);

            DB::commit();
            return successResponse($role, 'Role updated successfully.', 0, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function getRoles($data, $platform = 'web')
    {
        try {
            $query = Role::query();

            if ($data->search) {
                $query->where(function ($q) use ($data) {
                    $q->where('name', 'LIKE', "%{$data->search}%");
                });
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'roles'  => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Roles retrieved successfully.', 0, 200);
            } else {
                $result = $query->orderBy('created_at', 'DESC')->get();
                return successResponse($result, 'Roles retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function getSelectedRoleInfo($data)
    {
        try {
            $role = Role::find($data['role_id']);
            if (!$role) {
                return errorResponse(null, 'Role not found', 1);
            }
            return successResponse($role, 'Role retrieved successfully', 0);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    // Permmision Section
    public function permmsionIndex()
    {
        return view('admin.pages.user_management.permission.index');
    }
    public function getPermissions($data)
    {
        try {
            $query = Permission::query();

            $permissions = $query->select('id', 'name', 'group', 'description')
                ->get()
                ->groupBy('group');
            return successResponse($permissions, 'success.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function assignPermissionToRole($data)
    {
        try {
            $role = Role::find($data['role_id']);
            if (!$role) {
                return errorResponse(null, 'Role not found', 1);
            }

            $role->syncPermissions($data['permission_id']);

            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            return successResponse($role, 'Permission assigned successfully', 0);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function getRolePermissions($data)
    {
        try {
            $role = Role::find($data['role_id']);
            if (!$role) {
                return errorResponse(null, 'Role not found', 1);
            }
            $permissions = $role->permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ];
            });
            return successResponse($permissions, 'Permissions retrieved successfully', 0);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    // User Section
    public function manageUserIndex()
    {
        return view('admin.pages.user_management.user.index');
    }
    public function user_store($data = null, $platform = 'web')
    {
        try {
            DB::beginTransaction();

            $role = Role::find($data['role_id']);
            if (!$role) {
                return errorResponse(null, 'Role does not exist', 100, 500);
            }

            $user = User::create([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'password_without_hash' => $data['password'],
                'user_type' => $role->name,
                'status' => $data['status'],
            ]);

            if ($data->hasFile('profile_image')) {
                $file = $data->file('profile_image');
                $ext = $file->extension();
                $filename = 'user_' . preg_replace('/\s+/', '', $data['full_name']) . '-' . time() . '.' . $ext;
                $file->storeAs('uploads', $filename, ['disk' => 'public']);

                $user->profile_image = 'uploads/' . $filename;
                $user->save();
            }


            $user->assignRole($role);

            if ($role->name =='sales_executive' || $role->name == 'salesman') {
                $codeAuto = AutoIncrementMaster::where('auto_code', 'AI_EMPLOYEE_LEDGER_CODE')->first();
                $code = $codeAuto->auto_increment_general_value;
                Account::create([
                    'account_type_id' => 2,
                    'name' => $user->name . '_' . $user->id,
                    'parent_id' => 24,
                    'description' => $user->name . '_' . $user->id . ' payable',
                    'opening_balance' => 0,
                    'reference_id' => $user->id,
                    'reference_type'=>'employee',
                    'is_active' => 1,
                    'is_system' => 1,
                    'code' => $code
                ]);
                $code = $code + 1;
                $codeAuto->auto_increment_general_value = $code;
                $codeAuto->save();
            }
            DB::commit();
            return successResponse($user, 'Added Successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function user_update($request)
    {
        // dd($request);
        try {
            $user = User::find($request['id']);
            if (!$user) {
                return errorResponse(null, 'User not found', 101);
            }
            if ($user->email !== $request['email']) {
                $existingUser = User::where('email', $request['email'])->where('id', '!=', $user->id)->exists();
                if ($existingUser) {
                    return errorResponse(null, 'Email already exists', 101);
                }
            }
            DB::beginTransaction();
            $role = Role::find($request['role_id']);
            if (!$role) {
                return errorResponse(null, 'Role does not exist', 101);
            }

            $user->name = $request['full_name'];
            $user->email = $request['email'];
            $user->password = Hash::make($request['password']);
            $user->password_without_hash = $request['password'];
            $user->user_type = $role->name;
            $user->status = $request['status'] ?? 1;
            $user->profile_image = $request['profile_image'];

            if ($request->hasFile('profile_image')) {
                $file = $request->file('profile_image');
                $ext = $file->extension();
                $filename = 'user_' . preg_replace('/\s+/', '', $request['full_name']) . '-' . time() . '.' . $ext;
                $file->storeAs('uploads', $filename, ['disk' => 'public']);

                $user->profile_image = 'uploads/' . $filename;
            }

            $user->save();
            $user->syncRoles([$role->name]);


            DB::commit();
            return successResponse($user, 'User updated successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function getUsers($data, $platform = 'web')
    {

   
        try {
            $query = User::with('roles')->whereIn('user_type', ['superadmin', 'sales_man', 'salesman', 'sales_executive','outside_salesman']);

        
            if ($data->filled('user_type')) {
               
                $types = $data->user_type;
                if (is_array($types)) {
                    $query->whereIn('user_type', $types);
                } else {
                    $query->where('user_type', $types);
                }
            }

            if ($data->filled('employee_type')) {
               
                $types = $data->employee_type;
                if (is_array($types)) {
                    $query->whereIn('user_type', $types);
                } else {
                    $query->where('user_type', $types);
                }
            }
            if ($data->search) {
                $query->where(function ($query) use ($data) {
                    $query->where('name', 'like', '%' . $data->search . '%')
                        ->orWhere('user_type', 'like', '%' . $data->search . '%');
                });
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'users'  => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'User retrieved successfully.', 0, 200);
            } else {
                $result = $query->where('status', 1)->orderBy('created_at', 'DESC')->get();
                return successResponse($result, 'User retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function userDelete($data)
    {
        try {
            DB::beginTransaction();
            $user = User::findOrFail($data->id);
            if ($user) {
                $user->deleted_by = $data->user_id;
                $user->save();
                $user->delete();
                DB::commit();
                return successResponse(null, 'User deleted successfully.', 0, 200);
            } else {
                return errorResponse(null, 'User not found', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }



    public function sendForgotOtp($request)
    {
        try {
            $user = User::where('email', $request['email'])->first();
            if (!$user) {
                return errorResponse(null, 'User not found.', 100, 200);
            }


            $otp = rand(100000, 999999); // Generate 6-digit OTP


            PasswordReset::create([
                "user_id" => $user['id'],
                "otp" => $otp,
            ]);

            // Send email
            $mail = Mail::to($request['email'])->send(new MailVerifyOtp($otp));

            return successResponse(null, 'Send Otp successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }


    public function verifyForgotOtp($request)
    {
        try {
            $user = User::where('email', $request['email'])->first();
            if (!$user) {
                return errorResponse(null, 'User not found.', 100, 200);
            }

            $otpRecord = PasswordReset::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$otpRecord) {
                return errorResponse(null, 'OTP not found.', 100, 200);
            }

            // Check if OTP matches
            if ($otpRecord->otp != $request['otp']) {
                return errorResponse(null, 'Invalid OTP.', 100, 200);
            }

            // Check if OTP is expired - e.g., valid for 10 minutes
            $expiresAt = $otpRecord->created_at->addMinutes(10);
            if (now()->greaterThan($expiresAt)) {
                return errorResponse(null, 'OTP has expired.', 100, 200);
            }


            return successResponse(null, 'OTP verified successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }

    public function changeForgotPassword($request)
    {
        try {
            $user = User::where('email', $request['email'])->first();
            if (!$user) {
                return errorResponse(null, 'User not found.', 100, 200);
            }

            $user->update([
                'password' => Hash::make($request['password']),
            ]);


            PasswordReset::where('user_id', $user->id)->delete();

            return successResponse(null, 'Password changed successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
    public function getAllSalesMan($data, $platform = 'web')
    {
        try {
            $query = User::where('user_type', 'salesman')
                ->where('status', 1)
                ->orderBy('created_at', 'DESC');

            if ($platform === 'web') {
                $result = $query->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'salesMan' => $result->items(),
                    'pagination' => generatePaginationData($result)
                ];
            } else {
                $response = $query->get();
            }

            return successResponse($response, 'Sales Man retrieved successfully.', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
}
