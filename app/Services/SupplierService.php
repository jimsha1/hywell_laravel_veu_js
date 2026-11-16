<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AutoIncrementMaster;
use App\Models\FinancialYear;
use App\Models\Supplier;
use App\Models\SupplierOpeningBalance;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierService
{
    protected JournalEntryService $journalEntryService;

    public function __construct()
    {
        $this->journalEntryService = app(JournalEntryService::class);
    }
    public function supplier_store($data = null, $platform = 'web')
    {
        try {
            DB::beginTransaction();

             $requested_date = now();

            $financialYear = FinancialYear::whereDate('start_date', '<=', $requested_date)
                ->whereDate('end_date', '>=', $requested_date)
                ->first();

            // if (!$financialYear) {
            //     DB::rollBack();
            //     return errorResponse(null, 'No financial year found for the current date.', 100, 500);
            // }

            // if ($financialYear->is_closed == 1) {
            //     DB::rollBack();
            //     return errorResponse(null, 'Financial year is closed. Supplier cannot be created.', 100, 500);
            // }

            $openingBalance = (float) ($data->opening_balance ?? 0);
            $supplier = Supplier::Create([
                'supplier_code' => $this->genarateSupCode(),
                'supplier_name' => $data->supplier_name,
                'ar_supplier_name' => $data->ar_supplier_name,
                'commercial_reg_no' => $data->commercial_reg_no,
                'vat_reg_no' => $data->vat_reg_no,
                'email' => $data->email,
                'phone' => $data->phone,
                'country_name' => $data->country_name,
                'city' => $data->city,
                'legal_form' => $data->legal_form,
                'balance' => $openingBalance,
                'remarks' => $data->remarks,
                'address' => $data->address,
                'status' => $data->status ?? 1,
                'created_by' => $data->user_id,
            ]);

            $codeAuto = AutoIncrementMaster::where('auto_code', 'AI_SUPPLIER_LEDGER_CODE')->first();
            $next = $codeAuto->auto_increment_general_value;
            $code = 'S' . $next;
            $parent = Account::where('name', 'Accounts Payable(Creditors)')->first();
            if (!$parent) {
                return errorResponse(null, 'Parent account not found', 404, 404);
            }
            $account = Account::create([
                'account_type_id' => 2,
                'name' => $supplier->supplier_name . '_' . $supplier->supplier_code,
                'parent_id' => $parent->id,
                'description' => $supplier->supplier_name . '_' . $supplier->supplier_code . ' payable',
                'opening_balance' => 0,
                'reference_id' => $supplier->id,
                'reference_type' => 'supplier',
                'is_active' => 1,
                'is_system' => 1,
                'code' => $code
            ]);
            $next = $next + 1;
            $codeAuto->auto_increment_general_value = $next;
            $codeAuto->save();

            $supplier->account_id = $account->id;
            $supplier->save();

            if ($openingBalance > 0) {

                $opening_balance = SupplierOpeningBalance::create([
                    'supplier_id' => $supplier->id,
                    'amount' => $openingBalance,
                    'description' => 'Added opening balance',
                    'date' => Carbon::now(),
                ]);

                $journalEntry = $this->journalEntryService->createOpeningBalanceEntry([
                    'date' => "2025-08-31",
                    // 'date' => Carbon::now(),
                    'amount' => $data->opening_balance,
                    'reference_type' => 'App\\Models\\SupplierOpeningBalance',
                    'reference_id' => $opening_balance->id,
                    'description' => 'Supplier opening balance entry',
                    'account' => $account,
                    'user_id' => $data->logged_user_id,

                ]);
                if (!$journalEntry) {
                    return errorResponse(null, 'Failed to create journal entry', 500);
                }
                $currentBalance = $account->opening_balance;
                $account->opening_balance = $currentBalance - $data->opening_balance;
                $account->save();
            }
            DB::commit();

            return successResponse($supplier, 'Supplier created successfully.', 0, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function genarateSupCode()
    {
        $auto_sup_code = AutoIncrementMaster::where('auto_code', 'AI_SUPPLIER_CODE')->first();
        $currentValue = $auto_sup_code->auto_increment_general_value;
        $auto_sup_code->auto_increment_general_value = $currentValue + 1;
        $auto_sup_code->save();

        return $auto_sup_code->auto_prefix . $currentValue;
    }
    public function supplier_update($data = null, $platform = 'web')
    {

        try {
            DB::beginTransaction();

            $requested_date = now();

            $financialYear = FinancialYear::whereDate('start_date', '<=', $requested_date)
                ->whereDate('end_date', '>=', $requested_date)
                ->first();

            // if (!$financialYear) {
            //     DB::rollBack();
            //     return errorResponse(null, 'No financial year found for the current date.', 100, 500);
            // }

            // if ($financialYear->is_closed == 1) {
            //     DB::rollBack();
            //     return errorResponse(null, 'Financial year is closed. Supplier cannot be created.', 100, 500);
            // }

            $supplier = Supplier::find($data->id);
            if (!$supplier) {
                return errorResponse(null, 'Supplier not found', 404, 404);
            }

            $supplier->update([
                'supplier_name'      => $data->supplier_name,
                'ar_supplier_name'   => $data->ar_supplier_name,
                'commercial_reg_no' => $data->commercial_reg_no,
                'vat_reg_no'        => $data->vat_reg_no,
                'email'             => $data->email,
                'phone'             => $data->phone,
                'country_name'      => $data->country_name,
                'city'              => $data->city,
                'legal_form'        => $data->legal_form,
                'remarks'           => $data->remarks,
                'address'           => $data->address,
                'status'            => $data->status,
                'updated_by' => $data->user_id,
                // 'balance' => $data->opening_balance,
            ]);

            $account = Account::where('reference_type', 'supplier')
                ->where('reference_id', $supplier->id)
                ->first();
            
            if ($account) {
                $account->name = $supplier->supplier_name . '_' . $supplier->supplier_code;
                $account->description = $supplier->supplier_name . '_' . $supplier->supplier_code . ' payable';
                $account->save();
            }

            $openingBalance = (float) ($data->opening_balance ?? 0);

            if ($openingBalance > 0) {
                $supplier->balance += $openingBalance;
                $supplier->save();

                $opening_balance = SupplierOpeningBalance::create([
                    'supplier_id' => $supplier->id,
                    'amount' => $openingBalance,
                    'description' => 'Added opening balance',
                    'date' => Carbon::now(),
                ]);

                $account = Account::where('reference_type', 'supplier')->where('reference_id', $supplier->id)->first();

                $journalEntry = $this->journalEntryService->createOpeningBalanceEntry([
                    'date' => "2025-08-31",
                    // 'date' => Carbon::now(),
                    'amount' => $openingBalance,
                    'reference_type' => 'App\\Models\\SupplierOpeningBalance',
                    'reference_id' => $opening_balance->id,
                    'description' => 'Supplier opening balance entry',
                    'account' => $account,
                    'user_id' => $data->logged_user_id,

                ]);
                if (!$journalEntry) {

                    return errorResponse(null, 'Failed to create journal entry for the supplier opening balance', 500);
                }
            }
            DB::commit();
            return successResponse(null, 'Supplier updated successfully.', 0, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function getAllSupplier($data, $platform = 'web')
    {
        try {
            $query = Supplier::query();

            if ($data->search) {
                $query->where(function ($q) use ($data) {
                    $q->where('supplier_name', 'LIKE', "%{$data->search}%")
                        ->orWhere('supplier_code', 'LIKE', "%{$data->search}%");
                });
            }

            if ($platform == 'web') {
                $result = $query->orderBy('created_at', 'DESC')->paginate(config('settings.pagination.perpage.admin'));
                $response = [
                    'suppliers'  => $result->items(),
                    'pagination' => generatePaginationData($result),
                ];
                return successResponse($response, 'Suppliers retrieved successfully.', 0, 200);
            } else {
                $result = $query->where('status', 1)->orderBy('created_at', 'DESC')->get();
                return successResponse($result, 'Suppliers retrieved successfully.', 0, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
    public function supplierDelete($data)
    {
        try {
            DB::beginTransaction();
            $supplier = Supplier::findOrFail($data->id);
            if ($supplier) {
                $supplier->deleted_by = $data->user_id;
                $supplier->save();
                $supplier->delete();
                DB::commit();
                return successResponse(null, 'Supplier deleted successfully.', 0, 200);
            } else {
                return errorResponse(null, 'Supplier not found', 404, 404);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
}
