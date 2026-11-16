<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\StockService;
use Illuminate\Http\Request;

class OpeningStockController extends Controller
{
    private StockService $stockService;

    public function __construct(StockService $StockService)
    {
        $this->stockService = $StockService;
    }
    public function index()
    {
        return view('admin.pages.open_stock.index');
    }
    public function openingStockStore(Request $request)
    {
        // dd($request);
        $this->validate($request, [
            'product_id' => 'required',
            'qty' => 'required|numeric|min:1',
        ]);
        return $this->stockService->opening_stock_store($request);
    }
    public function openingStockList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->stockService->getAllOpeningStock($request, $platform);
    }
}
