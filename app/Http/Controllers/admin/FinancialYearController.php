<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\FinancialYear;
use App\Services\FinancialYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FinancialYearController extends Controller
{
    private FinancialYearService $financialYearService;

    public function __construct(FinancialYearService $financialYearService)
    {
        $this->financialYearService = $financialYearService;
    }
    public function index()
    {
        return view('admin.pages.financial_years.index');
    }
    public function financialYearList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->financialYearService->getAllFinancialYears($request, $platform);
        // try {
        //     $financialYears = FinancialYear::orderBy('start_date', 'desc')->get();
        //     return successResponse($financialYears, 'Financial years retrieved successfully', 0, 200);
        // } catch (\Exception $e) {
        //     return errorResponse(null, $e->getMessage(), 500);
        // }
    }


    /**
     * Store a newly created account in storage.
     */
    public function financialYearStore(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([

                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'status' => 'boolean',
            ]);

            $financialYear = $this->financialYearService->createFinancialYear(
                $validatedData['start_date'],
                $validatedData['end_date'],
                $validatedData['name'] ?? null,
                $validatedData['code'] ?? null,
                $request->boolean('status'),
            );

            return response()->json([
                'status' => 'Success',
                'code' => 0,
                'message' => 'Financial year created successfully',
                'data' => $financialYear
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 'Error',
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }


    public function financialYearUpdate(Request $request)
    {
        try {

            $validatedData = $request->validate([

                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'status' => 'boolean',
            ]);

            $financialYear = $this->financialYearService->financialYearUpdate($request);

            return response()->json([
                'status' => 'Success',
                'code' => 0,
                'message' => 'Financial year updated successfully',
                'data' => $financialYear
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 'Error',
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function financialYearClose(Request $request)
    {
        return $this->financialYearService->financialYearClose($request);
    }
}
