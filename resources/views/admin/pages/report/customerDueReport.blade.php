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
                                    <h4 class="page-title">Customer Due Report</h4>
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

                        <div class="card">
                            <div class="card-header">
                                <!-- Search Dropdowns -->
                                <div class="row align-items-center justify-content-left row-gap-3">

                                    <div class="col-sm-6">
                                        <Label class="mb-1">Start Date</Label>
                                        <input class="form-control" type="date" v-model="filter_start_date">
                                    </div>

                                    <div class="col-sm-6">
                                        <Label class="mb-1">End Date</Label>
                                        <input class="form-control" type="date" v-model="filter_end_date">
                                    </div>


                                    <div class="col-sm-6">
                                        <Label class="mb-1">Customer Name </Label>
                                        <input class="form-control" type="text" v-model="filter_customer_name"
                                            placeholder="Customer Name">
                                    </div>
                                    <div class="col-sm-6">
                                        <Label class="mb-1">Due Time</Label>
                                        <select class="form-control" v-model="due_time">
                                            <option value="">Select days</option>
                                            <option value="ninty">More than 90 days</option>
                                            <option value="sixty">More than 60 days</option>
                                            <option value="thirty">More than 30 days</option>
                                            <option value="thirty_less">Less than 30 days</option>

                                        </select>
                                    </div>

                                    <div class="col-sm-6">
                                        <Label class="mb-1">Employee</Label>
                                        <select class="form-control" v-model="filter_employee_id">
                                            <option value="">All Employees</option>
                                            <option v-for="employee in orderByList" :key="employee.id"
                                                :value="employee.id">
                                                @{{ employee.name }} (@{{ getEmployeeType(employee.user_type) }})
                                            </option>
                                        </select>
                                    </div>


                                    <!-- Search & Reset Buttons -->
                                    <div class="col-sm-12 d-flex gap-2  justify-content-end">
                                        {{-- <button class="btn btn-primary" v-on:click="getSummaryDetailsPopup()">
                                            View Summary
                                        </button> --}}

                                        <button class="btn btn-primary" v-on:click="getSalesList()">
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
                                    <div class="row mt-4" v-if="salesList && salesList.length">
                                        <div class="col-md-4">
                                            <div class="card shadow-sm border-0">
                                                <div class="card-body text-center">
                                                    <h6 class="text-muted">Total Due Amount</h6>
                                                    <h4 class="text-danger mb-0">
                                                        @{{ parseFloat(total_balance_amount || 0).toFixed(2) }}
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Export Button -->

                            </div>
                            <!-- /Search Dropdowns -->
                        </div>
                        <div class="card-body">
                            <!-- Contact List -->
                            <div class="table-responsive custom-table">
                                <table class="table" id="lead_tag_list">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="no-sort">
                                                <label class="checkboxs">
                                                    <input type="checkbox" id="select-all"><span class="checkmarks"></span>
                                                </label>
                                            </th>
                                            <th>Customer Name</th>

                                            <th>Date</th>

                                            <th>Balance Amount</th>
                                            <th>Due</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-if="!salesList || !salesList.length">
                                            <tr>
                                                <td colspan="15">No data found</td>
                                            </tr>
                                        </template>
                                        <template v-else v-for="(data, index) in salesList">
                                            <tr>
                                                <td>@{{ index + 1 }}</td>
                                                {{-- <td>@{{ data.customer?.customer_name }}[@{{ data.customer?.customer_code }}]</td> --}}
                                                <td v-if="data.display_customer">@{{ data.display_customer.name }}
                                                    [@{{ data.display_customer.code }}]
                                                </td>
                                                <td v-else>@{{ data.customer.customer_name }}[@{{ data.customer.customer_code }}]</td>
                                                <td>@{{ formatDate(data.delivered_date) }}</td>


                                                <td>@{{ data.balance_amount }}</td>
                                                <td>@{{ data.due_days }} days</td>


                                            </tr>
                                        </template>
                                    </tbody>
                                    <!-- Grand Total Row -->
                                    <tfoot v-if="salesList && salesList.length">
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold"> Total:</td>

                                            <td class="fw-bold">@{{ grandBalanceAmount }}</td>
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


    <div class="modal fade" id="salesSummary" role="dialog">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Summary</h5>
                    <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="card-body overflow-hidden">

                        <table class="table">

                            <tbody>
                                <tr>
                                    <th>Total Quantity</th>
                                    <td>
                                        @{{ totalQty ? totalQty : 0 }}

                                </tr>

                                <tr>
                                    <th>Total Free Quantity</th>
                                    <td>
                                        @{{ totalFreeQty ? totalFreeQty : 0 }}</td>
                                </tr>

                                <tr>
                                    <th>Total Amount</th>
                                    <td>

                                        @{{ totalAmount ? parseFloat(totalAmount).toFixed(2) : 0 }}</td>
                                </tr>

                                <tr>
                                    <th>Total Paid Amount</th>
                                    <td>
                                        @{{ totalPaidAmount ? parseFloat(totalPaidAmount).toFixed(2) : 0 }}</td>


                                    </td>
                                </tr>

                                <tr>
                                    <th>Total Refund Amount</th>
                                    <td>@{{ totalRefundAmount ? parseFloat(totalRefundAmount).toFixed(2) : 0 }}</td>
                                </tr>

                                <tr>
                                    <th>Total Refund Order Balance</th>
                                    <td>@{{ totalRefundOrderBalance ? parseFloat(totalRefundOrderBalance).toFixed(2) : 0 }}

                                    </td>
                                </tr>

                                <tr>
                                    <th>Total Balance Amount</th>
                                    <td>@{{ totalBalanceAmount ? parseFloat(totalBalanceAmount).toFixed(2) : 0 }}</td>
                                </tr>
                            </tbody>
                        </table>


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
@endsection

