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
                                    <h4 class="page-title">Pay Credit Balance</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Customer Credit Payment</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    {{-- <div class="col-md-6 mb-3">
                                        <label class="form-label">Select Customer <span class="text-danger">*</span></label>
                                        <select class="form-select" v-model="selectedCustomerId" @change="loadCustomerBalance">
                                            <option value="">-- Select Customer --</option>
                                            <option v-for="customer in customersList" :key="customer.id" :value="customer.id">
                                                @{{ customer.customer_name }} (@{{ customer.phone_number }})
                                            </option>
                                        </select>
                                    </div> --}}

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                                        <select class="select2 form-control" id="select2-placeholder-single">
                                            <option value="">-- Select Customer --</option>
                                           
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Credit Balance</label>
                                        <div class="form-control"
                                            style="background-color: #f8f9fa; font-size: 18px; font-weight: bold; color: #c61b5d;">
                                            SR @{{ formatCurrency(customerBalance) }}
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" v-model="payment_date" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Description</label>
                                        <input type="text" class="form-control" v-model="description"
                                            placeholder="Description">
                                    </div>
                                </div>

                                <div class="row mt-3" v-if="selectedCustomerId">
                                    <div class="col-md-12">
                                        <h6 class="mb-3">Payment Modes</h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Payment Mode</th>
                                                        <th>Amount</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(payment, index) in paymentModes" :key="index">
                                                        <td>
                                                            <select class="form-select" v-model="payment.payment_mode">
                                                                <option value="">Select Payment Mode</option>
                                                                {{-- <option value="Cash">Cash</option>
                                                                <option value="Card">Card</option>
                                                                <option value="Online">Online</option>
                                                                <option value="Bank Transfer Alrajhi">Bank Transfer Alrajhi
                                                                </option>
                                                                <option value="Bank Transfer SAB">Bank Transfer SAB</option>
                                                                <option value="POS Alrajhi">POS Alrajhi</option>
                                                                <option value="POS SAB">POS SAB</option> --}}

                                                                <option value="{{ config('constants.CASH') }}">
                                                                    Cash</option>


                                                                <option
                                                                    value="{{ config('constants.BANK_TRANSFER_ALRAJHI') }}">
                                                                    TRANSFER ALRAJHI</option>
                                                                <option value="{{ config('constants.BANK_TRANSFER_SAB') }}">
                                                                    TRANSFER SAB</option>
                                                                <option value="{{ config('constants.POS_ALRAJHIS') }}">
                                                                    POS ALRAJHIS</option>
                                                                <option value="{{ config('constants.POS_SAB') }}">
                                                                    POS SAB</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control"
                                                                v-model="payment.amount" placeholder="0.00" step="0.01"
                                                                min="0" @input="calculateTotal">
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-danger"
                                                                @click="removePaymentMode(index)"
                                                                v-if="paymentModes.length > 1">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="2" class="text-end"><strong>Total Payment:</strong>
                                                        </td>
                                                        <td><strong>SR @{{ formatCurrency(totalPayment) }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" class="text-end"><strong>Remaining
                                                                Balance:</strong></td>
                                                        <td><strong>SR @{{ formatCurrency(remainingBalance) }}</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        <div class="mt-3">
                                            <button class="btn btn-sm btn-primary" @click="addPaymentMode">
                                                <i class="ti ti-plus"></i> Add Payment Mode
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-4" v-if="selectedCustomerId">
                                    <div class="col-md-12">
                                        <button class="btn btn-primary" @click="processPayment"
                                            :disabled="!canProcessPayment || loading">
                                            <span v-if="loading">Processing...</span>
                                            <span v-else>Process Payment</span>
                                        </button>
                                        <button class="btn btn-light ms-2" @click="resetForm">Reset</button>
                                    </div>
                                </div>
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
        var app = new Vue({
            el: '#app',
            data: {
                base_url: "{{ url('/') }}",
                customersList: [],
                selectedCustomerId: '',
                customerBalance: 0,
                payment_date: '',
                description: '',
                paymentModes: [{
                    payment_mode: '',
                    amount: 0
                }],
                loading: false,
                logged_user_id: '',
            },
            computed: {
                totalPayment() {
                    return this.paymentModes.reduce((total, payment) => {
                        return total + (parseFloat(payment.amount) || 0);
                    }, 0);
                },
                remainingBalance() {
                    return Math.max(0, this.customerBalance - this.totalPayment);
                },
                canProcessPayment() {
                    return this.selectedCustomerId &&
                        this.payment_date &&
                        this.totalPayment > 0 &&
                        this.paymentModes.every(p => p.payment_mode && p.amount > 0);
                }
            },
            mounted() {
                this.getLoggedUserInfo();
                this.getCustomerList();
                this.payment_date = this.getCurrentDate();
            },
            watch: {
                selectedCustomerId(newVal) {
                    this.loadCustomerBalance();
                }
            },
            methods: {
                formatCurrency(value) {
                    if (!value) return '0.00';
                    return parseFloat(value).toFixed(2);
                },
                // getLoggedUserInfo() {
                //     const userJson = window.localStorage.getItem('user');
                //     if (userJson) {
                //         const user = JSON.parse(userJson);
                //         this.userId = user.data.id;
                //     } else {
                //         this.userId = 1;
                //     }
                // },


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
                    } else {
                        this.userId = 1;
                    }
                },
                getCustomerList() {
                    axios.get('/api/customer/list', {
                        params: {
                            platform: 'api'
                        }
                    }).then(response => {
                        const payload = response.data.data || response.data || {};
                        const rawCustomers = payload.customers || payload || [];
                        this.customersList = rawCustomers.map(customer => ({
                            ...customer,
                            customer_name: customer.customer_name || customer.name || '',
                            phone_number: customer.phone_number || ''
                        }));
                        this.$nextTick(() => {
                            this.initSelect2();
                        });
                    }).catch(error => {
                        console.error('Error loading customers:', error);
                        Vue.$toast.error('Failed to load customers.', 'Error');
                    });
                },
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
                            text: `${customer.customer_name} (${customer.phone_number})`
                        }))
                    });

                    // Listen to Select2 change event and update Vue
                    $sel.on('change', (e) => {
                        this.selectedCustomerId = e.target.value;
                    });
                },
                loadCustomerBalance() {
                    if (!this.selectedCustomerId) {
                        this.customerBalance = 0;
                        return;
                    }

                    const customer = this.customersList.find(c => c.id == this.selectedCustomerId);
                    if (customer) {
                        this.customerBalance = parseFloat(customer.balance_amount || 0);
                    } else {
                        // Fetch customer details if not found in list
                        axios.get('/api/customer-details/list', {
                            params: {
                                customer_id: this.selectedCustomerId,
                                platform: 'api'
                            }
                        }).then(response => {
                            if (response.data.code === 0 && response.data.data.customer) {
                                this.customerBalance = parseFloat(response.data.data.customer
                                    .balance_amount || 0);
                            }
                        }).catch(error => {
                            console.error('Error loading customer balance:', error);
                        });
                    }
                },
                addPaymentMode() {
                    this.paymentModes.push({
                        payment_mode: '',
                        amount: 0
                    });
                },
                removePaymentMode(index) {
                    if (this.paymentModes.length > 1) {
                        this.paymentModes.splice(index, 1);
                        this.calculateTotal();
                    }
                },
                calculateTotal() {
                    // This is handled by computed property
                },
                processPayment() {
                    if (!this.canProcessPayment) {
                        Vue.$toast.error('Please fill all payment details correctly.', 'Error');
                        return;
                    }

                    if (!this.payment_date) {
                        Vue.$toast.error('Please select a payment date.', 'Error');
                        return;
                    }

                    if (this.totalPayment > this.customerBalance) {
                        Vue.$toast.error('Payment amount cannot exceed credit balance.', 'Error');
                        return;
                    }

                    this.loading = true;

                    const paymentData = {
                        customer_id: this.selectedCustomerId,
                        user_id: this.userId ?? 1,
                        date: this.payment_date,
                        description: this.description,
                        payment_modes: this.paymentModes.filter(p => p.payment_mode && p.amount > 0)
                    };

                    axios.post("/api/pay-credit-balance", paymentData)
                        .then(response => {
                            if (response.data.code === 0) {
                                Vue.$toast.success(response.data.message || 'Payment processed successfully.',
                                    'Success');
                                this.resetForm();
                                this.getCustomerList(); // Refresh customer list to get updated balance
                            } else {
                                Vue.$toast.error(response.data.message || 'Failed to process payment.',
                                    'Error');
                            }
                            this.loading = false;
                        })
                        .catch(error => {
                            console.error('Error processing payment:', error);
                            Vue.$toast.error(error.response?.data?.message || 'Failed to process payment.',
                                'Error');
                            this.loading = false;
                        });
                },
                getCurrentDate() {
                    const today = new Date();
                    const year = today.getFullYear();
                    const month = String(today.getMonth() + 1).padStart(2, '0');
                    const day = String(today.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                },
                resetForm() {
                    this.selectedCustomerId = '';
                    this.customerBalance = 0;
                    this.payment_date = this.getCurrentDate();
                    this.description = '';
                    this.paymentModes = [{
                        payment_mode: '',
                        amount: 0
                    }];
                    // Reset Select2
                    $('#select2-placeholder-single').val(null).trigger('change');
                }
            }
        });
    </script>
@endsection
