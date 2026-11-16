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
                                    <h4 class="page-title">Supplier Payment Details Report</h4>
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
                                        <Label class="mb-1">Supplier</Label>
                                        <select v-model="filter_supplier_id" class="form-select">
                                            <option value="">Choose</option>
                                            <option v-for="item in supplierList" :value="item.id">
                                                @{{ item.supplier_name }}[@{{ item.supplier_code }}]
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <Label class="mb-1">Payment mode</Label>
                                        <select v-model="filter_payment_mode" class="form-select">
                                            <option value="">Choose</option>
                                            <option value="cash">Cash</option>
                                            <option value="alrajhi">Alrajhi</option>
                                            <option value="sab">sab</option>
                                            
                                        </select>
                                        </select>
                                    </div>

                                    <!-- Search & Reset Buttons -->
                                    <div class="col-sm-12 d-flex gap-2 justify-content-end">
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
                                    <div class="row mt-4" v-if="paymentList && paymentList.length">
                                        <div class="col-md-4">
                                            <div class="card shadow-sm border-0">
                                                <div class="card-body text-center">
                                                    <h6 class="text-muted">Total Paid Amount</h6>
                                                    <h4 class="text-success mb-0">
                                                        @{{ parseFloat(total_paid_amount || 0).toFixed(2) }}
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
                                    <table class="table" id="supplier_payment_list">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>SI.No</th>
                                                <th>Payment Date</th>
                                                <th>Purchase No</th>
                                                <th>Supplier Code</th>
                                                <th>Supplier Name</th>
                                                <th>Paid Amount</th>
                                                {{-- <th>Balance Amount</th> --}}
                                                <th>Payment Mode</th>
                                                <th>Description</th>
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
                                                    <td>@{{ (data.purchase && data.purchase.purchase_no) ? data.purchase.purchase_no: ((!data.purchase_id) ? 'Opening Balance' : '-') }}</td>
                                                    <td>@{{ data.purchase && data.purchase.supplier ? data.purchase.supplier.supplier_code : data.supplier?.supplier_code || '-' }}</td>
                                                    <td>@{{ data.purchase && data.purchase.supplier ? data.purchase.supplier.supplier_name : data.supplier?.supplier_name || '-' }}</td>
                                                    <td>@{{ parseFloat(data.amount_taken || 0).toFixed(2) }}</td>
                                                    {{-- <td>@{{ getBalanceAmount(data) }}</td> --}}
                                                    <td>@{{ data.payment_mode || '-' }}</td>
                                                    <td>@{{data.description || '-' }}</td>
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
                supplierList: [],

                filter_start_date: '',
                filter_end_date: '',
                filter_supplier_id: '',
                filter_payment_mode:'',
                search: '',
                total_paid_amount: 0,
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
                        return sum + parseFloat(item.amount_taken || 0);
                    }, 0).toFixed(2);
                },
                getTotalBalance() {
                    if (!this.paymentList || !this.paymentList.length) return '0.00';
                    return this.paymentList.reduce((sum, item) => {
                        var purchase = item.purchase;
                        if (purchase) {
                            var total = parseFloat(purchase.total || 0);
                            var paid = parseFloat(purchase.payment_total || 0);
                            return sum + (total - paid);
                        }
                        return sum;
                    }, 0).toFixed(2);
                }
            },


            methods: {
                getPaymentList: function(page = 1) {
                    url = "{{ route('supplier-payment-report.list') }}";

                    axios.post(url, {
                        page: page,
                        page_length: this.page_length,
                        search: this.search,
                        start_date: this.filter_start_date,
                        end_date: this.filter_end_date,
                        supplier_id: this.filter_supplier_id,
                        payment_mode:this.filter_payment_mode
                    }).then(function(response) {
                        app.paymentList = response.data.data.data;
                        app.pagination = response.data.pagination;
                        app.total_paid_amount = response.data.total_paid_amount;

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
                getBalanceAmount(data) {
                    if (!data.purchase) return '0.00';
                    var total = parseFloat(data.purchase.total || 0);
                    var paid = parseFloat(data.purchase.payment_total || 0);
                    return (total - paid).toFixed(2);
                },
                getSupplierList() {
                    axios.get('/api/supplier/list', {
                            params: {
                                platform: 'api',
                            }
                        })
                        .then(response => {
                            app.supplierList = response.data.data || [];
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },
                resetFilter: function() {
                    app.search = '';
                    app.filter_supplier_id = '';
                    app.filter_payment_mode = '';
                    app.filter_end_date = '';
                    app.filter_start_date = '';
                    app.getPaymentList();
                },
                exportToPDF() {
                    var url = "{{ route('supplier-payment-report-export-to-pdf') }}?search=" + this.search +
                        "&supplier_id=" + (this.filter_supplier_id ?? '') +
                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '')+"&payment_mode=" + (this.filter_payment_mode ?? '');

                    console.log(url);

                    window.open(url, '_self');
                },
                exportToExcel() {
                    var url = "{{ route('supplier-payment-report-export-to-excel') }}?search=" + this.search +
                        "&supplier_id=" + (this.filter_supplier_id ?? '') +
                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '')+"&payment_mode=" + (this.filter_payment_mode ?? '');

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
                this.getSupplierList();
                this.getLoggedUserInfo();
            }



        });
    </script>
@endsection
