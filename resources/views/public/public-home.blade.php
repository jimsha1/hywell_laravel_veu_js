@extends('public.layout.base')

@section('public-main-content')
    <div id="app">
        <div class="customer-web-app-modal">
            <div class="customer-home">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h2 class="greeting">Hello!</h2>
                        <h1 class="username">@{{ customer_name }}</h1>
                    </div>
                    <button class="logout" v-on:click="showLogoutModal()">
                        <i class="ti ti-logout"></i>
                    </button>
                </div>

                <div class="wallet-card">
                    <div class="wallet-icon"><i class="ti ti-wallet"></i></div>
                    <div class="wallet-info">
                        <h3 class="text-white">My Wallet</h3>
                        <h1 class="text-white fw-normal mt-0 mb-0"><strong>@{{ wallet_bottle }}</strong> Bottle</h1>
                        <p class="mt-0">Balance</p>
                        <p class="price pt-2">Price: <strong>SR @{{ wallet_bottle_balance }}</strong></p>
                    </div>
                </div>
                <h5 class="mb-3"><i class="ti ti-history fw-semibold me-1"></i>History</h5>
                <div class="history-section">


                    <ul class="nav custom-tabs mb-3 mx-3" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="order-tab" data-bs-toggle="tab" data-bs-target="#order"
                                type="button" role="tab">Order</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="wallet-tab" data-bs-toggle="tab" data-bs-target="#wallet"
                                type="button" role="tab">Wallet</button>
                        </li>
                    </ul>
                    <div class="tab-content px-3">
                        <!-- Order Tab -->
                        <div class="tab-pane fade show active" id="order" role="tabpanel">


                            <div v-if="orderList.length > 0">
                                <div v-for="order in orderList" :key="order.order_no" class="pb-2 mb-3 hsitory-card">
                                    <small class="text-muted">#@{{ order.order_no }}</small>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold">@{{ formatDateOnly(order.order_date) }}</div>
                                            <small class="text-muted">@{{ formatTimeOnly(order.order_date) }}</small>
                                        </div>
                                        <div>
                                            <span class="badge rounded-pill bg-success-subtle text-success me-1">
                                                @{{ order.order_item[0].delivered_qty }}b Given
                                            </span>
                                            <span class="badge rounded-pill bg-danger-subtle text-danger">
                                                @{{ order.order_item[0].return_qty }}b Return
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else>
                                <div class="text-center py-5">
                                    <h5>No Orders Found</h5>
                                    <p class="text-muted">You haven't placed any orders yet.</p>
                                </div>
                            </div>

                        </div>

                        <!-- Wallet Tab -->
                        <div class="tab-pane fade" id="wallet" role="tabpanel">
                            <div v-if="walletHistoryList.length > 0">
                                <div v-for="history in walletHistoryList" :key="history.id"
                                    class="pb-2 mb-3 hsitory-card">
                                    <div class="fw-semibold">
                                        @{{ history.type === 'credit' ? 'Wallet Recharge' : 'Bottle Deducted' }}
                                    </div>
                                    <small class="text-muted">
                                        <span :class="history.type === 'credit' ? 'text-success' : 'text-danger'">
                                            SR @{{ history.amount }}
                                        </span>
                                        @{{ formatDateTime(history.created_at) }}
                                    </small>
                                </div>
                            </div>
                            <div v-else>
                                <div class="text-center py-5">
                                    <h5>No Wallet History Found</h5>
                                    <p class="text-muted">You haven't made any wallet transactions yet.</p>
                                </div>
                            </div>

                        </div>

                        <!-- Logout Confirmation Modal -->
                        <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel"
                            aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 text-start p-4">
                                    <h5 class="modal-title mb-2" id="logoutModalLabel">Are you sure?</h5>
                                    <p class="text-muted mb-3">You will be logged out of your account.</p>
                                    <div class="d-flex justify-content-end gap-3 mt-2">
                                        <button type="button" class="btn btn-outline-primary"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <button v-on:click="logout()" type="button" class="btn btn-primary">Logout</button>
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

@section('public-script')
    <script>
        var app = new Vue({
            el: '#app',
            data: {
                customer_name: '',
                phone: '',
                wallet_bottle: 0,
                wallet_bottle_balance: 0,
                orderList: [],
                walletHistoryList: [],
            },
            methods: {
                getInfo() {
                    let publicCustomerInfo = JSON.parse(window.localStorage.getItem('publicCustomerInfo'));
                    if (publicCustomerInfo) {
                        this.getCustomer(publicCustomerInfo.phoneNumber);
                    }
                },
                getCustomer(p) {
                    axios.get('/api/get-customer-info-by-phone', {
                            params: {
                                phone: p
                            }
                        })
                        .then(response => {
                            if (response.data.code == 0) {
                                app.processCustomerInfo(response.data.data.customer);
                            }else{
                                 window.location.href = "{{ route('public.login') }}";
                            }
                        });
                },
                processCustomerInfo(customerInfo) {
                    app.customer_name = customerInfo.customer_name + "(" + customerInfo.customer_code + ")" ||
                        'Guest';
                    app.wallet_bottle = customerInfo.wallet ? customerInfo.wallet.no_of_bottle : 0;
                    app.wallet_bottle_balance = customerInfo.wallet ? customerInfo.wallet.balance_amount : 0;
                    app.orderList = customerInfo.orders || [];
                    app.walletHistoryList = customerInfo.wallet?.history || [];
                },
                formatDateOnly(dateTime) {
                    const date = new Date(dateTime.replace(' ', 'T')); // Ensure proper ISO format
                    return date.toLocaleDateString(); // e.g., "7/14/2025"
                },

                formatTimeOnly(dateTime) {
                    const date = new Date(dateTime.replace(' ', 'T')); // Ensure proper ISO format
                    return date.toLocaleTimeString(); // e.g., "12:00:00 AM"
                },
                formatDateTime(dateTime) {
                    const date = new Date(dateTime.replace(' ', 'T')); // Ensure proper ISO format
                    return date.toLocaleString('en-US', {
                        year: 'numeric',
                        month: 'long', // Full month name (e.g., "July")
                        day: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true // 12-hour format with AM/PM
                    });
                },
                logout() {
                    window.localStorage.removeItem('publicCustomerInfo');
                    window.location.href = "{{ route('public.login') }}";
                },
                showLogoutModal() {
                    $('#logoutModal').modal('show');
                },




            },
            mounted() {
                this.getInfo();
                console.log("Vue instance mounted successfully!");
            },
        });
    </script>
@endsection
