<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\GlobalTaxService;
use Illuminate\Http\Request;

class GlobalTaxController extends Controller
{
    private GlobalTaxService $globalTaxService;

    public function __construct(GlobalTaxService $GlobalTaxService)
    {
        $this->globalTaxService = $GlobalTaxService;
    }
    public function index()
    {
        return view('admin.pages.global_tax.index');
    }
    public function taxStore(Request $request)
    {
        if (empty($request->id)) {
            $this->validate($request, [
                'name' => 'required|unique:global_taxes,name,NULL,id,deleted_at,NULL',
            ]);
            return $this->globalTaxService->tax_store($request);
        } else {
            $this->validate($request, [
                'name' => 'required|unique:global_taxes,name,' . $request->id . ',id,deleted_at,NULL',
            ]);
            return $this->globalTaxService->tax_update($request);
        }
    }
    public function taxList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->globalTaxService->getAllTax($request, $platform);
    }
    public function taxDelete(Request $request)
    {
        return $this->globalTaxService->taxDelete($request);
    }
}
