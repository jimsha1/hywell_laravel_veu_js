<?php

namespace App\Http\Controllers\salesExecutive;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\salesExecutive\InvoiceService;
use App\Services\salesExecutive\ProfileService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesExecutiveController extends Controller
{
    private ProfileService $profileService;
    private InvoiceService $invoiceService;

    public function __construct(ProfileService $profileService, InvoiceService $invoiceService)
    {
        $this->profileService = $profileService;
        $this->invoiceService = $invoiceService;
    }

    public function storeInvoiceDraft(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'customer_id' => 'required',
                'user_id' => 'required',
                'products' => 'required',
                'commission_type_id' => 'nullable',
            ]);
            return $this->invoiceService->storeInvoiceDraft($validatedData);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function updateProductPrice(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'customer_id' => 'required',
                // 'user_id' => 'required',
                'commission_type_id' => 'nullable',
                'product_id' => 'required',
                'price' => 'required',
            ]);
            return $this->invoiceService->updateProductPrice($validatedData);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    public function updateInvoiceDraft(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'order_id' => 'required',
                'customer_id' => 'required',
                'user_id'     => 'required',
                'products'    => 'required',
                'commission_type_id' => 'nullable',
            ]);

            return $this->invoiceService->updateInvoiceDraft($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function storeInvoiceRequest(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'order_id' => 'required',
                'customer_id' => 'required',
                'user_id'     => 'required',
                'sales_man_user_id'    => 'required',
                'payment_status'    => 'nullable',
                'payment_modes'    => 'nullable',
            ]);

            return $this->invoiceService->storeInvoiceRequest($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function listInvoiceItems(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'order_id' => 'required',
                'user_id' => 'required',
            ]);

            return $this->invoiceService->listInvoiceItems($validatedData);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function getOrdersReport(Request $request)
    {
        try {
            // this is the optional request
            // "limit":10 ,
            // "sales_executive_user_id": 5,

            // "sales_man_user_id":2,
            // "customer_id":2
            // "start_date":"19-08-2025",
            // "end_date":"19-08-2025"


            // only_not_delivered - if this true then fetch only

            $request->validate([
                'user_id' => 'required',
                // 'user_type' => 'required'
            ]);

            return $this->invoiceService->getOrdersReport($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    public function getSalesManList()
    {
        try {
            $sales_man = User::whereIn('user_type', ['sales_man', 'salesman'])->where('status', 1)->orderBy('created_at', 'desc')->get();
            return successResponse($sales_man, 'Sales Man List', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }

    public function getSalesExecutiveProfile(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required'
            ]);
            return $this->profileService->getSalesExecutiveProfile($validatedData);
        } catch (Exception $e) {
            return apiResponse(100, $e->getMessage(), null);
        }
    }


    public function updateProfile(Request $request)
    {
        try {
            // dd($request->all());
            $validated = $request->validate([
                'user_id' => 'required',
                'name' => 'nullable',
                'email'   => ['nullable', 'email:rfc', 'regex:/^.+@.+\..+$/'],
            'phone'   => 'nullable|digits_between:9,15',
        ], [
            'email.email' => 'Please enter a valid email address.',
            'phone.digits_between' => 'Mobile number must be between 9 and 15 digits.',
        ]);

            return $this->profileService->updateProfile($validated);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }


    public function updateProfileImage(Request $request)
    {

        try {
            $validated = $request->validate([
                'user_id' => 'required',
                'profile_image' => 'nullable',
            ]);

            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profile_images', 'public');
                $validated['profile_image'] = $path;
            }

            return $this->profileService->updateProfileImage($validated);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
}
