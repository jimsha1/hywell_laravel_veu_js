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
                                    <h4 class="page-title">Order Payments</h4>
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
                                <div class="row align-items-center">
                                    <div class="col-sm-4">
                                        <div class="icon-form mb-3 mb-sm-0">
                                            <!-- Fake input to stop browser autofill -->
                                            <input type="text" style="display:none" autocomplete="username">
                                            <input type="password" style="display:none" autocomplete="new-password">

                                            <span class="form-icon"><i class="ti ti-search"></i></span>
                                            <input type="text" class="form-control" placeholder="Search" v-model="search"
                                                @input="getOrderList" autocomplete="off" autocorrect="off"
                                                autocapitalize="off" spellcheck="false" autocomplete="new-password">
                                        </div>
                                    </div>
                                </div>
                                <!-- /Search Dropdowns -->
                            </div>
                            <div class="card-body">
                                <!-- Order List -->
                                <div class="table-responsive custom-table">
                                    <table class="table" id="order_list_table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Order No</th>
                                                <th>Order Date</th>
                                                <th>Customer</th>
                                                <th>Total Amount</th>
                                                <th>Paid Amount</th>
                                                <th>Balance</th>
                                                <th>Payment Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!orderList || !orderList.length">
                                                <tr>
                                                    <td colspan="9" class="text-center">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(order, index) in orderList" :key="order.id">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    <td>@{{ order.order_no }}
                                                        {{-- <a href="javascript:void(0);" @click="selectOrder(order)"
                                                            style="color: blue; cursor: pointer;">
                                                            @{{ order.order_no }}
                                                        </a> --}}
                                                    </td>
                                                    <td>@{{ formatDate(order.order_date) }}</td>
                                                    <td>@{{ order.customer ? order.customer.customer_name : 'N/A' }}</td>
                                                    <td>@{{ formatCurrency(order.total_amount) }}</td>
                                                    <td>@{{ formatCurrency(order.paid_amount) }}</td>
                                                    <td>@{{ formatCurrency(order.balance_amount) }}</td>
                                                    <td>
                                                        <span :class="getPaymentStatusClass(order.payment_status)">
                                                            @{{ order.payment_status }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" @click="selectOrder(order)">
                                                            <i class="ti ti-edit"></i> Edit Payment
                                                        </button>
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

        <!-- Password Verification Modal -->
        <div class="modal fade" id="passwordModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Password Verification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label">Enter your password to continue <span
                                    class="text-danger">*</span></label>
                            <input type="password" class="form-control" v-model="password" placeholder="Enter password"
                                @keyup.enter="verifyPassword">
                            <div v-if="passwordError" class="text-danger mt-2">@{{ passwordError }}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="verifyPassword" :disabled="verifying">
                            <span v-if="verifying">Verifying...</span>
                            <span v-else>Verify</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Details and Payment Edit Modal -->
        <div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Order Details - @{{ selectedOrder ? selectedOrder.order_no : '' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <template v-if="selectedOrder">
                            <!-- Order Items -->
                            <div class="mb-4">
                                <h6 class="mb-3">Order Items</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Quantity</th>
                                                <th>Free Qty</th>
                                                <th>Price</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in selectedOrder.order_items" :key="item.id">
                                                <td>@{{ item.product ? item.product.product_name : 'N/A' }}</td>
                                                <td>@{{ item.quantity }}</td>
                                                <td>@{{ item.free_quantity || 0 }}</td>
                                                <td>@{{ formatCurrency(item.price) }}</td>
                                                <td>@{{ formatCurrency(item.total) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="4" class="text-end">Total Amount:</th>
                                                <th>@{{ formatCurrency(selectedOrder.total_amount) }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Order Payments -->
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Order Payments</h6>
                                    <button class="btn btn-sm btn-primary" @click="editAllPayments">
                                        <i class="ti ti-edit"></i> Edit Payments
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Payment Date</th>
                                                <th>Payment Mode</th>
                                                <th>Paid Amount</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="payment in selectedOrder.payments" :key="payment.id">
                                                <td>@{{ formatDate(payment.payment_date) }}</td>
                                                <td>@{{ payment.payment_mode }}</td>
                                                <td>@{{ formatCurrency(payment.paid_amount) }}</td>
                                                <td>@{{ payment.description || '-' }}</td>
                                            </tr>
                                            <tr v-if="!selectedOrder.payments || selectedOrder.payments.length === 0">
                                                <td colspan="4" class="text-center">No payments found</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="2" class="text-end">Total Paid:</th>
                                                <th>@{{ formatCurrency(getTotalPaidAmount()) }}</th>
                                                <th></th>
                                            </tr>
                                            <tr>
                                                <th colspan="2" class="text-end">Balance:</th>
                                                <th>@{{ formatCurrency(selectedOrder.total_amount - getTotalPaidAmount()) }}</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit All Payments Modal -->
        <div class="modal fade" id="editAllPaymentsModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit All Payments - @{{ selectedOrder ? selectedOrder.order_no : '' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Payment Date</th>
                                        <th>Payment Mode</th>
                                        <th>Paid Amount</th>
                                        <th>Collected By</th>
                                        <th>Description</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(payment, index) in editingPayments" :key="payment.temp_id || payment.id">
                                        <td>
                                            <input type="date" class="form-control form-control-sm"
                                                v-model="payment.payment_date" required>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" v-model="payment.payment_mode"
                                                required>
                                                <option value="">Select Payment Mode</option>
                                                <option value="{{ config('constants.CASH') }}">Cash</option>
                                                <option value="{{ config('constants.BANK_TRANSFER_ALRAJHI') }}">TRANSFER
                                                    ALRAJHI</option>
                                                <option value="{{ config('constants.BANK_TRANSFER_SAB') }}">TRANSFER
                                                    SAB</option>
                                                <option value="{{ config('constants.POS_ALRAJHIS') }}">POS ALRAJHI</option>
                                                <option value="{{ config('constants.POS_SAB') }}">POS SAB</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm"
                                                v-model="payment.paid_amount" step="0.01" min="0"
                                                :max="getMaxPaymentAmount(index)" required
                                                @input="validatePaymentAmount(index)">
                                            <small v-if="getPaymentAmountError(index)" class="text-danger">
                                                @{{ getPaymentAmountError(index) }}
                                            </small>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" v-model="payment.user_id" required>
                                                <option value="">Select Employee</option>
                                                <option v-for="employee in employeesList" :key="employee.id"
                                                    :value="employee.id">
                                                    @{{ employee.name }} (@{{ getEmployeeType(employee.user_type) }})
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm"
                                                v-model="payment.description" placeholder="Description">
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-danger" @click="removePayment(index)"
                                                v-if="editingPayments.length > 1 || payment.temp_id">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6">
                                            <button class="btn btn-sm btn-primary" @click="addNewPayment">
                                                <i class="ti ti-plus"></i> Add New Payment
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="text-end">Total Paid:</th>
                                        <th>@{{ formatCurrency(getEditingTotalPaid()) }}</th>
                                        <th colspan="3"></th>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="text-end">Order Total:</th>
                                        <th>@{{ formatCurrency(selectedOrder ? selectedOrder.total_amount : 0) }}</th>
                                        <th colspan="3"></th>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="text-end">Balance:</th>
                                        <th :class="getBalanceClass()">
                                            @{{ formatCurrency(getEditingBalance()) }}
                                        </th>
                                        <th colspan="3"></th>
                                    </tr>
                                    <tr v-if="getEditingTotalPaid() > (selectedOrder ? selectedOrder.total_amount : 0)">
                                        <td colspan="6" class="text-center text-danger">
                                            <strong>Warning: Total paid amount exceeds order total!</strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="form-group mt-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" v-model="editPassword"
                                placeholder="Enter password to confirm" required>
                            <div v-if="editPasswordError" class="text-danger mt-2">@{{ editPasswordError }}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="updateAllPayments"
                            :disabled="updating">
                            <span v-if="updating">Updating...</span>
                            <span v-else>Update Payments</span>
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
                orderList: [],
                selectedOrder: null,
                search: '',
                page_length: '10',
                password: '',
                passwordError: '',
                verifying: false,
                editingPayments: [],
                editPassword: '',
                editPasswordError: '',
                updating: false,
                logged_user_id: '',
                tempIdCounter: 0,
                employeesList: [],
                pagination: {
                    total: 0,
                    per_page: 10,
                    from: 1,
                    to: 0,
                    current_page: 1,
                    last_page: 1
                },
                offset: 4,
            },
            computed: {
                isActived: function() {
                    return this.pagination.current_page;
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
            },
            methods: {
                getOrderList: function(page = 1) {
                    this.loading = true;
                    const url = "/api/orders-for-payment-edit";
                    axios.get(url, {
                        params: {
                            page: page,
                            page_length: this.page_length,
                            search: this.search,
                            platform: 'web'
                        }
                    }).then((response) => {
                        if (response.data.code === 0) {
                            this.orderList = response.data.data.orders || [];
                            this.pagination = response.data.data.pagination || this.pagination;
                        }
                        this.loading = false;
                    }).catch((error) => {
                        console.error('Error loading orders:', error);
                        Vue.$toast.error('Failed to load orders.', 'Error');
                        this.loading = false;
                    });
                },
                changePage: function(page) {
                    this.getOrderList(page);
                },
                formatDate(value) {
                    if (!value) return '';
                    const date = new Date(value);
                    return date.toLocaleDateString();
                },
                formatCurrency(value) {
                    if (!value) return '0.00';
                    return parseFloat(value).toFixed(2);
                },
                getPaymentStatusClass(status) {
                    if (status === 'Paid') return 'badge bg-success';
                    if (status === 'Partial') return 'badge bg-warning';
                    return 'badge bg-danger';
                },
                getTotalPaidAmount() {
                    if (!this.selectedOrder || !this.selectedOrder.payments) return 0;
                    return this.selectedOrder.payments.reduce((sum, p) => sum + (parseFloat(p.paid_amount) || 0),
                        0);
                },
                getEditingTotalPaid() {
                    if (!this.editingPayments || this.editingPayments.length === 0) return 0;
                    return this.editingPayments.reduce((sum, p) => sum + (parseFloat(p.paid_amount) || 0), 0);
                },
                getEditingBalance() {
                    if (!this.selectedOrder) return 0;
                    return this.selectedOrder.total_amount - this.getEditingTotalPaid();
                },
                getBalanceClass() {
                    const balance = this.getEditingBalance();
                    if (balance < 0) return 'text-danger';
                    if (balance === 0) return 'text-success';
                    return 'text-warning';
                },
                getMaxPaymentAmount(index) {
                    if (!this.selectedOrder) return null;
                    const currentPayment = this.editingPayments[index];
                    const otherPaymentsTotal = this.editingPayments
                        .filter((p, i) => i !== index)
                        .reduce((sum, p) => sum + (parseFloat(p.paid_amount) || 0), 0);
                    return this.selectedOrder.total_amount - otherPaymentsTotal;
                },
                validatePaymentAmount(index) {
                    // This will trigger Vue reactivity to update the display
                    this.$forceUpdate();
                },
                getPaymentAmountError(index) {
                    if (!this.selectedOrder) return '';
                    const payment = this.editingPayments[index];
                    if (!payment || !payment.paid_amount) return '';

                    const otherPaymentsTotal = this.editingPayments
                        .filter((p, i) => i !== index)
                        .reduce((sum, p) => sum + (parseFloat(p.paid_amount) || 0), 0);
                    const maxAllowed = this.selectedOrder.total_amount - otherPaymentsTotal;

                    if (parseFloat(payment.paid_amount) > maxAllowed) {
                        return `Cannot exceed ${this.formatCurrency(maxAllowed)}`;
                    }
                    return '';
                },
                selectOrder(order) {
                    this.selectedOrder = order;
                    this.password = '';
                    this.passwordError = '';
                    $('#passwordModal').modal('show');
                },
                verifyPassword() {
                    if (!this.password) {
                        this.passwordError = 'Password is required';
                        return;
                    }

                    this.verifying = true;
                    this.passwordError = '';

                    axios.post('/api/verify-password-and-get-order', {
                        order_id: this.selectedOrder.id,
                        password: this.password,
                        user_id: this.logged_user_id
                    }).then((response) => {
                        if (response.data.code === 0) {
                            this.selectedOrder = response.data.data;
                            $('#passwordModal').modal('hide');
                            $('#orderDetailsModal').modal('show');
                        } else {
                            this.passwordError = response.data.message || 'Invalid password';
                        }
                        this.verifying = false;
                    }).catch((error) => {
                        this.passwordError = error.response?.data?.message || 'Invalid password';
                        this.verifying = false;
                    });
                },
                editAllPayments() {
                    // Initialize editing payments array with existing payments
                    this.editingPayments = [];
                    if (this.selectedOrder && this.selectedOrder.payments) {
                        this.selectedOrder.payments.forEach(payment => {
                            this.editingPayments.push({
                                id: payment.id,
                                payment_date: payment.payment_date ? payment.payment_date.split(
                                    ' ')[0] : this.getCurrentDate(),
                                payment_mode: payment.payment_mode,
                                paid_amount: payment.paid_amount,
                                user_id: payment.user_id || this.logged_user_id,
                                description: payment.description || ''
                            });
                        });
                    }

                    // If no payments exist, add one empty row
                    if (this.editingPayments.length === 0) {
                        this.addNewPayment();
                    }

                    this.editPassword = '';
                    this.editPasswordError = '';
                    $('#orderDetailsModal').modal('hide');
                    $('#editAllPaymentsModal').modal('show');
                },
                addNewPayment() {
                    this.tempIdCounter++;
                    this.editingPayments.push({
                        temp_id: 'new_' + this.tempIdCounter,
                        payment_date: this.getCurrentDate(),
                        payment_mode: '',
                        paid_amount: 0,
                        user_id: this.logged_user_id,
                        description: ''
                    });
                },
                removePayment(index) {
                    this.editingPayments.splice(index, 1);
                },
                getCurrentDate() {
                    const today = new Date();
                    const year = today.getFullYear();
                    const month = String(today.getMonth() + 1).padStart(2, '0');
                    const day = String(today.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                },
                updateAllPayments() {
                    if (!this.editPassword) {
                        this.editPasswordError = 'Password is required';
                        return;
                    }

                    // Validate all payments
                    let hasError = false;
                    let totalPaid = 0;

                    for (let i = 0; i < this.editingPayments.length; i++) {
                        const payment = this.editingPayments[i];
                        if (!payment.payment_mode) {
                            this.editPasswordError = `Payment mode is required for payment ${i + 1}`;
                            hasError = true;
                            break;
                        }
                        if (!payment.paid_amount || payment.paid_amount <= 0) {
                            this.editPasswordError = `Paid amount must be greater than 0 for payment ${i + 1}`;
                            hasError = true;
                            break;
                        }
                        if (!payment.payment_date) {
                            this.editPasswordError = `Payment date is required for payment ${i + 1}`;
                            hasError = true;
                            break;
                        }
                        if (!payment.user_id) {
                            this.editPasswordError = `Employee (Collected By) is required for payment ${i + 1}`;
                            hasError = true;
                            break;
                        }
                        totalPaid += parseFloat(payment.paid_amount) || 0;
                    }

                    if (hasError) {
                        return;
                    }

                    // Validate: Total paid cannot exceed order total
                    if (this.selectedOrder && totalPaid > this.selectedOrder.total_amount) {
                        this.editPasswordError =
                            `Total paid amount (${this.formatCurrency(totalPaid)}) cannot exceed order total amount (${this.formatCurrency(this.selectedOrder.total_amount)})`;
                        return;
                    }

                    this.updating = true;
                    this.editPasswordError = '';

                    axios.post('/api/update-all-order-payments', {
                        order_id: this.selectedOrder.id,
                        payments: this.editingPayments,
                        password: this.editPassword,
                        user_id: this.logged_user_id
                    }).then((response) => {
                        if (response.data.code === 0) {
                            Vue.$toast.success('Payments updated successfully.', 'Success');
                            $('#editAllPaymentsModal').modal('hide');
                            // Refresh order list
                            this.getOrderList(this.pagination.current_page);
                            // Reload order details
                            axios.get('/api/order-details-with-payments', {
                                params: {
                                    order_id: this.selectedOrder.id
                                }
                            }).then((res) => {
                                if (res.data.code === 0) {
                                    this.selectedOrder = res.data.data;
                                    // $('#orderDetailsModal').modal('show');
                                }
                            });
                        } else {
                            this.editPasswordError = response.data.message || 'Failed to update payments';
                        }
                        this.updating = false;
                    }).catch((error) => {
                        this.editPasswordError = error.response?.data?.message ||
                            'Failed to update payments';
                        this.updating = false;
                    });
                },
                getLoggedUserInfo() {
                    const userJson = window.localStorage.getItem('user');
                    if (userJson) {
                        const user = JSON.parse(userJson);
                        this.logged_user_id = user.data.id;
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
                            user_type: ['salesman', 'sales_executive', 'superadmin', 'admin']
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
                        'superadmin': 'Admin',
                        'admin': 'Admin'
                    };
                    return types[userType] || userType;
                },
            },
            mounted() {
                this.getOrderList();
                this.getLoggedUserInfo();
                this.getEmployeesList();
            }
        });
    </script>
@endsection
