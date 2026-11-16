@extends('admin.layout.base')

@section('main-content')
    <style>
        .swal2-container {
            z-index: 99999 !important;
        }

        .invalid-feedback {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .is-invalid {
            border-color: #dc3545 !important;
        }

        .pagination {
            margin-top: 1rem;
        }

        .pagination .page-item.active .page-link {
            color: #fff;
            background-color: #c2185b;
            border-color: #c2185b;
        }
    </style>

    <div id="app">
        <div class="page-wrapper">
            <div class="content">
                <a href="{{ route('customer.index') }}" class="btn btn-outline-primary mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="icon icon-tabler icons-tabler-outline icon-tabler-chevrons-left">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M11 7l-5 5l5 5" />
                        <path d="M17 7l-5 5l5 5" />
                    </svg>Back
                </a>
                <div class="row">
                    <div class="col-lg-5">
                        <div class="card pt-3">
                            <div class="card-body pb-3">
                                <div class="d-flex w-100 justify-content-between">
                                    <h4>{{ $customer->customer_name ?? '' }}</h4>
                                    <h4 class="fw-semibold">{{ $customer->customer_name_ar ?? '' }}</h4>

                                </div>
                                <p class="mb-2">{{ $customer->room_number }}</p>
                                <p class="mb-2">{{ $customer->phone_number }}</p>
                                <p class="mb-2">{{ $customer->room_number }}<br>{{ $customer->address }}</p>
                                <p class="mb-2">Country: {{ $customer->country }}</p>
                                <p class="mb-2">City: {{ $customer->city }}</p>
                                <p class="mb-2">National Address No: {{ $customer->national_address_no }}</p>

                                <div class="row mt-4">
                                    <div class="col-lg-12">
                                        <div class="campaign-box bg-purple-light mb-0"
                                            style="border-bottom-right-radius: 0px; border-bottom-left-radius: 0px;">
                                            <div class="campaign-img">
                                                <span><i class="ti ti-coin"></i></span>
                                                <p>Credit Balance</p>
                                            </div>
                                            <h2><span
                                                    style="font-size: 12px; background-color: transparent;">SR</span>@{{ credit_balance_amount }}
                                            </h2>
                                        </div>
                                        <div class="campaign-box bg-purple-light pt-0"
                                            style="border-top-right-radius: 0px; border-top-left-radius: 0px;">
                                            <a href="javascript:void(0);" v-if="credit_balance_amount > 0"
                                                v-on:click="showPayModal()" class="btn btn-success ms-auto"><i
                                                    class="ti ti-square-rounded-plus me-2 ms-auto"></i>Pay</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="card">
                            <div class="card-body pt-4">
                                <div class="mb-4 d-flex w-100 justify-content-between align-items-center">
                                    <h4>Payment History</h4>
                                    <a href="{{ route('customer.transaction.pdf', $customer_id) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="ti ti-file-type-pdf me-1"></i>Export PDF
                                    </a>
                                </div>
                                <ul class="nav nav-tabs nav-tabs-bottom mb-3" role="tablist">
                                    <li class="nav-item" role="presentation"><a class="nav-link active"
                                            href="#bottom-justified-tab0" data-bs-toggle="tab" aria-selected="true"
                                            role="tab">Special Price</a></li>
                                    <li class="nav-item" role="presentation"><a class="nav-link"
                                            href="#bottom-justified-tab1" data-bs-toggle="tab" aria-selected="true"
                                            role="tab">Order</a></li>
                                    <li class="nav-item" role="presentation"><a class="nav-link"
                                            href="#bottom-justified-tab2" data-bs-toggle="tab" aria-selected="false"
                                            role="tab">Payment Details</a></li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane active show" id="bottom-justified-tab0" role="tabpanel">
                                        <div class="table-responsive custom-table">
                                            <div id="roles_list_wrapper" class="dataTables_wrapper dt-bootstrap5 no-footer">
                                                <div class="row dt-row">
                                                    <div class="col-sm-12 table-responsive">
                                                        <table class="table">
                                                            <thead class="thead-light">
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Product</th>
                                                                    <th>Price</th>
                                                                    <th>Status</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr
                                                                    v-if="!customerAssignedSpecialPriceList.length && !loading">
                                                                    <td colspan="4" class="text-center">No special prices
                                                                        found.</td>
                                                                </tr>
                                                                <tr v-if="loading">
                                                                    <td colspan="4" class="text-center">Loading...</td>
                                                                </tr>
                                                                <template
                                                                    v-for="(price, index) in customerAssignedSpecialPriceList"
                                                                    :key="price.id">
                                                                    <tr>
                                                                        <td>@{{ index + 1 + ((specialPricePagination.current_page - 1) * specialPricePagination.per_page) }}</td>
                                                                        <td>@{{ price.product ? price.product.product_code + ' [' + price.product.product_name + ']' : 'N/A' }}</td>
                                                                        <td>@{{ price.special_price }}</td>
                                                                        <td>
                                                                            <span
                                                                                :class="price.status === '1' ?
                                                                                    'badge bg-danger' :
                                                                                    'badge bg-success'">
                                                                                <p v-if="price.status === '1'">Active</p>
                                                                                <p v-else>Inactive</p>
                                                                            </span>
                                                                        </td>
                                                                    </tr>
                                                                </template>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <nav aria-label="Special Price Pagination" class="mt-3"
                                                    v-if="specialPricePagination.total > specialPricePagination.per_page">
                                                    <ul class="pagination justify-content-end">
                                                        <li class="page-item"
                                                            :class="{ disabled: specialPricePagination.current_page <= 1 }">
                                                            <a class="page-link" href="#" aria-label="Previous"
                                                                @click.prevent="changeSpecialPricePage(specialPricePagination.current_page - 1)">
                                                                <span aria-hidden="true">«</span>
                                                            </a>
                                                        </li>
                                                        <li class="page-item" v-for="page in specialPricePagesNumber"
                                                            :key="page"
                                                            :class="{ active: page === specialPricePagination.current_page }">
                                                            <a class="page-link" href="#"
                                                                @click.prevent="changeSpecialPricePage(page)">@{{ page }}</a>
                                                        </li>
                                                        <li class="page-item"
                                                            :class="{
                                                                disabled: specialPricePagination.current_page >=
                                                                    specialPricePagination.last_page
                                                            }">
                                                            <a class="page-link" href="#" aria-label="Next"
                                                                @click.prevent="changeSpecialPricePage(specialPricePagination.current_page + 1)">
                                                                <span aria-hidden="true">»</span>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </nav>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="bottom-justified-tab1" role="tabpanel">
                                        <div class="table-responsive custom-table">
                                            <div id="roles_list_wrapper"
                                                class="dataTables_wrapper dt-bootstrap5 no-footer">
                                                <div class="row dt-row">
                                                    <div class="col-sm-12 table-responsive">
                                                        <table class="table">
                                                            <thead class="thead-light">
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>No</th>
                                                                    <th>Qty</th>
                                                                    <th>Total</th>
                                                                    <th>Paid Amount</th>
                                                                    <th>Balance</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr v-if="!customerOrderList.length && !loading">
                                                                    <td colspan="6" class="text-center">No orders
                                                                        found.</td>
                                                                </tr>
                                                                <tr v-if="loading">
                                                                    <td colspan="6" class="text-center">Loading...</td>
                                                                </tr>
                                                                <template v-for="(order, index) in customerOrderList"
                                                                    :key="order.id">
                                                                    <tr>
                                                                        <td>@{{ index + 1 + ((ordersPagination.current_page - 1) * ordersPagination.per_page) }}</td>
                                                                        <td>@{{ order.order_no }}</td>
                                                                        <td>@{{ order.total_quantity }}</td>
                                                                        <td>@{{ order.total_amount }}</td>
                                                                        <td>@{{ order.paid_amount }}</td>
                                                                        <td>@{{ order.balance_amount }}</td>
                                                                    </tr>
                                                                </template>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <nav aria-label="Order Pagination" class="mt-3"
                                                    v-if="ordersPagination.total > ordersPagination.per_page">
                                                    <ul class="pagination justify-content-end">
                                                        <li class="page-item"
                                                            :class="{ disabled: ordersPagination.current_page <= 1 }">
                                                            <a class="page-link" href="#" aria-label="Previous"
                                                                @click.prevent="changeOrdersPage(ordersPagination.current_page - 1)">
                                                                <span aria-hidden="true">«</span>
                                                            </a>
                                                        </li>
                                                        <li class="page-item" v-for="page in ordersPagesNumber"
                                                            :key="page"
                                                            :class="{ active: page === ordersPagination.current_page }">
                                                            <a class="page-link" href="#"
                                                                @click.prevent="changeOrdersPage(page)">@{{ page }}</a>
                                                        </li>
                                                        <li class="page-item"
                                                            :class="{
                                                                disabled: ordersPagination.current_page >=
                                                                    ordersPagination.last_page
                                                            }">
                                                            <a class="page-link" href="#" aria-label="Next"
                                                                @click.prevent="changeOrdersPage(ordersPagination.current_page + 1)">
                                                                <span aria-hidden="true">»</span>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </nav>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="bottom-justified-tab2" role="tabpanel">
                                        <div class="table-responsive custom-table">
                                            <div id="roles_list_wrapper"
                                                class="dataTables_wrapper dt-bootstrap5 no-footer">
                                                <div class="row dt-row">
                                                    <div class="col-sm-12 table-responsive">
                                                        <table class="table">
                                                            <thead class="thead-light">
                                                                <tr>
                                                                    <th>#</th>
                                                                    {{-- <th>Order No</th> --}}
                                                                    <th>Paid Amount</th>
                                                                    <th>Payment Mode</th>
                                                                    {{-- <th>Total Amount</th> --}}
                                                                    {{-- <th>Balance Amount</th> --}}
                                                                    <th>Collected By</th>
                                                                    <th>Date</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr v-if="!customerPaymentList.length && !loading">
                                                                    <td colspan="8" class="text-center">No payment
                                                                        details
                                                                        found.</td>
                                                                </tr>
                                                                <tr v-if="loading">
                                                                    <td colspan="8" class="text-center">Loading...</td>
                                                                </tr>
                                                                <template v-for="(payment, index) in customerPaymentList"
                                                                    :key="payment.id">
                                                                    <tr>
                                                                        <td>@{{ index + 1 + ((paymentsPagination.current_page - 1) * paymentsPagination.per_page) }}</td>
                                                                        {{-- <td>@{{ payment.order ? payment.order.order_no : 'N/A' }}</td> --}}
                                                                        <td>@{{ formatCurrency(payment.paid_amount) }}</td>
                                                                        <td>@{{ payment.payment_mode }}</td>
                                                                        {{-- <td>@{{ formatCurrency(payment.total_amount) }}</td> --}}
                                                                        {{-- <td>@{{ formatCurrency(payment.balance_amount) }}</td> --}}
                                                                        <td>@{{ payment.user ? payment.user.name : 'N/A' }}</td>
                                                                        <td>@{{ formatDate(payment.payment_date) }}</td>
                                                                    </tr>
                                                                </template>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <nav aria-label="Payment Pagination" class="mt-3"
                                                    v-if="paymentsPagination.total > paymentsPagination.per_page">
                                                    <ul class="pagination justify-content-end">
                                                        <li class="page-item"
                                                            :class="{ disabled: paymentsPagination.current_page <= 1 }">
                                                            <a class="page-link" href="#" aria-label="Previous"
                                                                @click.prevent="changePaymentsPage(paymentsPagination.current_page - 1)">
                                                                <span aria-hidden="true">«</span>
                                                            </a>
                                                        </li>
                                                        <li class="page-item" v-for="page in paymentsPagesNumber"
                                                            :key="page"
                                                            :class="{ active: page === paymentsPagination.current_page }">
                                                            <a class="page-link" href="#"
                                                                @click.prevent="changePaymentsPage(page)">@{{ page }}</a>
                                                        </li>
                                                        <li class="page-item"
                                                            :class="{
                                                                disabled: paymentsPagination.current_page >=
                                                                    paymentsPagination.last_page
                                                            }">
                                                            <a class="page-link" href="#" aria-label="Next"
                                                                @click.prevent="changePaymentsPage(paymentsPagination.current_page + 1)">
                                                                <span aria-hidden="true">»</span>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </nav>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
    </div>
