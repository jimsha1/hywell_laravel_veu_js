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
                            {{-- <div class="col-md-2" v-if="activeReport !== 'trial_balance'">
                                <label>Start Date</label>
                                <input type="date" class="form-control" v-model="startDate">
                            </div> --}}

                            <div class="col-md-2"
                                v-if="activeReport !== 'trial_balance' && activeReport !== 'day_book' && activeReport !== 'balance_sheet' ">
                                <label>Start Date</label>
                                <input type="date" class="form-control" v-model="startDate">
                            </div>

                            <div class="col-md-2" v-if="activeReport === 'balance_sheet' || activeReport === 'day_book' ">
                                <label>Start Date</label>
                                <input type="date" class="form-control" v-model="currentDate">
                            </div>
                            <div class="col-md-2"
                                v-if="(activeReport !== 'trial_balance')&&(activeReport !== 'day_book')&&(activeReport !== 'balance_sheet')">
                                <label>End Date</label>
                                <input type="date" class="form-control" v-model="endDate">
                            </div>
                            <div class="col-md-3" v-if="activeReport === 'general_ledger'">
                                <label>Account</label>
                                <select class="form-select" v-model="selectedAccount">
                                    <option value="">All Accounts</option>
                                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                        @{{ acc.name }}</option>
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
                        <small class="text-muted">@{{ formatDate(startDate) }} to @{{ formatDate(endDate) }}</small>
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
                                            class="card-header text-danger "style="background-color:rgba(194, 24, 24, 0.19);">
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
                                    {{-- <tr class="table-light">
                                        <td colspan="4">Opening Balance</td>
                                        <td class="text-end fw-bold">
                                            @{{ formatCurrency(reportData.opening_balance) }}
                                        </td>
                                    </tr> --}}

                                    <tr v-for="(entry,index) in reportData.transactions" :key="index">
                                        <td>@{{ formatDate(entry.date) }}</td>
                                        <td>@{{ entry.description }}</td>

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

                                <tfoot class="table-secondary">
                                    <tr>
                                        <td colspan="4">Closing Balance</td>
                                        <td class="text-end fw-bold">
                                            @{{ formatCurrency(reportData.closing_balance) }}
                                        </td>
                                    </tr>
                                </tfoot>

                            </table>

                        </div>

                        <!-- Income Statement -->
                        {{-- <div v-else-if="activeReport === 'income_statement' && reportData">
                            <div class="row">

                                <!-- LEFT SIDE (DEBIT) -->
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th colspan="2" class="text-center">Debit (Dr)</th>
                                            </tr>
                                            <tr>
                                                <th>Particulars</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr>
                                                <td>Opening Stock</td>
                                                <td class="text-end">@{{ formatCurrency(reportData.openingStock) }}</td>
                                            </tr>

                                            <tr v-for="item in reportData.purchases.accounts" :key="item.id">
                                                <td>@{{ item.name }}</td>
                                                <td class="text-end">@{{ formatCurrency(item.balance) }}</td>
                                            </tr>

                                            <tr>
                                                <td><strong>Total Purchases</strong></td>
                                                <td class="text-end fw-bold">@{{ formatCurrency(reportData.purchases.total) }}</td>
                                            </tr>

                                            <tr>
                                                <td>Direct Expenses</td>
                                                <td class="text-end">@{{ formatCurrency(reportData.directExpenses.total) }}</td>
                                            </tr>

                                            <tr>
                                                <td class="fw-bold text-success">Gross Profit c/o</td>
                                                <td class="text-end fw-bold text-success">
                                                    @{{ formatCurrency(reportData.grossProfit) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- RIGHT SIDE (CREDIT) -->
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th colspan="2" class="text-center">Credit (Cr)</th>
                                            </tr>
                                            <tr>
                                                <th>Particulars</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr v-for="item in reportData.sales.accounts" :key="item.id">
                                                <td>@{{ item.name }}</td>
                                                <td class="text-end">@{{ formatCurrency(item.balance) }}</td>
                                            </tr>

                                            <tr>
                                                <td><strong>Total Sales</strong></td>
                                                <td class="text-end fw-bold">@{{ formatCurrency(reportData.sales.total) }}</td>
                                            </tr>

                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- P&L SUMMARY -->
                            <div class="mt-4">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th colspan="2" class="text-center">Profit & Loss Account</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Gross Profit b/d</td>
                                            <td class="text-end">@{{ formatCurrency(reportData.grossProfit) }}</td>
                                        </tr>

                                        <tr>
                                            <td>Indirect Income</td>
                                            <td class="text-end">@{{ formatCurrency(reportData.indirectIncome.total) }}</td>
                                        </tr>

                                        <tr>
                                            <td>Indirect Expenses</td>
                                            <td class="text-end">@{{ formatCurrency(reportData.indirectExpenses.total) }}</td>
                                        </tr>

                                        <tr>
                                            <td class="fw-bold">
                                                @{{ reportData.net_income >= 0 ? 'Net Profit' : 'Net Loss' }}
                                            </td>
                                            <td class="text-end fw-bold"
                                                :class="reportData.net_income >= 0 ? 'text-success' : 'text-danger'">
                                                @{{ formatCurrency(Math.abs(reportData.net_income)) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div> --}}

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

                                                <td>Sales</td>
                                                <td class="text-end">@{{ formatCurrency(reportData.sales.total) }}</td>
                                            </tr>

                                            <tr>
                                                <td>Purchase Accounts</td>
                                                <td class="text-end">@{{ formatCurrency(reportData.purchases.total) }}</td>

                                                <td>Closing Stock</td>
                                                <td class="text-end">@{{ formatCurrency(reportData.closingStock) }}</td>
                                            </tr>

                                            <tr>
                                                <td>Direct Expenses</td>
                                                <td class="text-end">@{{ formatCurrency(reportData.directExpenses.total) }}</td>

                                                <td></td>
                                                <td></td>
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
                                                    @{{ formatCurrency(
    reportData.openingStock +
    reportData.purchases.total +
    reportData.directExpenses.total +
    reportData.grossProfit
) }}
                                                </td>

                                                <td></td>
                                                <td class="text-end fw-bold">
                                                    @{{ formatCurrency(
    reportData.sales.total +
    reportData.closingStock
) }}
                                                </td>
                                            </tr>

                                            <!-- P&L ACCOUNT -->
                                            <tr>
                                                <td>Indirect Expenses</td>
                                                <td class="text-end">@{{ formatCurrency(reportData.indirectExpenses.total) }}</td>

                                                <td>Gross Profit b/f</td>
                                                <td class="text-end">@{{ formatCurrency(reportData.grossProfit) }}</td>
                                            </tr>

                                            <tr>
                                                <td></td>
                                                <td></td>

                                                <td>Indirect Income</td>
                                                <td class="text-end">@{{ formatCurrency(reportData.indirectIncome.total) }}</td>
                                            </tr>

                                            <tr>
                                                <td class="fw-bold">Net Profit</td>
                                                <td class="text-end fw-bold text-success">
                                                    @{{ formatCurrency(reportData.net_income) }}
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


                        <!-- Balance Sheet -->
                        {{-- <div v-else-if="activeReport === 'balance_sheet' && reportData">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Assets</h5>
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr v-for="item in reportData.assets" :key="item.id">
                                                <td>@{{ item.name }}</td>
                                                <td class="text-end">@{{ formatCurrency(item.amount) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="table-primary">
                                            <tr>
                                                <td class="fw-bold">Total Assets</td>
                                                <td class="text-end fw-bold">@{{ formatCurrency(reportData.total_assets) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5>Liabilities & Equity</h5>
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr v-for="item in reportData.liabilities" :key="item.id">
                                                <td>@{{ item.name }}</td>
                                                <td class="text-end">@{{ formatCurrency(item.amount) }}</td>
                                            </tr>
                                            <tr class="table-light">
                                                <td colspan="2"><strong>Equity</strong></td>
                                            </tr>
                                            <tr v-for="item in reportData.equity" :key="item.id">
                                                <td>@{{ item.name }}</td>
                                                <td class="text-end">@{{ formatCurrency(item.amount) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="table-secondary">
                                            <tr>
                                                <td class="fw-bold">Total Liabilities & Equity</td>
                                                <td class="text-end fw-bold">@{{ formatCurrency(reportData.total_liabilities_equity) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="alert" :class="reportData.is_balanced ? 'alert-success' : 'alert-danger'">
                                <i class="ti" :class="reportData.is_balanced ? 'ti-check' : 'ti-alert-triangle'"></i>
                                @{{ reportData.is_balanced ? 'Balance Sheet is balanced' : 'Warning: Balance Sheet is NOT balanced!' }}
                            </div>
                        </div> --}}

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
                                                <td>Closing Stock</td>
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

                                            <tr v-for="item in reportData.equity.accounts" :key="item.code">
                                                <td>@{{ item.name }}</td>
                                                <td class="text-end">@{{ formatCurrency(item.balance) }}</td>
                                            </tr>

                                            <!-- Retained earnings -->
                                            <tr>
                                                <td>Retained Earnings</td>
                                                <td class="text-end">
                                                    @{{ formatCurrency(reportData.equity.retained_earnings) }}
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
    </div>
    <style>
        .cursor-pointer {
            cursor: pointer;
        }

        .cursor-pointer:hover {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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


            },
            mounted() {
                this.loadAccounts();
                this.loadCashBankAccounts();
                this.getLoggedUserInfo();

            },
            methods: {
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
                        }
                    } catch (error) {
                        console.error('Error loading accounts:', error);
                    }
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
                            account_id: this.selectedAccount
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
