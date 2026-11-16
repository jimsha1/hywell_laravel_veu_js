@extends('admin.layout.base')

@section('main-content')
    <div id="app">
        <div class="page-wrapper">
            <div class="content">
                <div class="page-header">
                    <div class="page-title">
                        <h4>Voucher Management</h4>
                        <h6>Create and manage Receipt, Payment, Journal, and Contra vouchers</h6>
                    </div>
                    <div class="col-sm-12 mt-3 d-flex justify-content-sm-end gap-2">
                        <button class="btn btn-primary" @click="openCreateModal(0)">
                            <i class="ti ti-square-rounded-plus me-2"></i>New Voucher
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <label>Search</label>
                                <input type="text" class="form-control" v-model="filters.search" @input="loadVouchers"
                                    placeholder="Voucher No, Narration...">
                            </div>
                            <div class="col-md-2">
                                <label>Type</label>
                                <select class="form-select" v-model="filters.type" @change="loadVouchers">
                                    <option value="">All Types</option>
                                    <option value="sales">Sales</option>
                                    <option value="purchase">Purchase</option>
                                    <option value="receipt">Receipt</option>
                                    <option value="payment">Payment</option>
                                    <option value="journal">Journal</option>
                                    <option value="contra">Contra</option>
                                </select>
                            </div>
                            {{-- <div class="col-md-2">
                                <label>Status</label>
                                <select class="form-select" v-model="filters.status" @change="loadVouchers">
                                    <option value="">All Status</option>
                                    <option value="draft">Draft</option>
                                    <option value="posted">Posted</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div> --}}
                            <div class="col-md-2">
                                <label>Start Date</label>
                                <input type="date" class="form-control" v-model="filters.startDate"
                                    @change="loadVouchers">
                            </div>
                            <div class="col-md-2">
                                <label>End Date</label>
                                <input type="date" class="form-control" v-model="filters.endDate" @change="loadVouchers">
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-secondary w-100" @click="resetFilters">Reset</button>
                            </div>

                        </div>
                        <div class="row">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Total Voucher Amount</h6>
                                <h4 class="text-danger mb-0">
                                    @{{ total_amount.toFixed(2) }}
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Vouchers Table -->
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Voucher No</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        {{-- <th>Total Amount</th> --}}
                                        <th>Narration</th>
                                        <th class="text-end">Amount</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="loading">
                                        <td colspan="7" class="text-center py-5">
                                            <div class="spinner-border text-primary" role="status"></div>
                                        </td>
                                    </tr>
                                    <tr v-else-if="vouchers.length === 0">
                                        <td colspan="7" class="text-center py-5 text-muted">No vouchers found matching
                                            your criteria.</td>
                                    </tr>
                                    <tr v-for="voucher in vouchers" :key="voucher.id">
                                        <td class="ps-4 fw-bold">
                                            <span class="text-primary" style="cursor:pointer;"
                                                @click="viewVoucher(voucher)">
                                                @{{ voucher.voucher_no }}
                                            </span>
                                        </td>
                                        <td>@{{ formatDate(voucher.voucher_date) }}</td>
                                        <td>
                                            @{{ voucher.voucher_type }}
                                        </td>

                                        <td class="text-truncate" style="max-width: 200px;">@{{ voucher.narration || '-' }}</td>

                                        <td class="text-end fw-bold">
                                            @{{ formatCurrency(voucher.total_credit) }}
                                        </td>
                                        <td>
                                            <span class="badge" :class="getStatusClass(voucher.status)">
                                                @{{ voucher.status ? voucher.status.toUpperCase() : '-' }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex justify-content-end gap-2">
                                                <button class="btn btn-sm btn-outline-info" @click="viewVoucher(voucher)"
                                                    title="View Details">
                                                    <i class="ti ti-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" @click="editVoucher(voucher)"
                                                    title="Edit Details">
                                                    <i class="ti ti-pencil"></i>
                                                </button>
                                                {{-- <button v-if="voucher.status === 'draft'"
                                                    class="btn btn-sm btn-outline-success" @click="postVoucher(voucher.id)"
                                                    title="Post to Ledger">
                                                    <i class="ti ti-check"></i> Post
                                                </button>
                                                <button v-if="voucher.status === 'draft'"
                                                    class="btn btn-sm btn-outline-danger" @click="deleteVoucher(voucher.id)"
                                                    title="Delete">
                                                    <i class="ti ti-trash"></i>
                                                </button> --}}
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3" v-if="pagination.last_page > 1">
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm justify-content-end mb-0">
                                <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                                    <a class="page-link" href="#"
                                        @click.prevent="loadVouchers(pagination.current_page - 1)">Previous</a>
                                </li>
                                <li class="page-item" v-for="page in pagination.last_page" :key="page"
                                    :class="{ active: pagination.current_page === page }">
                                    <a class="page-link" href="#"
                                        @click.prevent="loadVouchers(page)">@{{ page }}</a>
                                </li>
                                <li class="page-item"
                                    :class="{ disabled: pagination.current_page === pagination.last_page }">
                                    <a class="page-link" href="#"
                                        @click.prevent="loadVouchers(pagination.current_page + 1)">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div class="modal fade" id="voucherModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered 	modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{ modelHeading }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <form @submit.prevent="saveVoucher" v-if="!formSubmitted" method="post">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label>Voucher Date <span class="text-danger">*</span></label>


                                    <input type="date" class="form-control" v-model="voucher_date"
                                        v-validate.initial="voucher_date" data-rules="required" id="voucher_date"
                                        value="" placeholder="Date">
                                    <span v-if="formErrors['voucher_date']"
                                        class="error text-danger">@{{ formErrors['voucher_date'] }}</span>
                                    <p class="text-danger" v-if="errors.has('voucher_date')">@{{ errors.first('voucher_date') }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Voucher Type <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="voucher_type" required>
                                        <option value="">Select Voucher Type</option>
                                        <option value="receipt">Receipt (Money In)</option>
                                        <option value="payment">Payment (Money Out)</option>
                                        <option value="journal">Journal (Adjustments)</option>
                                        <option value="contra">Contra (Bank/Cash Transfer)</option>
                                    </select>

                                    <span v-if="formErrors['voucher_type']"
                                        class="error text-danger">@{{ formErrors['voucher_type'] }}</span>
                                    <p class="text-danger" v-if="errors.has('voucher_type')">@{{ errors.first('voucher_type') }}</p>
                                </div>

                                <div class="col-4 mb-3">
                                    <label>Description <span class="text-danger">*</span></label>
                                    <input class="form-control" v-model="description" placeholder="Description" required>
                                    {{-- ← required add  --}}
                                    <span v-if="formErrors['description']"
                                        class="error text-danger">@{{ formErrors['description'] }}</span>
                                </div>

                            </div>
                            <hr />
                            <div class="row">
                                <div class="col-sm-12">
                                    Dr
                                    <div class="row" v-for="(input, index) in sectionDr"
                                        :key="`sectionDrInput-${index}`">
                                        <div class="col-md-4 mb-2">

                                            <select v-model="input.account_id" :id="'dr-account-' + index"
                                                class="select2-account form-control"
                                                @change="changeAccountDr(input.account_id)"
                                                v-validate.initial="input.account_id" data-rules="required">
                                                <option value="" selected disabled>-Select Account-</option>
                                                <option v-for="item in accounts" v-bind:value="item.id">
                                                    @{{ item.name }}
                                                </option>
                                            </select>

                                            <span v-if="formErrors['input.account_id']"
                                                class="error text-danger">@{{ formErrors['input.account_id'] }}</span>
                                            <p class="text-danger" v-if="errors.has('input.account_id')">
                                                @{{ errors.first('input.account_id') }}
                                            </p>


                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <input type="text" class="form-control" v-model="input.debit_amount"
                                                v-validate.initial="input.debit_amount" data-rules="required"
                                                placeholder="Debit Amount" @input="differenceOfDebitAndCredit()">
                                            <span v-if="formErrors['input.debit_amount']"
                                                class="error text-danger">@{{ formErrors['input.debit_amount'] }}</span>
                                            <p class="text-danger" v-if="errors.has('input.debit_amount')">
                                                @{{ errors.first('input.debit_amount') }}
                                            </p>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <input type="text" class="form-control" v-model="input.remarks"
                                                v-validate.initial="input.remarks" data-rules="required"
                                                placeholder="Rmarks">
                                            <span v-if="formErrors['input.remarks']"
                                                class="error text-danger">@{{ formErrors['input.remarks'] }}</span>
                                            <p class="text-danger" v-if="errors.has('input.remarks')">
                                                @{{ errors.first('input.remarks') }}
                                            </p>
                                        </div>

                                        <div class="col-md-1 mb-2">
                                            <svg @click="addFieldDr(input, sectionDr)" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24" width="24" height="24"
                                                class="ml-2 cursor-pointer">
                                                <path fill="none" d="M0 0h24v24H0z" />
                                                <path fill="green"
                                                    d="M11 11V7h2v4h4v2h-4v4h-2v-4H7v-2h4zm1 11C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16z" />
                                            </svg>
                                        </div>
                                        <div class="col-md-1 mb-2">
                                            <svg v-show="sectionDr.length > 1" @click="removeFieldDr(index, sectionDr)"
                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24"
                                                height="24" class="ml-2 cursor-pointer">
                                                <path fill="none" d="M0 0h24v24H0z" />
                                                <path fill="#EC4899"
                                                    d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm0-9.414l2.828-2.829 1.415 1.415L13.414 12l2.829 2.828-1.415 1.415L12 13.414l-2.828 2.829-1.415-1.415L10.586 12 7.757 9.172l1.415-1.415L12 10.586z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>


                            </div>

                            <hr />
                            <div class="row">
                                <div class="col-sm-12">
                                    To(Cr)

                                    <div class="row" v-for="(input, index) in sectionCr"
                                        :key="`sectionDrInput-${index}`">
                                        <div class="col-md-4 mb-2">

                                            {{-- <select v-model="input.account_id" class="form-control"
                                                v-validate.initial="input.account_id" data-rules="required">
                                                <option value="" selected disabled>-Select Account-</option>
                                                <option v-for="item in accounts" v-bind:value="item.id">
                                                    @{{ item.name }}
                                                </option>
                                            </select> --}}
                                            <select v-model="input.account_id" :id="'cr-account-' + index"
                                                class="select2-account form-control"
                                                @change="changeAccountDr(input.account_id)"
                                                v-validate.initial="input.account_id" data-rules="required">
                                                <option value="" selected disabled>-Select Account-</option>
                                                <option v-for="item in accounts" v-bind:value="item.id">
                                                    @{{ item.name }}
                                                </option>
                                            </select>

                                            <span v-if="formErrors['input.account_id']"
                                                class="error text-danger">@{{ formErrors['input.account_id'] }}</span>
                                            <p class="text-danger" v-if="errors.has('input.account_id')">
                                                @{{ errors.first('input.account_id') }}
                                            </p>

                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <input type="text" class="form-control" v-model="input.credit_amount"
                                                v-validate.initial="input.credit_amount" data-rules="required"
                                                placeholder="Credit Amount" @input="differenceOfDebitAndCredit()">
                                            <span v-if="formErrors['input.credit_amount']"
                                                class="error text-danger">@{{ formErrors['input.credit_amount'] }}</span>
                                            <p class="text-danger" v-if="errors.has('input.credit_amount')">
                                                @{{ errors.first('input.credit_amount') }}
                                            </p>
                                        </div>

                                        <div class="col-md-4 mb-2">
                                            <input type="text" class="form-control" v-model="input.remarks"
                                                v-validate.initial="input.remarks" data-rules="required"
                                                placeholder="Rmarks">
                                            <span v-if="formErrors['input.remarks']"
                                                class="error text-danger">@{{ formErrors['input.remarks'] }}</span>
                                            <p class="text-danger" v-if="errors.has('input.remarks')">
                                                @{{ errors.first('input.remarks') }}</p>
                                        </div>


                                        <div class="col-md-1 mb-2">
                                            <svg @click="addFieldCr(input, sectionCr)" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24" width="24" height="24"
                                                class="ml-2 cursor-pointer">
                                                <path fill="none" d="M0 0h24v24H0z" />
                                                <path fill="green"
                                                    d="M11 11V7h2v4h4v2h-4v4h-2v-4H7v-2h4zm1 11C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16z" />
                                            </svg>
                                        </div>


                                        <div class="col-md-1 mb-2">
                                            <svg v-show="sectionCr.length > 1" @click="removeFieldCr(index, sectionCr)"
                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24"
                                                height="24" class="ml-2 cursor-pointer">
                                                <path fill="none" d="M0 0h24v24H0z" />
                                                <path fill="#EC4899"
                                                    d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm0-9.414l2.828-2.829 1.415 1.415L13.414 12l2.829 2.828-1.415 1.415L12 13.414l-2.828 2.829-1.415-1.415L10.586 12 7.757 9.172l1.415-1.415L12 10.586z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <div class="row">
                                <div class="col-md-4 col-sm-12  form-group" style="text-align: right; font-style: italic">
                                    <label for="diff_amount_label" class="form-label">Diff.</label>
                                </div>
                                <div class="col-md-2 col-sm-12  form-group" style="font-style: italic">
                                    <label for="diff_amount" class="form-label">@{{ diff_amount }}</label>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <div class="d-flex align-items-center justify-content-end m-0">
                                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                                    <button type="submit" class="btn btn-primary"
                                        v-bind:disabled="submit_button_flag">@{{ modalBtn }}</button>
                                </div>
                            </div>

                        </div>
                    </form>


                </div>
            </div>
        </div>

        <!-- View Details Modal -->
        <div class="modal fade" id="viewModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content" v-if="selectedVoucher">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title">Voucher Details: @{{ selectedVoucher.voucher_no }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Voucher Type</p>
                                <h6 class="text-uppercase fw-bold">@{{ selectedVoucher.voucher_type }}</h6>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Voucher Date</p>
                                <h6>@{{ formatDate(selectedVoucher.voucher_date) }}</h6>
                            </div>
                        </div>

                        <table class="table table-sm table-bordered">
                            <thead class="bg-light">
                                <tr>
                                    <th>Account</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in selectedVoucher.entries" :key="item.id">
                                    <td>@{{ item.account?.name }} (@{{ item.account?.code }})</td>
                                    <td class="text-end"> @{{ formatCurrency(item.debit) }}</td>
                                    <td class="text-end">@{{ formatCurrency(item.credit) }}</td>
                                    <td>@{{ item.remarks || '-' }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="fw-bold">
                                <tr class="bg-light">
                                    <td class="text-end">TOTAL</td>
                                    <td class="text-end">@{{ formatCurrency(selectedVoucher.total_debit) }}</td>
                                    <td class="text-end">@{{ formatCurrency(selectedVoucher.total_credit) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>

                        <div class="mt-4" v-if="selectedVoucher.narration">
                            <p class="mb-1 text-muted">Narration</p>
                            <p class="bg-light p-2 rounded">@{{ selectedVoucher.narration }}</p>
                        </div>

                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">Created by: @{{ selectedVoucher.creator?.name }} at
                                    @{{ new Date(selectedVoucher.created_at).toLocaleString() }}</small>
                            </div>
                            <div v-if="selectedVoucher.status === 'draft'">
                                <button class="btn btn-success" @click="postVoucher(selectedVoucher.id)">Post to
                                    Ledger</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        const app = new Vue({
            el: '#app',
            data: {
                loading: false,
                saving: false,
                vouchers: [],
                accounts: [],
                total_amount: 0,
                selectedVoucher: null,
                id: '',
                voucher_type: 'receipt',
                voucher_date: '',
                description: '',
                sectionDr: [{
                    account_id: "",
                    debit_amount: 0,
                    remarks: ""
                }],

                sectionCr: [{
                    account_id: "",
                    credit_amount: 0,
                    remarks: "",
                }],
                diff_amount: 0,
                paymentCrAmount: 0,
                formErrors: {},
                modalBtn: '',
                modelHeading: '',
                filters: {
                    search: '',
                    type: '',
                    status: '',
                    startDate: '',
                    endDate: ''
                },
                pagination: {
                    current_page: 1,
                    last_page: 1
                },

                totals: {
                    debit: 0,
                    credit: 0
                },
                formSubmitted: false,
                submit_button_flag: false,
            },

            methods: {

                changeAccountDr: function(account_id) {
                    if (this.voucher_type == '') {

                        alert('Please select voucher type!.');

                        app.sectionDr = [{
                            account_id: "",
                            debit_amount: 0,
                            remarks: ""
                        }];

                    }

                },

                // differences of debit and credit
                differenceOfDebitAndCredit() {

                    const debitTotal = this.sectionDr.reduce((acc, ele) => {
                        return acc + (parseFloat(ele.debit_amount) || 0);
                    }, 0);

                    const creditTotal = this.sectionCr.reduce((acc, ele) => {
                        return acc + (parseFloat(ele.credit_amount) || 0);
                    }, 0);

                    this.diff_amount = debitTotal - creditTotal;

                    // Enable Submit only when Debit == Credit
                    this.submit_button_flag = (debitTotal !== creditTotal);
                },

                // add field debit
                addFieldDr(value, fieldType) {

                    if (this.voucher_type === 'payment') {
                        const account = this.sectionDr.find(a => a.id == value.account_id);

                        if (account && account.reference_type === 'supplier') {

                            return;
                        }
                        if (account && account.reference_type === 'employee') {

                            return;
                        }
                        if (account && account.reference_type === 'commission') {

                            return;
                        }

                    } else {
                        fieldType.push({
                            account_id: "",
                            debit_amount: 0,
                            remarks: ""
                        });
                        this.$nextTick(() => {
                            this.initAccountSelect2();
                        });
                    }

                    // console.log(this.scoringApplications);
                },
                // remove field debit
                removeFieldDr(index, fieldType) {

                    fieldType.splice(index, 1);
                    this.$nextTick(() => {
                        this.initAccountSelect2();
                    });
                    this.differenceOfDebitAndCredit();
                },

                // add field credit
                addFieldCr(value, fieldType) {
                    if (this.voucher_type === 'receipt') {
                        const account = this.accounts.find(a => a.id == value.account_id);
                        console.log(account);
                        if (account && account.reference_type === 'customer') {

                            return;
                        }
                    } else {
                        fieldType.push({
                            account_id: "",
                            credit_amount: 0,
                            remarks: ""
                        });
                        this.$nextTick(() => {
                            this.initAccountSelect2();
                        });
                    }

                    // console.log(this.scoringApplications);
                },

                // remove field credit
                removeFieldCr(index, fieldType) {
                    fieldType.splice(index, 1);
                    this.$nextTick(() => {
                        this.initAccountSelect2();
                    });
                    this.differenceOfDebitAndCredit();
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
                getStatusClass(status) {
                    const classes = {
                        'approved': 'bg-success',
                        'pending': 'bg-warning',
                        'cancelled': 'bg-danger'
                    };
                    return classes[status] || 'bg-secondary';
                },
                loadAccounts() {
                    axios.get('/api/vouchers/accounts/list').then(response => {
                        this.accounts = response.data.data;
                        this.$nextTick(() => {
                            this.initAccountSelect2();
                        });
                    });

                },
                initAccountSelect2() {

                    const vm = this;

                    $('.select2-account').each(function() {

                        const $select = $(this);

                        // Already initialized? Skip it.
                        if ($select.hasClass('select2-hidden-accessible')) {
                            {{-- return; --}}

                            $select.select2({
                                width: '100%',
                                placeholder: 'Search Account',
                                allowClear: true,
                                dropdownParent: $('#voucherModal')
                            });
                        }

                        $select.select2({
                            width: '100%',
                            placeholder: 'Search Account',
                            allowClear: true,
                            dropdownParent: $('#voucherModal')
                        });

                        $select.off('select2:select').on('select2:select', function() {

                            const id = this.id;
                            const value = Number($(this).val());

                            if (id.startsWith('dr-account-')) {

                                const row = Number(id.replace('dr-account-', ''));

                                vm.$set(vm.sectionDr[row], 'account_id', value);

                            } else {

                                const row = Number(id.replace('cr-account-', ''));

                                vm.$set(vm.sectionCr[row], 'account_id', value);

                            }

                        });

                        // Refresh displayed value from Vue model
                        $select.val($select.val()).trigger('change');
                        $select.on('select2:select', function() {

                            const id = this.id;
                            const value = Number($(this).val());

                            if (id.startsWith('dr-account-')) {

                                const row = Number(id.replace('dr-account-', ''));

                                vm.$set(vm.sectionDr[row], 'account_id', value);

                            } else {

                                const row = Number(id.replace('cr-account-', ''));

                                vm.$set(vm.sectionCr[row], 'account_id', value);

                            }

                        });

                    });

                },
                loadVouchers(page = 1) {
                    this.loading = true;
                    axios.get('/api/voucher/list', {
                        params: {
                            page: page,
                            search: this.filters.search,
                            type: this.filters.type,
                            {{-- status: this.filters.status, --}}
                            start_date: this.filters.startDate,
                            end_date: this.filters.endDate
                        }
                    }).then(response => {
                        const data = response.data.data;
                        this.vouchers = data.vouchers;
                        this.total_amount = data.total_amount;

                        this.pagination = data.pagination;
                        this.loading = false;
                    }).catch(error => {
                        console.error('Error loading vouchers:', error);
                        this.loading = false;
                    });
                },
                resetFilters() {
                    this.filters = {
                        search: '',
                        type: '',
                        status: '',
                        startDate: '',
                        endDate: ''
                    };
                    this.loadVouchers();
                },
                openCreateModal(data) {


                    if (data.id > 0) {

                        app.voucher_date = data.voucher_date;
                        app.voucher_type = data.voucher_type;
                        app.narrations = data.narrations;
                        app.status = data.status;
                        app.id = data.id;

                        // Get Debit Entry
                        url = "api/voucher/account.general-ledger-entry";

                        axios.post(url, {
                            "voucher_id": data.id,
                            "type": 'debit'
                        }).then(function(response) {
                            // console.log(response.data);
                            if (response.data) {
                                app.sectionDr = response.data;
                            }
                        });

                        // Get credit entry
                        url = "api/voucher.account.general-ledger-entry";

                        axios.post(url, {
                            "voucher_id": data.id,
                            "type": 'credit'
                        }).then(function(response) {
                            // console.log(response.data);
                            if (response.data) {
                                app.sectionCr = response.data;
                            }
                        });

                        //

                        app.paymentCrAmount = "";

                        app.formSubmitted = false;
                        app.formErrors = {};

                        app.modelHeading = "Edit Voucher";

                        app.$modal.show('voucherModal');
                        this.$nextTick(() => {
                            this.initAccountSelect2();
                        });

                    } else {

                        app.id = '';
                        app.voucher_date = '';
                        app.voucher_type = '';
                        app.narrations = '';
                        app.status = 'active';
                        app.sectionDr = [{
                            account_id: "",
                            debit_amount: 0,
                            remarks: ""
                        }];
                        app.sectionCr = [{
                            account_id: "",
                            credit_amount: 0,
                            remarks: ""
                        }];

                        app.paymentCrAmount = "";

                        app.diff_amount = 0;

                        app.formSubmitted = false;
                        app.formErrors = {};

                        app.modelHeading = "Create Voucher";

                        app.modalBtn = "Submit";
                        $('#voucherModal').modal('show');
                        this.$nextTick(() => {
                            this.initAccountSelect2();
                        });
                    }

                    $('#voucherModal').modal('show');
                    this.$nextTick(() => {
                        this.initAccountSelect2();
                    });
                },



                saveVoucher() {
                    const debitTotal = this.sectionDr.reduce((acc, ele) => acc + (parseFloat(ele.debit_amount) ||
                        0), 0);
                    const creditTotal = this.sectionCr.reduce((acc, ele) => acc + (parseFloat(ele.credit_amount) ||
                        0), 0);

                    if (debitTotal !== creditTotal) {
                        Vue.$toast.error('Debit and Credit amounts must be equal.');
                        return;
                    }
                    if (!this.description || this.description.trim() === '') {
                        this.formErrors['description'] = 'Description is required.';
                        return;
                    }
                    this.formErrors = {};


                    const formData = new FormData();

                    formData.append("voucher_date", this.voucher_date);
                    formData.append("voucher_type", this.voucher_type);
                    formData.append("description", this.description);
                    formData.append("status", this.status);
                    formData.append("sectionDr", JSON.stringify(this.sectionDr));
                    formData.append("sectionCr", JSON.stringify(this.sectionCr));
                    formData.append("id", this.id);

                    this.saving = true;
                    this.submit_button_flag = true;

                    if (this.id) {

                        formData.append("_method", "put");
                        formData.append("id", this.id);

                        axios.post("/api/vouchers/update", formData)
                            .then(response => {
                                app.showAPIResponse(response);
                            })
                            .catch(error => {
                                app.submit_button_flag = false;
                                app.formErrors = error.response.data.errors;
                            });

                    } else {

                        axios.post("/api/vouchers/store", formData)
                            .then(response => {
                                this.showAPIResponse(response);
                            })
                            .catch(error => {
                                this.submit_button_flag = false;
                                this.formErrors = error.response?.data?.errors || {};
                            });
                    }



                },

                showAPIResponse(response) {
                    if (response.data.code == 0) {

                        Vue.$toast.success(response.data.message, 'Success Alert');
                        app.submit_button_flag = false;

                        app.formSubmitted = true;
                        app.formErrors = {};

                        this.loadVouchers();
                        app.resetForm();
                        $('#voucherModal').modal('hide');

                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        app.submit_button_flag = false;
                    }
                    app.submit_button_flag = false;
                },
                resetForm() {
                    this.id = '';
                    this.voucher_date = '';
                    this.voucher_type = '';
                    this.description = '';
                    this.status = '';
                    this.sectionDr = [{
                        account_id: "",
                        debit_amount: 0,
                        remarks: ""
                    }];
                    this.sectionCr = [{
                        account_id: "",
                        credit_amount: 0,
                        remarks: ""
                    }];
                    this.paymentCrAmount = "";
                    this.diff_amount = 0;

                    this.payment_mode = 'CASH';

                    this.description = '';
                    $('#select2-placeholder-single').val(null).trigger('change');
                },
                viewVoucher(voucher) {

                    this.selectedVoucher = voucher;

                    this.modelHeading = 'voucher Details';

                    $('#viewModal').modal('show');

                },
                editVoucher(voucher) {

                    console.log(voucher);

                    this.voucher_date = voucher.voucher_date ?
                        voucher.voucher_date.split('T')[0] :
                        '';
                    this.voucher_type = voucher.voucher_type;
                    this.description = voucher.narration;

                    this.id = voucher.id;

                    this.sectionDr = voucher.entries
                        .filter(entry => parseFloat(entry.debit) > 0)
                        .map(entry => ({
                            account_id: entry.account_id,
                            debit_amount: entry.debit,
                            remarks: entry.remarks || ''
                        }));

                    this.sectionCr = voucher.entries
                        .filter(entry => parseFloat(entry.credit) > 0)
                        .map(entry => ({
                            account_id: entry.account_id,
                            credit_amount: entry.credit,
                            remarks: entry.remarks || ''
                        }));
                    this.modelHeading = 'Edit Voucher';
                    this.modalBtn = 'Update';

                    this.$nextTick(() => {

                        this.sectionDr.forEach((row, index) => {
                            $('#dr-account-' + index)
                                .val(row.account_id)
                                .trigger('change.select2');
                        });

                        this.sectionCr.forEach((row, index) => {
                            $('#cr-account-' + index)
                                .val(row.account_id)
                                .trigger('change.select2');
                        });

                    });
                    $('#voucherModal').modal('show');

                },
                postVoucher(id) {
                    if (!confirm(
                            'Are you sure you want to post this voucher to the General Ledger? This action cannot be undone.'
                        )) return;

                    axios.post(`/api/vouchers/post/${id}`)
                        .then(response => {
                            Vue.$toast.success('Voucher posted successfully.');
                            $('#viewModal').modal('hide');
                            this.loadVouchers();
                        })
                        .catch(error => {
                            Vue.$toast.error(error.response?.data?.message || 'Error posting voucher.');
                        });
                },
                deleteVoucher(id) {
                    if (!confirm('Are you sure you want to delete this draft voucher?')) return;

                    axios.delete(`/api/vouchers/delete/${id}`)
                        .then(response => {
                            Vue.$toast.success('Voucher deleted successfully.');
                            this.loadVouchers();
                        })
                        .catch(error => {
                            Vue.$toast.error(error.response?.data?.message || 'Error deleting voucher.');
                        });
                }
            },
            mounted() {
                this.loadAccounts();
                this.loadVouchers();
                $('#voucherModal').on('hidden.bs.modal', () => {
                    this.resetForm();
                });
            }
        });
    </script>
@endsection
