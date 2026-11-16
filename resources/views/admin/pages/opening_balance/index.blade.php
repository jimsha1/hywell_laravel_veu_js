@extends('admin.layout.base')
@section('main-content')
    <div id="app">
        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <div class="content">

                <div class="row">
                    <div class="col-md-12">

                        <!-- Page Header -->
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h4 class="page-title">Opening Balance</h4>
                                </div>
                                {{-- <div class="col-4 text-end">
                                    <div class="head-icons">
                                        <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-original-title="Collapse" id="collapse-header"><i
                                                class="ti ti-chevrons-up"></i></a>
                                    </div>
                                </div> --}}
                                <div class="col-sm-12 mt-3 d-flex justify-content-sm-end gap-2">


                                    <a href="javascript:void(0);" class="btn btn-primary "
                                        v-on:click="showEditFinancialYearModal(0)"><i
                                            class="ti ti-square-rounded-plus me-2"></i>Enter Opening Balance
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- /Page Header -->

                        <!-- Filters -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-5">

                                        <label>Opening Balance Type</label>
                                        <select class="form-select" v-model="filter_type"
                                            @change="getOpeningBalanceList(filter_type)">
                                            <option value="">Select</option>
                                            <option value="co">Company</option>
                                            <option value="cu">Customer</option>
                                            <option value="su">Supplier</option>

                                        </select>

                                    </div>
                                    <div class="col-md-5">
                                        <label>Search</label>
                                        <input type="text" class="form-control" v-model="search"
                                            @input="getOpeningBalanceList(filter_type)" placeholder="Search...">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button class="btn btn-secondary" @click="resetFilters">Reset</button>
                                    </div>

                                </div>
                                {{-- <div class="row"> --}}
                                {{-- <div class="col-md-3">
                                        <label>Search</label>
                                        <input type="text" class="form-control" v-model="search"
                                            @input="getOpeningBalanceList" placeholder="Search...">
                                    </div> --}}

                                {{-- <div class="col-md-2">
                                        <label>Start Date</label>
                                        <input type="date" class="form-control" v-model="startDate"
                                            @change="getOpeningBalanceList">
                                    </div>
                                    <div class="col-md-2">
                                        <label>End Date</label>
                                        <input type="date" class="form-control" v-model="endDate"
                                            @change="getOpeningBalanceList">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Account Name</label>
                                        <select class="form-select" v-model="filter_account_id"
                                            @change="getOpeningBalanceList">
                                            <option value="">All Accounts</option>
                                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                                @{{ acc.name }}
                                            </option>
                                        </select>
                                    </div> --}}

                                {{-- <div class="col-md-2 d-flex align-items-end">
                                        <button class="btn btn-secondary" @click="resetFilters">Reset</button>
                                    </div> --}}
                                {{-- </div> --}}
                            </div>
                        </div>
                        <div class="card">
                            {{-- <div class="card-header">
                                <!-- Search -->
                                <div class="row align-items-center">

                                    <div class="col-sm-8">
                                        <div class="text-sm-end">
                                            <a href="javascript:void(0);" class="btn btn-primary "
                                                v-on:click="showEditFinancialYearModal(0)"><i
                                                    class="ti ti-square-rounded-plus me-2"></i>Enter Opening Balance</a>
                                        </div>
                                    </div>
                                </div>
                                <!-- /Search -->
                            </div> --}}
                            <div class="card-body">
                                <!-- Contact List -->
                                <div class="table-responsive custom-table">
                                    <table class="table" id="lead_tag_list">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="no-sort">
                                                    <label class="checkboxs">
                                                        <input type="checkbox" id="select-all"><span
                                                            class="checkmarks"></span>
                                                    </label>
                                                </th>

                                                <th>Date</th>
                                                <th>Account Name</th>
                                                <th>Amount</th>
                                                {{-- <th>Actions</th> --}}

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!openingBalanceEntries || !openingBalanceEntries.length">
                                                <tr>
                                                    <td colspan="7">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in openingBalanceEntries">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    <td v-if="filter_type=='co'">@{{ formatDate(data.opening_date) }}</td>
                                                    <td
                                                        v-else-if="filter_type=='cu' && formatDate(data.date)<='2026-08-31'">
                                                        2025-09-01</td>
                                                    <td v-else-if="filter_type=='su'&& formatDate(data.date)<='2026-08-31'">
                                                        2025-09-01</td>

                                                    <td v-if="filter_type=='co' && data.account">@{{ data.account.name }}</td>
                                                    <td v-else-if="filter_type=='cu' && data.customer">
                                                        @{{ data.customer.customer_name }}</td>
                                                    <td v-else-if="filter_type=='su' && data.supplier">
                                                        @{{ data.supplier.supplier_name }}</td>

                                                    <td v-if="filter_type=='co'">@{{ data.opening_balance }}</td>
                                                    <td v-if="filter_type=='cu'">@{{ data.amount }}</td>
                                                    <td v-if="filter_type=='su'">@{{ data.amount }}</td>

                                                    {{-- <td>
                                                        <button class="btn btn-transparent p-0"
                                                            @click="showEditFinancialYearModal(data)">
                                                            <i class="ti ti-edit text-blue"></i>
                                                        </button>
                                                    </td> --}}


                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-md-5"></div>
                                <div class="col-sm-12 col-md-7"></div>
                            </div>
                        </div>
                        {{-- pagination --}}
                        <nav aria-label="pagination">
                            <ul class="pagination justify-content-end">
                                <li class="page-item" v-if="pagination.current_page > 1">
                                    <a class="page-link" href="#" aria-label="Previous"
                                        @click.prevent="changePage(pagination.current_page - 1)">
                                        <span aria-hidden="true">«</span>
                                    </a>
                                </li>
                                <li class="page-item" v-for="page in pagesNumber"
                                    v-bind:class="[ page == isActived ? 'active' : '']">
                                    <a class="page-link" href="#"
                                        @click.prevent="changePage(page)">@{{ page }}</a>
                                </li>
                                <li class="page-item" v-if="pagination.current_page < pagination.last_page">
                                    <a class="page-link" href="#" aria-label="Next"
                                        @click.prevent="changePage(pagination.current_page + 1)">
                                        <span aria-hidden="true">»</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        {{-- end --}}
                    </div>

                </div>

            </div>

        </div>
        <!-- /Page Wrapper -->

        <div class="modal fade" id="add_opening_balance" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{ modelHeading }}</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>

                    </div>
                    <form @submit.prevent="validateBeforeSubmit" v-if="!formSubmitted" method="post">
                        <div class="modal-body">



                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Date</label>
                                        <input type="date" class="form-control" name="start_date" v-model="start_date"
                                            placeholder="Enter Date" :max="today">
                                        <span v-if="formErrors['start_date']"
                                            class="error text-danger">@{{ formErrors['start_date'] }}</span>
                                        <p class="text-danger" v-if="errors.has('start_date')">@{{ errors.first('start_date') }}
                                        </p>
                                    </div>

                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Account Name</label>
                                        <select class="form-select" v-model="account_id" v-if="id>0" disabled>
                                            <option value="">Select Accounts</option>
                                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                                @{{ acc.name }}</option>
                                        </select>
                                        <select class="form-select" v-model="account_id" v-else>
                                            <option value="">Select Accounts</option>
                                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                                @{{ acc.name }}</option>
                                        </select>
                                        <span v-if="formErrors['account_id']"
                                            class="error text-danger">@{{ formErrors['account_id'] }}</span>
                                        <p class="text-danger" v-if="errors.has('account_id')">@{{ errors.first('account_id') }}
                                        </p>
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Amount</label>
                                        <input type="number" step="any" class="form-control" name="amount"
                                            v-model="amount" placeholder="Enter Amount">
                                        <span v-if="formErrors['amount']"
                                            class="error text-danger">@{{ formErrors['amount'] }}</span>
                                        <p class="text-danger" v-if="errors.has('amount')">@{{ errors.first('amount') }}
                                        </p>
                                    </div>





                                </div>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <div class="d-flex align-items-center justify-content-end m-0">
                                <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                                <button type="submit" class="btn btn-primary"
                                    v-bind:disabled="submit_button_flag">@{{ modelBtn }}</button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
        <!-- /Add New Source -->


    </div>
