<?php

use App\Http\Controllers\admin\acl\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::controller(UserManagementController::class)->group(function(){
    Route::get('get-permissions','getPermissions');
    Route::post('assign-permission-to-role','assignPermissionToRole');
    Route::get('get-role-permissions','getRolePermissions');
    Route::get('get-selected-role-info','getSelectedRoleInfo');
});
