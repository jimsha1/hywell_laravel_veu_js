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
                                    <h4 class="page-title">Purchase</h4>
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
                                <!-- Search -->
                                <div class="row align-items-center">
                                    <div class="col-sm-4">
                                        <div class="icon-form mb-3 mb-sm-0">
                                            <span class="form-icon"><i class="ti ti-search"></i></span>
                                            <input type="text" class="form-control" placeholder="Search Purchase"
                                                v-model="search" @input="getPurchaseList">
                                        </div>

                                    </div>
                                    <div class="col-sm-4">
                                        <div class="icon-form mb-3 mb-sm-0">

                                            <select class="form-select" v-model="financial_year_id"
                                                @change="getPurchaseList">
                                                <option value="">Select Financial Year</option>
                                                <option v-for="year in financialYearList" :value="year.id">
                                                    @{{ year.name }}
                                                </option>
                                            </select>


                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="text-sm-end">
                                            <a href="javascript:void(0);" class="btn btn-primary "
                                                v-on:click="showEditPurchseModal(0)"><i
                                                    class="ti ti-square-rounded-plus me-2"></i>Add New Purchase</a>
                                        </div>
                                    </div>
                                </div>
                                <!-- /Search -->
                            </div>
                            <div class="card-body">

                                <!-- Purchase List -->
                                <div class="table-responsive custom-table">
                                    <table class="table" id="purchase_list">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="no-sort">
                                                    <label class="checkboxs">
                                                        <input type="checkbox" id="select-all"><span
                                                            class="checkmarks"></span>
                                                    </label>
                                                </th>
                                                {{-- <th>Invoice No</th> --}}
                                                <th>Purchase No</th>
                                                <th>Supplier Name</th>
                                                <th>Date</th>
                                                <th>Payment Total</th>
                                                <th>Paid Amount</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!purchaseList || !purchaseList.length">
                                                <tr>
                                                    <td colspan="6">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in purchaseList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    {{-- <td>@{{ data.invoice_number }}</td> --}}
                                                    <td>@{{ data.purchase_no }}</td>
                                                    <td>@{{ data.supplier.supplier_name }}[@{{ data.supplier.supplier_code }}]</td>
                                                    <td>@{{ formatDate(data.purchase_date) }}</td>
                                                    <td>@{{ data.total }}</td>
                                                    <td>@{{ data.payment_total }}</td>
                                                    <td>
                                                        <div class="dropdown table-action d-flex">
                                                            <a class="border-0 bg-transparent shadow-none ps-0"
                                                                v-on:click="showEditPurchseModal(data)"><i
                                                                    class="fa fa-pencil text-green" aria-hidden="true"></i>
                                                            </a>
                                                            <a class="border-0 bg-transparent shadow-none" href="#"
                                                                v-on:click="showPurchaseListModal(data)">
                                                                <i class="fa fa-eye text-info ps-0 " aria-hidden="true"></i>
                                                            </a>

                                                        </div>

                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="datatable-length"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="datatable-paginate"></div>
                                    </div>
                                </div>
                                <!-- /Purchase List -->

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
        <!-- /Page Wrapper -->

        <!-- Add/Edit purchase -->
        <div class="modal fade" id="add_purchase" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{ modelHeading }}</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="validateBeforeSubmit" v-if="!formSubmitted" method="post">
                        <div class="modal-body">
                            <div class="card-body overflow-hidden">
                                <div class="row border-bottom mb-4 pb-4">
                                    {{-- <div class="col-md-3 mb-3">
                                        <label class="form-label">Purchase Ref.No<span
                                                class="text-danger">*</span></label>
                                        <input type="text" v-model="purchase_no" class="form-control"
                                            v-validate.initial="purchase_no" data-rules="required" placeholder="Ref.No"
                                            readonly style="background-color: #6f6f6f1a">
                                        <span v-if="formErrors['purchase_no']"
                                            class="error text-danger">@{{ formErrors['purchase_no'] }}</span>
                                        <p class="text-danger" v-if="errors.has('purchase_no')">@{{ errors.first('purchase_no') }}
                                        </p>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Invoice No<span
                                                class="text-danger">*</span></label>
                                        <input type="text" v-model="invoice_number" class="form-control"
                                            v-validate.initial="invoice_number" data-rules="required" >

                                        <span v-if="formErrors['invoice_number']"
                                            class="error text-danger">@{{ formErrors['invoice_number'] }}</span>
                                        <p class="text-danger" v-if="errors.has('invoice_number')">@{{ errors.first('invoice_number') }}
                                        </p>
                                    </div> --}}
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Purchase Ref.No<span
                                                class="text-danger">*</span></label>
                                        <input type="text" v-model="purchase_no" class="form-control"
                                            v-validate.initial="purchase_no" data-rules="required" placeholder="Ref.No"
                                            readonly style="background-color: #6f6f6f1a">
                                        <span v-if="formErrors['purchase_no']"
                                            class="error text-danger">@{{ formErrors['purchase_no'] }}</span>
                                        <p class="text-danger" v-if="errors.has('purchase_no')">@{{ errors.first('purchase_no') }}
                                        </p>
                                    </div>
                                    <div class="col-md-12 mb-3" v-if="formErrors['general']">
                                        <div class="alert alert-danger py-2 mb-0">@{{ formErrors['general'] }}</div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Purchase Date<span class="text-danger">*</span></label>
                                        <input type="date" v-model="purchase_date" class="form-control"
                                            v-validate.initial="purchase_date" data-rules="required">
                                        <span v-if="formErrors['purchase_date']"
                                            class="error text-danger">@{{ formErrors['purchase_date'] }}</span>
                                        <p class="text-danger" v-if="errors.has('purchase_date')">@{{ errors.first('purchase_date') }}
                                        </p>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Supplier<span class="text-danger">*</span></label>
                                        <select v-model="supplier_id" class="form-select"
                                            v-validate.initial="supplier_id" data-rules="required">
                                            <option value="">Choose</option>
                                            <option v-for="item in supplierList" :value="item.id">
                                                @{{ item.supplier_name }}
                                            </option>
                                        </select>
                                        <span v-if="formErrors['supplier_id']"
                                            class="error text-danger">@{{ formErrors['supplier_id'] }}</span>
                                        <p class="text-danger" v-if="errors.has('supplier_id')">@{{ errors.first('supplier_id') }}
                                        </p>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Remarks</label>
                                        <textarea rows="1" cols="1" v-model="remarks" class="form-control" placeholder="Enter text here"></textarea>

                                    </div>
                                </div>

                                <div class="overflow-auto">
                                    <table class="table align-middle" style="min-width: 740px;">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Qty</th>
                                                <th>Unit Price</th>
                                                <th>Tax(%)</th>
                                                <th>Total</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <template v-for="(row, index) in product_rows">
                                                <tr>
                                                    <td style="width: 30%">
                                                        <select v-model="row.product_id" class="form-select"
                                                            @change="onProductSelect(index)">
                                                            <option value="">Choose</option>
                                                            <option v-for="product in productList" :value="product.id">
                                                                @{{ product.product_name }}</option>
                                                        </select>
                                                        <span v-if="!row.product_id && formErrors['product_id']"
                                                            class="text-danger">
                                                            @{{ formErrors['product_id'] }}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <input type="number" class="form-control" v-model="row.qty"
                                                            placeholder="Qty">
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control"
                                                            v-model="row.unit_price" placeholder="Unit Price"
                                                            step="any" min="0">
                                                    </td>
                                                    <td>
                                                        <select v-model="row.tax_id" class="form-select">
                                                            <option value="0">Choose</option>
                                                            <option v-for="item in taxList" :value="item.id">
                                                                @{{ item.name }}
                                                            </option>
                                                        </select>
                                                    </td>
                                                    <td><input type="text" class="form-control"
                                                            :value="calculateTotal(row).toFixed(2)" readonly
                                                            style="background-color: #6f6f6f1a"></td>
                                                    <td>
                                                        <button type="button" class="border-0 bg-transparent text-danger"
                                                            @click="removeRow(index)">
                                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                                        </button>
                                                        <button type="button" class="border-0 bg-transparent text-danger"
                                                            @click="addRow">
                                                            <i class="fa fa-plus" aria-hidden="true"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row justify-content-end mt-3">
                                    <div class="col-lg-8">
                                        <table class="table align-middle mt-3">
                                            <thead>
                                                <tr>
                                                    <th class="border-0 py-1">Amount Paid</th>
                                                    <th class="border-0 py-1">Payment Mode</th>
                                                    <th class="border-0 py-1"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(row, index) in payment_rows" :key="index">
                                                    <td class="border-0 py-1"><input type="number"
                                                            placeholder="Enter Price" class="form-control"
                                                            v-model.number="row.amount_taken" min="0"
                                                            step="0.01" :disabled="row.disabled">
                                                    </td>
                                                    {{-- <td class="border-0 py-1">
                                                        <select class="form-select" v-model="row.payment_mode"
                                                            :disabled="row.disabled">
                                                            <option value="">Choose</option>
                                                            <option value="{{ config('constants.CASH') }}">
                                                                {{ config('constants.CASH') }}</option>
                                                            <option value="{{ config('constants.ONLINE') }}">
                                                                {{ config('constants.ONLINE') }}</option>
                                                            <option value="{{ config('constants.CREDIT') }}">
                                                                {{ config('constants.CREDIT') }}</option>

                                                        </select>
                                                    </td> --}}

                                                    <td class="border-0 py-1">

                                                        {{-- <select class="form-select" v-model="row.payment_mode" :disabled="row.disabled">
                                                            <option value="">Choose</option>
                                                            <option value="0">Credit</option>
                                                            <option v-for="acc in accounts" :key="acc.id"
                                                                :value="acc.id">
                                                                @{{ acc.name }}
                                                            </option>
                                                        </select> --}}
                                                        <select class="form-select" v-model="row.payment_mode"
                                                            :disabled="row.disabled">
                                                            <option value="">Choose</option>
                                                            <option value="{{ config('constants.CASH') }}">
                                                                Cash</option>
                                                            <option value="{{ config('constants.CREDIT') }}">
                                                                Credit</option>

                                                            <option
                                                                value="{{ config('constants.BANK_TRANSFER_ALRAJHI') }}">
                                                                TRANSFER ALRAJHI</option>
                                                            <option value="{{ config('constants.BANK_TRANSFER_SAB') }}">
                                                                TRANSFER SAB</option>
                                                            <option value="{{ config('constants.POS_ALRAJHIS') }}">
                                                                POS ALRAJHIS</option>
                                                            <option value="{{ config('constants.POS_SAB') }}">
                                                                POS SAB</option>
                                                            </option>

                                                        </select>
                                                    </td>

                                                    <td class="border-0 py-1"><button type="button"
                                                            class="border-0 py-1 bg-transparent text-danger"
                                                            @click="removePaymentRow(index)"><i aria-hidden="true"
                                                                class="fa fa-trash"></i>
                                                        </button>
                                                        <button type="button"
                                                            class="border-0 py-1 bg-transparent text-danger"
                                                            @click="addPaymentRow"><i aria-hidden="true"
                                                                class="fa fa-plus"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-lg-4 col-sm-7">
                                        <table class="table table-nowrap">
                                            <tbody>
                                                <tr>
                                                    <td style="font-size: 16px;" class=" border-0"><strong>Grand
                                                            Total</strong></td>
                                                    <td style="font-size: 16px;" class="text-end border-0">
                                                        <strong>₹ @{{ grandTotal.toFixed(2) }}</strong>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <div class="d-flex align-items-center justify-content-end m-0">
                                <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                                <button type="submit" class="btn btn-primary" v-bind:disabled="validateBeforeSubmit"
                                    :disabled="!isTotalPaidEqualToGrandTotal">@{{ modelBtn }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /Add New Source -->

        <!-- View Purchase -->
        <div class="modal fade" id="add_purchase_list" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Purchase View</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card-body overflow-hidden">
                            <template v-if="selectedPurchase">
                                <p><strong>Ref.No:</strong> @{{ selectedPurchase.invoice_number }}</p>
                                <p><strong>Supplier:</strong> @{{ selectedPurchase.supplier.supplier_name }}</p>
                                <p><strong>Date:</strong> @{{ formatDate(selectedPurchase.purchase_date) }}</p>
                                {{-- <p><strong>Grand Total:</strong> @{{ selectedPurchase.payment_total }}</p> --}}
                                <p><strong>Grand Total:</strong> @{{ selectedPurchaseGrandTotal }}</p>

                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Unit Price</th>
                                            <th>Tax Amount</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in selectedPurchase.purchase_item" :key="item.id">
                                            <td>@{{ item.product.product_name || 'N/A' }}</td>
                                            <td>@{{ item.qty }}</td>
                                            <td>@{{ parseFloat(item.unit_price).toFixed(2) }}</td>
                                            <td>@{{ parseFloat(item.tax_amount).toFixed(2) }}</td>
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
                supplierList: [],
                productList: [],
                purchaseList: [],
                taxList: [],
                financialYearList: [],
                product_rows: [{
                    product_id: '',
                    unit_price: 0,
                    qty: 0,
                    tax_id: 0
                }],
                accounts: [],
                filter_account_id: '',
                payment_rows: [{
                    payment_id: '',
                    amount_taken: 0,
                    payment_mode: '',
                    disabled: false,
                }],
                purchase_no: "",
                invoice_number: "",
                supplier_id: "",
                purchase_date: "",
                remarks: "",
                id: '',
                selectedPurchase: null,
                user_id: '',

                modelHeading: '',
                modelBtn: '',
                base_url: "{{ url('/') }}",


                formSubmitted: false,
                submit_button_flag: false,

                search: '',
                financial_year_id: '',
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


                purchaseReferenceNo: "",

            },
            mounted() {
                this.purchase_date = this.getTodayDate();

                this.getFinancialYears();
                this.loadAccounts();
                this.getLoggedUserInfo();
                this.getProductList();
                this.getSupplierList();
                this.getPurchaseList();
                this.getTaxList();

                $('#add_purchase').on('hidden.bs.modal', () => {
                    this.resetForm();
                });
            },
            methods: {


                async loadAccounts() {
                    try {
                        const response = await axios.get('/api/accounting/cash-bank-accounts-list');
                        if (response.data.status === 'Success') {
                            this.accounts = response.data.data;
                        }
                    } catch (error) {
                        console.error('Error loading accounts:', error);
                    }
                },
                formatNumber(value) {
                    const num = parseFloat(value);
                    return Number.isInteger(num) ? num.toString() : num.toFixed(2);
                },

                // getInvoiceDisplayValue(value) {
                //     const rawValue = (value ?? '').toString().trim().toUpperCase();
                //     return rawValue.replace(/^IN/i, '');
                // },

                formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    const date = new Date(dateString);
                    // return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                    return date.toLocaleDateString();
                },
                getTodayDate() {
                    const today = new Date();
                    const year = today.getFullYear();
                    const month = String(today.getMonth() + 1).padStart(2, '0');
                    const day = String(today.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                },
                getLoggedUserInfo() {
                    const userJson = window.localStorage.getItem('user');
                    if (userJson) {
                        const user = JSON.parse(userJson);
                        this.user_id = user.data.id;
                        $('#user_name').text(user.data.name || 'Unknown');
                        if (user.data.user_type === "super_admin") {
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
                validateBeforeSubmit() {
                    if (this.submit_button_flag) return;
                    this.$validator.validateAll();

                    const invalidRow = this.product_rows.find(row => !row.product_id || row.product_id === '');

                    if (invalidRow) {
                        this.formErrors['product_id'] = "Please select a product.";
                        return;
                    }

                    // Only proceed if there are no validation errors
                    if (!this.errors.any()) {
                        this.submit_button_flag = true;
                        this.createPurchase();
                    }
                },


                createPurchase() {
                    const formData = new FormData();
                    formData.append("purchase_no", app.purchase_no);
                    formData.append("invoice_number", app.invoice_number);
                    formData.append("purchase_date", app.purchase_date);
                    formData.append("supplier_id", app.supplier_id);
                    formData.append("remarks", app.remarks);
                    formData.append("product_rows", JSON.stringify(this.product_rows));
                    formData.append("payment_rows", JSON.stringify(this.payment_rows));
                    formData.append("id", app.id);
                    formData.append("user_id", this.user_id);

                    app.submit_button_flag = true;
                    // app.formErrors = {};

                    if (app.id > 0) {
                        formData.append("_method", "put");
                        formData.append("id", app.id);

                        axios.post("/api/purchase/update", formData)
                            .then(response => {
                                app.showAPIResponse(response);
                            })
                            .catch(error => {
                                // app.submit_button_flag = false;
                                console.error("Error updating purchase:", error);
                                this.$toast.error(error.response.data.message);
                            });

                    } else {
                        axios.post("/api/purchase/store", formData)
                            .then(response => {
                                app.showAPIResponse(response);
                            })
                            .catch(error => {
                                app.submit_button_flag = false;
                                if (error.response && error.response.data) {
                                    Vue.$toast.error(error.response.data.message, 'Error Alert');
                                } else {
                                    Vue.$toast.error('Something went wrong.', 'Error Alert');
                                }
                                app.formErrors = error.response.data.errors;
                            });
                    }
                },

                showAPIResponse(response) {
                    if (response.data.code == 0) {

                        Vue.$toast.success(response.data.message, 'Success Alert');
                        app.submit_button_flag = false;
                        app.formSubmitted = true;
                        app.formErrors = {};

                        this.getPurchaseList();
                        app.resetForm();

                        $('#add_purchase').modal('hide');
                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        app.submit_button_flag = false;
                        // app.formErrors = {
                        //     ...app.formErrors,
                        //     general: response.data.message
                        // };
                    }
                    app.submit_button_flag = false;
                },

                // handleRequestError(error, action = 'store') {
                //     let message = 'Something went wrong while saving the purchase.';

                //     if (error.response && error.response.data && error.response.data.message) {
                //         message = error.response.data.message;
                //     }

                //     Vue.$toast.error(message, 'Error Alert');
                //     app.formErrors = {
                //         ...app.formErrors,
                //         general: message
                //     };

                //     if (error.response && error.response.data && error.response.data.errors) {
                //         app.formErrors = {
                //             ...app.formErrors,
                //             ...error.response.data.errors
                //         };
                //     }
                // },

                showEditPurchseModal(data) {
                    if (data.id > 0) {
                        app.purchase_no = data.purchase_no;
                        app.invoice_number = data.invoice_number;
                        app.purchase_date = data.purchase_date;
                        app.supplier_id = data.supplier_id;
                        app.remarks = data.remarks;
                        app.id = data.id;

                        // Populate product_rows with purchase_item array
                        app.product_rows = data.purchase_item && data.purchase_item.length > 0 ?
                            data.purchase_item.map(item => ({
                                product_id: item.product_id || '',
                                unit_price: parseFloat(item.unit_price) || 0,
                                qty: parseFloat(item.qty) || 0,
                                tax_id: parseFloat(item.tax_id) || 0
                            })) : [{
                                product_id: '',
                                unit_price: 0,
                                qty: 1,
                                tax_id: 0
                            }];

                        // Populate payment_rows with purchase_payment array
                        app.payment_rows = data.purchase_payment && data.purchase_payment.length > 0 ?
                            data.purchase_payment.map(payment => ({
                                payment_id: payment.id || '',
                                amount_taken: parseFloat(payment.amount_taken) || 0,
                                payment_mode: payment.payment_mode || '',
                                disabled: payment.disabled || false
                            })) : [{
                                payment_id: '',
                                amount_taken: 0,
                                payment_mode: '',
                                disabled: false
                            }];

                        app.formSubmitted = false;
                        app.formErrors = {};

                        app.modelHeading = "Edit Purchase";
                        app.modelBtn = "Update";
                        $('#add_purchase').modal('show');

                        console.log(app.id);
                    } else {
                        app.resetForm();
                        app.getPurchaseReferenceNo();
                        app.modelHeading = "Add Purchase";
                        app.modelBtn = "Create";

                        $('#add_purchase').modal('show');
                    }

                },

                showPurchaseListModal(data) {
                    if (data && data.id > 0) {
                        this.selectedPurchase = data;
                        $('#add_purchase_list').modal('show');
                    }
                },

                getProductList(platform) {
                    axios.get('/api/product/list', {
                            params: {
                                platform: 'api',
                                productType: 'Raw Material'
                            }
                        })
                        .then(response => {
                            app.productList = response.data.data.products;
                            if (this.productList.length > 0 && this.product_rows.length > 0) {
                                const firstProduct = this.productList[0];
                                this.product_rows[0].product_id = firstProduct.id;
                                this.product_rows[0].unit_price = parseFloat(firstProduct.unit_price) || 0;
                                this.product_rows[0].qty = parseFloat(firstProduct.quantity) || 1;
                                this.product_rows[0].tax_id = parseFloat(firstProduct.tax_id) || 0;
                            }
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
                getTaxList() {
                    axios.get('/api/tax/list', {
                            params: {
                                platform: 'api'
                            }
                        })
                        .then(response => {
                            app.taxList = response.data.data;
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },
                changePage: function(page) {
                    this.getPurchaseList(page);
                },
                getPurchaseList: function(page = 1) {
                    const url = "/api/purchase/list";

                    axios.get(url, {
                        params: {
                            page: page,
                            page_length: this.page_length,
                            search: this.search,
                            financial_year_id: this.financial_year_id,
                        }
                    }).then(response => {
                        app.purchaseList = response.data.data.purchases;
                        app.pagination = response.data.data.pagination;
                        app.loading = false;
                    });
                },

                getFinancialYears: function() {
                    const url = "/api/financial-year/list";

                    axios.get(url, {
                        params: {
                            platform: 'api',
                            search: this.search,
                        }
                    }).then(response => {
                        app.financialYearList = response.data.data;

                        app.loading = false;
                    });
                },
                onProductSelect(index) {
                    const selectedId = this.product_rows[index].product_id;
                    const selectedProduct = this.productList.find(p => p.id === selectedId);
                    console.log(selectedProduct);


                    if (selectedProduct) {
                        this.product_rows[index].unit_price = selectedProduct.unit_price;
                        this.product_rows[index].qty = selectedProduct.quantity ?? 1;
                        this.product_rows[index].tax_id = selectedProduct.tax_id;
                    } else {
                        this.product_rows[index].unit_price = 0;
                        this.product_rows[index].qty = 0;
                        this.product_rows[index].tax_id = 0;
                    }
                },

                addRow() {
                    this.product_rows.push({
                        product_id: '',
                        unit_price: 0,
                        qty: 0,
                        tax_id: 0
                    });
                },

                removeRow(index) {
                    this.product_rows.splice(index, 1);
                },
                calculateTotal(row) {
                    const qty = parseFloat(row.qty) || 0;
                    const unitPrice = parseFloat(row.unit_price) || 0;
                    const tax = this.taxList.find(t => t.id === row.tax_id)?.tax || 0; // Fetch tax percentage
                    const base = qty * unitPrice;
                    const taxAmount = (base * tax) / 100;
                    return base + taxAmount;
                },
                addPaymentRow() {
                    const totalPaid = this.payment_rows.reduce((sum, row) => sum + (parseFloat(row
                        .amount_taken) || 0), 0);
                    this.remainingAmount = Math.max(0, this.grandTotal - totalPaid);

                    // Disable the last row before adding a new one (if there are any rows)
                    if (this.payment_rows.length > 0) {
                        this.$set(this.payment_rows, this.payment_rows.length - 1, {
                            ...this.payment_rows[this.payment_rows.length - 1],
                            disabled: true // Disable the last row
                        });
                    }

                    // Add new row
                    this.$set(this.payment_rows, this.payment_rows.length, {
                        payment_id: null,
                        amount_taken: this.remainingAmount > 0 ? this.remainingAmount.toFixed(
                            2) : 0.00,
                        payment_mode: '',
                        disabled: false // New row is not disabled
                    });
                },
                removePaymentRow(index) {
                    if (this.payment_rows.length <= 1) {
                        console.log('removeRow1: Cannot remove last row');
                        return;
                    }

                    // Remove the selected row
                    this.payment_rows.splice(index, 1);

                    // Recalculate total paid
                    let totalPaid = this.payment_rows.reduce((sum, row) => sum + (parseFloat(row
                        .amount_taken) || 0), 0);
                    this.remainingAmount = Math.max(0, this.grandTotal - totalPaid);
                    console.log('removeRow1: index=', index, 'totalPaid=', totalPaid,
                        'remainingAmount=', this.remainingAmount);

                    if (this.payment_rows.length === 1) {
                        // If only one row remains, set its amount_taken to grandTotal and enable it
                        this.$set(this.payment_rows[0], 'amount_taken', this.grandTotal.toFixed(2));
                        this.$set(this.payment_rows[0], 'disabled', false);
                        console.log('removeRow1: One row left, set amount_taken to grandTotal=', this
                            .grandTotal, 'and enabled');
                    } else {
                        // Set amount_taken of the last row to remainingAmount and enable it
                        const lastRowIndex = this.payment_rows.length - 1;
                        let sumExceptLast = this.payment_rows.slice(0, lastRowIndex).reduce(
                            (sum, row) => sum + (parseFloat(row.amount_taken) || 0), 0
                        );
                        let newLastAmount = this.grandTotal - sumExceptLast;
                        this.$set(this.payment_rows[lastRowIndex], 'amount_taken', newLastAmount
                            .toFixed(2));
                        this.$set(this.payment_rows[lastRowIndex], 'disabled', false);
                        console.log('removeRow1: Updated last row amount_taken =', newLastAmount,
                            'and enabled');
                    }
                },
                amountTakenChanged() {
                    const totalPaid = this.payment_rows.reduce((sum, row) => sum + (parseFloat(row
                        .amount_taken) || 0), 0);
                    this.remainingAmount = Math.max(0, this.grandTotal - totalPaid);
                    console.log('amountTakenChanged: totalPaid=', totalPaid, 'remainingAmount=', this
                        .remainingAmount);
                },
                updatePaymentRows() {
                    // Update the last payment row's amount_taken to match grandTotal minus other payments
                    if (this.payment_rows.length === 1) {
                        this.$set(this.payment_rows[0], 'amount_taken', this.grandTotal.toFixed(2));
                        this.$set(this.payment_rows[0], 'disabled', false);
                    } else {
                        const lastRowIndex = this.payment_rows.length - 1;
                        const sumExceptLast = this.payment_rows.slice(0, lastRowIndex).reduce(
                            (sum, row) => sum + (parseFloat(row.amount_taken) || 0), 0
                        );
                        const newLastAmount = Math.max(0, this.grandTotal - sumExceptLast);
                        this.$set(this.payment_rows[lastRowIndex], 'amount_taken', newLastAmount.toFixed(2));
                        this.$set(this.payment_rows[lastRowIndex], 'disabled', false);
                    }
                },

                getPurchaseReferenceNo() {
                    const url = "/api/next-purchase-ref";

                    axios.get(url).then(response => {
                            app.purchaseReferenceNo = response.data.data.purchase_ref_code;
                            app.purchase_no = response.data.data.purchase_ref_code;

                            // console.log(app.purchaseReferenceNo);
                        })
                        .catch(error => {
                            console.error(error);
                        });
                },


                resetForm() {
                    app.supplier_id = '';
                    app.purchase_date = this.getTodayDate();
                    app.purchase_no = '';
                    app.invoice_number = '';
                    app.remarks = '';
                    app.id = '';
                    this.product_rows = [{
                        product_id: '',
                        unit_price: 0,
                        qty: 0,
                        tax_id: 0
                    }];
                    this.payment_rows = [{
                        payment_id: '',
                        amount_taken: 0,
                        payment_mode: '',
                        disabled: false
                    }];
                    app.selectedPurchase = '';

                    app.formSubmitted = false;
                    app.formErrors = {};

                    if (this.errors && this.errors.clear) {
                        this.errors.clear();
                    }
                },
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
                subtotal() {
                    return this.product_rows.reduce((sum, row) => {
                        const qty = parseFloat(row.qty) || 0;
                        const unit = parseFloat(row.unit_price) || 0;
                        return sum + (qty * unit);
                    }, 0);
                },
                taxTotal() {
                    return this.product_rows.reduce((sum, row) => {
                        const qty = parseFloat(row.qty) || 0;
                        const unit = parseFloat(row.unit_price) || 0;
                        const tax = this.taxList.find(t => t.id === row.tax_id)?.tax || 0;
                        return sum + ((qty * unit * tax) / 100);
                    }, 0);
                },
                grandTotal() {
                    return this.subtotal + this.taxTotal;
                },
                isTotalPaidEqualToGrandTotal() {
                    const totalPaid = this.payment_rows.reduce((sum, row) => sum + (parseFloat(row
                        .amount_taken) || 0), 0);
                    return Math.abs(totalPaid - this.grandTotal) < 0.01;
                },
                selectedPurchaseGrandTotal() {
                    if (!this.selectedPurchase || !this.selectedPurchase.purchase_item || this.selectedPurchase
                        .purchase_item.length === 0) {
                        return '0.00';
                    }
                    return this.selectedPurchase.purchase_item
                        .reduce((sum, item) => sum + parseFloat(item.total || 0), 0)
                        .toFixed(2);
                },

            },
            watch: {
                grandTotal(newGrandTotal) {
                    this.updatePaymentRows();
                }
            }
        });
    </script>
@endsection
