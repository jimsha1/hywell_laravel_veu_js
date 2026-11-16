<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private ProductService $productService;

    public function __construct(ProductService $ProductService)
    {
        $this->productService = $ProductService;
    }
    public function index()
    {
        return view('admin.pages.product.index');
    }
   public function productStore(Request $request)
{
    $request->merge([
        'unit_price' => number_format((float) $request->unit_price, 2, '.', ''),
        'selling_price' => number_format((float) $request->selling_price, 2, '.', ''),
    ]);

    if (empty($request->id)) {

        $this->validate($request, [
            'product_name' => 'required|unique:products,product_name',
            'unit_price' => 'required|decimal:2|min:0.01',
        ]);

        return $this->productService->product_store($request);

    } else {

        $this->validate($request, [
            'product_name' => 'required|unique:products,product_name,' . $request->id,
            'unit_price' => 'required|decimal:2|min:0.01',
        ]);

        return $this->productService->product_update($request);
    }
}
    public function productList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->productService->getAllProduct($request, $platform);
    }
    public function productDelete(Request $request)
    {
        return $this->productService->productDelete($request);
    }
}