@endsection

@section('script')
    <script>
        Vue.use(window["vue-js-modal"].default);
        var app = new Vue({
            el: '#app',
            data: {
                loading: false,
                base_url: "{{ url('/') }}",
                customerDetailsList: [],
                customerAssignedSpecialPriceList: [],
                customerOrderList: [],
                customerPaymentList: [],
                productList: [],
                assignedSpecialPriceList: [],

                redeemCreditList: [],
                selected_product: '',
                amount: '',
                payment_method: '',
                filter_start_date: '',
                filter_end_date: '',

                modelBtn: 'Pay',
                modelHeading: 'Add Payment',
                formSubmitted: false,
                submit_button_flag: false,
                credit_payment_mode: '',
                credit_amount: '',
                credit_balance_amount: '',
                payment_date: '',

                search: '',
                page_length: 10,
                specialPricePagination: {
                    total: 0,
                    per_page: 10,
                    from: 1,
                    to: 0,
                    current_page: 1,
                    last_page: 0
                },
                ordersPagination: {
                    total: 0,
                    per_page: 10,
                    from: 1,
                    to: 0,
                    current_page: 1,
                    last_page: 0
                },
                paymentsPagination: {
                    total: 0,
                    per_page: 10,
                    from: 1,
                    to: 0,
                    current_page: 1,
                    last_page: 0
                },
                offset: 4,
                formErrors: {},
                customer_id: @json($customer_id ?? null),
                logged_user_id: null,
                token: null
            },
            mounted() {
                this.getLoggedUserInfo();
                if (this.customer_id) {
                    this.getCustomerDetailsList();
                } else {
                    Vue.$toast.error('Customer ID is missing', 'Error');
                    this.customerOrderList = [];
                    this.customerAssignedSpecialPriceList = [];
                }
                $('#add_payment').on('hidden.bs.modal', () => {
                    this.resetPaymentForm();
                });
            },
            computed: {
                specialPricePagesNumber() {
                    if (!this.specialPricePagination.total) {
                        return [];
                    }

                    let from = this.specialPricePagination.current_page - this.offset;
                    if (from < 1) {
                        from = 1;
                    }
                    let to = from + (this.offset * 2);
                    if (to > this.specialPricePagination.last_page) {
                        to = this.specialPricePagination.last_page;
                    }
                    let pagesArray = [];
                    for (let page = from; page <= to; page++) {
                        pagesArray.push(page);
                    }
                    return pagesArray;
                },
                ordersPagesNumber() {
                    if (!this.ordersPagination.total) {
                        return [];
                    }
                    let from = this.ordersPagination.current_page - this.offset;
                    if (from < 1) {
                        from = 1;
                    }
                    let to = from + (this.offset * 2);
                    if (to > this.ordersPagination.last_page) {
                        to = this.ordersPagination.last_page;
                    }
                    let pagesArray = [];
                    for (let page = from; page <= to; page++) {
                        pagesArray.push(page);
                    }
                    return pagesArray;
                },
                paymentsPagesNumber() {
                    if (!this.paymentsPagination.total) {
                        return [];
                    }
                    let from = this.paymentsPagination.current_page - this.offset;
                    if (from < 1) {
                        from = 1;
                    }
                    let to = from + (this.offset * 2);
                    if (to > this.paymentsPagination.last_page) {
                        to = this.paymentsPagination.last_page;
                    }
                    let pagesArray = [];
                    for (let page = from; page <= to; page++) {
                        pagesArray.push(page);
                    }
                    return pagesArray;
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
                            `Please enter a valid amount less than ${this.credit_balance_amount}`;
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
                        this.creditPayment();
                    } else {
                        this.submit_button_flag = false;
                    }
                },
                creditPayment() {
                    const formData = new FormData();
                    formData.append('customer_id', this.customer_id);
                    formData.append('user_id', this.logged_user_id);
                    formData.append('amount', this.credit_amount);
                    formData.append('payment_mode', this.credit_payment_mode);
                    formData.append('date', this.payment_date);

                    this.submit_button_flag = true;

                    axios.post('/api/redeem-credit', formData, {
                            headers: {
                                'Authorization': `Bearer ${this.token}`
                            }
                        })
                        .then(response => {
                            this.showAPIResponse(response);
                            this.getCustomerDetailsList();
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
                showAPIResponse(response) {
                    if (response.data.code === 0) {
                        Vue.$toast.success(response.data.message, 'Success');
                        this.submit_button_flag = false;
                        this.formSubmitted = true;
                        this.formErrors = {};
                        this.resetPaymentForm();
                        $('#add_payment').modal('hide');
                    } else {
                        Vue.$toast.error(response.data.message, 'Error');
                        this.submit_button_flag = false;
                    }
                },

                getCustomerDetailsList() {
                    this.loading = true;
                    axios.get('/api/customer-details/list', {
                        params: {
                            customer_id: this.customer_id,
                            orders_page: this.ordersPagination.current_page,
                            special_price_page: this.specialPricePagination.current_page,
                            payments_page: this.paymentsPagination.current_page,
                            per_page: this.page_length
                        },
                        headers: {
                            'Authorization': `Bearer ${this.token}`
                        }
                    }).then(response => {
                        this.credit_amount = response.data.data.customer.balance_amount;
                        this.credit_balance_amount = response.data.data.customer.balance_amount;
                        this.customerDetailsList = response.data.data.customer;
                        this.customerAssignedSpecialPriceList = response.data.data.assigned_special_price ||
                            [];
                        this.customerOrderList = response.data.data.customer_orders || [];
                        this.customerPaymentList = response.data.data.customer_payments || [];
                        this.specialPricePagination = response.data.data.special_price_pagination || {
                            total: 0,
                            per_page: this.page_length,
                            current_page: 1,
                            last_page: 0,
                            from: 1,
                            to: 0
                        };
                        this.ordersPagination = response.data.data.orders_pagination || {
                            total: 0,
                            per_page: this.page_length,
                            current_page: 1,
                            last_page: 0,
                            from: 1,
                            to: 0
                        };
                        this.paymentsPagination = response.data.data.payments_pagination || {
                            total: 0,
                            per_page: this.page_length,
                            current_page: 1,
                            last_page: 0,
                            from: 1,
                            to: 0
                        };
                        this.loading = false;
                    }).catch(error => {
                        Vue.$toast.error('Failed to load customer details', 'Error');
                        this.customerOrderList = [];
                        this.customerAssignedSpecialPriceList = [];
                        this.customerPaymentList = [];
                        this.specialPricePagination.total = 0;
                        this.ordersPagination.total = 0;
                        this.paymentsPagination.total = 0;
                        this.loading = false;
                    });
                },
                changeSpecialPricePage(page) {
                    if (page >= 1 && page <= this.specialPricePagination.last_page) {
                        this.specialPricePagination.current_page = page;
                        this.getCustomerDetailsList();
                    }
                },
                changeOrdersPage(page) {
                    if (page >= 1 && page <= this.ordersPagination.last_page) {
                        this.ordersPagination.current_page = page;
                        this.getCustomerDetailsList();
                    }
                },
                changePaymentsPage(page) {
                    if (page >= 1 && page <= this.paymentsPagination.last_page) {
                        this.paymentsPagination.current_page = page;
                        this.getCustomerDetailsList();
                    }
                },
                getProductList() {
                    axios.get('/api/product/list', {
                            params: {
                                platform: 'api'
                            },
                            headers: {
                                'Authorization': `Bearer ${this.token}`
                            }
                        })
                        .then(response => {
                            this.productList = response.data.data.products || [];
                        })
                        .catch(error => {
                            console.error('Error fetching product list:', error);
                            Vue.$toast.error('Failed to load product list', 'Error');
                        });
                },
                getCurrentDate() {
                    const today = new Date();
                    const year = today.getFullYear();
                    const month = String(today.getMonth() + 1).padStart(2, '0');
                    const day = String(today.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                },
                showPayModal() {
                    this.resetPaymentForm();
                    this.modelHeading = 'Add Payment';
                    this.modelBtn = 'Pay';
                    this.credit_amount = this.credit_balance_amount;
                    this.payment_date = this.getCurrentDate();
                    $('#add_payment').modal('show');
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
                resetForm() {
                    this.product_id = '';
                    this.amount = '';
                    this.payment_method = '';
                    this.description = '';
                    this.formSubmitted = false;
                    this.formErrors = {};
                },
                resetPaymentForm() {
                    // this.credit_amount = '';
                    this.credit_payment_mode = '';
                    this.payment_date = this.getCurrentDate();
                    this.formErrors = {};
                    this.submit_button_flag = false;
                    this.formSubmitted = false;
                },
                formatCurrency(value) {
                    if (!value) return '0.00';
                    return parseFloat(value).toFixed(2);
                },
                formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    const date = new Date(dateString);
                    // return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                    return date.toLocaleDateString();
                }
            }
        });
    </script>
@endsection
