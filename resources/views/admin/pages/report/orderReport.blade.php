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
                                    <h4 class="page-title">Order Report</h4>
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

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Start Date</Label>
                                        <input class="form-control" type="date" v-model="filter_start_date">
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">End Date</Label>
                                        <input class="form-control" type="date" v-model="filter_end_date">
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Order No/Order Type/Order By</Label>
                                        <input class="form-control" type="text" v-model="filter_order_no"
                                            placeholder="Search">
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Customer Name </Label>
                                        <input class="form-control" type="text" v-model="filter_customer_name"
                                            placeholder="Customer Name">
                                    </div>

                                    <div class="col-sm-3 mt-2">
                                        <label class="mb-1">Customer Type</label>
                                        <select v-model="filter_customer_type" class="form-select" @change="getOrderList">
                                            <option value="in">Company Customers</option>
                                            <option value="out">Outside Customers</option>

                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <Label class="mb-1">Order Status</Label>
                                        <select class="form-select" v-model="filter_order_status">
                                            <option value="">All</option>
                                            <option value="requested">Requested</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>

                                    <!-- Search & Reset Buttons -->
                                    <div class="col-sm-12 d-flex gap-2 justify-content-end">
                                        <button class="btn btn-primary" v-on:click="getSummaryDetailsPopup()">
                                            View Summary
                                        </button>
                                        <button class="btn btn-primary" v-on:click="getOrderList()">
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
                                                        <input type="checkbox" id="select-all"><span
                                                            class="checkmarks"></span>
                                                    </label>
                                                </th>
                                                <th>Order No</th>
                                                <th>Date</th>
                                                <th>Order Type</th>
                                                <th>Order By</th>
                                                <th>Delivery By</th>
                                                <th>customer Name</th>
                                                <th>Total Quantity</th>
                                                <th>Free Quantity</th>
                                                <th>Total Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!orderList || !orderList.length">
                                                <tr>
                                                    <td colspan="10">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in orderList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    <td>
                                                        <a href="javascript:void(0);" @click="showOrderListModal(data)"
                                                            style="color: blue;">
                                                            @{{ data.order_no }}
                                                        </a>
                                                    </td>
                                                    <td>@{{ formatDate(data.order_date) }}</td>
                                                    <td>@{{ data.order_type }}</td>
                                                    <td>@{{ data.order_by?.name }}</td>
                                                    <td>@{{ data.delivery_by?.name }}</td>
                                                    {{-- <td v-if="data.customer">@{{ data.customer.customer_name }}[@{{ data.customer.customer_code }}]
                                                    </td>
                                                    <td v-else>@{{ data.display_customer.name }}[Salesman]</td> --}}
                                                    <td v-if="data.display_customer">@{{ data.display_customer.name }} <span v-if="data.display_customer.type=='employee'">[Outside Salesman]</span>
                                                    </td>
                                                    <td v-else>@{{ data.customer.customer_name }}[@{{ data.customer.customer_code }}]</td>
                                                    <td>@{{ data.total_quantity }}</td>
                                                    <td>@{{ getFreeQty(data.order_items) }}</td>
                                                    <td>@{{ data.total_amount }}</td>
                                                    <td>
                                                        <span v-if="data.order_status === 'delivered'"
                                                            class="badge bg-success">
                                                            Delivered
                                                        </span>
                                                        <span v-else-if="data.order_status === 'requested'"
                                                            class="badge bg-info">
                                                            Requested
                                                        </span>
                                                        <span v-else-if="data.order_status === 'rejected'"
                                                            class="badge bg-danger">
                                                            Rejected
                                                        </span>
                                                        <span v-else class="badge bg-secondary">
                                                            @{{ data.order_status || 'N/A' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        <!-- Grand Total Row -->
                                        <tfoot v-if="orderList && orderList.length">
                                            <tr>
                                                <td colspan="6" class="text-end fw-bold"> Total:</td>
                                                <td class="fw-bold">@{{ grandTotalQuantity }}</td>
                                                <td class="fw-bold">@{{ grandTotalAmount }}</td>
                                                <td colspan="2"></td>
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


        <div class="modal fade" id="orderSummary" role="dialog">
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
                                        <td>@{{ totalQty ? totalQty : 0 }}</td>
                                    </tr>
                                    <tr>
                                        <th>Total Free Quantity</th>
                                        <td>@{{ totalFreeQty ? totalFreeQty : 0 }}</td>
                                    </tr>
                                    <tr>
                                        <th>Total Amount</th>
                                        <td>@{{ totalAmount ? parseFloat(totalAmount).toFixed(2) : 0 }}</td>
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

                orderList: [],
                productList: [],

                totalQty: '',
                totalFreeQty: '',
                totalAmount: '',

                filter_start_date: '',
                filter_end_date: '',
                filter_order_no: '',
                filter_customer_name: '',
                filter_order_status: '',
                filter_customer_type: 'in',
                filter_payment_status: '',
                selectedOrder: '',
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
                grandTotalQuantity() {
                    return this.orderList.reduce((sum, item) => sum + Number(item.total_quantity || 0), 0);
                },
                grandTotalAmount() {
                    return this.orderList.reduce((sum, item) => sum + Number(item.total_amount || 0), 0);
                },
                grandPaidAmount() {
                    return this.orderList.reduce((sum, item) => sum + Number(item.paid_amount || 0), 0);
                },
                grandBalanceAmount() {
                    return this.orderList.reduce((sum, item) => sum + Number(item.balance_amount || 0), 0);
                }

            },

            methods: {
                formatDate(dateString) {
                    if (!dateString) return '';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-GB').replaceAll('/', '-');
                },

                getFreeQty(items) {
                    return items.reduce((sum, item) => sum + Number(item.free_quantity || 0), 0);
                },
                changePage: function(page) {
                    this.getOrderList(page);
                },

                getOrderList: function(page = 1) {

                    url = "{{ route('order-report.list') }}";

                    axios.post(url, {
                        page: page,
                        page_length: this.page_length,
                        search: this.search,
                        start_date: this.filter_start_date,
                        end_date: this.filter_end_date,
                        order: this.filter_order_no,
                        customer_name: this.filter_customer_name,
                        order_status: this.filter_order_status,
                        payment_status: this.filter_payment_status,
                        customer_type: this.filter_customer_type,
                    }).then(function(response) {
                        app.orderList = response.data.data.data;
                        app.pagination = response.data.pagination;

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
                showOrderListModal(data) {
                    if (data && data.id > 0) {
                        this.selectedOrder = data;
                        $('#order_list').modal('show');
                    }
                },
                resetFilter: function() {
                    app.search = '';
                    app.filter_payment_status = '';
                    app.filter_order_status = '';
                    app.filter_customer_type = 'in';
                    app.filter_customer_name = '';
                    app.filter_order_no = '';
                    app.filter_end_date = '';
                    app.filter_start_date = '';
                    app.getOrderList();
                },
                exportToPDF() {
                    var url = "{{ route('order-report-export-to-pdf') }}?search=" + this.search +
                        "&customer_name=" + (this.filter_customer_name ?? '') +
                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '') +
                        "&order=" + (this.filter_order_no ?? '') +
                        "&order_status=" + (this.filter_order_status ?? '') +
                        "&customer_type=" + (this.filter_customer_type ?? '');

                    console.log(url);

                    window.open(url, '_self');
                },
                exportToExcel() {
                    var url = "{{ route('order-report-export-to-excel') }}?search=" + this.search +
                        "&customer_name=" + (this.filter_customer_name ?? '') +
                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '') +
                        "&order=" + (this.filter_order_no ?? '') +
                        "&order_status=" + (this.filter_order_status ?? '') +
                        "&customer_type=" + (this.filter_customer_type ?? '');

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
                getSummaryDetailsPopup() {
                    app.getOrderList();
                    $('#orderSummary').modal('show');
                },

                getOrderList: function(page = 1) {

                    url = "{{ route('order-report.list') }}";

                    axios.post(url, {
                        page: page,
                        page_length: this.page_length,
                        search: this.search,
                        start_date: this.filter_start_date,
                        end_date: this.filter_end_date,
                        order: this.filter_order_no,
                        customer_name: this.filter_customer_name,
                        order_status: this.filter_order_status,
                        payment_status: this.filter_payment_status,
                        customer_type: this.filter_customer_type,
                    }).then(function(response) {
                        app.orderList = response.data.data.data;
                        app.pagination = response.data.pagination;

                        // NEW: set totals
                        app.totalQty = response.data.totals.total_quantity;
                        app.totalFreeQty = response.data.totals.total_free_quantity;
                        app.totalAmount = response.data.totals.total_amount;

                        app.loading = false;
                    });

                },

            },


            mounted() {
                this.getOrderList();
                // this.getSupplierList();
                this.getProductList();
                this.getLoggedUserInfo();
            },


        });
    </script>
@endsection
