<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use Exception;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Calculation\Category;

class ProductCategoryController extends Controller
{
    private CategoryService $categoryService;

    public function __construct(CategoryService $CategoryService)
    {
        $this->categoryService = $CategoryService;
    }
    public function index()
    {
        return view('admin.pages.product_category.index');
    }
    public function categoryStore(Request $request)
    {

        if (empty($request->id)) {
            $this->validate($request, [
                'category_name' => 'required',
            ]);
            return $this->categoryService->category_store($request);
        } else {
            $this->validate($request, [
                'category_name' => 'required',
            ]);
            return $this->categoryService->category_update($request);
        }
    }
    public function categoryList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->categoryService->getAllCategory($request, $platform);
    }
    public function categoryDelete(Request $request)
    {
        return $this->categoryService->categoryDelete($request);
    }
}
