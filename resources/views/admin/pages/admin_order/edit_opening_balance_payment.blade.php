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
                                    <h4 class="page-title">Opening Balance Payments</h4>
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
                                            <input type="text" class="form-control"
                                                placeholder="Search by customer name or code" v-model="search"
                                                @input="getOpeningBalancePayments" autocomplete="off" autocorrect="off"
                                                autocapitalize="off" spellcheck="false" autocomplete="new-password">
                                        </div>
                                    </div>
                                </div>
                                <!-- /Search Dropdowns -->
                            </div>
                            <div class="card-body">
                                <!-- Opening Balance Payments List -->
                                <div class="table-responsive custom-table">
                                    <table class="table" id="opening_balance_payments_table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Customer</th>
                                                <th>Customer Code</th>
                                                <th>Payment Date</th>
                                                <th>Paid Amount</th>
                                                <th>Payment Mode</th>
                                                <th>Collected By</th>
                                                {{-- <th>Description</th> --}}
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template
                                                v-if="!openingBalancePaymentsList || !openingBalancePaymentsList.length">
                                                <tr>
                                                    <td colspan="9" class="text-center">No opening balance payments found
                                                    </td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(payment, index) in openingBalancePaymentsList"
                                                :key="payment.id">
                                                <tr>
                                                    <td>@{{ index + 1 + ((openingBalancePagination.current_page - 1) * openingBalancePagination.per_page) }}</td>
                                                    <td>@{{ payment.customer ? payment.customer.customer_name : 'N/A' }}</td>
                                                    <td>@{{ payment.customer ? payment.customer.customer_code : 'N/A' }}</td>
                                                    <td>@{{ formatDate(payment.payment_date) }}</td>
                                                    <td>@{{ formatCurrency(payment.paid_amount) }}</td>
                                                    <td>@{{ payment.payment_mode || 'N/A' }}</td>
                                                    <td>@{{ payment.user ? payment.user.name : 'N/A' }}</td>
                                                    {{-- <td>@{{ payment.description || '-' }}</td> --}}
                                                    <td>
                                                        <button class="btn btn-sm btn-primary"
                                                            @click="selectOpeningBalancePayment(payment)">
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
                                <li class="page-item" v-if="openingBalancePagination.current_page > 1">
                                    <a class="page-link" href="#" aria-label="Previous"
                                        @click.prevent="changePage(openingBalancePagination.current_page - 1)">
                                        <span aria-hidden="true">«</span>
                                    </a>
                                </li>
                                <li class="page-item" v-for="page in pagesNumber" :key="page"
                                    :class="{ active: page === isActived }">
                                    <a class="page-link" href="#"
                                        @click.prevent="changePage(page)">@{{ page }}</a>
                                </li>
                                <li class="page-item"
                                    v-if="openingBalancePagination.current_page < openingBalancePagination.last_page">
                                    <a class="page-link" href="#" aria-label="Next"
                                        @click.prevent="changePage(openingBalancePagination.current_page + 1)">
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

        <!-- Edit Opening Balance Payment Modal -->
        <div class="modal fade" id="openingBalancePaymentModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Opening Balance Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" v-if="selectedOpeningBalancePayment">
                        <div class="mb-3">
                            <label class="form-label">Customer</label>
                            <input type="text" class="form-control"
                                :value="selectedOpeningBalancePayment.customer ? selectedOpeningBalancePayment.customer
                                    .customer_name : 'N/A'"
                                readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control"
                                v-model="selectedOpeningBalancePayment.payment_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                            <select class="form-select" v-model="selectedOpeningBalancePayment.payment_mode" required>
                                <option value="">Select Payment Mode</option>
                                <option value="{{ config('constants.CASH') }}">Cash</option>
                                <option value="{{ config('constants.BANK_TRANSFER_ALRAJHI') }}">TRANSFER ALRAJHI</option>
                                <option value="{{ config('constants.BANK_TRANSFER_SAB') }}">TRANSFER SAB</option>
                                <option value="{{ config('constants.POS_ALRAJHIS') }}">POS ALRAJHI</option>
                                <option value="{{ config('constants.POS_SAB') }}">POS SAB</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Paid Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control"
                                v-model.number="selectedOpeningBalancePayment.paid_amount" step="0.01" min="0"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" v-model="selectedOpeningBalancePayment.description" rows="3"
                                placeholder="Enter description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" v-model="editPassword"
                                placeholder="Enter password to confirm" required>
                            <div v-if="editPasswordError" class="text-danger mt-2">@{{ editPasswordError }}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="updateOpeningBalancePayment"
                            :disabled="updating">
                            <span v-if="updating">Updating...</span>
                            <span v-else>Update Payment</span>
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
                openingBalancePaymentsList: [],
                selectedOpeningBalancePayment: null,
                search: '',
                page_length: '10',
                password: '',
                passwordError: '',
                verifying: false,
                editPassword: '',
                editPasswordError: '',
                updating: false,
                logged_user_id: '',
                openingBalancePagination: {
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
                    return this.openingBalancePagination.current_page;
                },
                pagesNumber: function() {
                    if (!this.openingBalancePagination.to) {
                        return [];
                    }
                    var from = this.openingBalancePagination.current_page - this.offset;
                    if (from < 1) {
                        from = 1;
                    }
                    var to = from + (this.offset * 2);
                    if (to >= this.openingBalancePagination.last_page) {
                        to = this.openingBalancePagination.last_page;
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
                getOpeningBalancePayments: function(page = 1) {
                    this.loading = true;
                    const url = "{{ route('opening.balance.payments') }}";

                    axios.get(url, {
                        params: {
                            page: page,
                            page_length: this.page_length,
                            search: this.search,
                            platform: 'web'
                        }
                    }).then((response) => {
                        if (response.data.code === 0) {
                            this.openingBalancePaymentsList = response.data.data.payments || [];
                            this.openingBalancePagination = response.data.data.pagination || this
                                .openingBalancePagination;
                        }
                        this.loading = false;
                    }).catch((error) => {
                        console.error('Error loading opening balance payments:', error);
                        Vue.$toast.error('Failed to load opening balance payments.', 'Error');
                        this.loading = false;
                    });
                },
                changePage: function(page) {
                    this.getOpeningBalancePayments(page);
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
                selectOpeningBalancePayment: function(payment) {
                    // Create a copy to avoid mutating the original
                    this.selectedOpeningBalancePayment = JSON.parse(JSON.stringify(payment));
                    // Format payment_date for date input
                    if (this.selectedOpeningBalancePayment.payment_date) {
                        const dateStr = this.selectedOpeningBalancePayment.payment_date;
                        this.selectedOpeningBalancePayment.payment_date = dateStr.includes(' ') ? dateStr.split(
                            ' ')[0] : dateStr;
                    } else {
                        this.selectedOpeningBalancePayment.payment_date = this.getCurrentDate();
                    }
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

                    // Verify password for opening balance payment
                    axios.post('/admin/verify-password', {
                        password: this.password,
                        user_id: this.logged_user_id
                    }).then((response) => {
                        if (response.data.code === 0) {
                            // CLEAR SEARCH FILTER
                            this.search = '';

                            // RELOAD LIST WITHOUT FILTER
                            this.getOpeningBalancePayments(1);

                            $('#passwordModal').modal('hide');
                            $('#openingBalancePaymentModal').modal('show');
                        } else {
                            this.passwordError = response.data.message || 'Invalid password';
                        }
                        this.verifying = false;
                    }).catch((error) => {
                        this.passwordError = error.response?.data?.message || 'Invalid password';
                        this.verifying = false;
                    });
                },
                updateOpeningBalancePayment: function() {
                    if (!this.editPassword) {
                        this.editPasswordError = 'Password is required';
                        return;
                    }

                    if (!this.selectedOpeningBalancePayment.payment_date) {
                        this.editPasswordError = 'Payment date is required';
                        return;
                    }

                    if (!this.selectedOpeningBalancePayment.payment_mode) {
                        this.editPasswordError = 'Payment mode is required';
                        return;
                    }

                    //if (!this.selectedOpeningBalancePayment.paid_amount || this.selectedOpeningBalancePayment
                    //    .paid_amount <= 0) {
                    //    this.editPasswordError = 'Paid amount must be greater than 0';
                    //   return;
                    // }

                    this.updating = true;
                    this.editPasswordError = '';

                    const formData = new FormData();
                    formData.append('payment_id', this.selectedOpeningBalancePayment.id);
                    formData.append('paid_amount', this.selectedOpeningBalancePayment.paid_amount);
                    formData.append('payment_mode', this.selectedOpeningBalancePayment.payment_mode);
                    formData.append('payment_date', this.selectedOpeningBalancePayment.payment_date);
                    formData.append('description', this.selectedOpeningBalancePayment.description || '');
                    formData.append('password', this.editPassword);
                    formData.append('user_id', this.logged_user_id);

                    axios.post('/admin/update-opening-payment', formData)
                        .then((response) => {
                            if (response.data.code === 0) {
                                Vue.$toast.success('Opening balance payment updated successfully',
                                    'Success');
                                $('#openingBalancePaymentModal').modal('hide');
                                this.getOpeningBalancePayments(this.openingBalancePagination.current_page);
                                this.selectedOpeningBalancePayment = null;
                                this.editPassword = '';
                            } else {
                                this.editPasswordError = response.data.message ||
                                    'Failed to update payment';
                            }
                            this.updating = false;
                        })
                        .catch((error) => {
                            this.editPasswordError = error.response?.data?.message ||
                                'Error updating payment';
                            this.updating = false;
                        });
                },
                getCurrentDate() {
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
                        this.logged_user_id = user.data.id ?? 1;
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
                this.getOpeningBalancePayments();
            }
        });
    </script>
@endsection
