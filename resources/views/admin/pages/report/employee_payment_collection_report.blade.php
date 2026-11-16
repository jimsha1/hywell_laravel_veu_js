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
                                    <h4 class="page-title">Payment Collection Report</h4>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="head-icons">
                                        <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-original-title="Collapse" id="collapse-header"><i
                                                class="ti ti-chevrons-up"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Page Header -->

                        <div class="card">
                            <div class="card-header">
                                <!-- Filters -->
                                <div class="row align-items-center justify-content-left row-gap-3">
                                    <div class="col-sm-4">
                                        <Label class="mb-1">Start Date</Label>
                                        <input class="form-control" type="date" v-model="filter_start_date">
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">End Date</Label>
                                        <input class="form-control" type="date" v-model="filter_end_date">
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Employee</Label>
                                        <select v-model="filter_employee_id" class="form-select">
                                            <option value="">All Employees</option>
                                            <option v-for="item in employeeList" :value="item.id">
                                                @{{ item.name }} (@{{ item.user_type }})
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Search & Reset Buttons -->
                                    <div class="col-sm-12 d-flex gap-2 justify-content-end">
                                        <button class="btn btn-primary" v-on:click="getEmployeePaymentCollectionReport()">
                                            <i class="ti ti-search"></i> Search
                                        </button>
                                        <button class="btn btn-secondary" v-on:click="resetFilter()">
                                            <i class="ti ti-reload"></i> Reset
                                        </button>
                                    </div>
                                </div>
                                <!-- /Filters -->
                            </div>

                            <div class="card-body">
                                <!-- Payment Summary Cards -->
                                <div class="row mb-4" v-if="grandTotals">
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card text-white" style="background-color: #0d6efd30;">
                                            <div class="card-body text-center">
                                                <h6 class="card-title" style="color: #171724; font-size: 16px;">Cash</h6>
                                                <h3 class="mb-0" style="color: #0d6efd; font-size: 25px;">
                                                    @{{ formatCurrency(grandTotals.cash) }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card text-white" style="background-color: #19875430;">
                                            <div class="card-body text-center">
                                                <h6 class="card-title" style="color: #171724; font-size: 16px;">Card</h6>
                                                <h3 class="mb-0" style="color: #198754; font-size: 25px;">
                                                    @{{ formatCurrency(grandTotals.card) }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card text-white" style="background-color: rgba(251, 138, 0, 0.12);">
                                            <div class="card-body text-center">
                                                <h6 class="card-title" style="color: #171724; font-size: 16px;">Online</h6>
                                                <h3 class="mb-0" style="color: rgb(251, 140, 0); font-size: 25px;">
                                                    @{{ formatCurrency(grandTotals.online) }}</h3>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card text-white" style="background-color: rgba(126, 87, 194, 0.145);">
                                            <div class="card-body text-center">
                                                <h6 class="card-title" style="color: #171724; font-size: 16px;">Alrajhi
                                                    (Bank)</h6>
                                                <h3 class="mb-0" style="color: rgb(126, 87, 194); font-size: 25px;">
                                                    @{{ formatCurrency(grandTotals.bank_transfer_alrajhi) }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card text-white" style="background-color: #17a2b830;">
                                            <div class="card-body text-center">
                                                <h6 class="card-title" style="color: #171724; font-size: 16px;">SAB (Bank)
                                                </h6>
                                                <h3 class="mb-0" style="color: #17a2b8; font-size: 25px;">
                                                    @{{ formatCurrency(grandTotals.bank_transfer_sab) }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card text-white" style="background-color: #0dcaf030;">
                                            <div class="card-body text-center">
                                                <h6 class="card-title" style="color: #171724; font-size: 16px;">Alrajhi
                                                    (POS)</h6>
                                                <h3 class="mb-0" style="color: #0dcaf0; font-size: 25px;">
                                                    @{{ formatCurrency(grandTotals.pos_alrajhis) }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card text-white" style="background-color: #19875430;">
                                            <div class="card-body text-center">
                                                <h6 class="card-title" style="color: #171724; font-size: 16px;">SAB (POS)
                                                </h6>
                                                <h3 class="mb-0" style="color: #198754; font-size: 25px;">
                                                    @{{ formatCurrency(grandTotals.pos_sab) }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card text-white" style="background-color: #c2185b30;">
                                            <div class="card-body text-center">
                                                <h6 class="card-title" style="color: #171724; font-size: 16px;">Total
                                                </h6>
                                                <h3 class="mb-0" style="color: #c61b5d; font-size: 25px;">
                                                    @{{ formatCurrency(grandTotals.grand_total) }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Employee Table -->
                                <div class="table-responsive custom-table">
                                    <table class="table" id="employee_payment_collection_report">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Employee Name</th>
                                                {{-- <th>User Type</th> --}}
                                                <th>Cash</th>
                                                <th>Card</th>
                                                <th>Online</th>
                                                <th>Alrajhi (Bank)</th>
                                                <th>SAB (Bank)</th>
                                                <th>Alrajhi (POS)</th>
                                                <th>SAB (POS)</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!employees || !employees.length">
                                                <tr>
                                                    <td colspan="11" class="text-center">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(employee, index) in employees"
                                                :key="employee.user_id">
                                                <tr>
                                                    <td>@{{ pagination && pagination.per_page ? (pagination.current_page - 1) * pagination.per_page + index + 1 : index + 1 }}</td>
                                                    <td>@{{ employee.employee_name }}</td>
                                                    {{-- <td>@{{ employee.user_type }}</td> --}}
                                                    <td>@{{ formatCurrency(employee.cash) }}</td>
                                                    <td>@{{ formatCurrency(employee.card) }}</td>
                                                    <td>@{{ formatCurrency(employee.online) }}</td>
                                                    <td>@{{ formatCurrency(employee.bank_transfer_alrajhi) }}</td>
                                                    <td>@{{ formatCurrency(employee.bank_transfer_sab) }}</td>
                                                    <td>@{{ formatCurrency(employee.pos_alrajhis) }}</td>
                                                    <td>@{{ formatCurrency(employee.pos_sab) }}</td>
                                                    <td>@{{ formatCurrency(employee.total) }}</td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        {{-- <tfoot v-if="employees && employees.length">
                                            <tr class="fw-bold">
                                                <td colspan="3" class="text-end">Grand Total:</td>
                                                <td>@{{ formatCurrency(grandTotals.cash) }}</td>
                                                <td>@{{ formatCurrency(grandTotals.card) }}</td>
                                                <td>@{{ formatCurrency(grandTotals.online) }}</td>
                                                <td>@{{ formatCurrency(grandTotals.bank_transfer_alrajhi) }}</td>
                                                <td>@{{ formatCurrency(grandTotals.bank_transfer_sab) }}</td>
                                                <td>@{{ formatCurrency(grandTotals.pos_alrajhis) }}</td>
                                                <td>@{{ formatCurrency(grandTotals.pos_sab) }}</td>
                                                <td>@{{ formatCurrency(grandTotals.grand_total) }}</td>
                                            </tr>
                                        </tfoot> --}}
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <nav aria-label="pagination" v-if="pagination && pagination.last_page > 1">
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
                                <!-- /Pagination -->
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
        var app = new Vue({
            el: '#app',
            data: {
                loading: false,
                base_url: "{{ url('/') }}",
                employees: [],
                employeeList: [],
                grandTotals: null,
                filter_start_date: '',
                filter_end_date: '',
                filter_employee_id: '',
                pagination: {
                    total: 0,
                    per_page: 0,
                    from: 1,
                    to: 0,
                    current_page: 1,
                    last_page: 1
                },
                offset: 4,
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
                formatCurrency(value) {
                    if (!value) return '0.00';
                    return parseFloat(value).toFixed(2);
                },

                changePage: function(page) {
                    this.getEmployeePaymentCollectionReport(page);
                },

                getEmployeePaymentCollectionReport(page = 1) {
                    this.loading = true;
                    const url = "{{ route('employee-payment-collection-report.list') }}";

                    axios.post(url, {
                        page: page,
                        start_date: this.filter_start_date,
                        end_date: this.filter_end_date,
                        employee_id: this.filter_employee_id,
                    }).then(response => {
                        if (response.data.code == 0) {
                            this.employees = response.data.data.employees || [];
                            this.grandTotals = response.data.data.grand_totals || null;
                            this.pagination = response.data.data.pagination || this.pagination;
                        } else {
                            Vue.$toast.error(response.data.message, 'Error Alert');
                        }
                        this.loading = false;
                    }).catch(error => {
                        console.log("Error:", error);
                        Vue.$toast.error('Failed to load employee payment collection report',
                            'Error Alert');
                        this.loading = false;
                    });
                },

                getUsersList() {
                    axios.get('/api/get-users', {
                        params: {
                            platform: 'api'
                        }
                    }).then(response => {
                        app.employeeList = response.data.data || [];
                    }).catch(error => {
                        console.log("Error loading users:", error);
                    });
                },

                resetFilter() {
                    this.filter_start_date = '';
                    this.filter_end_date = '';
                    this.filter_employee_id = '';
                    this.employees = [];
                    this.grandTotals = null;
                    this.pagination = {
                        total: 0,
                        per_page: 0,
                        from: 1,
                        to: 0,
                        current_page: 1,
                        last_page: 1
                    };

                    // Auto-load today's report on mount
                    const today = new Date().toISOString().split('T')[0];
                    this.filter_start_date = today;
                    this.filter_end_date = today;
                    this.getEmployeePaymentCollectionReport();
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
                            $('#user_image_header').attr('src', this.base_url + '/storage/' + userImage);
                        } else {
                            const defaultImg = 'https://cdn-icons-png.freepik.com/512/9703/9703596.png';
                            $('#user_image').attr('src', defaultImg);
                            $('#user_image_header').attr('src', defaultImg);
                        }
                    }
                },
            },

            mounted() {
                this.getUsersList();
                this.getLoggedUserInfo();
                // Auto-load today's report on mount
                const today = new Date().toISOString().split('T')[0];
                this.filter_start_date = today;
                this.filter_end_date = today;
                this.getEmployeePaymentCollectionReport();
            }
        });
    </script>
@endsection
