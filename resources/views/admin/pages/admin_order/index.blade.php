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
                                <div class="col-12">
                                    <h4 class="page-title">Create Order</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="card">
                            <div class="card-header">
                                <div class="row">

                                    {{-- <div class="col-md-6 mb-3 mt-3 custom-select-v">
                                        <label class="form-label">Select Customer</label>
                                        <select class="select2 form-control" v-model="customer_id">
                                            <option v-for="item in customerList" :value="item.id">
                                                @{{ item.customer_name }} (@{{ item.phone_number }})
                                            </option>
                                        </select>
                                    </div> --}}
                                    <div class="col-md-6 mb-3 mt-3">
                                        <label class="form-label">Customer Name</label>
                                        <select class="select2 form-control" id="select2-placeholder-single"
                                            v-model="customer_id">
                                            <option v-for="item in customersList" :value="item.id">
                                                @{{ item.customer_name }} ( @{{ item.phone_number }} )</option>

                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3 mt-3">
                                        <label class="form-label">Commission Type</label>
                                        <select class="form-select" v-model="commission_type">
                                            <option disabled value="">Select</option>
                                            <option v-for="item in commissionTypeList" :value="item.id">
                                                @{{ item.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>


                            <div class="card-body">

                                <h4 class="mb-3">Orders</h4>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            Company Name</label>
                                        <select class="form-select" v-model="selected_category"
                                            @change="getProductListByCategory">
                                            <option disabled value="">Select</option>
                                            <option v-for="item in categoryList" :value="item.id">
                                                @{{ item.category_name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Product</label>
                                        <select class="form-select" v-model="selected_product" @change="updateSpecialPrice">
                                            <option disabled value="">Select</option>
                                            <option v-for="item in productList" :value="item.id">
                                                @{{ item.product_name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Price</label>
                                        <input type="number" class="form-control" placeholder="Price"
                                            v-model="special_price">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Free Qty</label>
                                        <input type="number" class="form-control" placeholder="Free Qty"
                                            v-model="free_qty">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Qty</label>
                                        <input type="number" class="form-control" placeholder="Qty" v-model="qty">
                                    </div>

                                    <div class="col-md-12 text-end">
                                        <button type="button" class="btn btn-primary"
                                            @click="addProductToCart">Add</button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Order Date</label>
                                    <input type="date" class="form-control" v-model="order_date">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="direct_sale"
                                            v-model="is_direct_sale" @change="handleDirectSaleChange">
                                        <label class="form-check-label" for="direct_sale">
                                            Direct Sales
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3" v-if="!is_direct_sale">
                                    <label class="form-label">Assign Salesman</label>
                                    <select class="form-select" v-model="salesman_id">
                                        <option disabled value="">Select</option>
                                        <option v-for="item in saleManList" :value="item.id">
                                            @{{ item.name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" v-model="description" rows="3"></textarea>
                                </div>
                                    </select>
                                </div>

                                <div class="table-responsive" style="max-height: 48vh;">
                                    <table class="table mt-3 ">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Product Name</th>
                                                <th>Qty</th>
                                                <th>Price</th>
                                                <th class="text-end">Action</th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, index) in cart" :key="item.id">
                                                <td class="fw-semibold">
                                                    @{{ item.product_name }}
                                                    <p style="color: #C2185B" class="fw-bold">SR @{{ item.price }}
                                                    </p>
                                                </td>
                                                <td>@{{ item.qty }} <span v-if="item.free_qty" class="text-success">
                                                        (+@{{ item.free_qty }})</span></td>
                                                <td>SR @{{ (item.qty * item.price).toFixed(2) }}</td>
                                                <td class="text-end py-1">
                                                    <button type="button"
                                                        class="border-0 py-1 bg-transparent text-danger"
                                                        @click="removeFromCart(index)">
                                                        <i aria-hidden="true" class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>

                                        </tbody>
                                    </table>
                                </div>
                                <h4 class="fw-bold text-end me-2 mt-3 text-primery">Total: SR @{{ cartTotal }}</h4>

                                <thead>
                                    <th></th>
                                </thead>
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
                                            <td class="border-0 py-1">
                                                <input type="number" placeholder="Enter Price" min="0"
                                                    step="0.01" class="form-control" v-model="row.amount_taken"
                                                    :disabled="row.disabled">
                                            </td>
                                            <td class="border-0 py-1">
                                                <select class="form-select" v-model="row.payment_mode"
                                                    :disabled="row.disabled">
                                                    <option value="">Choose</option>
                                                    <option value="{{ config('constants.CASH') }}">
                                                        Cash</option>
                                                    <option value="{{ config('constants.CREDIT') }}">
                                                        Credit</option>

                                                    <option value="{{ config('constants.BANK_TRANSFER_ALRAJHI') }}">
                                                        TRANSFER ALRAJHI</option>
                                                    <option value="{{ config('constants.BANK_TRANSFER_SAB') }}">
                                                        TRANSFER SAB</option>
                                                    <option value="{{ config('constants.POS_ALRAJHIS') }}">
                                                        POS ALRAJHIS</option>
                                                    <option value="{{ config('constants.POS_SAB') }}">
                                                        POS SAB</option>
                                                    </option>
                                                </select>

                                                {{-- <select class="form-select" v-model="row.payment_mode">
                                                    <option value="">Choose</option>
                                                    <option value="0">Credit</option>
                                                    <option v-for="acc in accounts" :key="acc.id"
                                                        :value="acc.id">
                                                        @{{ acc.name }}
                                                    </option>
                                                </select> --}}
                                            </td>
                                            <td class="border-0 py-1 text-end"><button type="button"
                                                    class="border-0 py-1 bg-transparent text-danger"
                                                    @click="removePaymentRow(index)"><i aria-hidden="true"
                                                        class="fa fa-trash"></i></button> <button type="button"
                                                    class="border-0 py-1 bg-transparent text-danger"
                                                    @click="addPaymentRow"><i aria-hidden="true"
                                                        class="fa fa-plus"></i></button></td>

                                        </tr>
                                    </tbody>
                                </table>
                                <div class="text-end mt-3">
                                    <a href="javascript:void(0);" @click="createAdminOrder"
                                        class="btn btn-primary ">Request</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="modal fade" id="addCustomer_popup" role="dialog">
            <div class="modal-dialog modal-dialog-centered 	modal-xl">
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

                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label"> Customer Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="customer_name"
                                            v-model="customer_name" placeholder="Customer Name"
                                            v-validate.initial="customer_name" data-rules="required">
                                        <span v-if="formErrors['customer_name']"
                                            class="error text-danger">@{{ formErrors['customer_name'] }}</span>
                                        <p class="text-danger" v-if="errors.has('customer_name')">@{{ errors.first('customer_name') }}
                                        </p>
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Customer Name(ar)</label>
                                        <input type="text" class="form-control" name="customer_name_ar"
                                            placeholder="Customer Name(ar)" v-model="customer_name_ar">
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Email</label>
                                        <input type="email" class="form-control" name="email" v-model="email"
                                            placeholder="Email">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Phone <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="phone_number"
                                            v-model="phone_number" placeholder="Phone" v-validate.initial="phone_number"
                                            data-rules="required">
                                        <span v-if="formErrors['phone_number']"
                                            class="error text-danger">@{{ formErrors['phone_number'] }}</span>
                                        <p class="text-danger" v-if="errors.has('phone_number')">@{{ errors.first('phone_number') }}
                                        </p>
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Customer Type <span
                                                class="text-danger">*</span></label>
                                        <select v-model="customer_type" class="form-select"
                                            v-validate.initial="customer_type" data-rules="required">
                                            <option value="">Choose</option>
                                            <option value="{{ config('constants.WHOLESALE') }}">
                                                {{ config('constants.WHOLESALE') }}</option>
                                            <option value="{{ config('constants.RETAIL') }}">
                                                {{ config('constants.RETAIL') }}</option>
                                            <option value="{{ config('constants.OTHERS') }}">
                                                {{ config('constants.OTHERS') }}</option>
                                        </select>
                                        <span v-if="formErrors['customer_type']"
                                            class="error text-danger">@{{ formErrors['customer_type'] }}</span>
                                        <p class="text-danger" v-if="errors.has('customer_type')">@{{ errors.first('customer_type') }}
                                        </p>
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Postal Code</label>
                                        <input type="number" class="form-control" name="postal_code"
                                            v-model="postal_code" placeholder="Postal Code">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Commercial Reg.No</label>
                                        <input type="text" class="form-control" name="commercial_reg_no"
                                            v-model="commercial_reg_no" placeholder="Commercial Reg.No">
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Vat Reg.No</label>
                                        <input type="text" class="form-control" name="vat_reg_no"
                                            v-model="vat_reg_no" placeholder="Vat Reg.No">
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Country</label>
                                        <select v-model="country_name" class="form-select">
                                            <option value="">Choose</option>
                                            <option value="{{ config('constants.UAE') }}">
                                                {{ config('constants.UAE') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">City</label>
                                        <input type="text" class="form-control" name="city" v-model="city"
                                            placeholder="City">
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label clacol-md-4 mb-6ss="col-form-label">National Address No</label>
                                        <input type="text" class="form-control" name="national_address_no"
                                            v-model="national_address_no" placeholder="National Address No">
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label clacol-md-4 mb-6ss="col-form-label">Account No</label>
                                        <input type="text" class="form-control" name="account_no"
                                            v-model="account_no" placeholder="Account No">
                                    </div>


                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4 mb-6">
                                        <label clacol-md-4 mb-6ss="col-form-label">CR Number</label>
                                        <input type="text" class="form-control" name="cr_no" v-model="cr_no"
                                            placeholder="CR Number">
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Remarks</label>
                                        <input type="text" class="form-control" name="remarks" v-model="remarks"
                                            placeholder="remarks">
                                    </div>
                                    <div class="col-md-4 mb-6 ">
                                        <label class="col-form-label">Address</label>
                                        <textarea class="form-control" rows="1" v-model="address" placeholder="Address"></textarea>
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Latitude</label>
                                        <input type="number" class="form-control" name="latitude" v-model="latitude"
                                            placeholder="Latitude" step="any">
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Longitude</label>
                                        <input type="number" class="form-control" name="longitude" v-model="longitude"
                                            placeholder="Longitude" step="any">
                                    </div>
                                    <div class="col-md-4 mb-6" v-if="!open_balance">
                                        <label class="col-form-label">Opening Balance</label>
                                        <input type="number" class="form-control" name="open_balance"
                                            v-model="open_balance" placeholder="Opening Balance" step="any">
                                    </div>

                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="col-form-label">Status</label>
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <input type="radio" class="status-radio" id="add-active" name="status"
                                            value="1" v-model="status">
                                        <label for="add-active">Active</label>
                                    </div>
                                    <div>
                                        <input type="radio" class="status-radio" id="add-inactive" name="status"
                                            value="0" v-model="status">
                                        <label for="add-inactive">Inactive</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="d-flex align-items-center justify-content-end m-0">
                                <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                                <button type="submit" class="btn btn-primary"
                                    v-bind:disabled="submit_button_flag">@{{ modelBtn }}</button>
                            </div>
                        </div>
                    </form>
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
                    taxList: [],
                    customersList: [],
                    commissionTypeList: [],
                    categoryList: [],
                    productList: [],
                    saleManList: [],
                    selected_category: '',
                    selected_product: '',
                    special_price: 0,
                    free_qty: 0,
                    qty: 0,
                    commission_type: '',
                    customer_id: '',
                    salesman_id: '',
                    description:'',
                    user_id: '',
                    order_date: '',
                    is_direct_sale: false,
                    id: '',
                    modelBtn: '',
                    modelHeading: '',
                    formSubmitted: false,
                    submit_button_flag: false,
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

                    cart: [],
                    payment_rows: [{
                        payment_id: null,
                        amount_taken: 0,
                        payment_mode: '',
                        disabled: false
                    }],
                    accounts: [],
                    grandTotal: 0,
                    remainingAmount: 0,

                    modelBtn: 'Submit',
                    modelHeading: 'Create Customer',
                    logged_user_id: '',
                    customer_name: '',
                    customer_name_ar: '',
                    email: '',
                    phone_number: '',
                    customer_type: '',
                    postal_code: '',
                    commercial_reg_no: '',
                    vat_reg_no: '',
                    country_name: "{{ config('constants.UAE') }}",
                    city: '',
                    account_no: '',
                    cr_no: '',
                    national_address_no: '',
                    remarks: '',
                    status: '',
                    latitude: '',
                    longitude: '',
                    open_balance: '',
                    address: ''
                },

                computed: {
                    cartTotal() {
                        const total = this.cart.reduce((sum, item) => sum + ((parseFloat(item.qty) || 0) * (parseFloat(
                            item.price) || 0)), 0);
                        this.grandTotal = parseFloat(total.toFixed(2));
                        return this.grandTotal.toFixed(2);
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
                    }
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
                    validateBeforeSubmit() {
                        this.$validator.validateAll();
                        console.log(this.errors);
                        if (!this.errors.any()) {
                            this.createCustomer();
                        }
                    },
                    // createCustomer() {
                    //     const formData = new FormData();

                    //     formData.append("customer_name", app.customer_name);
                    //     formData.append("customer_name_ar", app.customer_name_ar);
                    //     formData.append("postal_code", app.postal_code);
                    //     formData.append("customer_type", app.customer_type);
                    //     formData.append("commercial_reg_no", app.commercial_reg_no);
                    //     formData.append("vat_reg_no", app.vat_reg_no);
                    //     formData.append("email", app.email);
                    //     formData.append("phone_number", app.phone_number);
                    //     formData.append("country_name", app.country_name ? app.country_name : '');
                    //     formData.append("city", app.city ? app.city : '');
                    //     formData.append("account_no", app.account_no ? app.account_no : '');
                    //     formData.append("cr_no", app.cr_no ? app.cr_no : '');
                    //     formData.append("national_address_no", app.national_address_no ? app.national_address_no : '');
                    //     formData.append("remarks", app.remarks);
                    //     formData.append("address", app.address ? app.address : '');
                    //     formData.append("status", app.status);
                    //     formData.append("latitude", app.latitude);
                    //     formData.append("longitude", app.longitude);
                    //     formData.append("opening_balance", app.open_balance);
                    //     formData.append("logged_user_id", this.logged_user_id);

                    //     app.submit_button_flag = true;

                    //     axios.post("/api/customer/store", formData)
                    //         .then(response => {
                    //             this.showAPIResponse(response);
                    //             if (response.data.code === 0) {
                    //                 this.getCustomerList();
                    //             }
                    //         })
                    //         .catch(error => {
                    //             this.submit_button_flag = false;
                    //             this.formErrors = error.response?.data?.errors || {};
                    //             Vue.$toast.error('Failed to create customer', 'Error');
                    //         });
                    // },
                    createCustomer() {
                        const formData = new FormData();
                        formData.append("customer_name", this.customer_name);
                        formData.append("customer_name_ar", this.customer_name_ar);
                        formData.append("postal_code", this.postal_code);
                        formData.append("customer_type", this.customer_type);
                        formData.append("commercial_reg_no", this.commercial_reg_no);
                        formData.append("vat_reg_no", this.vat_reg_no);
                        formData.append("email", this.email);
                        formData.append("phone_number", this.phone_number);
                        formData.append("country_name", this.country_name ? this.country_name : '');
                        formData.append("city", this.city ? this.city : '');
                        formData.append("account_no", this.account_no ? this.account_no : '');
                        formData.append("cr_no", this.cr_no ? this.cr_no : '');
                        formData.append("national_address_no", this.national_address_no ? this.national_address_no :
                            '');
                        formData.append("remarks", this.remarks);
                        formData.append("address", this.address ? this.address : '');
                        formData.append("status", this.status);
                        formData.append("latitude", this.latitude);
                        formData.append("longitude", this.longitude);
                        formData.append("opening_balance", this.open_balance);
                        formData.append("logged_user_id", this.logged_user_id);

                        this.submit_button_flag = true;

                        axios.post("/api/customer/store", formData)
                            .then(response => {
                                this.showAPIResponse(response);
                                if (response.data.code === 0) {
                                    // Store the newly created customer ID
                                    const newCustomerId = response.data.data?.id || null;
                                    // Refresh customer list and select new customer
                                    this.getCustomerList(newCustomerId);
                                }
                            })
                            .catch(error => {
                                this.submit_button_flag = false;
                                this.formErrors = error.response?.data?.errors || {};
                                Vue.$toast.error('Failed to create customer', 'Error');
                            });
                    },
                    createAdminOrder() {
                        // Validate customer selection
                        if (!this.customer_id || this.customer_id === '') {
                            Vue.$toast.error('Please select a customer', 'Error Alert');
                            return;
                        }



                        // Validate product selection and quantity
                        if (this.cart.length === 0) {
                            Vue.$toast.error('Please add at least one product with quantity', 'Error Alert');
                            return;
                        }

                        // Check if all products have at least 1 quantity
                        const hasInvalidQty = this.cart.some(item => !item.qty || parseFloat(item.qty) < 1);
                        if (hasInvalidQty) {
                            Vue.$toast.error('All products must have at least 1 quantity', 'Error Alert');
                            return;
                        }

                        // Validate salesman selection (only if not direct sale)
                        if (!this.is_direct_sale && (this.salesman_id == null || this.salesman_id === '')) {
                            Vue.$toast.error('Please select salesman', 'Error Alert');
                            return;
                        }

                        this.submit_button_flag = true;
                        const formData = new FormData();
                        formData.append('customer_id', this.customer_id);
                        formData.append('commission_type', this.commission_type);
                        formData.append('salesman_id', this.is_direct_sale ? this.user_id : this.salesman_id);
                        formData.append('user_id', this.user_id);
                        formData.append('description', this.description || '');
                        formData.append('order_date', this.order_date || this.getCurrentDate());
                        formData.append('is_direct_sale', this.is_direct_sale ? 1 : 0);
                        formData.append('cart', JSON.stringify(this.cart));
                        formData.append('payment_rows', JSON.stringify(this.payment_rows));
                        formData.append('customer_type', 'in');

                        axios.post("/api/admin-order/store", formData)
                            .then(response => {
                                this.showAPIResponse(response);
                            })
                            .catch(error => {
                                this.submit_button_flag = false;
                                const responseData = error.response?.data || {};

                                if (responseData.errors) {
                                    this.formErrors = responseData.errors;
                                } else {
                                    this.formErrors = {};
                                }

                                if (responseData.message) {
                                    Vue.$toast.error(responseData.message, 'Error Alert');
                                } else {
                                    Vue.$toast.error('Something went wrong while creating the order.', 'Error Alert');
                                }

                                console.error(error);
                            });
                    },

                    // showAPIResponse(response) {
                    //     if (response.data && response.data.code == 0) {
                    //         Vue.$toast && Vue.$toast.success(response.data.message, 'Success Alert');
                    //         this.submit_button_flag = false;
                    //         this.formSubmitted = true;
                    //         this.formErrors = {};
                    //         this.resetForm();
                    //         $('#addCustomer_popup').modal('hide');
                    //     } else {
                    //         Vue.$toast && Vue.$toast.error(response.data.message || 'Something went wrong',
                    //             'Error Alert');
                    //         this.submit_button_flag = false;
                    //     }
                    // },
                    showAPIResponse(response) {
                        if (response.data && response.data.code == 0) {
                            Vue.$toast.success(response.data.message, 'Success Alert');
                            this.submit_button_flag = false;
                            this.formSubmitted = true;
                            this.formErrors = {};
                            this.resetForm();
                            $('#addCustomer_popup').modal('hide');
                        } else {
                            Vue.$toast.error(response.data.message || 'Something went wrong', 'Error Alert');
                            this.submit_button_flag = false;
                        }
                    },

                    handleDirectSaleChange() {
                        if (this.is_direct_sale) {
                            this.salesman_id = '';
                        }
                    },
                    getCurrentDate() {
                        const today = new Date();
                        const year = today.getFullYear();
                        const month = String(today.getMonth() + 1).padStart(2, '0');
                        const day = String(today.getDate()).padStart(2, '0');
                        return `${year}-${month}-${day}`;
                    },
                    resetForm() {
                        this.customer_id = '';
                        this.commission_type = '';
                        this.salesman_id = '';
                        this.description = '';
                        this.order_date = this.getCurrentDate();
                        this.is_direct_sale = false;
                        this.selected_category = '';
                        this.selected_product = '';
                        this.special_price = 0;
                        this.free_qty = 0;
                        this.qty = 0;
                        this.cart = [];
                        this.payment_rows = [{
                            payment_id: null,
                            amount_taken: 0,
                            payment_mode: '',
                            disabled: false
                        }];
                        this.submit_button_flag = false;
                        this.formSubmitted = false;
                        this.formErrors = {};

                        if (this.errors && this.errors.clear) {
                            this.errors.clear();
                        }

                        // update select2 visual state
                        $('#select2-placeholder-single').val(null).trigger('change');
                    },

                    getTaxList: function(page = 1) {
                        const url = "/api/tax/list";
                        axios.get(url, {
                            params: {
                                page: page,
                                page_length: this.page_length,
                                search: this.search,
                            }
                        }).then(response => {
                            this.taxList = response.data.data.tax || [];
                            this.pagination = response.data.data.pagination || this.pagination;
                            this.loading = false;
                        }).catch(err => {
                            console.error('getTaxList error', err);
                        });
                    },

                    // getCustomerList() {
                    //     return axios.get('/api/customer/list', {
                    //             params: {
                    //                 platform: 'api'
                    //             }
                    //         })
                    //         .then(response => {
                    //             const payload = response.data.data || response.data || {};
                    //             const rawCustomers = payload.customers || payload || [];
                    //             this.customersList = rawCustomers.map(customer => ({
                    //                 ...customer,
                    //                 phone_number: (customer.phone_number || '').toString().trim(),
                    //                 customer_name: (customer.customer_name || customer.name || '')
                    //                     .toString().trim(),
                    //                 label: `${(customer.customer_name || customer.name || '').toString().trim()}${customer.phone_number ? ' (' + customer.phone_number + ')' : ''}`
                    //             }));
                    //             this.$nextTick(() => this.initSelect2());
                    //         })
                    //         .catch(error => {
                    //             console.error('Error loading customers:', error);
                    //             Vue.$toast && Vue.$toast.error('Failed to load customers.', 'Error');
                    //         });
                    // },
                    getCustomerList(selectCustomerId = null) {
                        return axios.get('/api/customer/list', {
                                params: {
                                    platform: 'api',
                                    customer_type:'in',
                                }
                            })
                            .then(response => {
                                const payload = response.data.data || response.data || {};
                                const rawCustomers = payload.customers || payload || [];
                                this.customersList = rawCustomers.map(customer => ({
                                    ...customer,
                                    phone_number: (customer.phone_number || '').toString().trim(),
                                    customer_name: (customer.customer_name || customer.name || '')
                                        .toString().trim(),
                                    label: `${(customer.customer_name || customer.name || '').toString().trim()}${customer.phone_number ? ' (' + customer.phone_number + ')' : ''}`
                                }));
                                this.$nextTick(() => {
                                    this.initSelect2();
                                    // Auto-select the newly created customer if provided
                                    if (selectCustomerId) {
                                        this.customer_id = selectCustomerId;
                                        $('#select2-placeholder-single').val(selectCustomerId).trigger(
                                            'change');
                                    }
                                });
                            })
                            .catch(error => {
                                console.error('Error loading customers:', error);
                                Vue.$toast.error('Failed to load customers.', 'Error');
                            });
                    },

                    // initSelect2() {
                    //     const $sel = $('#select2-placeholder-single');
                    //     if ($sel.hasClass('select2-hidden-accessible')) {
                    //         $sel.select2('destroy');
                    //     }

                    //     $sel.select2({
                    //         placeholder: 'Search customer',
                    //         allowClear: true,
                    //         width: '100%',
                    //         minimumInputLength: 0,
                    //         language: {
                    //             noResults: () => {
                    //                 const searchTerm = $('.select2-container--open .select2-search__field')
                    //                     .val()?.trim() || '';
                    //                 const existingCustomer = this.findExistingCustomer(searchTerm);
                    //                 if (existingCustomer) {
                    //                     return `
            //                     <div class="text-center p-2">
            //                         <div class="alert alert-info mb-2">
            //                             <i class="fas fa-user-check"></i> Customer already exists:
            //                             <br><strong>${existingCustomer.customer_name}</strong>
            //                             <br>Phone: ${existingCustomer.phone_number}
            //                         </div>
            //                         <button type="button" id="selectExistingCustomer" class="btn btn-success btn-sm w-100" data-customer-id="${existingCustomer.id}">
            //                             <i class="fas fa-check"></i> Select This Customer
            //                         </button>
            //                     </div>`;
                    //                 }
                    //                 return `
            //                 <div class="text-center p-2">
            //                     <div class="mb-2 text-muted">
            //                         <i class="fas fa-search"></i> No customer found ${searchTerm ? `for "${searchTerm}"` : ''}
            //                     </div>
            //                     <button type="button" id="addCustomerBtn" class="btn btn-primary btn-sm w-100">
            //                         <i class="fas fa-plus"></i> Add New Customer
            //                     </button>
            //                 </div>`;
                    //             },
                    //             searching: () => '<i class="fas fa-spinner fa-spin"></i> Searching customers...'
                    //         },
                    //         escapeMarkup: markup => markup
                    //     });

                    //     $sel.on('change', () => {
                    //         this.customer_id = $sel.val() || '';
                    //     });

                    //     $(document).off('click', '#selectExistingCustomer').on('click', '#selectExistingCustomer', (
                    //         e) => {
                    //         const customerId = $(e.currentTarget).data('customer-id');
                    //         $sel.val(customerId).trigger('change');
                    //         $sel.select2('close');
                    //     });

                    //     $(document).off('click', '#addCustomerBtn').on('click', '#addCustomerBtn', () => {
                    //         this.openAddCustomerModal();
                    //         $sel.select2('close');
                    //     });

                    //     $(document).off('keydown', '.select2-container--open .select2-search__field').on('keydown',
                    //         '.select2-container--open .select2-search__field', (e) => {
                    //             if (e.key === 'Enter') {
                    //                 e.preventDefault();
                    //                 this.openAddCustomerModal();
                    //                 $sel.select2('close');
                    //             }
                    //         });
                    // },
                    initSelect2() {
                        const $sel = $('#select2-placeholder-single');
                        if ($sel.hasClass('select2-hidden-accessible')) {
                            $sel.select2('destroy');
                        }

                        $sel.select2({
                            placeholder: 'Search customer',
                            allowClear: true,
                            width: '100%',
                            minimumInputLength: 0,
                            data: this.customersList.map(customer => ({
                                id: customer.id,
                                text: customer.label
                            })),
                            language: {
                                noResults: () => {
                                    const searchTerm = $('.select2-container--open .select2-search__field')
                                        .val()?.trim() || '';
                                    const existingCustomer = this.findExistingCustomer(searchTerm);
                                    if (existingCustomer) {
                                        return `
                                        <div class="text-center p-2">
                                            <div class="alert alert-info mb-2">
                                                <i class="fas fa-user-check"></i> Customer already exists:
                                                <br><strong>${existingCustomer.customer_name}</strong>
                                                <br>Phone: ${existingCustomer.phone_number}
                                            </div>
                                            <button type="button" id="selectExistingCustomer" class="btn btn-success btn-sm w-100" data-customer-id="${existingCustomer.id}">
                                                <i class="fas fa-check"></i> Select This Customer
                                            </button>
                                        </div>`;
                                    }
                                    return `
                                    <div class="text-center p-2">
                                        <div class="mb-2 text-muted">
                                            <i class="fas fa-search"></i> No customer found ${searchTerm ? `for "${searchTerm}"` : ''}
                                        </div>
                                        <button type="button" id="addCustomerBtn" class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-plus"></i> Add New Customer
                                        </button>
                                    </div>`;
                                },
                                searching: () => '<i class="fas fa-spinner fa-spin"></i> Searching customers...'
                            },
                            escapeMarkup: markup => markup
                        });

                        if (!this.customer_id) {
                            $sel.val(null).trigger('change');
                        }

                        $sel.on('change', () => {
                            this.customer_id = $sel.val() || '';
                        });

                        $(document).off('click', '#selectExistingCustomer').on('click', '#selectExistingCustomer', (
                            e) => {
                            const customerId = $(e.currentTarget).data('customer-id');
                            $sel.val(customerId).trigger('change');
                            $sel.select2('close');
                        });

                        $(document).off('click', '#addCustomerBtn').on('click', '#addCustomerBtn', () => {
                            this.openAddCustomerModal();
                            $sel.select2('close');
                        });

                        $(document).off('keydown', '.select2-container--open .select2-search__field').on('keydown',
                            '.select2-container--open .select2-search__field', (e) => {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    this.openAddCustomerModal();
                                    $sel.select2('close');
                                }
                            });
                    },

                    // openAddCustomerModal() {
                    //     const searchTerm = $('.select2-container--open .select2-search__field').val()?.trim() || '';
                    //     const existingCustomer = this.findExistingCustomer(searchTerm);

                    //     if (existingCustomer && confirm(
                    //             `Customer "${existingCustomer.customer_name}" (${existingCustomer.phone_number}) already exists. Do you want to select this customer instead?`
                    //         )) {
                    //         this.customer_id = existingCustomer.id;
                    //         this.$nextTick(() => {
                    //             $('#select2-placeholder-single').val(existingCustomer.id).trigger('change');
                    //         });
                    //         return;
                    //     }

                    //     // this.resetCustomerForm();
                    //     if (searchTerm) {
                    //         const phoneRegex = /^\d+$/;
                    //         if (phoneRegex.test(searchTerm)) {
                    //             this.phone_number = searchTerm;
                    //         } else {
                    //             this.customer_name = searchTerm;
                    //         }
                    //     }

                    //     this.$nextTick(() => {
                    //         $('#addCustomer_popup').modal('show');
                    //         $('#addCustomer_popup').on('shown.bs.modal', () => {
                    //             this.$refs.customer_full_name.focus();
                    //         });
                    //     });
                    // },
                    openAddCustomerModal() {
                        const searchTerm = $('.select2-container--open .select2-search__field').val()?.trim() || '';
                        const existingCustomer = this.findExistingCustomer(searchTerm);

                        if (existingCustomer && confirm(
                                `Customer "${existingCustomer.customer_name}" (${existingCustomer.phone_number}) already exists. Do you want to select this customer instead?`
                            )) {
                            this.customer_id = existingCustomer.id;
                            this.$nextTick(() => {
                                $('#select2-placeholder-single').val(existingCustomer.id).trigger('change');
                            });
                            return;
                        }

                        // Populate form with search term if applicable
                        if (searchTerm) {
                            const phoneRegex = /^\d+$/;
                            if (phoneRegex.test(searchTerm)) {
                                this.phone_number = searchTerm;
                            } else {
                                this.customer_name = searchTerm;
                            }
                        }

                        this.$nextTick(() => {
                            $('#addCustomer_popup').modal('show');
                        });
                    },

                    findExistingCustomer(searchTerm) {
                        if (!searchTerm) return null;
                        const lowered = searchTerm.toLowerCase();
                        return this.customersList.find(c =>
                            (c.customer_name && c.customer_name.toLowerCase().includes(lowered)) ||
                            (c.phone_number && c.phone_number.toLowerCase().includes(lowered))
                        ) || null;
                    },

                    getCommissionTypeList() {
                        axios.get('/api/commission-type/list', {
                                params: {
                                    platform: 'api'
                                }
                            })
                            .then(response => {
                                this.commissionTypeList = response.data.data || [];
                            })
                            .catch(error => {
                                console.log(error);
                            });
                    },

                    getProductCategoryList() {
                        axios.get('/api/category/list', {
                                params: {
                                    platform: 'api'
                                }
                            })
                            .then(response => {
                                this.categoryList = response.data.data || [];
                            })
                            .catch(error => {
                                console.log(error);
                            });
                    },

                    getProductListByCategory() {
                        if (!this.selected_category) {
                            this.productList = [];
                            return;
                        }
                        const url = "/api/product/list";
                        axios.get(url, {
                            params: {
                                platform: 'api',
                                category_id: this.selected_category,
                                customer_id: this.customer_id
                            }
                        }).then(response => {
                            this.productList = (response.data.data && response.data.data.products) || [];
                        }).catch(err => {
                            console.error('getProductListByCategory error', err);
                        });
                    },

                    getSalesManList() {
                        axios.get('/api/sales-man-list', {
                                params: {
                                    platform: 'api'
                                }
                            })
                            .then(response => {
                                this.saleManList = response.data.data || [];
                            })
                            .catch(error => {
                                console.log(error);
                            });
                    },

                    updateSpecialPrice() {
                        const product = this.productList.find(p => p.id === this.selected_product);
                        if (product) {
                            this.special_price = parseFloat(product.selling_price) || 0;
                        } else {
                            this.special_price = 0;
                        }
                    },

                    addProductToCart() {
                        if (!this.selected_product) {
                            Vue.$toast && Vue.$toast.error('Please select a product', 'Error');
                            return;
                        }

                        if (!this.qty || parseFloat(this.qty) < 1) {
                            Vue.$toast && Vue.$toast.error('Please enter at least 1 quantity', 'Error');
                            return;
                        }

                        const product = this.productList.find(p => p.id === this.selected_product);
                        if (!product) return;

                        const existingItem = this.cart.find(item => item.id === product.id);
                        if (existingItem) {
                            existingItem.qty = (parseInt(existingItem.qty) || 0) + parseInt(this.qty || 0);
                            existingItem.free_qty = (parseInt(existingItem.free_qty) || 0) + parseInt(this.free_qty ||
                                0);
                        } else {
                            this.cart.push({
                                id: product.id,
                                product_name: product.product_name,
                                qty: parseInt(this.qty || 0),
                                free_qty: parseInt(this.free_qty || 0),
                                price: parseFloat(this.special_price) || 0,
                            });
                        }

                        this.selected_product = '';
                        this.qty = 0;
                        this.free_qty = 0;
                        this.special_price = 0;
                    },

                    removeFromCart(index) {
                        this.cart.splice(index, 1);
                    },

                    addPaymentRow() {
                        // recompute remaining
                        const totalPaid = this.payment_rows.reduce((sum, row) => sum + (parseFloat(row.amount_taken) ||
                            0), 0);
                        this.remainingAmount = Math.max(0, this.grandTotal - totalPaid);

                        // disable previous last row
                        if (this.payment_rows.length > 0) {
                            this.$set(this.payment_rows, this.payment_rows.length - 1, {
                                ...this.payment_rows[this.payment_rows.length - 1],
                                disabled: true
                            });
                        }

                        // add new row with remainingAmount
                        this.$set(this.payment_rows, this.payment_rows.length, {
                            payment_id: null,
                            amount_taken: this.remainingAmount > 0 ? parseFloat(this.remainingAmount.toFixed(
                                2)) : 0.00,
                            payment_mode: '',
                            disabled: false
                        });
                    },

                    removePaymentRow(index) {
                        if (this.payment_rows.length <= 1) {
                            console.log('Cannot remove last payment row');
                            return;
                        }

                        this.payment_rows.splice(index, 1);

                        // recalc and set last row appropriately
                        const totalPaid = this.payment_rows.reduce((sum, row) => sum + (parseFloat(row.amount_taken) ||
                            0), 0);
                        this.remainingAmount = Math.max(0, this.grandTotal - totalPaid);

                        if (this.payment_rows.length === 1) {
                            this.$set(this.payment_rows[0], 'amount_taken', parseFloat(this.grandTotal.toFixed(2)));
                            this.$set(this.payment_rows[0], 'disabled', false);
                        } else {
                            const lastIdx = this.payment_rows.length - 1;
                            let sumExceptLast = this.payment_rows.slice(0, lastIdx).reduce((s, r) => s + (parseFloat(r
                                .amount_taken) || 0), 0);
                            let newLastAmount = Math.max(0, this.grandTotal - sumExceptLast);
                            this.$set(this.payment_rows[lastIdx], 'amount_taken', parseFloat(newLastAmount.toFixed(2)));
                            this.$set(this.payment_rows[lastIdx], 'disabled', false);
                        }
                    },

                    amountTakenChanged() {
                        const totalPaid = this.payment_rows.reduce((sum, row) => sum + (parseFloat(row.amount_taken) ||
                            0), 0);
                        this.remainingAmount = Math.max(0, this.grandTotal - totalPaid);
                    },

                    updatePaymentRows() {
                        if (this.payment_rows.length === 1) {
                            this.$set(this.payment_rows[0], 'amount_taken', parseFloat(this.grandTotal.toFixed(2)));
                            this.$set(this.payment_rows[0], 'disabled', false);
                        } else {
                            const lastRowIndex = this.payment_rows.length - 1;
                            const sumExceptLast = this.payment_rows.slice(0, lastRowIndex).reduce((sum, row) => sum + (
                                parseFloat(row.amount_taken) || 0), 0);
                            const newLastAmount = Math.max(0, this.grandTotal - sumExceptLast);
                            this.$set(this.payment_rows[lastRowIndex], 'amount_taken', parseFloat(newLastAmount.toFixed(
                                2)));
                            this.$set(this.payment_rows[lastRowIndex], 'disabled', false);
                        }
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
                    this.getLoggedUserInfo();
                    this.loadAccounts();
                    this.getTaxList();
                    // load customers and init select2 after they arrive
                    this.getCustomerList();
                    this.getCommissionTypeList();
                    this.getProductCategoryList();
                    this.getSalesManList();
                    // Set default order date to current date
                    this.order_date = this.getCurrentDate();
                },

                watch: {
                    // cartTotal is a computed string (formatted). parseFloat to get numeric value.
                    cartTotal(newTotal) {
                        const n = parseFloat(newTotal) || 0;
                        this.grandTotal = n;
                        if (this.payment_rows.length > 0) {
                            this.$set(this.payment_rows[0], 'amount_taken', parseFloat(n.toFixed(2)));
                        }
                    },
                    customer_id() {
                        if (this.selected_category) {
                            this.getProductListByCategory(); // reload products with correct price
                        }
                    }
                }

            });
        </script>
    @endsection
