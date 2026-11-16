<?php

namespace App\Http\Controllers\admin\acl;

use App\Http\Controllers\Controller;
use App\Services\UserManagementService;
use Exception;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    private UserManagementService $userManagementService;
    public function __construct(UserManagementService $userManagementService)
    {
        $this->userManagementService = $userManagementService;
    }

    // Role Section
    public function roleIndex()
    {
        return $this->userManagementService->rolesIndex();
    }
    public function getRoles(Request $request)
    {
        return $this->userManagementService->getRoles($request);
    }
    public function roleStore(Request $request)
    {

        if (empty($request->role_id)) {
            $this->validate($request, [
                'name' => 'required',
                'status' => 'required',
            ]);
            return $this->userManagementService->roleStore($request);
        } else {
            $this->validate($request, [
                'name' => 'required',
                'status' => 'required',
            ]);
            return $this->userManagementService->roleUpdate($request);
        }
    }
    public function roleList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->userManagementService->getRoles($request, $platform);
    }

    // Permission Section
    public function permmsionIndex()
    {
        return $this->userManagementService->permmsionIndex();
    }

    public function getPermissions(Request $request)
    {
        $validatedData = $request->validate([
            'role_id' => 'nullable',
        ]);

        return $this->userManagementService->getPermissions($validatedData);
    }
    public function assignPermissionToRole(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'role_id' => 'required',
                'permission_id' => 'required',
            ]);
            return $this->userManagementService->assignPermissionToRole($validatedData);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }
    public function getRolePermissions(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'role_id' => 'required',
            ]);
            return $this->userManagementService->getRolePermissions($validatedData);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }
    public function getSelectedRoleInfo(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'role_id' => 'required',
            ]);
            return $this->userManagementService->getSelectedRoleInfo($validatedData);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    // User Section
    public function index()
    {
        return $this->userManagementService->manageUserIndex();
    }
    public function userStore(Request $request)
    {
        // dd($request);
        if (empty($request->id)) {
            $this->validate($request, [
                'full_name' => 'required',
                'email' => 'required|email:rfc,dns|unique:users,email',
                'password' => 'required|min:6',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
            return $this->userManagementService->user_store($request);
        } else {
            $this->validate($request, [
                'full_name' => 'required',
                // 'email' => 'required|email|unique:users,email,' . $request->id,
                'email' => 'required|email:rfc,dns|unique:users,email,' . $request->id,
                'password' => 'required',
            ]);
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profile_images', 'public');
                $request['profile_image'] = $path;
            }
            return $this->userManagementService->user_update($request);
        }
    }
    public function userDelete(Request $request)
    {
        return $this->userManagementService->userDelete($request);
    }
    public function getUsers(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->userManagementService->getUsers($request, $platform);
    }
    public function getSalesMan(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->userManagementService->getAllSalesMan($request, $platform);
    }

    // public function sendForgotOtp(Request $request)
    // {
    //     try {
    //         $validated = $request->validate([
    //             'email' => 'required',
    //         ]);
    //         return $this->userManagementService->sendForgotOtp($validated);
    //     } catch (Exception $e) {
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }

    // public function verifyForgotOtp(Request $request)
    // {
    //     try {
    //         $validated = $request->validate([
    //             'email' => 'required',
    //             'otp' => 'required',
    //         ]);
    //         return $this->userManagementService->verifyForgotOtp($validated);
    //     } catch (Exception $e) {
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }
    // public function changeForgotPassword(Request $request)
    // {
    //     try {
    //         $validated = $request->validate([
    //             'email' => 'required',
    //             'password' => 'required',
    //         ]);
    //         return $this->userManagementService->changeForgotPassword($validated);
    //     } catch (Exception $e) {
    //         return errorResponse(null, $e->getMessage(), 100, 500);
    //     }
    // }



}
