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
                                    <h4 class="page-title">Supplier</h4>
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
                                            <input type="text" class="form-control" placeholder="Search Supplier"
                                                v-model="search" @input="getSupplierList">
                                        </div>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="text-sm-end">
                                            <a href="javascript:void(0);" class="btn btn-primary "
                                                v-on:click="showEditSupplierModal(0)"><i
                                                    class="ti ti-square-rounded-plus me-2"></i>Create</a>
                                        </div>
                                    </div>
                                </div>
                                <!-- /Search -->
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

                                                <th>Code</th>
                                                <th> Name</th>
                                                <th>Phone</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!supplierList || !supplierList.length">
                                                <tr>
                                                    <td colspan="7">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in supplierList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>

                                                    <td><a href="javascript:void(0);">@{{ data.supplier_code }}</a>
                                                    </td>
                                                    <td>@{{ data.supplier_name }}</td>
                                                    <td>@{{ data.phone }}</td>
                                                    <td>
                                                        <span
                                                            :class="data.status === 1 ? 'badge bg-success' :
                                                                'badge bg-danger'">
                                                            <p v-if="data.status==1">Active</p>
                                                            <p v-else>Inactive</p>
                                                        </span>
                                                    </td>

                                                    {{-- <td>
                                                        <div class="dropdown table-action"><a href="#"
                                                                data-bs-toggle="dropdown" aria-expanded="false"
                                                                class="action-icon"><i class="fa fa-ellipsis-v"></i></a>
                                                            <div class="dropdown-menu dropdown-menu-right" style="">
                                                                <a class="dropdown-item"
                                                                    v-on:click="showEditSupplierModal(data)"><i
                                                                        class="ti ti-edit text-blue"></i>Edit</a>
                                                                <a class="dropdown-item"
                                                                    v-on:click="deleteItem(data,index)"><i
                                                                        class="ti ti-trash text-red"></i>Delete</a>
                                                            </div>
                                                        </div>
                                                    </td> --}}

                                                    <td class="d-flex align-items-center gap-5">
                                                        <!-- Dropdown -->

                                                        <div class="d-flex align-items-center gap-3">
                                                            <a class="text-bg-transparent"
                                                                v-on:click="showEditSupplierModal(data)">
                                                                <i class="ti ti-edit text-blue"></i>
                                                            </a>
                                                            {{-- <a class="text-bg-transparent"
                                                                    v-on:click="deleteItem(data,index)">
                                                                    <i class="ti ti-trash text-red"></i>
                                                                </a> --}}
                                                        </div>



                                                        <!-- Pay Button -->
                                                        <button v-if="data.balance > 0" class="btn btn-sm btn-primary"
                                                            v-on:click="showPayModal(data)">
                                                            Pay
                                                        </button>
                                                    </td>

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


            <!-- Add/Edit Payment Modal -->
            <div class="modal fade" id="add_payment" role="dialog" aria-labelledby="addPaymentModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addPaymentModalLabel">@{{ modelHeading }}</h5>
                            <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                                aria-label="Close">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="col-md-12">
                                <div class="row row-gap-3">
                                    <div class="col-md-12">
                                        <label class="col-form-label">Amount <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" v-model.number="credit_amount"
                                            v-model="credit_amount" placeholder="Enter Amount"
                                            :class="{ 'is-invalid': formErrors.credit_amount }" step="0.01"
                                            min="0">
                                        <div class="invalid-feedback" v-if="formErrors.credit_amount">
                                            @{{ formErrors.credit_amount }}</div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="col-form-label">Payment Mode <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select" v-model="credit_payment_mode"
                                            :class="{ 'is-invalid': formErrors.credit_payment_mode }">
                                            <option value="" disabled>Select Payment Mode</option>
                                            <option value="{{ config('constants.CASH') }}">
                                                Cash</option>
                                            {{-- <option value="{{ config('constants.CREDIT') }}">
                                                Credit</option> --}}

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
                                        <div class="invalid-feedback" v-if="formErrors.credit_payment_mode">
                                            @{{ formErrors.credit_payment_mode }}</div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="col-form-label">Payment Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" class="form-control" v-model="payment_date" required
                                            :class="{ 'is-invalid': formErrors.payment_date }">
                                        <div class="invalid-feedback" v-if="formErrors.payment_date">
                                            @{{ formErrors.payment_date }}</div>
                                    </div>
                                    <div>
                                        <label for="description">Description</label>
                                        <textarea class="form-control" id="description" rows="3" v-model="description"
                                            placeholder="Enter description"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="d-flex align-items-center justify-content-end m-0">
                                <button class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" @click="validatePaymentBeforeSubmit"
                                    :disabled="submit_button_flag">
                                    <span v-if="submit_button_flag" class="spinner-border spinner-border-sm me-2"
                                        role="status" aria-hidden="true"></span>
                                    @{{ modelBtn }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Page Wrapper -->

        <div class="modal fade" id="add_supplier" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
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
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label"> Supplier Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="supplier_name"
                                            v-model="supplier_name" v-validate.initial="supplier_name"
                                            data-rules="required" id="supplier_name" placeholder="Supplier Name">
                                        <span v-if="formErrors['supplier_name']"
                                            class="error text-danger">@{{ formErrors['supplier_name'][0] }}</span>
                                        <p class="text-danger" v-if="errors.has('supplier_name')">@{{ errors.first('supplier_name') }}
                                        </p>
                                    </div>

                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Supplier Name(ar)</label>
                                        <input type="text" class="form-control" name="ar_supplier_name"
                                            v-model="ar_supplier_name" placeholder="Supplier Name(ar)"
                                            @input="ar_supplier_name = ar_supplier_name.replace(/[^\u0600-\u06FF\s]/g, '')">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Email</label>
                                        <input type="email" class="form-control" name="email" v-model="email"
                                            placeholder="Email">
                                        <span v-if="formErrors['email']"
                                            class="error text-danger">@{{ formErrors['email'][0] }}</span>

                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Phone <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="phone" v-model="phone"
                                            v-validate.initial="phone" data-rules="required" placeholder="Phone">
                                        <span v-if="formErrors['phone']"
                                            class="error text-danger">@{{ formErrors['phone'][0] }}</span>
                                        <p class="text-danger" v-if="errors.has('phone')">@{{ errors.first('phone') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Commercial Reg.No</label>
                                        <input type="text" class="form-control" name="commercial_reg_no"
                                            v-model="commercial_reg_no" placeholder="Commercial Reg.No">
                                        <span v-if="formErrors['commercial_reg_no']"
                                            class="error text-danger">@{{ formErrors['commercial_reg_no'][0] }}</span>
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Vat Reg.No</label>
                                        <input type="text" class="form-control" name="vat_reg_no"
                                            v-model="vat_reg_no" placeholder="Vat Reg.No">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Country</label>
                                        <select v-model="country_name" class="form-select">
                                            <option value="">Choose</option>
                                            <option value="{{ config('constants.UAE') }}">
                                                {{ config('constants.UAE') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">City</label>
                                        <input type="text" class="form-control" name="city" v-model="city"
                                            placeholder="City">
                                    </div>

                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Legal Form</label>
                                        <select v-model="legal_form" class="form-select">
                                            <option value="">Choose</option>
                                            <option value="{{ config('constants.LLC') }}">
                                                {{ config('constants.LLC') }}</option>
                                            <option value="{{ config('constants.SOLE_PROPRIETORSHIP') }}">
                                                {{ config('constants.SOLE_PROPRIETORSHIP') }}</option>
                                            <option value="{{ config('constants.PARTNERSHIP') }}">
                                                {{ config('constants.PARTNERSHIP') }}</option>

                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Remarks</label>
                                        <input type="text" class="form-control" name="remarks" v-model="remarks"
                                            placeholder="Remarks">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="col-form-label">Address</label>
                                    <textarea class="form-control" rows="5" v-model="address" placeholder="Address"></textarea>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6 mb-6">

                                        <label class="col-form-label">Opening Balance</label>
                                        <input type="number" class="form-control" name="opening_balance"
                                            v-model="opening_balance" placeholder="Opening Balance" step="any">
                                    </div>
                                    {{-- <div class="mb-0"> --}}
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Status</label>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <input type="radio" class="status-radio" id="add-active"
                                                    name="status" value="1" v-model="status">
                                                <label for="add-active">Active</label>
                                            </div>
                                            <div>
                                                <input type="radio" class="status-radio" id="add-inactive"
                                                    name="status" value="0" v-model="status">
                                                <label for="add-inactive">Inactive</label>
                                            </div>
                                        </div>
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
        <!-- /Add New Source -->




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
                id: '',
                supplier_name: "",
                ar_supplier_name: "",
                commercial_reg_no: "",
                vat_reg_no: "",
                email: "",
                phone: "",
                country_name: "",
                city: "",
                legal_form: "",
                remarks: "",
                address: "",
                description: "",
                status: "",
                opening_balance: "",
                open_balance: 0,
                user_id: "",
                modelBtn: '',
                modelHeading: '',

                formSubmitted: false,
                submit_button_flag: false,

                credit_amount: '',
                credit_balance_amount: '',
                credit_payment_mode: '',
                payment_date: '',
                logged_user_id: null,
                supplier_id: '',

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
            mounted() {
                this.getLoggedUserInfo();
                this.getSupplierList();
                $('#add_supplier').on('hidden.bs.modal', () => {
                    this.resetForm();
                });

                $('#add_payment').on('hidden.bs.modal', () => {
                    this.resetPaymentForm();
                });
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
                hasPositiveBalance() {
                    const balance = parseFloat(this.open_balance);
                    return !isNaN(balance) && balance > 0;
                }
            },
            methods: {

                validatePaymentBeforeSubmit() {
                    this.formErrors = {};
                    let isValid = true;

                    this.submit_button_flag = true;
                    if (!this.credit_amount || this.credit_amount <= 0) {
                        this.formErrors.credit_amount = 'Please enter a valid amount greater than 0.';
                        isValid = false;
                    }

                    if (this.credit_amount > this.credit_balance_amount) {
                        this.formErrors.credit_amount =
                            `Please enter a valid amount less than or equal to ${this.credit_balance_amount}`;
                        isValid = false;
                    }


                    if (!this.credit_payment_mode) {
                        this.formErrors.credit_payment_mode = 'Please select a payment mode.';
                        isValid = false;
                    }

                    if (!this.payment_date) {
                        this.formErrors.payment_date = 'Please select a payment date.';
                        isValid = false;
                    }

                    if (isValid) {
                        this.purchaseCreditPayment();
                    } else {
                        this.submit_button_flag = false;
                    }
                },

                purchaseCreditPayment() {
                    const formData = new FormData();
                    formData.append('supplier_id', this.supplier_id);
                    formData.append('user_id', this.logged_user_id);
                    formData.append('amount', this.credit_amount);
                    formData.append('payment_mode', this.credit_payment_mode);
                    formData.append('date', this.payment_date);
                    formData.append('description', this.description);

                    this.submit_button_flag = true;

                    axios.post('/api/purchase-credit-payment', formData, {
                            headers: {
                                'Authorization': `Bearer ${this.token}`
                            }
                        })
                        .then(response => {
                            this.showAPIResponse(response);
                        })
                        .catch(error => {
                            this.submit_button_flag = false;
                            if (error.response && error.response.data.errors) {
                                this.formErrors = error.response.data.errors;
                            } else {
                                Vue.$toast.error('An error occurred while processing the payment.', 'Error');
                            }
                        });
                },
                showEditSupplierModal(data) {
                    if (data.id > 0) {
                        app.supplier_name = data.supplier_name;
                        app.ar_supplier_name = data.ar_supplier_name;
                        app.commercial_reg_no = data.commercial_reg_no;
                        app.vat_reg_no = data.vat_reg_no;
                        app.email = data.email;
                        app.phone = data.phone;
                        app.country_name = data.country_name;
                        app.city = data.city;
                        app.legal_form = data.legal_form;
                        app.remarks = data.remarks;
                        app.address = data.address;
                        app.status = data.status;
                        app.opening_balance = data.balance
                        app.id = data.id;
                        const balanceAmount = parseFloat(data.balance);
                        app.open_balance = isNaN(balanceAmount) ? 0 : balanceAmount;
                        app.formSubmitted = false;
                        app.formErrors = {};

                        app.modelHeading = "Edit Supplier";
                        app.modelBtn = "Update";

                        $('#add_supplier').modal('show');

                    } else {
                        app.resetForm();
                        app.modelHeading = "Add Supplier";
                        app.modelBtn = "Create";
                        app.open_balance = 0;
                        $('#add_supplier').modal('show');
                    }

                },
                resetForm() {
                    app.supplier_name = '';
                    app.ar_supplier_name = '';
                    app.commercial_reg_no = '';
                    app.vat_reg_no = '';
                    app.email = '';
                    app.phone = '';
                    app.country_name = '{{ config('constants.UAE') }}';
                    app.city = '',
                        app.legal_form = '';
                    app.remarks = '';
                    app.address = '';
                    app.status = 1;
                    app.id = '';
                    app.opening_balance = '';
                    app.open_balance = 0;
                    app.formSubmitted = false;
                    app.formErrors = {};

                    if (this.errors && this.errors.clear) {
                        this.errors.clear();
                    }

                },
                validateBeforeSubmit() {
                    this.$validator.validateAll();
                    // if (!this.phone) {
                    //     this.formErrors['phone'] = "Phone is required.";
                    //     return;
                    // }

                    // if (this.phone.toString().length !== 10) {
                    //     this.formErrors['phone'] = "Phone number must be exactly 10 digits.";
                    //     return;
                    // }
                    console.log(this.errors);
                    if (!this.errors.any()) {
                        this.createSupplier();
                    }
                },
                createSupplier() {
                    const formData = new FormData();

                    formData.append("supplier_name", app.supplier_name);
                    formData.append("ar_supplier_name", app.ar_supplier_name);
                    formData.append("commercial_reg_no", app.commercial_reg_no);
                    formData.append("vat_reg_no", app.vat_reg_no);
                    formData.append("email", app.email);
                    formData.append("phone", app.phone);
                    formData.append("country_name", app.country_name);
                    formData.append("city", app.city);
                    formData.append("legal_form", app.legal_form);
                    formData.append("remarks", app.remarks);
                    formData.append("address", app.address);
                    formData.append("status", app.status);
                    formData.append("opening_balance", app.opening_balance);
                    formData.append("id", app.id);
                    formData.append("user_id", this.user_id);

                    app.submit_button_flag = true;

                    if (app.id > 0) {
                        formData.append("_method", "put");
                        formData.append("id", app.id);

                        axios.post("/api/supplier/update", formData)
                            .then(response => {
                                app.showAPIResponse(response);
                            })
                            .catch(error => {
                                app.submit_button_flag = false;
                                if (error.response && error.response.data) {
                                    Vue.$toast.error(error.response.data.message, 'Error Alert');
                                    app.formErrors = error.response.data.errors || {};
                                } else {
                                    Vue.$toast.error('Something went wrong.', 'Error Alert');
                                }
                            });

                    } else {
                        axios.post("/api/supplier/store", formData)
                            .then(response => {
                                app.showAPIResponse(response);
                            })
                            .catch(error => {
                                app.submit_button_flag = false;
                                if (error.response && error.response.data) {
                                    Vue.$toast.error(error.response.data.message, 'Error Alert');
                                    app.formErrors = error.response.data.errors || {};
                                } else {
                                    Vue.$toast.error('Something went wrong.', 'Error Alert');
                                }
                            });
                    }
                },

                showAPIResponse(response) {
                    if (response.data.code == 0) {

                        Vue.$toast.success(response.data.message, 'Success Alert');
                        app.submit_button_flag = false;
                        app.formSubmitted = true;
                        app.formErrors = {};

                        this.getSupplierList();
                        app.resetForm();

                        $('#add_supplier').modal('hide');
                        $('#add_payment').modal('hide');
                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        app.submit_button_flag = false;
                    }
                    app.submit_button_flag = false;
                },

                getCurrentDate() {
                    const today = new Date();
                    const year = today.getFullYear();
                    const month = String(today.getMonth() + 1).padStart(2, '0');
                    const day = String(today.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                },
                showPayModal(data) {
                    this.resetPaymentForm();
                    this.modelHeading = 'Add Payment';
                    this.modelBtn = 'Pay';
                    this.credit_amount = data.balance;
                    this.credit_balance_amount = data.balance;
                    this.supplier_id = data.id;
                    this.payment_date = this.getCurrentDate();
                    $('#add_payment').modal('show');
                },

                deleteItem(data) {
                    Swal.fire({
                        title: "Are you sure you want to remove " + data.supplier_code + "?",
                        text: "You won't be able to revert this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, delete it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/delete/supplier", {
                                    data: {
                                        id: data.id,
                                        user_id: this.user_id
                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.error("Error deleting tag:", error);
                                });
                        }
                    });
                },

                getSupplierList: function(page = 1) {
                    const url = "/api/supplier/list";
                    axios.get(url, {
                        params: {
                            page: page,
                            page_length: this.page_length,
                            search: this.search,
                        }
                    }).then(response => {
                        app.supplierList = response.data.data.suppliers;
                        app.pagination = response.data.data.pagination;
                        app.loading = false;
                    });
                },

                getLoggedUserInfo() {
                    const userJson = window.localStorage.getItem('user');
                    if (userJson) {
                        const user = JSON.parse(userJson);
                        this.logged_user_id = user.data.id;
                        this.token = user.data.token;
                        $('#user_name').text(user.data.name || 'Unknown');
                        if (user.data.user_type === 'superadmin') {
                            $('#user_type').text('Super Admin');
                        }
                        const userImage = user.data.user_image;
                        const defaultImg = 'https://cdn-icons-png.freepik.com/512/9703/9703596.png';
                        $('#user_image').attr('src', userImage ? this.base_url + '/storage/' + userImage :
                            defaultImg);
                        $('#user_image_header').attr('src', userImage ? this.base_url + '/storage/' + userImage :
                            defaultImg);
                    }
                },

                resetPaymentForm() {
                    // this.credit_amount = '';
                    this.credit_payment_mode = '';
                    this.description = '';
                    this.payment_date = this.getCurrentDate();

                    this.formErrors = {};
                    this.submit_button_flag = false;
                    this.formSubmitted = false;
                },
            }
        });
    </script>
@endsection
