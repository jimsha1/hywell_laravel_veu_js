<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\SpecialPriceService;
use Illuminate\Http\Request;

class SpecialPriceController extends Controller
{
    private SpecialPriceService $specialPriceService;

    public function __construct(SpecialPriceService $SpecialPriceService)
    {
        $this->specialPriceService = $SpecialPriceService;
    }
    public function index()
    {
        return view('admin.pages.special_price.index');
    }
    public function specialPriceStore(Request $request)
    {
        if (empty($request->id)) {
            $this->validate($request, [
                'product_id' => 'required',
            ]);
            return $this->specialPriceService->specialPriceStore($request);
        } else {
            $this->validate($request, [
                'product_id' => 'required',
            ]);
            return $this->specialPriceService->specialPriceUpdate($request);
        }
    }
    public function specialPriceList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->specialPriceService->specialPriceList($request, $platform);
    }
    public function specialPriceDelete(Request $request)
    {
        return $this->specialPriceService->specialPriceDelete($request);
    }
}