@endsection

@section('script')
    <script>
        var app = new Vue({
            el: '#app',
            data: {
                loading: false,

                base_url: "{{ url('/') }}",

                openingBalanceEntries: [],
                accounts: [],
                id: '',
                account_id: '',
                start_date: '',
                amount: '',
                logged_user_id: '',
                modelBtn: '',
                modelHeading: '',

                today: new Date().toISOString().split('T')[0],
                formSubmitted: false,
                submit_button_flag: false,

                search: '',
                startDate: '',
                endDate: '',
                page_length: '10',
                filter_account_id: '',
                filter_type: 'co',

                pagination: {
                    total: 0,
                    per_page: 2,
                    from: 1,
                    to: 0,
                    current_page: 1
                },
                offset: 4,
                formErrors: {},

            },

            computed: {
                isActived: function() {
                    return app.pagination.current_page;
                },
                pagesNumber: function() {
                    if (!this.pagination.to) {
                        return [];
                    }
                    var from = this.pagination.current_page - this.offset;
                    if (from < 1) {
                        from = 1;
                    }
                    var to = from + (this.offset * 2);
                    if (to >= this.pagination.last_page) {
                        to = this.pagination.last_page;
                    }
                    var pagesArray = [];
                    while (from <= to) {
                        pagesArray.push(from);
                        from++;
                    }
                    return pagesArray;
                }
            },

            methods: {

                async loadAccounts() {
                    try {
                        const response = await axios.get('/api/accounting/cash-bank-accounts-list');
                        if (response.data.status === 'Success') {
                            this.accounts = response.data.data;
                        }
                    } catch (error) {
                        console.error('Error loading accounts:', error);
                    }
                },
                validateBeforeSubmit() {
                    this.$validator.validateAll();
                    console.log(this.errors);
                    if (!this.errors.any()) {
                        this.enterOpeningBalance();
                    }
                },
                resetFilters() {
                    this.search = '';
                    this.filter_type = 'co';

                    this.startDate = '';
                    this.endDate = '';
                    this.filter_account_id = '';
                    this.getOpeningBalanceList();
                },
                enterOpeningBalance() {


                    const formData = new FormData();


                    formData.append("start_date", this.start_date);
                    formData.append("amount", this.amount);
                    formData.append("account_id", this.account_id);
                    formData.append("id", this.id);
                    formData.append("logged_user_id", this.logged_user_id);

                    this.submit_button_flag = true;


                    axios.post("/api/opening-balance/store", formData)
                        .then(response => {
                            this.showAPIResponse(response);
                        })
                        .catch(error => {

                            this.submit_button_flag = false;
                            this.formErrors = error.response.data.errors;
                        });

                },



                showAPIResponse(response) {

                    if (response.data.code == 0) {

                        Vue.$toast.success(response.data.message, 'Success Alert');
                        this.submit_button_flag = false;
                        this.formSubmitted = true;
                        this.formErrors = {};

                        this.getOpeningBalanceList();
                        this.resetForm();
                        $('#add_opening_balance').modal('hide');
                    } else {

                        Vue.$toast.error(response.data.message, 'Error Alert');
                        this.submit_button_flag = false;
                    }
                    this.submit_button_flag = false;
                },

                showEditFinancialYearModal(data) {
                    if (data.id > 0) {

                        this.start_date = this.formatDate(data.opening_date);


                        this.account_id = data.account_id;
                        this.id = data.id;
                        this.amount = data.opening_balance




                        this.formSubmitted = false;
                        this.formErrors = {};

                        this.modelHeading = "Edit Opening Balance";
                        this.modelBtn = "Update";

                        $('#add_opening_balance').modal('show');

                    } else {
                        this.resetForm();
                        this.modelHeading = "Add Opening Balance";
                        this.modelBtn = "Create";

                        $('#add_opening_balance').modal('show');
                    }

                },

                deleteItem(data) {
                    Swal.fire({
                        title: "Are you sure you want to close this opening balance " + data.id + "?",
                        text: "You won't be able to revert this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, revert it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/opening-balance/close", {
                                    data: {
                                        id: data.id,

                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.error("Error Closing Opening Balance:", error);
                                });
                        }
                    });
                },

                getOpeningBalanceList: function(page = 1, filter_type = this.filter_type) {
                    if (filter_type == 'co') {

                        const url = "/api/opening-balance/list";

                        axios.get(url, {
                            params: {
                                page: page,
                                page_length: this.page_length,
                                search: this.search,
                                startDate: this.startDate,
                                endDate: this.endDate,
                                account_id: this.filter_account_id,

                            }
                        }).then(response => {
                            app.openingBalanceEntries = response.data.data.opening_balances;
                            app.pagination = response.data.data.pagination;
                            app.loading = false;
                        });
                    } else if (filter_type == 'cu') {
                        const url = "/api/opening-balance/customer-list";

                        axios.get(url, {
                            params: {
                                page: page,
                                page_length: this.page_length,
                                search: this.search,
                                startDate: this.startDate,
                                endDate: this.endDate,
                                account_id: this.filter_account_id,

                            }
                        }).then(response => {
                            app.openingBalanceEntries = response.data.data.opening_balances;
                            app.pagination = response.data.data.pagination;
                            app.loading = false;
                        });
                    } else if (filter_type == 'su') {
                        const url = "/api/opening-balance/supplier-list";

                        axios.get(url, {
                            params: {
                                page: page,
                                page_length: this.page_length,
                                search: this.search,
                                startDate: this.startDate,
                                endDate: this.endDate,
                                account_id: this.filter_account_id,

                            }
                        }).then(response => {
                            app.openingBalanceEntries = response.data.data.opening_balances;
                            app.pagination = response.data.data.pagination;
                            app.loading = false;
                        });
                    }
                },



                getLoggedUserInfo() {
                    const userJson = window.localStorage.getItem('user');
                    if (userJson) {
                        const user = JSON.parse(userJson);
                        this.logged_user_id = user.data.id;
                        $('#user_name').text(user.data.name || 'Unknown');
                        if (user.data.user_type === "superadmin") {
                            $('#user_type').text('Super Admin');
                        }
                        let userImage = user.data.user_image;
                        if (userImage) {
                            $('#user_image').attr('src', this.base_url + '/storage/' + userImage);
                            $('#user_image_header').attr('src', this.base_url + '/storage/' + userImage);
                        } else {
                            const defaultImg = 'https://cdn-icons-png.freepik.com/512/9703/9703596.png';
                            $('#user_image').attr('src', defaultImg);
                            $('#user_image_header').attr('src', defaultImg);
                        }
                    }
                },

                resetForm() {

                    app.start_date = '';

                    app.id = '';
                    app.account_id = '';
                    app.amount = '';

                    app.formSubmitted = false;
                    app.formErrors = {};


                    if (this.errors && this.errors.clear) {
                        this.errors.clear();
                    }
                },

                formatDate(date) {
                    const d = new Date(date);
                    const year = d.getFullYear();
                    const month = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');

                    return `${year}-${month}-${day}`;
                },
            },

            mounted() {
                this.getLoggedUserInfo();
                this.loadAccounts();
                this.getOpeningBalanceList();
                $('#add_opening_balance').on('hidden.bs.modal', () => {
                    this.resetForm();
                });

            },
        });
    </script>
@endsection
