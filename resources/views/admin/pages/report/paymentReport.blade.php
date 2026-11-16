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
                                    <h4 class="page-title">Customer Payment Report</h4>
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
                        <!-- /Page Header -->

                        <div class="View Summarycard">
                            <div class="card-header">
                                <!-- Search Dropdowns -->
                                <div class="row align-items-center justify-content-left row-gap-3">

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Start Date</Label>
                                        <input class="form-control" type="date" v-model="filter_start_date">
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">End Date</Label>
                                        <input class="form-control" type="date" v-model="filter_end_date">
                                    </div>

                                    {{-- <div class="col-sm-4">
                                        <Label class="mb-1">Customer</Label>
                                        <select v-model="filter_customer_id" class="form-select">
                                            <option value="">Choose</option>
                                            <option v-for="item in customerList" :value="item.id">
                                                @{{ item.customer_name }}[@{{ item.customer_code }}]
                                            </option>
                                        </select>
                                    </div> --}}

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Customer</Label>
                                        <input class="form-control" type="text" v-model="filter_customer_search"
                                            placeholder="Customer">
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Employee</Label>
                                        <select v-model="filter_employee_id" class="form-select">
                                            <option value="">All Employees</option>
                                            <option v-for="employee in employeesList" :key="employee.id"
                                                :value="employee.id">
                                                @{{ employee.name }} (@{{ getEmployeeType(employee.user_type) }})
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Payment Type</Label>
                                        <select v-model="filter_payment_type" class="form-select">
                                            <option value="">All Payments</option>
                                            <option value="order_payments">Order Payments</option>

                                            <option value="previous_payments">Previous Payments</option>
                                            <option value="opening_balance">Opening Balance</option>
                                        </select>
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Payment Mode</Label>
                                        <select class="form-select" v-model="filter_payment_mode">
                                            <option value="">Choose</option>
                                            <option value="Cash">CASH</option>
                                            <option value="TRANSFER ALRAJHI">TRANSFER ALRAJHI</option>
                                            <option value="TRANSFER SAB">TRANSFER SAB</option>
                                            <option value="POS ALRAJHI">POS ALRAJHI</option>
                                            <option value="POS SAB">POS SAB</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="mb-1">Customer Type</label>
                                        <select class="form-select" v-model="filter_customer_type"
                                            @change="getOutsideEmployeeList()">
                                            <option value="in">Company Customer</option>
                                            <option value="out">Outside Customer</option>
                                        </select>
                                    </div>

                                    <!-- Search & Reset Buttons -->

                                    <div class="col-sm-2 d-flex gap-2 justify-content-start">


                                        <div class="bg-primary text-white px-3 py-2 rounded shadow-sm">
                                            <small>Total Paid</small><br>
                                            <strong>@{{ parseFloat(totalPaidAmount || 0).toFixed(2) }}</strong>
                                        </div>
                                    </div>
                                    <div class="col-sm-10 d-flex gap-2 justify-content-end">

                                        {{-- <div class="bg-primary text-white px-3 py-2 rounded shadow-sm">
                                            <small>Total Paid</small><br>
                                            <strong>@{{ parseFloat(totalPaidAmount || 0).toFixed(2) }}</strong>
                                        </div> --}}
                                        <button class="btn btn-primary" v-on:click="getPaymentList()">
                                            <i class="ti ti-search"></i> Search
                                        </button>
                                        <button class="btn btn-secondary" v-on:click="resetFilter()">
                                            <i class="ti ti-reload"></i> Reset
                                        </button>

                                        <div class="dropdown ms-3">
                                            <a href="javascript:void(0);" class="dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="ti ti-package-export me-2"></i>Export
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <ul>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item"
                                                            v-on:click="exportToPDF()">
                                                            <i class="ti ti-file-type-pdf text-danger me-1"></i>Export as
                                                            PDF
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item"
                                                            v-on:click="exportToExcel()">
                                                            <i class="ti ti-file-type-xls text-green me-1"></i>Export as
                                                            Excel
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <!-- /Search Dropdowns -->
                            </div>
                            <div class="card-body">
                                <!-- Contact List -->
                                <div class="table-responsive custom-table">
                                    <table class="table" id="payment_list">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>SI.No</th>
                                                <th>Date</th>
                                                <th>Order No</th>
                                                <th>Customer Code</th>
                                                <th>Name</th>
                                                <th>Paid Amount</th>
                                                {{-- <th>Balance Amount</th> --}}
                                                <th>Payment Mode</th>
                                                <th>Employee</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!paymentList || !paymentList.length">
                                                <tr>
                                                    <td colspan="7">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in paymentList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    <td>@{{ formatDate(data.payment_date) }}</td>
                                                    <td>@{{ (data.order && data.order.order_no) ? data.order.order_no: ((!data.order_id || data.type === 'opening_balance') ? 'Opening Balance' : '-') }}</td>
                                                    {{-- <td>@{{ data.customer ? data.customer.customer_code : (data.order && data.order.customer ? data.order.customer.customer_code : '-') }}</td>
                                                    <td>@{{ data.customer ? data.customer.customer_name : (data.order && data.order.customer ? data.order.customer.customer_name : '-') }}</td> --}}
                                                    <td v-if="data.order?.display_customer">@{{ data.order.display_customer.code }} <span
                                                            v-if="data.order?.display_customer.type=='employee'"></span>
                                                    </td>
                                                    <td v-else>@{{ data.user ? data.customer.customer_code : (data.order && data.order.customer ? data.order.customer.customer_code : '-') }}</td>
                                                    <td v-if="data.order?.display_customer">@{{ data.order.display_customer.name }} <span
                                                            v-if="data.order?.display_customer.type=='employee'">[Outside
                                                            Salesman]</span>
                                                    </td>
                                                    <td v-else>@{{ data.customer ? data.customer.customer_name : (data.order && data.order.customer ? data.order.customer.customer_name : '-') }}</td>
                                                    <td>@{{ parseFloat(data?.paid_amount || 0).toFixed(2) }}</td>
                                                    {{-- <td>@{{ parseFloat(data.balance_amount || 0).toFixed(2) }}</td> --}}
                                                    <td>@{{ data?.payment_mode || '-' }}</td>
                                                    <td>@{{ data?.user?.name || '-' }}</td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        <tfoot v-if="paymentList && paymentList.length">
                                            <tr>
                                                <td colspan="5" class="text-end"><strong> Total:</strong></td>
                                                <td><strong>@{{ getTotalPaid }}</strong></td>
                                                {{-- <td><strong>@{{ getTotalBalance }}</strong></td> --}}
                                                <td></td>
                                            </tr>
                                        </tfoot>
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

    </div>
