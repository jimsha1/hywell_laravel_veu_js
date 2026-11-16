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
                                    <h4 class="page-title">Commission Report</h4>
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

                                    <div class="col-sm-3">
                                        <Label class="mb-1">Start Date</Label>
                                        <input class="form-control" type="date" v-model="filter_start_date">
                                    </div>

                                    <div class="col-sm-3">
                                        <Label class="mb-1">End Date</Label>
                                        <input class="form-control" type="date" v-model="filter_end_date">
                                    </div>

                                    <div class="col-sm-3">
                                        <Label class="mb-1">Employee</Label>
                                        {{-- <input class="form-control" type="text" v-model="filter_employee"
                                            placeholder="Employee"> --}}
                                        <select class="form-select" v-model="filter_employee">
                                            <option value="">Select Employee</option>
                                            <option v-for="emp in employeeList" :key="emp.id"
                                                :value="emp.name">
                                                @{{ emp.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-sm-3">
                                        <Label class="mb-1">Product</Label>
                                        <select v-model="filter_product_id" class="form-select">
                                            <option value="">Choose</option>
                                            <option v-for="item in productList" :value="item.id">
                                                @{{ item.product_name }}[@{{ item.product_code }}]
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-sm-3">
                                        <Label class="mb-1">Order Code</Label>
                                        <input class="form-control" type="text" v-model="filter_order_code"
                                            placeholder="Order Code">
                                    </div>

                                    <!-- Search & Reset Buttons -->
                                    <div class="col-sm-12 d-flex gap-2 justify-content-end">
                                        <button class="btn btn-primary" v-on:click="getSummaryDetailsPopup()">
                                            View Summary
                                        </button>
                                        <button class="btn btn-primary" v-on:click="getCommissionList()">
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
                                    <div class="row mt-4" v-if="commissionList && commissionList.length">
                                        <div class="col-md-4">
                                            <div class="card shadow-sm border-0">
                                                <div class="card-body text-center">
                                                    <h6 class="text-muted">Total Commission</h6>
                                                    <h4 class="text-success mb-0">@{{ total_commission }}</h4>
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
                                                <th>Employee</th>
                                                <th>Order No</th>
                                                <th>Product</th>
                                                <th>Commission Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!commissionList || !commissionList.length">
                                                <tr>
                                                    <td colspan="6">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in commissionList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    <td>@{{ formatDate(data.date || data.created_at) }}</td>
                                                    <td>@{{ data.order_by.name }}</td>
                                                    <td>@{{ data.order ? data.order.order_no : '' }}</td>
                                                    <td>@{{ data.product.product_name }}[@{{ data.product.product_code }}]</td>
                                                    <td>@{{ data.commission_amount }}</td>
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

        <!-- View commission -->
        <div class="modal fade" id="commission_list" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
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
                            <template v-if="selectedCommission">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                            <th>Commission Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in selectedCommission.commission_history" :key="item.id">
                                            <td>@{{ item.product.product_name || 'N/A' }}</td>
                                            <td>@{{ item.product_quantity }}</td>
                                            <td>@{{ parseFloat(item.product_price).toFixed(2) }}</td>
                                            <td>@{{ parseFloat(item.total_amount).toFixed(2) }}</td>
                                            <td>@{{ parseFloat(item.commission_amount).toFixed(2) }}</td>
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
        <!-- /View commission -->

        <div class="modal fade" id="commissionSummary" role="dialog">
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
                                    <th>Total Commission</th>
                                    <td>@{{ parseFloat(total_commission).toFixed(2) }}</td>
                                </tr>

                                <tr>
                                    <th>Total Records</th>
                                    <td>@{{ total_records }}</td>
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

                commissionList: [],
                productList: [],
                total_commission: "",
                employeeList: [],
                filter_start_date: '',
                filter_end_date: '',
                filter_product_id: '',
                filter_employee: '',
                filter_order_code: '',
                search: '',
                total_commission: 0,
                total_records: 0,
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
                totalCommission() {
                    if (!this.commissionList || !this.commissionList.length) return 0;
                    return this.commissionList.reduce((sum, item) => {
                        return sum + parseFloat(item.commission_amount || 0);
                    }, 0).toFixed(2);
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
                    if (!this.commissionList || !this.commissionList.length) return 0;
                    return this.commissionList.reduce((sum, item) => {
                        return sum + parseFloat(item.total || 0);
                    }, 0).toFixed(2);
                }
            },

            mounted() {
                this.getCommissionList();
                this.getProductList();
                this.getLoggedUserInfo();
                this.getEmployeeList();
            },

            methods: {
                formatDate(dateString) {
                    if (!dateString) return '';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-GB').replaceAll('/', '-');
                },

                changePage: function(page) {
                    this.getCommissionList(page);
                },

                getCommissionList: function(page = 1) {

                    url = "{{ route('commission-report.list') }}";

                    axios.post(url, {
                        page: page,
                        page_length: this.page_length,
                        search: this.search,
                        start_date: this.filter_start_date,
                        end_date: this.filter_end_date,
                        product_id: this.filter_product_id,
                        employee_name: this.filter_employee,
                        order_code: this.filter_order_code,
                    }).then(function(response) {
                        app.commissionList = response.data.data.data;
                        app.pagination = response.data.pagination;
                        app.total_commission = response.data.total_commission;
                        // app.total_commission = response.data.total_commission;
                        app.total_records = response.data.total_records;

                        app.loading = false;
                    });

                },
                getSummaryDetailsPopup() {
                    this.getCommissionList();
                    $('#commissionSummary').modal('show');
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
                getEmployeeList() {
                    axios.get('/api/employees/mainlist', {
                            params: {
                                platform: 'api' // important → returns active employees only
                            }
                        })
                        .then(response => {
                            this.employeeList = response.data.data.employees;
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },
                showCommissionListModal(data) {
                    if (data && data.id > 0) {
                        this.selectedCommission = data;
                        $('#commission_list').modal('show');
                    }
                },
                resetFilter: function() {
                    app.search = '';
                    app.filter_employee = '';
                    app.filter_product_id = '';
                    app.filter_order_code = '';
                    app.filter_end_date = '';
                    app.filter_start_date = '';
                    app.getCommissionList();
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


                exportToExcel() {
                    var url = "{{ route('commission-report-export-to-excel') }}?search=" + this.search +
                        "&product_id=" + (this.filter_product_id ?? '') +
                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '') +
                        "&employee_name=" + (this.filter_employee ?? '') +
                        "&order_code=" + (this.filter_order_code ?? '');


                    console.log(url);

                    window.open(url, '_self');
                },




                exportToPDF() {
                    var url = "{{ route('commission-report-export-to-pdf') }}?search=" + this.search +
                        "&product_id=" + (this.filter_product_id ?? '') +
                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '') +
                        "&employee_name=" + (this.filter_employee ?? '') +
                        "&order_code=" + (this.filter_order_code ?? '');

                    console.log(url);

                    window.open(url, '_self');
                },
            },


        });
    </script>
@endsection
