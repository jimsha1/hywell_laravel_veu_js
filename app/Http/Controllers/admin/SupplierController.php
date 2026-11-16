<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\SupplierService;
use Exception;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    private SupplierService $supplierService;

    public function __construct(SupplierService $SupplierService)
    {
        $this->supplierService = $SupplierService;
    }
    public function index()
    {
        return view('admin.pages.supplier.index');
    }
    public function supplierStore(Request $request)
    {

        if (empty($request->id)) {
            $this->validate($request, [
                'supplier_name' => 'required',
                'phone' => 'required|unique:suppliers,phone,NULL,id,deleted_at,NULL',
                'commercial_reg_no' => 'nullable|unique:suppliers,commercial_reg_no,NULL,id,deleted_at,NULL',
                'email' => 'nullable|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ], [
                'email.regex' => 'Enter valid email address',
            ]);
            return $this->supplierService->supplier_store($request);
        } else {
            $this->validate($request, [
                'supplier_name' => 'required',
                'phone' => 'required|unique:suppliers,phone,' . $request->id . ',id,deleted_at,NULL',
                'commercial_reg_no' => 'nullable|unique:suppliers,commercial_reg_no,' . $request->id . ',id,deleted_at,NULL',
                'email' => 'nullable|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ], [
                'email.regex' => 'Enter valid email address',
            ]);
            return $this->supplierService->supplier_update($request);
        }
    }
    public function supplierList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->supplierService->getAllSupplier($request, $platform);
    }
    public function supplierDelete(Request $request)
    {
        return $this->supplierService->supplierDelete($request);
    }
}