@endsection

@section('script')
    <script>
        var app = new Vue({
            el: '#app',
            data: {
                loading: false,

                base_url: "{{ url('/') }}",

                paymentList: [],
                customerList: [],
                employeesList: [],

                filter_start_date: '',
                filter_end_date: '',
                filter_customer_id: '',
                filter_employee_id: '',
                filter_payment_type: '',
                filter_payment_mode: '',
                filter_customer_type: 'in',
                filter_customer_search: '',


                totalPaidAmount: '',
                search: '',
                page_length: '10',

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
                },
                getTotalPaid() {
                    if (!this.paymentList || !this.paymentList.length) return '0.00';
                    return this.paymentList.reduce((sum, item) => {
                        return sum + parseFloat(item.paid_amount || 0);
                    }, 0).toFixed(2);
                },
                getTotalBalance() {
                    if (!this.paymentList || !this.paymentList.length) return '0.00';
                    return this.paymentList.reduce((sum, item) => {
                        return sum + parseFloat(item.balance_amount || 0);
                    }, 0).toFixed(2);
                }
            },


            methods: {

                getPaymentList: function(page = 1) {
                    url = "{{ route('customer-payment-report.list') }}";

                    axios.post(url, {
                        page: page,
                        page_length: this.page_length,
                        search: this.search,
                        start_date: this.filter_start_date,
                        end_date: this.filter_end_date,
                        // customer_id: this.filter_customer_id,
                        customer_search: this.filter_customer_search,
                        employee_id: this.filter_employee_id,
                        payment_type: this.filter_payment_type,
                        payment_mode: this.filter_payment_mode,
                        customer_type: this.filter_customer_type,
                    }).then(function(response) {
                        app.paymentList = response.data.data.data;
                        app.totalPaidAmount = response.data.total_paid_amount;
                        app.pagination = response.data.pagination;

                        app.loading = false;
                    });

                },
                changePage: function(page) {
                    this.getPaymentList(page);
                },
                formatDate(value) {
                    if (!value) return '';
                    const date = new Date(value);
                    return date.toLocaleDateString('en-GB', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                },
                getCustomerList() {
                    axios.get('/api/customer/list', {
                            params: {
                                platform: 'api',
                            }
                        })
                        .then(response => {
                            const payload = response.data.data || response.data || {};
                            app.customerList = payload.customers || payload || [];
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },
                getEmployeesList() {
                    axios.get('/api/get-users', {
                        params: {
                            platform: 'api',
                            user_type: ['salesman', 'sales_executive', 'superadmin']
                        }
                    }).then((response) => {
                        if (response.data.code === 0) {
                            this.employeesList = response.data.data || [];
                        }
                    }).catch((error) => {
                        console.error('Error loading employees:', error);
                    });
                },
                getOutsideEmployeeList() {
                    if (this.filter_customer_type === 'out') {
                        axios.get('/api/get-users', {
                            params: {
                                platform: 'api',
                                user_type: ['outside_salesman']
                            }
                        }).then((response) => {
                            if (response.data.code === 0) {
                                this.employeesList = response.data.data || [];
                            }
                        }).catch((error) => {
                            console.error('Error loading outside employees:', error);
                        });
                    } else {
                        this.getEmployeesList();
                    }
                },
                getEmployeeType(userType) {
                    const types = {
                        'salesman': 'Salesman',
                        'sales_man': 'Salesman',
                        'sales_executive': 'Sales Executive',
                    };
                    return types[userType] || userType;
                },
                resetFilter: function() {
                    app.search = '';
                    // app.filter_customer_id = '';
                    app.filter_customer_search = '';
                    app.filter_employee_id = '';
                    app.filter_payment_type = '';
                    app.filter_payment_mode = '';
                    app.filter_end_date = '';
                    app.filter_start_date = '';
                    app.filter_customer_type = 'in';
                    app.getPaymentList();
                },
                exportToPDF() {
                    var url = "{{ route('customer-payment-report-export-to-pdf') }}?search=" + this.search +
                        // "&customer_id=" + (this.filter_customer_id ?? '') +
                        "&customer_search=" + (this.filter_customer_search ?? '') +
                        "&employee_id=" + (this.filter_employee_id ?? '') +
                        "&payment_type=" + (this.filter_payment_type ?? '') +
                        "&payment_mode=" + (this.filter_payment_mode ?? '') +

                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '') +
                        "&customer_type=" + (this.filter_customer_type ?? 'in');

                    console.log(url);

                    window.open(url, '_self');
                },
                exportToExcel() {
                    var url = "{{ route('customer-payment-report-export-to-excel') }}?search=" + this.search +
                        // "&customer_id=" + (this.filter_customer_id ?? '') +
                        "&customer_search=" + (this.filter_customer_search ?? '') +
                        "&employee_id=" + (this.filter_employee_id ?? '') +
                        "&payment_type=" + (this.filter_payment_type ?? '') +
                        "&payment_mode=" + (this.filter_payment_mode ?? '') +
                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '') +
                        "&customer_type=" + (this.filter_customer_type ?? 'in');

                    console.log(url);

                    window.open(url, '_self');
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



            },


            mounted() {
                this.getPaymentList();
                this.getCustomerList();
                this.getEmployeesList();
                this.getLoggedUserInfo();
            }



        });
    </script>
@endsection
