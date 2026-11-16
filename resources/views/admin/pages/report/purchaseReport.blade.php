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
                                    <h4 class="page-title">Purchase Report</h4>
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
                                        <Label class="mb-1">Purchase No</Label>
                                        <input class="form-control" type="text" v-model="filter_purchase_no"
                                            placeholder="Purchase No">
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
                                        <Label class="mb-1">Product</Label>
                                        <select v-model="filter_product_id" class="form-select">
                                            <option value="">Choose</option>
                                            <option v-for="item in productList" :value="item.id">
                                                @{{ item.product_name }}[@{{ item.product_code }}]
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Payment</Label>
                                        <select class="form-select" v-model="filter_payment_mode">
                                            <option value="">Choose</option>
                                            <option value="{{ config('constants.CASH') }}">
                                                {{ config('constants.CASH') }}</option>
                                            <option value="{{ config('constants.ONLINE') }}">
                                                {{ config('constants.ONLINE') }}</option>
                                            {{-- <option value="{{ config('constants.CREDIT') }}">
                                                {{ config('constants.CREDIT') }}</option> --}}
                                        </select>
                                    </div>
                                    <!-- Search & Reset Buttons -->
                                    <div class="col-sm-12 d-flex gap-2 justify-content-end">
                                        <button class="btn btn-primary" @click="getSummaryDetailsPopup()">
                                            View Summary
                                        </button>
                                        <button class="btn btn-primary" v-on:click="getPurchaseList()">
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

                                                <th>Purchase No</th>
                                                <th>Purchase Date</th>
                                                <th>Supplier Name</th>
                                                <th>Payment Mode</th>
                                                <th>Purchase Amount</th>
                                                <th>Paid Amount</th>
                                                <th>Refund Amount</th>
                                                <th>Refund Order Balance</th>
                                                <th>Balance Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!purchaseList || !purchaseList.length">
                                                <tr>
                                                    <td colspan="11">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in purchaseList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    <td>
                                                        <a href="javascript:void(0);" @click="showPurchaseListModal(data)"
                                                            style="color: blue;">
                                                            @{{ data.purchase_no }}
                                                        </a>
                                                        <span v-if="data.is_returned == 1" class="ms-1"
                                                            style="font-size: 13px;">Returned</span>
                                                    </td>
                                                    <td>@{{ formatDate(data.purchase_date) }}</td>
                                                    <td>@{{ data.supplier.supplier_name }}</td>
                                                    <td>
                                                        <span v-for="p in data.purchase_payment" :key="p.id"
                                                            v-if="p.payment_mode.toLowerCase() !== 'credit'"
                                                            class="badge bg-primary me-1">
                                                            @{{ p.payment_mode }}(@{{ parseFloat(p.amount_taken).toFixed(2) }})
                                                        </span>
                                                    </td>


                                                    <td>@{{ getPurchaseItemsTotal(data) }}</td>
                                                    <td>@{{ parseFloat(data.payment_total || 0).toFixed(2) }}</td>
                                                    <td>@{{ parseFloat(data.total_refund_amount || 0).toFixed(2) }}</td>
                                                    <td>@{{ parseFloat(data.refund_amount_from_order_balance || 0).toFixed(2) }}</td>
                                                    <td>@{{ parseFloat(data.balance_amount || 0).toFixed(2) }}</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info"
                                                            @click="showCashPaymentModal(data)">
                                                            View
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        <tfoot v-if="purchaseList && purchaseList.length">
                                            <tr>
                                                <td colspan="5" class="text-end"><strong> Total:</strong></td>
                                                <td><strong>@{{ getGrandTotal }}</strong></td>
                                                <td><strong>@{{ getPaidGrandTotal }}</strong></td>
                                                <td><strong></td>
                                                <td><strong></td>
                                                <td><strong>@{{ getBalanceGrandTotal }}</strong></td>
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

        <!-- View Purchase -->
        <div class="modal fade" id="purchase_list" role="dialog">
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
                            <template v-if="selectedPurchase">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in selectedPurchase.purchase_item" :key="item.id">
                                            <td>@{{ item.product.product_name || 'N/A' }}</td>
                                            <td>@{{ item.qty }}</td>
                                            <td>@{{ parseFloat(item.unit_price).toFixed(2) }}</td>
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
        <!-- Cash Payments Modal -->
        <div class="modal fade" id="cash_payment_modal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Cash Payments</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <template v-if="selectedPurchase && selectedPurchase.purchase_payment.length">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Payment Mode</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in selectedPurchase.purchase_payment" :key="p.id"
                                        v-if="p.payment_mode.toLowerCase() !== 'credit'">
                                        <td>@{{ p.payment_mode }}</td>
                                        <td>@{{ parseFloat(p.amount_taken).toFixed(2) }}</td>
                                        <td>@{{ formatDate(p.created_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </template>
                        <template v-else>
                            <p>No cash payments available.</p>
                        </template>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- view Summary Modal -->
        <div class="modal fade" id="purchaseSummary" role="dialog">
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
                                    <th>Total Amount</th>
                                    <td>@{{ parseFloat(totalAmount).toFixed(2) }}</td>
                                </tr>

                                <tr>
                                    <th>Cash</th>
                                    <td>@{{ parseFloat(cashTotal).toFixed(2) }}</td>
                                </tr>

                                <tr>
                                    <th>Alrajhi (Bank)</th>
                                    <td>@{{ parseFloat(bankTransferAlrajhi).toFixed(2) }}</td>
                                </tr>

                                <tr>
                                    <th>SAB (Bank)</th>
                                    <td>@{{ parseFloat(bankTransferSab).toFixed(2) }}</td>
                                </tr>

                                <tr>
                                    <th>Alrajhi (POS)</th>
                                    <td>@{{ parseFloat(posAlrajhi).toFixed(2) }}</td>
                                </tr>

                                <tr>
                                    <th>SAB (POS)</th>
                                    <td>@{{ parseFloat(posSab).toFixed(2) }}</td>
                                </tr>
                                <tr>
                                    <th>Refunded Amount</th>
                                    <td>@{{ parseFloat(totalRefundAmount).toFixed(2) }}</td>
                                </tr>
                                <tr>
                                    <th>Refund From Order Balance</th>
                                    <td>@{{ parseFloat(totalRefundFromOrderBalance).toFixed(2) }}</td>
                                </tr>

                                <tr>
                                    <th>Credit</th>
                                    <td>@{{ parseFloat(creditTotal).toFixed(2) }}</td>
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

                purchaseList: [],
                supplierList: [],
                productList: [],

                filter_start_date: '',
                filter_end_date: '',
                filter_purchase_no: '',
                filter_supplier_id: '',
                filter_product_id: '',
                filter_payment_mode: '',
                selectedPurchase: '',
                search: '',
                totalPurchase: 0,
                totalQty: 0,
                totalAmount: 0,
                cashTotal: 0,
                bankTransferAlrajhi: 0,
                bankTransferSab: 0,
                posAlrajhi: 0,
                posSab: 0,
                creditTotal: 0,
                totalRefundAmount: 0,
                totalRefundFromOrderBalance: 0,
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
                getGrandTotal() {
                    if (!this.purchaseList || !this.purchaseList.length) return 0;
                    return this.purchaseList.reduce((sum, item) => {
                        const itemsTotal = this.getPurchaseItemsTotalValue(item);
                        return sum + itemsTotal;
                    }, 0).toFixed(2);
                },
                getPaidGrandTotal() {
                    if (!this.purchaseList || !this.purchaseList.length) return '0.00';
                    return this.purchaseList.reduce((sum, item) => {
                        return sum + parseFloat(item.payment_total || 0);
                    }, 0).toFixed(2);
                },
                getRefundGrandTotal() {
                    if (!this.purchaseList || !this.purchaseList.length) return '0.00';
                    return this.purchaseList.reduce((sum, item) => {
                        return sum + parseFloat(item.total_refund_amount || 0);
                    }, 0).toFixed(2);
                },
                getRefundOrderBalanceGrandTotal() {
                    if (!this.purchaseList || !this.purchaseList.length) return '0.00';
                    return this.purchaseList.reduce((sum, item) => {
                        return sum + parseFloat(item.refund_amount_from_order_balance || 0);
                    }, 0).toFixed(2);
                },
                getBalanceGrandTotal() {
                    if (!this.purchaseList || !this.purchaseList.length) return '0.00';
                    return this.purchaseList.reduce((sum, item) => {
                        return sum + parseFloat(item.balance_amount || 0);
                    }, 0).toFixed(2);
                }
            },


            methods: {
                getSummaryDetailsPopup() {
                    this.getPurchaseList();
                    $('#purchaseSummary').modal('show');
                },
                getPurchaseItemsTotal(data) {
                    if (!data || !data.purchase_item || !data.purchase_item.length) {
                        return '0.00';
                    }
                    return data.purchase_item.reduce((sum, item) => {
                        return sum + parseFloat(item.total || 0);
                    }, 0).toFixed(2);
                },
                getPurchaseItemsTotalValue(data) {
                    if (!data || !data.purchase_item || !data.purchase_item.length) {
                        return 0;
                    }
                    return data.purchase_item.reduce((sum, item) => {
                        return sum + parseFloat(item.total || 0);
                    }, 0);
                },
                getPurchaseList: function(page = 1) {

                    url = "{{ route('purchase-report.list') }}";

                    axios.post(url, {
                        page: page,
                        page_length: this.page_length,
                        search: this.search,
                        start_date: this.filter_start_date,
                        end_date: this.filter_end_date,
                        purchase_no: this.filter_purchase_no,
                        supplier_id: this.filter_supplier_id,
                        product_id: this.filter_product_id,
                        payment_mode: this.filter_payment_mode,
                    }).then(function(response) {
                        app.purchaseList = response.data.data.data;
                        app.pagination = response.data.pagination;

                        app.totalPurchase = response.data.totals.total_purchase;
                        app.totalQty = response.data.totals.total_quantity;
                        app.totalAmount = response.data.totals.total_amount;

                        app.cashTotal = response.data.totals.cash;
                        app.bankTransferAlrajhi = response.data.totals.bank_transfer_alrajhi;
                        app.bankTransferSab = response.data.totals.bank_transfer_sab;
                        app.posAlrajhi = response.data.totals.pos_alrajhis;
                        app.posSab = response.data.totals.pos_sab;
                        app.totalRefundAmount = response.data.totals.totalRefundAmount;
                        app.totalRefundFromOrderBalance = response.data.totals
                            .totalRefundFromOrderBalance;
                        app.creditTotal = response.data.totals.credit;

                        app.loading = false;
                    });

                },
                changePage: function(page) {
                    this.getPurchaseList(page);
                },
                showCashPaymentModal(data) {
                    if (data && data.id > 0) {
                        this.selectedPurchase = data;
                        $('#cash_payment_modal').modal('show');
                    }
                },
                formatDate(value) {
                    if (!value) return '';
                    const date = new Date(value);
                    // return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                    return date.toLocaleDateString();
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
                showPurchaseListModal(data) {
                    if (data && data.id > 0) {
                        this.selectedPurchase = data;
                        $('#purchase_list').modal('show');
                    }
                },
                resetFilter: function() {
                    app.search = '';
                    app.filter_supplier_id = '';
                    app.filter_product_id = '';
                    app.filter_payment_mode = '';
                    app.filter_purchase_no = '';
                    app.filter_end_date = '';
                    app.filter_start_date = '';
                    app.getPurchaseList();
                },
                exportToPDF() {
                    var url = "{{ route('purchase-report-export-to-pdf') }}?search=" + this.search +
                        "&supplier_id=" + (this.filter_supplier_id ?? '') +
                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '') +
                        "&purchase_no=" + (this.filter_purchase_no ?? '') +
                        "&product_id=" + (this.filter_product_id ?? '') +
                        "&payment_mode=" + (this.filter_payment_mode ?? '');


                    console.log(url);

                    window.open(url, '_self');
                },
                exportToExcel() {
                    var url = "{{ route('purchase-report-export-to-excel') }}?search=" + this.search +
                        "&supplier_id=" + (this.filter_supplier_id ?? '') +
                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '') +
                        "&purchase_no=" + (this.filter_purchase_no ?? '') +
                        "&product_id=" + (this.filter_product_id ?? '') +
                        "&payment_mode=" + (this.filter_payment_mode ?? '');


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
                this.getPurchaseList();
                this.getSupplierList();
                this.getProductList();
                this.getLoggedUserInfo();
            }



        });
    </script>
@endsection
