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
                                    <h4 class="page-title">Daily Sales Report</h4>
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
                                <!-- Filters -->
                                <div class="row align-items-center justify-content-left row-gap-3">
                                    <div class="col-sm-6">
                                        <Label class="mb-1">Start Date</Label>
                                        <input class="form-control" type="date" v-model="filter_start_date">
                                    </div>

                                    <div class="col-sm-6">
                                        <Label class="mb-1">End Date</Label>
                                        <input class="form-control" type="date" v-model="filter_end_date">
                                    </div>

                                    <div class="col-sm-3 mt-2">
                                        <label class="mb-1">Employee Type</label>
                                        <select v-model="filter_employee_type" class="form-select" @change="getUsersList">
                                            <option value="in">Company Employee</option>
                                            <option value="out">Outside Employee</option>

                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <Label class="mb-1">Employee (Salesman/Sales Executive)</Label>
                                        <select v-model="filter_employee_id" class="form-select">
                                            <option value="">All Employees</option>
                                            <option v-for="item in employeeList" :value="item.id">
                                                @{{ item.name }} (@{{ item.user_type }})
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Search & Reset Buttons -->
                                    <div class="col-sm-12 d-flex gap-2 justify-content-end">
                                        <button class="btn btn-primary" v-on:click="getDailySalesReport()">
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
                                        <div class="card text-white" style="background-color: rgba(251, 138, 0, 0.12);">
                                            <div class="card-body text-center">
                                                <h6 class="card-title" style="color: #171724; font-size: 16px;">Total Sales
                                                </h6>
                                                <h3 class="mb-0" style="color: rgb(251, 140, 0); font-size: 25px;">
                                                    @{{ formatCurrency(grandTotals.grand_total_sales) }}</h3>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card text-white" style="background-color: #0d6efd30;">
                                            <div class="card-body text-center">
                                                <h6 class="card-title" style="color: #171724; font-size: 16px;">Cash</h6>
                                                <h3 class="mb-0" style="color: #0d6efd; font-size: 25px;">
                                                    @{{ formatCurrency(grandTotals.cash) }}</h3>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- <div class="col-md-3 col-sm-6 mb-3">
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
                                                    @{{ formatCurrency(grandTotals.grand_total_sales) }}</h3>
                                            </div>
                                        </div>
                                    </div> --}}

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
                                                <h6 class="card-title" style="color: #171724; font-size: 16px;">Credit
                                                </h6>
                                                <h3 class="mb-0" style="color: #c61b5d; font-size: 25px;">
                                                    @{{ formatCurrency(grandTotals.credit) }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Table -->
                                <div class="table-responsive custom-table">
                                    <table class="table" id="daily_sales_report">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Order Code</th>
                                                <th>Order By</th>
                                                <th>Delivery By</th>
                                                <th>Status</th>
                                                <th>Cash</th>
                                                {{-- <th>Card</th> --}}
                                                {{-- <th>Online</th> --}}

                                                <th>Alrajhi (Bank)</th>
                                                <th>SAB (Bank)</th>
                                                <th>Alrajhi (POS)</th>
                                                <th>SAB (POS)</th>
                                                <th>Credit</th>
                                                {{-- <th>POS Alrajhi</th>
                                                <th>POS SAB</th> --}}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!orders || !orders.length">
                                                <tr>
                                                    <td colspan="13" class="text-center">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else>
                                                <tr v-for="(order, index) in orders" :key="order.order_id">
                                                    <td>@{{ pagination && pagination.per_page ? (pagination.current_page - 1) * pagination.per_page + index + 1 : index + 1 }}</td>
                                                    <td>
                                                        <a href="javascript:void(0);" @click="showOrderListModal(order)"
                                                            style="color: blue;">
                                                            @{{ order.order_no }}
                                                        </a>
                                                    </td>
                                                    <td>@{{ order.order_by }}</td>
                                                    <td>@{{ order.delivered_by }}</td>
                                                    <td>
                                                        <span
                                                            :class="{
                                                                'badge bg-warning text-dark': order
                                                                    .order_status === 'Requested',
                                                                'badge bg-success': order.order_status === 'delivered',
                                                                'badge bg-danger': order.order_status === 'rejected',
                                                                'badge bg-secondary': order
                                                                    .order_status !== 'Requested' && order
                                                                    .order_status !== 'Delivered'
                                                            }">
                                                            @{{ order.order_status }}
                                                        </span>
                                                    </td>


                                                    <td>@{{ formatCurrency(order.cash) }}</td>
                                                    {{-- <td>@{{ formatCurrency(order.bank) }}</td> --}}
                                                    {{-- <td>@{{ formatCurrency(order.card) }}</td> --}}
                                                    {{-- <td>@{{ formatCurrency(order.online) }}</td> --}}
                                                    <td>@{{ formatCurrency(order.bank_transfer_alrajhi) }}</td>
                                                    <td>@{{ formatCurrency(order.bank_transfer_sab) }}</td>
                                                    <td>@{{ formatCurrency(order.pos_alrajhis) }}</td>
                                                    <td>@{{ formatCurrency(order.pos_sab) }}</td>
                                                    <td>@{{ formatCurrency(order.credit) }}</td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        {{-- <tfoot v-if="orders && orders.length">
                                            <tr class="fw-bold">
                                                <td colspan="5" class="text-end">Grand Total:</td>
                                                <td>@{{ formatCurrency(grandTotals.cash) }}</td>

                                                <td>@{{ formatCurrency(grandTotals.card) }}</td>
                                                <td>@{{ formatCurrency(grandTotals.online) }}</td>

                                                <td>@{{ formatCurrency(grandTotals.bank_transfer_alrajhi) }}</td>
                                                <td>@{{ formatCurrency(grandTotals.bank_transfer_sab) }}</td>
                                                <td>@{{ formatCurrency(grandTotals.pos_alrajhis) }}</td>
                                                <td>@{{ formatCurrency(grandTotals.pos_sab) }}</td>
                                                <td>@{{ formatCurrency(grandTotals.credit) }}</td>
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


        <!-- View Purchase -->
        <div class="modal fade" id="order_list" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Products</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card-body overflow-hidden">
                            <template v-if="selectedOrder">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Free Qty</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in selectedOrder.order_items" :key="item.id">
                                            <td>@{{ item.product.product_name || 'N/A' }}</td>
                                            <td>@{{ item.quantity }}</td>
                                            <td>@{{ item.free_quantity }}</td>
                                            <td>@{{ parseFloat(item.price).toFixed(2) }}</td>
                                            <td>@{{ parseFloat(item.total).toFixed(2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </template>
                            <template v-else>
                                <p>No purchase data available.</p>
                            </template>
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
        <!-- /View Purchase -->
    </div>
@endsection

@section('script')
    <script>
        var app = new Vue({
            el: '#app',
            data: {
                loading: false,
                base_url: "{{ url('/') }}",
                orders: [],
                employeeList: [],
                grandTotals: null,
                filter_start_date: '',
                filter_end_date: '',
                filter_employee_id: '',
                filter_employee_type: 'in',
                selectedOrder: '',
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
                    this.getDailySalesReport(page);
                },

                getDailySalesReport(page = 1) {
                    this.loading = true;
                    const url = "{{ route('daily-sales-report.list') }}";

                    axios.post(url, {
                        page: page,
                        start_date: this.filter_start_date,
                        end_date: this.filter_end_date,
                        employee_type:this.filter_employee_type,
                        employee_id: this.filter_employee_id,
                    }).then(response => {
                        if (response.data.code == 0) {
                            this.orders = response.data.data.orders || [];
                            this.grandTotals = response.data.data.grand_totals || null;
                            this.pagination = response.data.data.pagination || this.pagination;
                        } else {
                            Vue.$toast.error(response.data.message, 'Error Alert');
                        }
                        this.loading = false;
                    }).catch(error => {
                        console.log("Error:", error);
                        Vue.$toast.error('Failed to load daily sales report', 'Error Alert');
                        this.loading = false;
                    });
                },

                getUsersList() {
                      const userTypes = this.filter_employee_type === 'in' ? ['salesman', 'sales_executive','superadmin'
                    ] : ['outside_salesman'];

                    axios.get('/api/get-users', {
                        params: {
                            platform: 'api',
                            user_type: userTypes,
                            customer_type: this.filter_employee_type ?? 'in'
                        }

                        
                    }).then(response => {
                        app.employeeList = response.data.data || [];
                    }).catch(error => {
                        console.log("Error loading users:", error);
                    });
                },

                showOrderListModal(order) {
                    if (!order || !order.order_id) {
                        Vue.$toast.error('Order information not available', 'Error Alert');
                        return;
                    }

                    this.loading = true;

                    // Fetch order details with items
                    const url = "{{ route('get-order-details') }}";
                    axios.post(url, {
                        order_id: order.order_id
                    }).then(response => {
                        if (response.data.code == 0) {
                            this.selectedOrder = response.data.data;
                            $('#order_list').modal('show');
                        } else {
                            Vue.$toast.error(response.data.message || 'Failed to load order details',
                                'Error Alert');
                        }
                        this.loading = false;
                    }).catch(error => {
                        console.log("Error loading order details:", error);
                        Vue.$toast.error('Failed to load order details', 'Error Alert');
                        this.loading = false;
                    });
                },

                resetFilter() {
                    this.filter_start_date = '';
                    this.filter_end_date = '';
                    this.filter_employee_type='in';
                    this.filter_employee_id = '';
                    this.employeeData = [];
                    this.grandTotals = null;
                    this.orders = [];
                    this.pagination = {
                        total: 0,
                        per_page: 0,
                        from: 1,
                        to: 0,
                        current_page: 1,
                        last_page: 1
                    };

                    const today = new Date().toISOString().split('T')[0];
                    this.filter_start_date = today;
                    this.filter_end_date = today;
                    this.getDailySalesReport();
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
                this.getDailySalesReport();
            }
        });
    </script>
@endsection
