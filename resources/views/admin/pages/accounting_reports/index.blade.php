@extends('admin.layout.base')
@section('main-content')
<div id="app">
    <div class="page-wrapper">
        <div class="content">
            <div class="page-header">
                <div class="page-title">
                    <h4>Accounting Reports</h4>
                    <h6>View financial reports and statements</h6>
                </div>
            </div>

            <!-- Report Selection -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card h-100 cursor-pointer"
                        :class="{ 'border-primary border-2': activeReport === 'trial_balance' }"
                        @click="loadReport('trial_balance')">
                        <div class="card-body text-center">
                            <i class="ti ti-scale text-primary fs-1"></i>
                            <h5 class="mt-2">Trial Balance</h5>
                            <small class="text-muted">Debit & Credit balances</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 cursor-pointer"
                        :class="{ 'border-primary border-2': activeReport === 'day_book' }"
                        @click="loadReport('day_book')">
                        <div class="card-body text-center">
                            <i class="ti ti-book text-primary fs-1"></i>
                            <h5 class="mt-2">Day Book</h5>
                            <small class="text-muted">Daily transactions</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 cursor-pointer"
                        :class="{ 'border-primary border-2': activeReport === 'cash_book' }"
                        @click="loadReport('cash_book')">
                        <div class="card-body text-center">
                            <i class="ti ti-cash text-primary fs-1"></i>
                            <h5 class="mt-2">Cash Book</h5>
                            <small class="text-muted">Cash transactions</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 cursor-pointer"
                        :class="{ 'border-primary border-2': activeReport === 'general_ledger' }"
                        @click="loadReport('general_ledger')">
                        <div class="card-body text-center">
                            <i class="ti ti-file-text text-primary fs-1"></i>
                            <h5 class="mt-2">General Ledger</h5>
                            <small class="text-muted">Account-wise transactions</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 cursor-pointer"
                        :class="{ 'border-primary border-2': activeReport === 'income_statement' }"
                        @click="loadReport('income_statement')">
                        <div class="card-body text-center">
                            <i class="ti ti-chart-bar text-primary fs-1"></i>
                            <h5 class="mt-2">Profit and Loss</h5>
                            <small class="text-muted">Profit & Loss</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 cursor-pointer"
                        :class="{ 'border-primary border-2': activeReport === 'balance_sheet' }"
                        @click="loadReport('balance_sheet')">
                        <div class="card-body text-center">
                            <i class="ti ti-report-money text-primary fs-1"></i>
                            <h5 class="mt-2">Balance Sheet</h5>
                            <small class="text-muted">Assets & Liabilities</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Date Filters -->
            <div class="card mb-3" v-if="activeReport">
                <div class="card-body">
                    <div class="row align-items-end">

                        <div class="col-md-5">
                            <label>Financial Year</label>
                            <select class="select2 form-control" id="financialYears" v-model="financialYearId"
                                style="width: 100%;" @change="changeDate(financialYearId)">
                                <option value="">-Select Year</option>
                                <option v-for="year in financialYears" :value="year.id">
                                    @{{ year.code ? (year.code + ' - ') : '' }}@{{ year.name }}
                                </option>

                            </select>
                        </div>

                        {{-- <div class="col-md-2" v-if="financialYearId">

                                <label>Start Date</label>
                                <input type="date" class="form-control" v-model="startDate" disabled>
                            </div>
                            <div class="col-md-2" v-else>
                                <label>Start Date</label>
                                <input type="date" class="form-control" v-model="startDate">
                            </div>
                            <div class="col-md-2" v-if="financialYearId">

                                <label>End Date</label>
                                <input type="date" class="form-control" v-model="endDate" disabled>
                            </div>
                            <div class="col-md-2" v-else>
                                <label>End Date</label>
                                <input type="date" class="form-control" v-model="endDate">
                            </div> --}}
                        <div class="col-md-2">
                            <label>Start Date</label>
                            <input type="date" class="form-control" v-model="startDate">
                        </div>
                        <div class="col-md-2">
                            <label>End Date</label>
                            <input type="date" class="form-control" v-model="endDate">
                        </div>
                        <div class="col-md-5" v-if="activeReport === 'general_ledger'">
                            <label>Account</label>
                            <select class="select2 form-control" id="generalLedgerAccountSelect"
                                v-model="selectedAccount" style="width: 100%;">
                                <option value=""></option>
                                {{-- <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                        @{{ acc.code ? (acc.code + ' - ') : '' }}@{{ acc.name }}
                                </option> --}}
                            </select>
                        </div>
                        <div class="col-md-3" v-if="activeReport === 'cash_book'">
                            <label>Cash/Bank Account</label>
                            <select class="form-select" v-model="selectedCashBankAccount">
                                <option value="">Select Account</option>
                                <option v-for="acc in cashBankAccounts" :key="acc.id" :value="acc.code">
                                    @{{ acc.code }} - @{{ acc.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary" @click="fetchReport">
                                <i class="ti ti-refresh me-1"></i> Generate
                            </button>
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-outline-secondary" @click="printReport">
                                <i class="ti ti-printer"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Display -->
            <div class="card" v-if="activeReport" id="reportContent">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">@{{ getReportTitle() }}</h5>
                    <small class="text-muted">
                        {{-- v-if="activeReport != 'balance_sheet'"> --}}
                        @{{ formatDate(startDate) }} to
                        @{{ formatDate(endDate) }}</small>
                </div>
                <div class="card-body">
                    <div v-if="loading" class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2">Loading report...</p>
                    </div>

                    <!-- Trial Balance -->
                    <div v-else-if="activeReport === 'trial_balance' && reportData">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Account Code</th>
                                    <th>Account Name</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in reportData.accounts" :key="row.id">
                                    <td>@{{ row.account_code }}</td>
                                    <td>@{{ row.account_name }}</td>
                                    <td class="text-end">@{{ row.debit > 0 ? formatCurrency(row.debit) : '' }}</td>
                                    <td class="text-end">@{{ row.credit > 0 ? formatCurrency(row.credit) : '' }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="table-secondary fw-bold">
                                <tr>
                                    <td colspan="2" class="text-end">Total</td>
                                    <td class="text-end">@{{ formatCurrency(reportData.total_debits) }}</td>
                                    <td class="text-end">@{{ formatCurrency(reportData.total_credits) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                        <div class="alert" :class="reportData.is_balanced ? 'alert-success' : 'alert-danger'">
                            <i class="ti" :class="reportData.is_balanced ? 'ti-check' : 'ti-alert-triangle'"></i>
                            @{{ reportData.is_balanced ? 'Trial Balance is balanced' : 'Warning: Trial Balance is NOT balanced!' }}
                        </div>
                    </div>

                    <!-- Day Book -->
                    <div v-else-if="activeReport === 'day_book' && reportData">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Entry No</th>
                                    <th>Description</th>
                                    {{-- <th>Account</th> --}}
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- <tr v-for="entry in reportData.transactions" :key="entry.id">
                                        <td>@{{ formatDate(entry.date) }}</td>
                                <td>@{{ entry.entry_number }}</td>
                                <td>@{{ entry.description }}</td>
                                <td>@{{ entry.account_name }}</td>
                                <td class="text-end">@{{ entry.total_debits > 0 ? formatCurrency(entry.total_debits) : '' }}</td>
                                <td class="text-end">@{{ entry.total_credits > 0 ? formatCurrency(entry.total_credits) : '' }}</td>


                                </tr> --}}
                                <template v-for="entry in reportData.transactions">

                                    <tr v-for="(line, index) in entry.lines">

                                        <td v-if="index === 0">@{{ formatDate(entry.date) }}</td>
                                        <td v-else></td>

                                        <td v-if="index === 0">@{{ entry.entry_number }}</td>
                                        <td v-else></td>

                                        <td>@{{ line.account_name }}</td>

                                        <td class="text-end">
                                            @{{ line.type === 'debit' ? formatCurrency(line.amount) : '' }}
                                        </td>

                                        <td class="text-end">
                                            @{{ line.type === 'credit' ? formatCurrency(line.amount) : '' }}
                                        </td>

                                    </tr>

                                </template>
                            </tbody>
                            <tfoot class="table-secondary fw-bold">
                                <tr>
                                    <td colspan="3" class="text-end">Total</td>
                                    <td class="text-end">@{{ formatCurrency(reportData.total_debits) }}</td>
                                    <td class="text-end">@{{ formatCurrency(reportData.total_credits) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Cash Book -->
                    <div v-else-if="activeReport === 'cash_book' && reportData">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="alert alert-info mb-0">
                                    <strong>Cash/Bank Account:</strong> @{{ reportData.cash_account?.code }} -
                                    @{{ reportData.cash_account?.name }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="alert alert-secondary mb-0">
                                    <strong>Opening Balance:</strong> @{{ formatCurrency(reportData.opening_balance) }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="alert mb-0"
                                    :class="reportData.closing_balance >= 0 ? 'alert-success' : 'alert-danger'">
                                    <strong>Closing Balance:</strong> @{{ formatCurrency(reportData.closing_balance) }}
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Receipts (Money In) -->
                            <div class="col-md-12">
                                <div class="card border-success">
                                    <div class="card-header  text-white"
                                        style="background-color:rgba(13, 202, 240, 0.19);">
                                        <h6 class="mb-0 text-success"><i class="ti ti-arrow-down-left me-1"></i>
                                            Receipts (Cash
                                            In)</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-striped table-lg mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Entry No</th>
                                                    <th>Particulars</th>
                                                    <th class="text-end">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-if="reportData.receipts.length === 0">
                                                    <td colspan="4" class="text-center text-muted py-3">No receipts
                                                        found</td>
                                                </tr>
                                                <tr v-for="entry in reportData.receipts" :key="entry.entry_number">
                                                    <td>@{{ formatDate(entry.date) }}</td>
                                                    <td><small>@{{ entry.entry_number }}</small></td>
                                                    <td>
                                                        <div>@{{ entry.description }}</div>
                                                        <small class="text-muted">@{{ entry.contra_account_name }}</small>
                                                    </td>
                                                    <td class="text-end text-success fw-bold">@{{ formatCurrency(entry.amount) }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="text-success">
                                                <tr>
                                                    <td colspan="3" class="text-end fw-bold">Total Receipts</td>
                                                    <td class="text-end fw-bold">@{{ formatCurrency(reportData.total_receipts) }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <!-- Payments (Money Out) -->
                            <div class="col-md-12">
                                <div class="card border-danger">
                                    <div
                                        class="card-header text-danger " style="background-color:rgba(194, 24, 24, 0.19);">
                                        <h6 class="mb-0 text-danger"><i class="ti ti-arrow-up-right me-1"></i>
                                            Payments (Cash
                                            Out)</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-striped table-lg mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Entry No</th>
                                                    <th>Particulars</th>
                                                    <th class="text-end">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-if="reportData.payments.length === 0">
                                                    <td colspan="4" class="text-center text-muted py-3">No payments
                                                        found</td>
                                                </tr>
                                                <tr v-for="entry in reportData.payments" :key="entry.entry_number">
                                                    <td>@{{ formatDate(entry.date) }}</td>
                                                    <td><small>@{{ entry.entry_number }}</small></td>
                                                    <td>
                                                        <div>@{{ entry.description }}</div>
                                                        <small class="text-muted">@{{ entry.contra_account_name }}</small>
                                                    </td>
                                                    <td class="text-end text-danger fw-bold">@{{ formatCurrency(entry.amount) }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="table-danger">
                                                <tr>
                                                    <td colspan="3" class="text-end fw-bold">Total Payments</td>
                                                    <td class="text-end fw-bold">@{{ formatCurrency(reportData.total_payments) }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="card mt-3">
                            <div class="card-body">
                                <table class="table  mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="w-50">Opening Balance</td>
                                            <td class="text-end fw-bold">@{{ formatCurrency(reportData.opening_balance) }}</td>
                                        </tr>
                                        <tr class="text-success">
                                            <td>Add: Total Receipts</td>
                                            <td class="text-end fw-bold text-success">+ @{{ formatCurrency(reportData.total_receipts) }}</td>
                                        </tr>
                                        <tr class="text-danger">
                                            <td>Less: Total Payments</td>
                                            <td class="text-end fw-bold text-danger">- @{{ formatCurrency(reportData.total_payments) }}</td>
                                        </tr>
                                        <tr class="text-dark">
                                            <td class="fw-bold">Closing Balance</td>
                                            <td class="text-end fw-bold">@{{ formatCurrency(reportData.closing_balance) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- General Ledger -->
                    {{-- <div v-else-if="activeReport === 'general_ledger' && reportData">
                            <div v-for="account in reportData.accounts" :key="account.id" class="mb-4">
                                <h5 class="bg-light p-2">@{{ account.code }} - @{{ account.name }}</h5>
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-light">
                                <td colspan="4">Opening Balance</td>
                                <td class="text-end fw-bold">@{{ formatCurrency(account.opening_balance) }}</td>
                            </tr>
                            <tr v-for="entry in account.entries" :key="entry.id">
                                <td>@{{ formatDate(entry.date) }}</td>
                                <td>@{{ entry.description }}</td>
                                <td class="text-end">@{{ entry.debit > 0 ? formatCurrency(entry.debit) : '' }}</td>
                                <td class="text-end">@{{ entry.credit > 0 ? formatCurrency(entry.credit) : '' }}</td>
                                <td class="text-end fw-bold">@{{ formatCurrency(entry.running_balance) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <td colspan="2">Closing Balance</td>
                                <td class="text-end">@{{ formatCurrency(account.total_debits) }}</td>
                                <td class="text-end">@{{ formatCurrency(account.total_credits) }}</td>
                                <td class="text-end fw-bold">@{{ formatCurrency(account.closing_balance) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div> --}}


            <div v-else-if="activeReport === 'general_ledger' && reportData">

                <h5 class="bg-light p-2">
                    @{{ reportData.account.code }} - @{{ reportData.account.name }}
                </h5>

                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr class="table-light">
                            <td colspan="4">Opening Balance</td>
                            <td class="text-end fw-bold">
                                @{{ formatCurrency(reportData.opening_balance) }}
                            </td>
                        </tr>

                        <tr v-for="(entry,index) in reportData.transactions" :key="index">
                            <td>@{{ formatDate(entry.date) }}</td>
                            <td>@{{ entry.description }}-@{{ entry.entry_number }}</td>

                            <td class="text-end">
                                @{{ entry.debit > 0 ? formatCurrency(entry.debit) : '' }}
                            </td>

                            <td class="text-end">
                                @{{ entry.credit > 0 ? formatCurrency(entry.credit) : '' }}
                            </td>

                            <td class="text-end fw-bold">
                                @{{ formatCurrency(entry.balance) }}
                            </td>
                        </tr>
                    </tbody>

                    {{-- <tfoot class="table-secondary">
                                    <tr>
                                        <td colspan="4">Closing Balance</td>
                                        <td class="text-end fw-bold">
                                            @{{ formatCurrency(reportData.closing_balance) }}
                    </td>
                    </tr>
                    </tfoot> --}}

                </table>
                <div class="closing-balance">
                    <table class="table table-bordered table-sm mb-0">
                        <tbody>
                            <tr class="table-secondary">
                                <td colspan="4">
                                    <strong>Closing Balance</strong>
                                </td>
                                <td class="text-end fw-bold">
                                    @{{ formatCurrency(reportData.closing_balance) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>



            <div v-else-if="activeReport === 'income_statement' && reportData">

                <h4 class="text-center mb-3">Trading and Profit & Loss Statement</h4>
                <p class="text-center">
                    Date: @{{ reportData.start_date }} to @{{ reportData.end_date }}
                </p>

                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-bordered">

                            <thead>
                                <tr>
                                    <th>Particulars</th>
                                    <th class="text-end">Amount</th>
                                    <th>Particulars</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>

                            <tbody>

                                <!-- TRADING ACCOUNT -->
                                <tr>
                                    <td>Opening Stock</td>
                                    <td class="text-end">@{{ formatCurrency(reportData.openingStock) }}</td>

                                    <td style="cursor:pointer"
                                        @click="popupSection('Sales Account','sales',reportData,0,0)">
                                        Sales</td>

                                    <td class="text-end" style="cursor:pointer"
                                        @click="popupSection('Sales Account','sales',reportData,0,0)">
                                        @{{ formatCurrency(reportData.sales.total) }}
                                        <i class="ti"
                                            :class="expanded.sales ?
                                                            'ti-square-rounded-minus me-2' :
                                                            'ti-square-rounded-plus me-2'"></i>
                                    </td>
                                </tr>


                                <tr>

                                    <td style="cursor:pointer"
                                        @click="popupSection('Purchase Account','purchases',reportData,0,0)">


                                        Purchase Accounts <i class="ti"
                                            :class="expanded.purchases ?
                                                            'ti-square-rounded-minus me-2' :
                                                            'ti-square-rounded-plus me-2'"></i>
                                    </td>
                                    <td class="text-end" style="cursor:pointer"
                                        @click="popupSection('Purchase Account','purchases',reportData,0,0)">
                                        @{{ formatCurrency(reportData.purchases.total) }}
                                    </td>
                                    <td style="cursor:pointer"
                                        @click="popupSection('Sales Return Account','salesReturn',reportData,0,0)">
                                        Sales
                                        Return</td>
                                    <td class="text-end" style="cursor:pointer"
                                        @click="popupSection('Sales Return Account','salesReturn',reportData,0,0)">
                                        @{{ formatCurrency(reportData.salesReturn.total) }}
                                        <i class="ti"
                                            :class="expanded.purchases ?
                                                            'ti-square-rounded-minus me-2' :
                                                            'ti-square-rounded-plus me-2'"></i>
                                    </td>

                                </tr>

                                <tr>

                                    <td style="cursor:pointer"
                                        @click="popupSection('Purchase Return Account','purchaseReturn',reportData,0,0)">


                                        Purchase Return Accounts <i class="ti"
                                            :class="expanded.purchaseReturn ?
                                                            'ti-square-rounded-minus me-2' :
                                                            'ti-square-rounded-plus me-2'"></i>
                                    </td>
                                    <td class="text-end" style="cursor:pointer"
                                        @click="popupSection('Purchase Return Account','purchaseReturn',reportData,0,0)">
                                        @{{ formatCurrency(reportData.purchaseReturn.total) }}
                                    </td>


                                </tr>
                                <tr v-if="expanded.salesReturn"
                                    v-for="item in reportData.salesReturn.accounts" :key="item.id">
                                    <td></td>
                                    <td></td>

                                    <td class="ps-4" style="text-align:right">
                                        @{{ formatVoucherDate(item.journalEntryLines[0].journal_entry.vouchers.voucher_date) }} -
                                        <a href=""><b>@{{ item.journalEntryLines[0].journal_entry.vouchers.voucher_no }}</b></a> -
                                        @{{ item.journalEntryLines[0].journal_entry.vouchers.narration }}
                                    </td>

                                    <td style="text-align:left">
                                        @{{ formatCurrency(item.journalEntryLines[0].journal_entry.vouchers.amount) }}
                                    </td>

                                </tr>

                                <tr>
                                    <td>Direct Expenses</td>
                                    <td class="text-end">@{{ formatCurrency(reportData.directExpenses.total) }}</td>

                                    <td>Closing Stock</td>
                                    <td class="text-end">@{{ formatCurrency(reportData.closingStock) }}</td>
                                </tr>

                                <tr>
                                    <td class="fw-bold">Gross Profit c/o</td>
                                    <td class="text-end fw-bold">@{{ formatCurrency(reportData.grossProfit) }}</td>

                                    <td></td>
                                    <td></td>
                                </tr>

                                <!-- TRADING TOTAL -->
                                <tr class="border-top">
                                    <td></td>
                                    <td class="text-end fw-bold">
                                        @{{ formatCurrency(reportData.trading_total) }}
                                    </td>

                                    <td></td>
                                    <td class="text-end fw-bold">
                                        @{{ formatCurrency(reportData.trading_total_right) }}

                                    </td>
                                </tr>

                                <!-- P&L ACCOUNT -->
                                <tr>
                                    <td style="cursor:pointer" @click="toggleSection('indirectExpenses')">
                                        Indirect Expenses</td>
                                    <td class="text-end" style="cursor:pointer"
                                        @click="toggleSection('indirectExpenses')">
                                        @{{ formatCurrency(reportData.indirectExpenses.total) }}
                                        <i class="ti"
                                            :class="expanded.indirectExpenses ?
                                                            'ti-square-rounded-minus me-2' :
                                                            'ti-square-rounded-plus me-2'"></i>
                                    </td>

                                    <td>Gross Profit b/f</td>
                                    <td class="text-end">@{{ formatCurrency(reportData.grossProfit) }}</td>
                                </tr>
                                <tr v-if="expanded.indirectExpenses"
                                    v-for="(item,index) in reportData.indirectExpenses.accounts"
                                    :key="item.id">
                                    <td>@{{ item.name }}</td>
                                    <td class="text-end" style="cursor:pointer"
                                        @click="popupSection(item.name+'Account','indirectExpenses',reportData,index,1)">
                                        @{{ formatCurrency(item.balance) }}
                                        <i class="ti"
                                            :class="expanded.indirectExpenses ?
                                                            'ti-square-rounded-minus me-2' :
                                                            'ti-square-rounded-plus me-2'"></i>
                                    </td>

                                    {{-- <td class="ps-4" style="text-align:right">
                                                    @{{ formatVoucherDate(item.journalEntryLines[0].journal_entry.vouchers.voucher_date) }} -
                                    <a href=""><b>@{{ item.journalEntryLines[0].journal_entry.vouchers.voucher_no }}</b></a> -
                                    @{{ item.journalEntryLines[0].journal_entry.vouchers.narration }}
                                    </td>

                                    <td style="text-align:left">
                                        @{{ formatCurrency(item.journalEntryLines[0].journal_entry.vouchers.amount) }}
                                    </td> --}}

                                </tr>

                                <tr>
                                    <td></td>
                                    <td></td>

                                    <td>Indirect Income</td>
                                    <td class="text-end">@{{ formatCurrency(reportData.indirectIncome.total) }}</td>
                                </tr>

                                <tr>
                                    <td class="fw-bold">@{{ reportData.net_income >= 0 ? 'Net Profit' : 'Net Loss' }}</td>
                                    <td class="text-end fw-bold"
                                        :class="reportData.net_income >= 0 ? 'text-success' : 'text-danger'">
                                        @{{ formatCurrency(Math.abs(reportData.net_income)) }}
                                    </td>

                                    <td></td>
                                    <td></td>
                                </tr>

                                <!-- FINAL TOTAL -->
                                <tr class="border-top">
                                    <td></td>
                                    <td class="text-end fw-bold">
                                        @{{ formatCurrency(
    reportData.indirectExpenses.total +
    reportData.net_income
) }}
                                    </td>

                                    <td></td>
                                    <td class="text-end fw-bold">
                                        @{{ formatCurrency(
    reportData.grossProfit +
    reportData.indirectIncome.total
) }}
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>




            <div v-else-if="activeReport === 'balance_sheet' && reportData">
                <div class="row">

                    <!-- ASSETS -->
                    <div class="col-md-6">
                        <h5>Assets</h5>

                        <table class="table table-bordered">
                            <tbody>
                                <tr v-for="item in reportData.assets.accounts" :key="item.code"
                                    v-if="item.balance>0">
                                    <td>@{{ item.name }}</td>
                                    <td class="text-end">@{{ formatCurrency(item.balance) }}</td>
                                </tr>
                                <tr>
                                    <td>Stock In Hand</td>
                                    <td class="text-end">@{{ formatCurrency(reportData.closingStock) }}
                                </tr>
                            </tbody>

                            <tfoot class="table-primary">
                                <tr>
                                    <td class="fw-bold">Total Assets</td>
                                    <td class="text-end fw-bold">
                                        @{{ formatCurrency(reportData.total_assets_and_stock) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>


                    <!-- LIABILITIES & EQUITY -->
                    <div class="col-md-6">
                        <h5>Liabilities & Equity</h5>

                        <table class="table table-bordered">

                            <tbody>

                                <!-- Liabilities -->
                                <tr v-for="item in reportData.liabilities.accounts" :key="item.code">
                                    <td>@{{ item.name }}</td>
                                    <td class="text-end">@{{ formatCurrency(item.balance) }}</td>
                                </tr>

                                <!-- Equity -->
                                <tr class="table-light">
                                    <td colspan="2"><strong>Equity</strong></td>
                                </tr>

                                {{-- <tr v-for="item in reportData.equity.accounts" :key="item.code">
                                                <td>@{{ item.name }}</td>
                                <td class="text-end">@{{ formatCurrency(item.balance) }}</td>
                                </tr> --}}
                                <tr>
                                    <td>Capital</td>
                                    <td class="text-end">@{{ formatCurrency(reportData.equity.total) }}</td>
                                </tr>

                                <!-- Retained earnings -->
                                <tr>
                                    <td>Retained Earnings</td>
                                    <td class="text-end">
                                        @{{ formatCurrency(reportData.equity.profit) }}
                                    </td>
                                </tr>

                            </tbody>

                            <tfoot class="table-secondary">
                                <tr>
                                    <td class="fw-bold">Total Liabilities & Equity</td>
                                    <td class="text-end fw-bold">
                                        @{{ formatCurrency(reportData.total_liabilities_and_equity) }}
                                    </td>
                                </tr>
                            </tfoot>

                        </table>
                    </div>

                </div>

                <!-- Balance check -->
                <div class="alert mt-3" :class="reportData.is_balanced ? 'alert-success' : 'alert-danger'">

                    <i class="ti" :class="reportData.is_balanced ? 'ti-check' : 'ti-alert-triangle'">
                    </i>

                    @{{ reportData.is_balanced ?
    'Balance Sheet is balanced' :
    'Warning: Balance Sheet is NOT balanced!' }}
                </div>

            </div>

            <div v-else class="text-center py-5 text-muted">
                <p>Select a report and click Generate to view</p>
            </div>
        </div>
    </div>

    <!-- Initial Selection -->
    <div v-else class="text-center py-5">
        <i class="ti ti-report-analytics fs-1 text-muted"></i>
        <h5 class="text-muted mt-3">Select a report from above to get started</h5>
    </div>
</div>
</div>

<div class="modal fade" id="showDetails" role="dialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@{{ popup_title }}</h5>

                <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                    aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <h6>@{{ start_date }}--@{{ end_date }}</h6>
                <div class="card-body overflow-hidden">
                    <template>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Voucher NO</th>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!sections">
                                    <td>No Data Available</td>
                                </tr>

                                <tr v-else v-for="(item,index) in sections">
                                    <td>@{{ index + 1 }}</td>
                                    {{-- <td>@{{ item ?? '--' }} --}}
                                    <td>@{{ item?.voucher?.voucher_no ?? '--' }}</td>
                                    <td>@{{ formatDate(item?.voucher?.voucher_date) ?? '--' }}</td>
                                    <td>@{{ item?.voucher?.narration ?? '--' }}</td>
                                    <td v-if="item?.debit>0 ">@{{ item?.debit }}</td>
                                    <td v-else>0</td>
                                    <td v-if="item?.credit>0 ">@{{ item?.credit }}</td>
                                    <td v-else>0</td>

                                </tr>


                            </tbody>
                        </table>
                    </template>
                    {{-- <template v-else>
                                <p>No data available.</p>
                            </template> --}}
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex align-items-center justify-content-end m-0">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<style>
    .cursor-pointer {
        cursor: pointer;
    }

    .cursor-pointer:hover {
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #212529;
    }

    @media print {

        .page-header,
        .card:not(#reportContent) {
            display: none !important;
        }

        #reportContent {
            border: none !important;
        }
    }
</style>
@endsection

@section('script')
<script>
    Vue.use(window["vue-js-modal"].default);
    var app = new Vue({
        el: '#app',
        data: {

            base_url: "{{ url('/') }}",
            activeReport: null,
            loading: false,
            reportData: null,
            startDate: (() => {
                const now = new Date();
                return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
            })(),
            endDate: new Date().toISOString().split('T')[0],
            currentDate: new Date().toISOString().split('T')[0],
            selectedAccount: '',
            selectedCashBankAccount: '',
            accounts: [],
            cashBankAccounts: [],
            showItemDetails: false,

            financialYears: [],
            financialYearId: '',
            sections: {},
            start_date: new Date().toISOString().split('T')[0],
            end_date: new Date().toISOString().split('T')[0],
            popup_title: '',

            expanded: {
                purchases: false,
                directExpenses: false,
                sales: false,
                salesReturn: false,
                indirectExpenses: false,
                indirectIncome: false,
            },


        },
        mounted() {
            this.loadAccounts();
            this.getFinancialYearList();
            this.loadCashBankAccounts();
            this.getLoggedUserInfo();
            $('#generalLedgerAccountSelect').val(null).trigger('change');
        },
        watch: {
            activeReport(value) {
                if (value === 'general_ledger') {
                    this.$nextTick(() => this.initAccountSelect2());
                }
            }
        },
        methods: {

            formatDate(date) {
                return new Date(date).toLocaleDateString('en-IN');
            },
            changeDate(financialYearId) {
                const year = this.financialYears.find(
                    item => item.id == this.financialYearId
                );
                console.log(year);
                if (year) {
                    this.startDate = year.start_date;
                    this.endDate = year.end_date;

                }
            },
            formatDate(date) {
                const d = new Date(date);
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');

                return `${year}-${month}-${day}`;
            },
            formatVoucherDate(date) {
                if (!date) return '';

                return new Date(date).toISOString().split('T')[0];
            },
            toggleSection(section) {
                this.expanded[section] = !this.expanded[section];
            },
            popupSection(title, section, sections, index, additional) {


                console.log(sections);

                this.popup_title = title;

                this.start_date = sections.start_date;
                this.end_date = sections.end_date;

                if (section == 'sales') {
                    this.sections = [];
                    this.sections = sections.sales.salesVouchers;
                    console.log(this.sections);
                }

                if (section == 'salesReturn') {
                    this.sections = [];
                    this.sections = sections.salesReturn.salesReturnVouchers;
                    console.log(this.sections);
                }
                if (section == 'purchases') {
                    this.sections = [];
                    this.sections = sections.purchases.purchaseVouchers;
                    console.log(this.sections);
                }
                if (section == 'purchaseReturn') {
                    this.sections = [];
                    this.sections = sections.purchaseReturn.purchaseReturnVouchers;
                    console.log(this.sections);
                }
                if (section == 'indirectExpenses' && additional == 1) {

                    this.sections = sections.indirectExpenses.accounts[index].journalEntryLines;
                    console.log(this.sections);
                }

                $('#showDetails').modal('show');

            },
            openLedger() {
                this.showItemDetails = true;
            },
            closeLedger() {
                this.showItemDetails = false;
            },
            formatCurrency(amount) {
                return new Intl.NumberFormat('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(amount || 0);
            },
            formatDate(date) {
                return new Date(date).toLocaleDateString('en-IN');
            },
            getReportTitle() {
                const titles = {
                    'trial_balance': 'Trial Balance',
                    'day_book': 'Day Book',
                    'cash_book': 'Cash/Bank Book',
                    'general_ledger': 'General Ledger',
                    'income_statement': 'Profit & Loss',
                    'balance_sheet': 'Balance Sheet'
                };
                let title = titles[this.activeReport] || '';

                // Add account name for cash book
                if (this.activeReport === 'cash_book' && this.reportData && this.reportData.cash_account) {
                    title = this.reportData.cash_account.name + ' Book';
                }

                return title;
            },
            async loadAccounts() {
                try {
                    const response = await axios.get('/api/accounting/accounts-list');
                    if (response.data.status === 'Success') {
                        this.accounts = response.data.data;
                        this.$nextTick(() => {
                            if (this.activeReport === 'general_ledger') {
                                this.initAccountSelect2();
                            }
                        });
                    }
                } catch (error) {
                    console.error('Error loading accounts:', error);
                }
            },

            async getFinancialYearList() {
                try {
                    const response = await axios.get('/api/financial-year/list', {
                        params: {
                            platform: 'api'
                        }
                    });
                    if (response.data.status === 'Success') {
                        this.financialYears = response.data.data;

                    }
                } catch (error) {
                    console.error('Error loading accounts:', error);
                }
            },
            initAccountSelect2() {
                const $sel = $('#generalLedgerAccountSelect');

                if (!$sel.length) {
                    return;
                }

                if ($sel.hasClass('select2-hidden-accessible')) {
                    $sel.select2('destroy');
                }
                $sel.select2({
                    placeholder: 'Search account',
                    allowClear: true,
                    width: '100%',
                    minimumInputLength: 0,
                    data: this.accounts.map(account => ({
                        id: account.id,
                        text: `${account.name} (${account.code})`
                    }))
                });

                $sel.on('change', (e) => {
                    this.selectedAccount = e.target.value;
                });
            },
            async loadCashBankAccounts() {
                try {
                    const response = await axios.get('/api/accounting/cash-bank-accounts-list');
                    if (response.data.status === 'Success') {
                        // Filter only cash and bank accounts (codes starting with 1100 or 11)
                        this.cashBankAccounts = response.data.data;
                    }
                } catch (error) {
                    console.error('Error loading cash/bank accounts:', error);
                }
            },
            loadReport(reportType) {
                this.activeReport = reportType;
                this.financialYearId = '';
                this.startDate = (() => {
                    const now = new Date();
                    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
                })();
                this.endDate = new Date().toISOString().split('T')[0];

                this.reportData = null;
            },
            async fetchReport() {
                if (!this.activeReport) return;

                this.loading = true;
                this.reportData = null;

                try {
                    const reportName = this.activeReport.replace('_', '-');

                    // Build params
                    let params = {
                        start_date: this.startDate,
                        current_date: this.currentDate,
                        end_date: this.endDate,
                        account_id: this.selectedAccount,
                        financialYearId: this.financialYearId,

                    };

                    // Add account_code for cash_book if selected
                    if (this.activeReport === 'cash_book' && this.selectedCashBankAccount) {
                        params.account_code = this.selectedCashBankAccount;
                    }

                    const response = await axios.get(`/api/accounting/${reportName}`, {
                        params: params
                    });

                    if (response.data.status === 'Success') {
                        this.reportData = response.data.data;
                    } else {
                        alert(response.data.message);
                    }
                } catch (error) {
                    console.error('Error fetching report:', error);
                    alert('Error loading report');
                } finally {
                    this.loading = false;
                }
            },
            printReport() {
                window.print();
            },

            getLoggedUserInfo() {
                const userJson = window.localStorage.getItem('user');
                if (userJson) {
                    const user = JSON.parse(userJson);
                    this.user_id = user.data.id;
                    $('#user_name').text(user.data.name || 'Unknown');
                    if (user.data.user_type === "superadmin") {
                        $('#user_type').text('Super Admin');
                    }
                    let userImage = user.data.user_image;
                    if (userImage) {
                        $('#user_image').attr('src', this.base_url + '/storage/' + userImage);
                        $('#user_image_header').attr('src', this.base_url + '/storage/' +
                            userImage);
                    } else {
                        const defaultImg = 'https://cdn-icons-png.freepik.com/512/9703/9703596.png';
                        $('#user_image').attr('src', defaultImg);
                        $('#user_image_header').attr('src', defaultImg);
                    }
                }
            },
        }
    });
</script>
@endsection