@section('script')
    <script>
        var app = new Vue({
            el: '#app',
            data: {
                loading: false,

                base_url: "{{ url('/') }}",

                salesList: [],
                productList: [],
                orderByList: [],
                deliveryByList: [],

                filter_start_date: '',
                filter_end_date: '',
                filter_invoice_no: '',
                filter_customer_name: '',
                filter_employee_id: '',
                employeesList: [],
                due_time: '',
                filter_payment_status: '',
                filter_delivery_status: '',
                filter_payment_mode: '',
                filter_order_by: '',
                filter_delivery_by: '',
                selectedOrder: '',


                totalQty: '',
                totalFreeQty: '',
                totalAmount: '',
                totalPaidAmount: '',
                totalRefundAmount: '',
                totalRefundOrderBalance: '',
                totalBalanceAmount: '',
                total_balance_amount: 0,

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

                grandBalanceAmount() {
                    return this.salesList.reduce((sum, item) => sum + Number(item.balance_amount || 0), 0);
                }

            },

            methods: {

                exportToPDF() {
                    var url = "{{ route('customer-due-report-export-to-pdf') }}?customer_name=" +
                        (this.filter_customer_name ?? '') +
                        "&employee_id=" + (this.filter_employee_id ?? '') +
                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '') +
                        "&due_time=" + (this.due_time ?? '');

                    window.open(url, '_self');
                },

                exportToExcel() {
                    var url = "{{ route('customer-due-report-export-to-excel') }}?customer_name=" +
                        (this.filter_customer_name ?? '') +
                        "&employee_id=" + (this.filter_employee_id ?? '') +
                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '') +
                        "&due_time=" + (this.due_time ?? '');

                    window.open(url, '_self');
                },
                getEmployeeType(userType) {
                    const types = {
                        'salesman': 'Salesman',
                        'sales_man': 'Salesman',
                        'sales_executive': 'Sales Executive',
                    };
                    return types[userType] || userType;
                },

                hasMatchingPaymentMode(bookingPayments, filterPaymentMode) {
                    if (!filterPaymentMode) return false;
                    return bookingPayments.some(payment => payment.payment_mode === filterPaymentMode);
                },

                // Calculate total amount for filtered payment mode
                calculateFilteredAmount(bookingPayments, filterPaymentMode) {
                    if (!filterPaymentMode) return '0.00';
                    return bookingPayments
                        .filter(payment => payment.payment_mode === filterPaymentMode)
                        .reduce((total, payment) => total + parseFloat(payment.paid_amount), 0)
                        .toFixed(2);
                },

                getFreeQty(items) {
                    return items.reduce((sum, item) => sum + Number(item.free_quantity || 0), 0);
                },
                formatDate(dateString) {
                    if (!dateString) return '';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-GB').replaceAll('/', '-');
                },
                changePage: function(page) {
                    this.getSalesList(page);
                },

                getSummaryDetailsPopup() {
                    app.getSalesList();
                    $('#salesSummary').modal('show');


                },
                getSalesList: function(page = 1) {

                    url = "{{ route('customer-due-report.list') }}";

                    axios.post(url, {
                        page: page,
                        page_length: this.page_length,
                        search: this.search,
                        start_date: this.filter_start_date,
                        end_date: this.filter_end_date,
                        order: this.filter_invoice_no,
                        customer_name: this.filter_customer_name,
                        employee_id: this.filter_employee_id,
                        due_time: this.due_time,
                        payment_status: this.filter_payment_status,
                        delivery_status: this.filter_delivery_status,
                        payment_mode: this.filter_payment_mode,
                        filter_order_by: this.filter_order_by,
                        filter_delivery_by: this.filter_delivery_by,
                    }).then(function(response) {
                        let salesData = response.data.data.data;

                        // Filter sales by payment_mode if filter_payment_mode is set
                        if (app.filter_payment_mode) {
                            salesData = salesData.filter(sale =>
                                sale.payments.some(payment => payment.payment_mode === app
                                    .filter_payment_mode)
                            );
                        }
                        app.salesList = response.data.data.data;


                        app.pagination = response.data.pagination;
                        app.total_balance_amount = response.data.total_balance_amount;

                        app.loading = false;
                    });

                },

                getProductList() {
                    axios.get('/api/product/list', {
                            params: {
                                platform: 'api',
                            }
                        })
                        .then(response => {
                            app.productList = response.data.data.products;
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },
                getOrderByList() {
                    axios.get('/api/get-users', {
                        params: {
                            platform: 'api',
                            user_type: ['salesman', 'sales_executive', 'superadmin']
                        }
                    }).then(response => {
                        app.orderByList = response.data.data;
                    });
                },
                getDeliveryByList() {
                    axios.get('/api/get-users', {
                        params: {
                            platform: 'api',
                            user_type: ['salesman', 'superadmin']
                        }
                    }).then(response => {
                        app.deliveryByList = response.data.data;
                    });
                },

                resetFilter: function() {
                    app.search = '';
                    app.filter_order_by = '',
                        app.filter_delivery_by = '',
                        app.filter_payment_mode = '';
                    app.filter_payment_status = '';
                    app.filter_delivery_status = '';
                    app.filter_customer_name = '';
                    app.filter_employee_id = '';
                    app.due_time = '';
                    app.filter_invoice_no = '';
                    app.filter_end_date = '';
                    app.filter_start_date = '';
                    app.getSalesList();
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
                this.getSalesList();

                this.getOrderByList();
                this.getDeliveryByList();
                this.getProductList();
                this.getLoggedUserInfo();
            }
        });
    </script>
@endsection
