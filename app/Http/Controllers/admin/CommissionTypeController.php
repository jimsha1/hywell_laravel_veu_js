<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\CommissionTypeService;
use Illuminate\Http\Request;

class CommissionTypeController extends Controller
{
    private CommissionTypeService $commissionTypeService;

    public function __construct(CommissionTypeService $CommissionTypeService)
    {
        $this->commissionTypeService = $CommissionTypeService;
    }
    public function index()
    {
        return view('admin.pages.commission_type.index');
    }
    public function commissionTypeStore(Request $request)
    {

        if (empty($request->id)) {
            $this->validate($request, [
                'name' => 'required',
            ]);
            return $this->commissionTypeService->commission_type_store($request);
        } else {
            $this->validate($request, [
                'name' => 'required',
            ]);
            return $this->commissionTypeService->commission_type_update($request);
        }
    }
    public function commissionTypeList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->commissionTypeService->getAllCommissionType($request, $platform);
    }
}
