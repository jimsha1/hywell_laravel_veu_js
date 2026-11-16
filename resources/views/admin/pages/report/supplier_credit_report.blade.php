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
                                    <h4 class="page-title">Supplier Credit Report</h4>
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
                                        <Label class="mb-1">Supplier Name</Label>
                                        <input class="form-control" type="text" v-model="filter_supplier_name"
                                            placeholder="Supplier Name">
                                    </div>

                                    <div class="col-sm-6">
                                        <Label class="mb-1">Supplier Code</Label>
                                        <input class="form-control" type="text" v-model="filter_supplier_code"
                                            placeholder="Supplier Code">
                                    </div>

                                    

                                    <!-- Search & Reset Buttons -->
                                    <div class="col-sm-12 d-flex gap-2 justify-content-end">
                                        <button class="btn btn-primary" v-on:click="getSupplierCreditReport()">
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
                                <!-- /Filters -->
                            </div>

                            <div class="card-body">
                                <!-- Total Credit Summary Card -->
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="card text-white" style="background-color: #c2185b30;">
                                            <div class="card-body text-center">
                                                <h6 class="card-title" style="color: #171724; font-size: 16px;">Total Credit
                                                    Amount</h6>
                                                <h3 class="mb-0" style="color: #c61b5d; font-size: 25px;">
                                                    @{{ formatCurrency(grandTotalCredit) }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Supplier Credit Table -->
                                <div class="table-responsive custom-table">
                                    <table class="table" id="supplier_credit_report">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Supplier Code</th>
                                                <th>Supplier Name</th>
                                                {{-- <th>Email</th> --}}
                                                <th>Phone</th>
                                                <th>Credit Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!supplierList || !supplierList.length">
                                                <tr>
                                                    <td colspan="6" class="text-center">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(supplier, index) in supplierList"
                                                :key="supplier.id">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    <td>@{{ supplier.supplier_code || '-' }}</td>
                                                    <td>@{{ supplier.supplier_name || '-' }}</td>
                                                    {{-- <td>@{{ supplier.email || '-' }}</td> --}}
                                                    <td>@{{ supplier.phone || '-' }}</td>
                                                    <td class="fw-bold text-danger">@{{ formatCurrency(supplier.balance) }}</td>
                                                </tr>
                                            </template>
                                        </tbody>
                                      
                                    </table>
                                </div>
                            </div>
                        </div>
                        {{-- pagination --}}
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
                supplierList: [],
                totalCreditAmount: 0,
                filter_supplier_name: '',
                filter_supplier_code: '',
              
                employeesList: [],
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
                grandTotalCredit() {
                    return this.totalCreditAmount || 0;
                }
            },

            methods: {
                formatCurrency(value) {
                    if (!value) return '0.00';
                    return parseFloat(value).toFixed(2);
                },

                changePage: function(page) {
                    this.getSupplierCreditReport(page);
                },

                getSupplierCreditReport: function(page = 1) {
                    this.loading = true;
                    const url = "{{ route('supplier-credit-report.list') }}";

                    axios.post(url, {
                        page: page,
                        page_length: this.page_length,
                        supplier_name: this.filter_supplier_name,
                        supplier_code: this.filter_supplier_code,
                       
                    }).then(response => {
                        if (response.data.code == 0 || response.data.data) {
                            this.supplierList = response.data.data?.data || response.data.data || [];
                            this.pagination = response.data.pagination || response.data.data
                                ?.pagination || {
                                    total: this.supplierList.length,
                                    per_page: this.page_length,
                                    current_page: 1,
                                    last_page: 1
                                };
                            // Store total credit amount from API (all pages)
                            this.totalCreditAmount = response.data.total_credit_amount || 0;
                        } else {
                            Vue.$toast.error(response.data.message ||
                                'Failed to load customer credit report', 'Error Alert');
                        }
                        this.loading = false;
                    }).catch(error => {
                        console.log("Error:", error);
                        Vue.$toast.error('Failed to load customer credit report', 'Error Alert');
                        this.loading = false;
                    });
                },

                resetFilter() {
                    this.filter_supplier_name = '';
                    this.filter_supplier_code = '';
                   
                    this.supplierList = [];
                    this.totalCreditAmount = 0;
                    this.getSupplierCreditReport();
                },

                exportToPDF() {
                    var url = "{{ route('supplier-credit-report-export-to-pdf') }}?supplier_name=" +
                        (this.filter_supplier_name ?? '') +
                        "&supplier_code=" + (this.filter_supplier_code ?? '') ;

                    window.open(url, '_self');
                },

                exportToExcel() {
                    var url = "{{ route('supplier-credit-report-export-to-excel') }}?supplier_name=" +
                        (this.filter_supplier_name ?? '') +
                        "&supplier_code=" + (this.filter_supplier_code ?? '');

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
                            $('#user_image_header').attr('src', this.base_url + '/storage/' + userImage);
                        } else {
                            const defaultImg = 'https://cdn-icons-png.freepik.com/512/9703/9703596.png';
                            $('#user_image').attr('src', defaultImg);
                            $('#user_image_header').attr('src', defaultImg);
                        }
                    }
                },

                getEmployeesList() {
                    axios.get('/api/get-users', {
                        params: {
                            platform: 'api',
                            user_type: ['salesman', 'sales_executive']
                        }
                    }).then((response) => {
                        if (response.data.code === 0) {
                            this.employeesList = response.data.data || [];
                        }
                    }).catch((error) => {
                        console.error('Error loading employees:', error);
                    });
                },

                getEmployeeType(userType) {
                    const types = {
                        'salesman': 'Salesman',
                        'sales_man': 'Salesman',
                        'sales_executive': 'Sales Executive',
                    };
                    return types[userType] || userType;
                },
            },

            mounted() {
                this.getLoggedUserInfo();
                this.getEmployeesList();
                this.getSupplierCreditReport();
            }
        });
    </script>
@endsection
