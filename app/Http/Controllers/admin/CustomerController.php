<?php

namespace App\Http\Controllers\admin;

use App\Exports\CustomerExport;
use App\Exports\TransactionExcelGenerate;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerCreditBalance;
use App\Models\Order;
use App\Models\Wallet;
use App\Services\CustomerService;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


class CustomerController extends Controller
{
    private CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }
    public function index()
    {
        return view('admin.pages.customer.index');
    }
    public function customerInfo($id)
    {
        $customer = Customer::findOrFail($id);

        return view('admin.pages.customer.customer-info', [
            'customer_id' => $id,
            'customer' => $customer,
        ]);
    }
    public function customerStore(Request $request)
    {
        if (empty($request->id)) {
            $this->validate($request, [
                'customer_name' => 'required',
                'phone_number'  => 'required',
                'customer_type' => 'required',
                'email'         => 'nullable|email',
                'vat_reg_no'    => 'nullable|regex:/^[0-9]+$/',
                'commercial_reg_no' => 'nullable|string|max:255',
                'logged_user_id' => 'required',
                // 'employee_id' => 'required',
            ]);
            return $this->customerService->customer_store($request);
        } else {
            $this->validate($request, [
                'customer_name' => 'required',
                'phone_number' => 'required|unique:customers,phone_number,' . $request->id . ',id',
                'customer_type' => 'required',
                'email'         => 'nullable|email',
                'vat_reg_no'    => 'nullable|regex:/^[0-9]+$/',
                'commercial_reg_no' => 'nullable|string|max:255',
                'logged_user_id' => 'required',
                // 'employee_id' => 'required',
            ]);
            return $this->customerService->customer_update($request);
        }
    }
    public function customerList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->customerService->getAllCustomer($request, $platform);
    }

    public function outsideSalesmanList(Request $request)
    {
        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->customerService->outsideSalesmanList($request, $platform);
    }

    public function getOutsideSalesmanBalance(Request $request){
         return $this->customerService->getOutsideSalesmanBalance($request);
    }
    public function customerDelete(Request $request)
    {
        return $this->customerService->customerDelete($request);
    }
    public function assignProductStore(Request $request)
    {
        if (empty($request->id)) {
            $this->validate($request, [
                'product_id' => 'required',
                'special_price' => 'required',
            ]);
        } else {
            $this->validate($request, [
                'product_id' => 'required',
                'special_price' => 'required',
            ]);
        }
        return $this->customerService->assign_product($request);
    }
    public function assignProductList(Request $request)
    {
        return $this->customerService->assignProductList($request);
    }
    public function assignProductDelete(Request $request)
    {
        return $this->customerService->assignProductDelete($request->id);
    }
    public function getCustomerDetails(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'required'
            ]);
            return $this->customerService->getCustomerDetails($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 200);
        }
    }
    public function customerDetailList(Request $request)
    {
        return $this->customerService->customerDetailList($request);
    }


    public function payCreditBalance(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'required',
                'user_id' => 'required',
                'amount' => 'required',
                'payment_mode' => 'required',
            ]);

            return $this->customerService->payCreditBalance($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }


    public function payCreditBalanceMultiplePayment(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'required',
                'user_id' => 'required',
                'payment_modes' => 'required|array|min:1',
                'payment_modes.*.payment_mode' => 'required|string',
                'payment_modes.*.amount' => 'required|numeric|min:0.01',
            ]);

            return $this->customerService->payCreditBalanceMultiplePayment($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }

    public function creditPaymentIndex()
    {
        return view('admin.pages.customer.credit_payment');
    }

    public function outsidecreditPaymentIndex(){
         return view('admin.pages.outside_salesman.credit_payment');
    }
    public function exportCustomerTransactionPDF($id)
    {
        return $this->customerService->generateCustomerTransactionPDF($id);
    }

    public function exportCustomerListPDF(Request $request)
    {
        return $this->customerService->generateCustomerListPDF($request);
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(
            new CustomerExport($request),
            'customers.xlsx'
        );
    }

    public function payOutsideCreditBalanceMultiplePayment(Request $request)
    {
        try {
            $request->validate([
                'salesman_id' => 'required',
                'user_id' => 'required',
                'payment_modes' => 'required|array|min:1',
                'payment_modes.*.payment_mode' => 'required|string',
                'payment_modes.*.amount' => 'required|numeric|min:0.01',
            ]);

            return $this->customerService->payOutsideCreditBalanceMultiplePayment($request);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100);
        }
    }
}
