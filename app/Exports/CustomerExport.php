<?php

namespace App\Exports;

use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerExport implements FromCollection, WithHeadings
{
    protected $request;
    protected $customerService;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->customerService = app(CustomerService::class);
    }

    public function collection()
    {
        $query = Customer::with('createdBy','employee');

        $this->customerService->applyCustomerFilters($query, $this->request);

        $customers = $query->orderBy('created_at', 'DESC')->get();

        return $customers->map(function ($customer) {
            return [
                'customer_code' => $customer->customer_code,
                'customer_name' => $customer->customer_name,
                'phone_number' => $customer->phone_number,
                'account_no' => $customer->account_no,
                'employee_name' => optional($customer->employee)->name,
                'status' => $customer->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Customer Code',
            'Customer Name',
            'Phone',
            'Account No',
            'Employee Name',
            'Status',
        ];
    }
}
