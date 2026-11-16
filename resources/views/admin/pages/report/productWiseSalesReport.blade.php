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
                                    <h4 class="page-title">Product Wise Sales Report</h4>
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

                                    {{-- <div class="col-sm-4">
                                        <Label class="mb-1">Product Name/Code</Label>
                                        <input class="form-control" type="text" v-model="filter_product_name"
                                            placeholder="Product Name or Code">
                                    </div> --}}

                                    {{-- <div class="col-sm-4">
                                        <Label class="mb-1">Category</Label>
                                        <select v-model="filter_category_id" class="form-select">
                                            <option value="">Choose</option>
                                            <option v-for="item in categoryList" :value="item.id">
                                                @{{ item?.category_name }}
                                            </option>
                                        </select>
                                    </div> --}}

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Product</Label>
                                        <select v-model="filter_product_id" class="form-select">
                                            <option value="">Choose</option>
                                            <option v-for="item in productList" :value="item.id">
                                                @{{ item?.product_name }} [@{{ item?.product_code }}]
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="mb-1">Order Type</label>
                                        <select class="form-select" v-model="filter_is_outside" @change="getOrderByList">
                                            <option value=0>Inside Orders</option>
                                            <option value=1>Outside Orders</option>
                                        </select>
                                    </div>


                                    <div class="col-sm-4">
                                        <Label class="mb-1">Order By</Label>
                                        <select v-model="filter_order_by" class="form-select">
                                            <option value="">Choose</option>
                                            <option v-for="item in orderList" :value="item.id">
                                                @{{ item?.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Delivery By</Label>
                                        <select v-model="filter_delivered_by" class="form-select">
                                            <option value="">Choose</option>
                                            <option v-for="item in deliveryManList" :value="item.id">
                                                @{{ item?.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Customer Name</Label>
                                        <input class="form-control" type="text" v-model="customer_name"
                                            placeholder="Customer Name">
                                    </div>




                                    {{-- <div class="col-sm-4">
                                        <Label class="mb-1">Order Status</Label>
                                        <select class="form-select" v-model="filter_order_status">
                                            <option value="">Choose</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="requested">Requested</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div> --}}

                                    <!-- Search & Reset Buttons -->
                                    <div class="col-sm-12 d-flex gap-2 justify-content-end">
                                        <button class="btn btn-primary" v-on:click="getSummaryDetailsPopup()">
                                            View Summary
                                        </button>
                                        <button class="btn btn-primary" v-on:click="getProductWiseSalesList()">
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
                                                            <i class="ti ti-file-type-pdf text-danger me-1"></i>Export as PDF
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item"
                                                            v-on:click="exportToExcel()">
                                                            <i class="ti ti-file-type-xls text-green me-1"></i>Export as Excel
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
                                <!-- Summary Cards -->
                                <div class="row mb-3" v-if="summary">
                                    {{-- <div class="col-md-3">
                                        <div class="card bg-primary text-white">
                                            <div class="card-body">
                                                <h6>Total Products</h6>
                                                <h3>@{{ summary.total_products || 0 }}</h3>
                                            </div>
                                        </div>
                                    </div> --}}
                                    {{-- <div class="col-md-3">
                                        <div class="card bg-warning text-white">
                                            <div class="card-body">
                                                <h6>Total Amount</h6>
                                                <h3>@{{ formatCurrency(summary.grand_total_amount) }}</h3>
                                            </div>
                                        </div>
                                    </div> --}}
                                </div>

                                <!-- Contact List -->
                                <div class="table-responsive custom-table">
                                    <table class="table" id="product_wise_sales_list">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>SI.No</th>
                                                <th>Product Code</th>
                                                <th>Product Name</th>
                                                {{-- <th>Category</th> --}}
                                                {{-- <th>Unit Price</th> --}}
                                                {{-- <th>Selling Price</th> --}}
                                                <th>Quantity</th>
                                                <th>Free Quantity</th>
                                                <th>Returned Quantity</th>
                                                {{-- <th>Net Quantity</th> --}}
                                                {{-- <th>Total Amount</th> --}}
                                                {{-- <th>Average Price</th> --}}
                                                {{-- <th>Total Orders</th> --}}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!productWiseSalesList || !productWiseSalesList.length">
                                                <tr>
                                                    <td colspan="6">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in productWiseSalesList">
                                                <tr>
                                                    <td>@{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                                                    <td>@{{ data.product_code }}</td>
                                                    <td>@{{ data.product_name }}</td>
                                                    {{-- <td>@{{ data.category }}</td> --}}
                                                    {{-- <td>@{{ formatCurrency(data.unit_price) }}</td> --}}
                                                    {{-- <td>@{{ formatCurrency(data.selling_price) }}</td> --}}
                                                    <td>@{{ data.total_quantity }}</td>
                                                    <td>@{{ data.total_free_quantity }}</td>
                                                    <td>@{{ data.total_returned_quantity }}</td>
                                                    {{-- <td>@{{ data.net_quantity }}</td> --}}
                                                    {{-- <td>@{{ formatCurrency(data.total_amount) }}</td> --}}
                                                    {{-- <td>@{{ formatCurrency(data.avg_price) }}</td> --}}
                                                    {{-- <td>@{{ data.total_orders }}</td> --}}
                                                </tr>
                                            </template>
                                        </tbody>
                                        <!-- Grand Total Row -->
                                        {{-- <tfoot v-if="productWiseSalesList && productWiseSalesList.length">
                                            <tr>
                                                <td colspan="6" class="text-end fw-bold">Total:</td>
                                                <td class="fw-bold">@{{ grandTotalQuantity }}</td>
                                                <td class="fw-bold">@{{ grandTotalFreeQuantity }}</td>
                                                <td class="fw-bold">@{{ grandTotalReturnedQuantity }}</td>
                                                <td class="fw-bold">@{{ grandNetQuantity }}</td>
                                                <td class="fw-bold">@{{ formatCurrency(grandTotalAmount) }}</td>
                                                <td></td>
                                                <td class="fw-bold">@{{ grandTotalOrders }}</td>
                                            </tr>
                                        </tfoot> --}}
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

        <div class="modal fade" id="productSummary" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width:700px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Summary</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th>Total Quantity</th>
                                    <td>@{{ totalQty }}</td>
                                </tr>
                                <tr>
                                    <th>Total Free Quantity</th>
                                    <td>@{{ totalFreeQty }}</td>
                                </tr>
                                <tr>
                                    <th>Total Returned Quantity</th>
                                    <td>@{{ totalReturnedQty }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-light" data-bs-dismiss="modal">
                            Close
                        </button>
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
                productWiseSalesList: [],
                productList: [],
                categoryList: [],
                filter_start_date: new Date().toISOString().split('T')[0], // Today's date
                filter_end_date: new Date().toISOString().split('T')[0], // Today's date
                filter_product_name: '',
                filter_category_id: '',
                filter_product_id: '',
                filter_order_status: '',
                customer_name: '',
                filter_delivered_by: '',
                filter_order_by: '',
                customerList: [],
                deliveryManList: [],
                orderList: [],
                search: '',
                totalQty: 0,
                totalFreeQty: 0,
                totalReturnedQty: 0,
                page_length: '10',
                summary: null,
                filter_is_outside: 0,
                pagination: {
                    total: 0,
                    per_page: 15,
                    from: 1,
                    to: 0,
                    current_page: 1,
                    last_page: 1
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
                    return this.productWiseSalesList.reduce((sum, item) => sum + Number(item.total_quantity || 0),
                        0);
                },
                grandTotalFreeQuantity() {
                    return this.productWiseSalesList.reduce((sum, item) => sum + Number(item.total_free_quantity ||
                        0), 0);
                },
                grandTotalReturnedQuantity() {
                    return this.productWiseSalesList.reduce((sum, item) => sum + Number(item
                        .total_returned_quantity || 0), 0);
                },
                grandNetQuantity() {
                    return this.productWiseSalesList.reduce((sum, item) => sum + Number(item.net_quantity || 0), 0);
                },
                grandTotalAmount() {
                    return this.productWiseSalesList.reduce((sum, item) => sum + Number(item.total_amount || 0), 0);
                },
                grandTotalOrders() {
                    return this.productWiseSalesList.reduce((sum, item) => sum + Number(item.total_orders || 0), 0);
                }
            },

            methods: {
                formatCurrency(value) {
                    if (!value) return '0.00';
                    return parseFloat(value).toFixed(2);
                },
                changePage: function(page) {
                    this.getProductWiseSalesList(page);
                },
                getProductWiseSalesList: function(page = 1) {
                    this.loading = true;
                    url = "{{ route('product-wise-sales-report.list') }}";

                    axios.post(url, {
                        page: page,
                        start_date: this.filter_start_date,
                        end_date: this.filter_end_date,
                        product_name: this.filter_product_name,
                        category_id: this.filter_category_id,
                        product_id: this.filter_product_id,
                        order_status: this.filter_order_status,
                        customer_name: this.customer_name,
                        delivered_by: this.filter_delivered_by,
                        order_by: this.filter_order_by,
                        is_outside: this.filter_is_outside,
                    }).then(function(response) {
                        app.productWiseSalesList = response.data.data || [];
                        app.pagination = response.data.pagination || {};

                        app.totalQty = response.data.totals.total_quantity;
                        app.totalFreeQty = response.data.totals.total_free_quantity;
                        app.totalReturnedQty = response.data.totals.total_returned_quantity;
                        app.loading = false;
                    }).catch(function(error) {
                        console.error('Error:', error);
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
                            app.productList = response.data.data.products || [];
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },
                getCategoryList() {
                    axios.get('/api/category/list', {
                            params: {
                                platform: 'api',
                            }
                        })
                        .then(response => {
                            app.categoryList = response.data.data || [];
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },
                getCustomerList() {
                    axios.get('/api/customer/list', {
                            params: {
                                platform: 'api',
                            }
                        })
                        .then(response => {
                            app.customerList = response.data.data || [];
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },


                getOrderByList() {

                    const userTypes = this.filter_is_outside == 0 ? ['salesman', 'sales_executive',
                        'superadmin'
                    ] : ['outside_salesman'];

                    axios.get('/api/get-users', {
                        params: {
                            platform: 'api',
                            user_type: userTypes,
                            customer_type: this.filter_is_outside ?? 0
                        }
                    }).then(response => {
                        app.orderList = response.data.data;
                    });
                    this.getDeliveryByList(this.filter_is_outside);

                },
                getDeliveryByList(filter_is_outside) {



                    const userTypes = filter_is_outside == 0 ? ['salesman'] : [
                        'outside_salesman'
                    ];

                    axios.get('/api/get-users', {
                        params: {
                            platform: 'api',
                            user_type: userTypes,
                            customer_type: filter_is_outside ?? 0
                        }
                    }).then(response => {
                        app.deliveryManList = response.data.data;
                    });
                },
                resetFilter: function() {
                    app.filter_product_name = '';
                    app.filter_category_id = '';
                    app.filter_product_id = '';
                    app.filter_order_status = '';
                    app.customer_name = '';
                    app.filter_delivered_by = '';
                    app.filter_order_by = '';
                    app.filter_is_outside = 0;
                    // app.filter_end_date = '';
                    // app.filter_start_date = '';
                    app.filter_start_date = new Date().toISOString().split('T')[0]; // Today's date
                    app.filter_end_date = new Date().toISOString().split('T')[0]; // Today's date
                    app.getProductWiseSalesList();
                },
                exportToPDF() {
                    var url = "{{ route('product-wise-sales-report.pdf') }}?start_date=" + (this
                            .filter_start_date || '') +
                        "&end_date=" + (this.filter_end_date || '') +
                        "&product_name=" + (this.filter_product_name || '') +
                        "&category_id=" + (this.filter_category_id || '') +
                        "&product_id=" + (this.filter_product_id || '') +
                        "&order_status=" + (this.filter_order_status || '') +
                        "&customer_id=" + (this.customer_name || '') +
                        "&delivered_by=" + (this.filter_delivered_by || '') +
                        "&is_outside=" + (this.filter_is_outside || 0) +
                        "&order_by=" + (this.filter_order_by || '');
                    window.open(url, '_self');
                },
                exportToExcel() {
                    var url = "{{ route('product-wise-sales-report.excel') }}?start_date=" + (this
                            .filter_start_date || '') +
                        "&end_date=" + (this.filter_end_date || '') +
                        "&product_name=" + (this.filter_product_name || '') +
                        "&category_id=" + (this.filter_category_id || '') +
                        "&product_id=" + (this.filter_product_id || '') +
                        "&order_status=" + (this.filter_order_status || '') +
                        "&customer_id=" + (this.customer_name || '') +
                        "&delivered_by=" + (this.filter_delivered_by || '') +
                        "&is_outside=" + (this.filter_is_outside || 0) +
                        "&order_by=" + (this.filter_order_by || '');
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
                    this.getProductWiseSalesList();
                    $('#productSummary').modal('show');
                },



            },



            mounted() {
                this.getProductWiseSalesList();
                this.getProductList();
                this.getCategoryList();
                this.getCustomerList();
                this.getOrderByList();
                this.getDeliveryByList(0);

                this.getLoggedUserInfo();
            }
        });
    </script>
@endsection
