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
                                    <h4 class="page-title">Stock Report</h4>
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
                                        <Label class="mb-1">Product</Label>
                                        <select v-model="filter_product_id" class="form-select">
                                            <option value="">Choose</option>
                                            <option v-for="item in productList" :value="item.id">
                                                @{{ item.product_name }}[@{{ item.product_code }}]
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Sale Man</Label>
                                        <select v-model="filter_salesman_id" class="form-select">
                                            <option value="">Choose</option>
                                            <option v-for="item in salesManList" :value="item.id">
                                                @{{ item.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Stock Movement Mode</Label>
                                        <select v-model="filter_stock_movement" class="form-select">
                                            <option value="">Choose</option>
                                            <option value="in">IN</option>
                                            <option value="out">OUT</option>
                                            {{-- <option value="closing stock">Closing stock</option> --}}
                                            </option>
                                        </select>
                                    </div>
                                    {{-- <div class="col-sm-4">
                                        <label class="mb-1">Customer Type</label>
                                        <select v-model="filter_customer_type" class="form-select" @change="getStockList()">
                                            <option value="company">Company Customers</option>
                                            <option value="outside">Outside Customers</option>
                                        </select>
                                    </div> --}}

                                    <!-- Search & Reset Buttons -->
                                    <div class="col-sm-12 d-flex gap-2 justify-content-end">
                                        <button class="btn btn-primary" v-on:click="getStockList()">
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
                                    <div class="row mt-4" v-if="stockList && stockList.length">
                                        <div class="col-md-4">
                                            <div class="card shadow-sm border-0">
                                                <div class="card-body text-center">
                                                    <h6 class="text-muted">Total Inward</h6>
                                                    <h4 class="text-success mb-0">@{{ getTotalInward }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card shadow-sm border-0">
                                                <div class="card-body text-center">
                                                    <h6 class="text-muted">Total Outward</h6>
                                                    <h4 class="text-danger mb-0">@{{ getTotalOutward }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card shadow-sm border-0">
                                                <div class="card-body text-center">
                                                    <h6 class="text-muted">Closing Balance</h6>
                                                    <h4 class="text-primary mb-0">@{{ getClosingBalance }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card shadow-sm border-0">
                                                <div class="card-body text-center">
                                                    <h6 class="text-muted">Total Opening Stock</h6>
                                                    <h4 class="text-success mb-0">
                                                        @{{ getTotalOpeningStock.toFixed(2) }}
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="card shadow-sm border-0">
                                                <div class="card-body text-center">
                                                    <h6 class="text-muted">Total Closing Stock</h6>
                                                    <h4 class="text-primary mb-0">
                                                        @{{ getTotalClosingStock.toFixed(2) }}
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

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

                                                <th>Date</th>
                                                <th>Operation</th>
                                                <th>Product</th>
                                                <th>Inward</th>
                                                <th>Outward</th>
                                                <th>Remaining qty</th>
                                                <th>Cost Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!stockList || !stockList.length">
                                                <tr>
                                                    <td colspan="5">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in stockList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    <td>@{{ formatDate(data.date || data.created_at) }}</td>
                                                    <td>@{{ data.movement_type }}(@{{ data.reason }})</td>
                                                    <td>@{{ data.product.product_name }}</td>
                                                    <td>
                                                        @{{ data.movement_type === 'in' ? Math.floor(data.quantity) : 0 }}
                                                    </td>
                                                    <td>
                                                        @{{ data.movement_type === 'out' ? Math.floor(data.quantity) : 0 }}
                                                    </td>
                                                    <td>
                                                        @{{ Math.floor(data.remaining_qty || 0) }}
                                                    </td>
                                                    <td> @{{ data.cost_price }}
                                                    </td>


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


    </div>
@endsection

@section('script')
    <script>
        var app = new Vue({
            el: '#app',
            data: {
                loading: false,

                base_url: "{{ url('/') }}",

                stockList: [],
                productList: [],
                salesManList: [],
                {{-- filter_customer_type: 'company', --}}

                totals: {
                    total_inward: 0,
                    total_outward: 0,
                    closing_balance: 0,
                    total_opening_stock: 0,
                    total_closing_stock: 0,
                },

                filter_start_date: '',
                filter_end_date: '',
                filter_product_id: '',
                filter_salesman_id: '',
                filter_stock_movement: '',
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
                getTotalInward() {
                    return this.totals.total_inward || 0;
                },
                getTotalOutward() {
                    return this.totals.total_outward || 0;
                },
                getClosingBalance() {
                    return this.totals.closing_balance || 0;
                },
                getTotalOpeningStock() {
                    return this.totals.total_opening_stock || 0;
                },
                getTotalClosingStock() {
                    return this.totals.total_closing_stock || 0;
                },
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
                getGrandTotal() {
                    if (!this.stockList || !this.stockList.length) return 0;
                    return this.stockList.reduce((sum, item) => {
                        return sum + parseFloat(item.total || 0);
                    }, 0).toFixed(2);
                }
            },

            methods: {
                formatDate(dateString) {
                    if (!dateString) return '';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-GB').replaceAll('/', '-');
                },
                changePage: function(page) {
                    this.getStockList(page);
                },
                getStockList: function(page = 1) {

                    url = "{{ route('original-stock-report.list') }}";

                    axios.post(url, {
                        page: page,
                        page_length: this.page_length,
                        search: this.search,
                        start_date: this.filter_start_date,
                        end_date: this.filter_end_date,
                        product_id: this.filter_product_id,
                        salesman_id: this.filter_salesman_id,
                        stock_movement_mode: this.filter_stock_movement,
                        {{-- customer_type: this.filter_customer_type, --}}
                    }).then(function(response) {
                        app.stockList = response.data.data.data;
                        app.pagination = response.data.pagination;

                        // Store totals from backend
                        if (response.data.totals) {
                            app.totals = {
                                total_inward: response.data.totals.total_inward || 0,
                                total_outward: response.data.totals.total_outward || 0,
                                closing_balance: response.data.totals.closing_balance || 0,
                                total_opening_stock: response.data.totals.total_opening_stock || 0,
                                total_closing_stock: response.data.totals.total_closing_stock || 0,
                            };
                        } else {
                            app.totals = {
                                total_inward: 0,
                                total_outward: 0,
                                closing_balance: 0,
                            };
                        }

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
                getSupplierList() {
                    axios.get('/api/supplier/list', {
                            params: {
                                platform: 'api'
                            }
                        })
                        .then(response => {
                            app.supplierList = response.data.data;
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },
                getUsersList() {
                    const userTypes = ['salesman', 'sales_executive', 'superadmin'];
                    axios.get('/api/get-users', {
                        params: {
                            platform: 'api',
                            user_type: userTypes
                        }
                    }).then(response => {
                        app.salesManList = response.data.data;
                    });
                },

                showPurchaseListModal(data) {
                    if (data && data.id > 0) {
                        this.selectedPurchase = data;
                        $('#purchase_list').modal('show');
                    }
                },
                resetFilter: function() {
                    app.search = '';
                    app.filter_product_id = '';
                    app.filter_salesman_id = '';
                    app.filter_stock_movement = '';
                    app.filter_end_date = '';
                    app.filter_start_date = '';
                    app.totals = {
                        total_inward: 0,
                        total_outward: 0,
                        closing_balance: 0,
                    };
                    app.getStockList();
                },

                exportToExcel() {
                    var url = "{{ route('original-stock-report-export-to-excel') }}?search=" + this.search +
                        "&product_id=" + (this.filter_product_id ?? '') +
                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '') +
                        "&stock_movement_mode=" + (this.filter_stock_movement ?? '') +
                        "&salesman_id=" + (this.filter_salesman_id ?? '') +
                        "&customer_type=" + (this.filter_customer_type ?? 'company');
                    // "&order_no=" + (this.filter_order_no ?? '') +
                    // "&payment_mode=" + (this.filter_payment_mode ?? '');

                    console.log(url);

                    window.open(url, '_self');
                },


                exportToPDF() {
                    var url = "{{ route('original-stock-report-export-to-pdf') }}?search=" + this.search +
                        "&product_id=" + (this.filter_product_id ?? '') +
                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '') +
                        "&stock_movement_mode=" + (this.filter_stock_movement ?? '') +
                        "&salesman_id=" + (this.filter_salesman_id ?? '') +
                        "&customer_type=" + (this.filter_customer_type ?? 'company');
                    // "&order_no=" + (this.filter_order_no ?? '')+
                    // "&payment_mode=" + (this.filter_payment_mode ?? '');

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
                this.getStockList();
                this.getProductList();
                this.getUsersList();
                this.getLoggedUserInfo();
            }
        });
    </script>
@endsection
