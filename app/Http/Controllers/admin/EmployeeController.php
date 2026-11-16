<?php

namespace App\Http\Controllers\admin;

use App\Exports\CustomerExport;
use App\Exports\TransactionExcelGenerate;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerCreditBalance;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CustomerService;
use App\Services\EmployeeService;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Employee;



class EmployeeController extends Controller
{
    private EmployeeService $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }
    public function index()
    {
        return view('admin.pages.employee.index');
    }

    // public function customerStore(Request $request)
    // {
    //     if (empty($request->id)) {
    //         $this->validate($request, [
    //             'customer_name' => 'required',
    //             'phone_number'  => 'required',
    //             'customer_type' => 'required',
    //         ]);
    //         return $this->customerService->customer_store($request);
    //     } else {
    //         $this->validate($request, [
    //             'customer_name' => 'required',
    //             'phone_number' => 'required|unique:customers,phone_number,' . $request->id . ',id',
    //             'customer_type' => 'required',
    //         ]);
    //         return $this->customerService->customer_update($request);
    //     }
    // }
    // public function customerList(Request $request)
    // {
    //     if ($request->platform == "api") {
    //         $platform = 'api';
    //     } else {
    //         $platform = 'web';
    //     }
    //     return $this->customerService->getAllCustomer($request, $platform);
    // }
    // public function customerDelete(Request $request)
    // {
    //     return $this->customerService->customerDelete($request);
    // }

    // public function getCustomerDetails(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'customer_id' => 'required'
    //         ]);
    //         return $this->customerService->getCustomerDetails($request);
    //     } catch (Exception $e) {
    //         return errorResponse(null, $e->getMessage(), 100, 200);
    //     }
    // }
    // public function customerDetailList(Request $request)
    // {
    //     return $this->customerService->customerDetailList($request);
    // }


    public function getEmployeesMain(Request $request)
    {

        return $this->employeeService->getAllEmployee($request, $request->platform);
    }
    /**
     * Update employee salary details
     */
    public function storeEmployee(Request $request)
    {


        $this->validate($request, [
            'name'            => 'required|string|max:255',
            'email'           => 'required|email:rfc|regex:/^.+@.+\..+$/|unique:users,email',
            'phone_number'    => 'required|string|max:20',
            'profile_image'   => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,bmp,svg,ico|max:2048',
            'designation'     => 'required|in:salesman,sales_executive,outside_salesman',
            'password'        => 'required|string|min:6|max:100',
            // 'basic_salary'    => 'required_if:designation,salesman,sales_executive|numeric|min:0',
            // 'allowances'      => 'nullable|numeric|min:0',
            // 'bank_name'       => 'required_if:designation,salesman,sales_executive|string|max:255',
            // 'bank_account_no' => 'required_if:designation,salesman,sales_executive|string|max:50',
            // 'bank_ifsc'       => 'required_if:designation,salesman,sales_executive|string|max:20',
            // 'credit_limit' => 'required_if:designation,outside_salesman|string|max:20',

        ]);

        if ($request->platform == "api") {
            $platform = 'api';
        } else {
            $platform = 'web';
        }
        return $this->employeeService->storeEmployee($request, $platform);
    }

    public function updateEmployee(Request $request, $id)
    {
        $employee = Employee::find($id);
        $userId = $employee ? $employee->user_id : null;

        $this->validate($request, [
            'name'           => 'required|string|max:255',
            'email'          => 'nullable|email:rfc|regex:/^.+@.+\..+$/|unique:users,email,' . $userId . ',id',
            'phone_number'   => 'required|string|max:20',
            'profile_image'  => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,bmp,svg,ico|max:2048',
            'designation'    => 'required|in:salesman,sales_executive,outside_salesman',
            // 'basic_salary'   => 'required|numeric|min:0',
            // 'allowances'     => 'nullable|numeric|min:0',
            // 'bank_name'      => 'required|string|max:255',
            // 'bank_account_no' => 'required|string|max:50',
            // 'bank_ifsc'      => 'required|string|max:20',
            'status'         => 'required|in:0,1',
        ]);

        return $this->employeeService->updateEmployee($request, $id);
    }
}
