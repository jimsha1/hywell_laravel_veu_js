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
                                    <h4 class="page-title">Order List</h4>
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
                                            <span class="form-icon"><i class="ti ti-search"></i></span>
                                            <input type="text" class="form-control" placeholder="Search" v-model="search"
                                                @input="searchOrders">
                                        </div>
                                    </div>
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

                                                <th>Order No</th>
                                                <th>Order Date</th>
                                                <th>Customer</th>
                                                <th>SaleMan</th>
                                                <th>Qty</th>
                                                <th>Amount</th>
                                                <th>Paid Amount</th>
                                                <th>Refunded Amount</th>
                                                <th>Refund From  order balance</th>
                                                <th>Balance</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!adminOrderList || !adminOrderList.length">
                                                <tr>
                                                    <td colspan="10">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in adminOrderList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    <td>
                                                        <a href="javascript:void(0);" @click="showAdminOrderListModal(data)"
                                                            style="color: blue;">
                                                            @{{ data.order_no }}
                                                        </a>
                                                    </td>
                                                    <td>@{{ formatDate(data.order_date) }}</td>
                                                    <td>@{{ data.customer.customer_name }}</td>
                                                    <td>@{{ data.delivery_by ? data.delivery_by.name : '-' }}</td>
                                                    <td>@{{ data.total_quantity }}</td>
                                                    <td>@{{ data.total_amount }}</td>
                                                    <td>@{{ data.paid_amount }}</td>
                                                    <td>@{{ data.total_refunded_amount }}</td>
                                                    <td>@{{ data.total_refund_from_order_balance }}</td>
                                                    <td>@{{ data.balance_amount }}</td>
                                                    <td>
                                                        <span v-if="data.order_status === 'delivered'"
                                                            class="badge bg-success">
                                                            Delivered
                                                        </span>
                                                        <span v-else-if="data.order_status === 'requested'"
                                                            class="badge bg-info">
                                                            Requested
                                                        </span>
                                                        <span v-else-if="data.order_status === 'rejected'"
                                                            class="badge bg-danger">
                                                            Rejected
                                                        </span>
                                                        <span v-else class="badge bg-secondary">
                                                            @{{ data.order_status || 'N/A' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span v-for="p in data.purchase_payment" :key="p.id"
                                                            class="badge bg-primary me-1">
                                                            @{{ p.payment_mode }}(@{{ parseFloat(p.amount_taken).toFixed(2) }})
                                                        </span>
                                                    </td>


                                                    <td>@{{ data.total }}</td>

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

        <!-- View Purchase -->
        <div class="modal fade" id="order_list" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 800px;">
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
                            <template v-if="selectedAdminOrder">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Free Qty</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in selectedAdminOrder.order_items" :key="item.id">
                                            <td>@{{ item.product.product_name || 'N/A' }}</td>
                                            <td>@{{ item.quantity }}</td>
                                            <td>@{{ item.free_quantity }}</td>
                                            <td>@{{ parseFloat(item.price).toFixed(2) }}</td>
                                            <td>@{{ parseFloat(item.total).toFixed(2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </template>
                            <template v-else>
                                <p>No order data available.</p>
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
    </div>
@endsection

@section('script')
    <script>
        var app = new Vue({
            el: '#app',
            data: {
                loading: false,

                base_url: "{{ url('/') }}",

                adminOrderList: [],
                selectedAdminOrder: '',
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
            },


            methods: {
                getAdminOrderList: function(page = 1) {
                    var url = "{{ route('admin-order.list') }}";
                    axios.get(url, {
                        params: {
                            page: page,
                            page_length: this.page_length,
                            search: this.search
                        }
                    }).then(function(response) {
                        app.adminOrderList = response.data.data.admin_order;
                        app.pagination = response.data.data.pagination;

                        app.loading = false;
                    }).catch(function(error) {
                        console.error('Error fetching orders:', error);
                        app.loading = false;
                    });

                },
                searchOrders: function() {
                    // Reset to page 1 when searching
                    this.pagination.current_page = 1;
                    this.getAdminOrderList(1);
                },
                changePage: function(page) {
                    this.getAdminOrderList(page);
                },
                formatDate(value) {
                    if (!value) return '';
                    const date = new Date(value);
                    // return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                    return date.toLocaleDateString();
                },
                showAdminOrderListModal(data) {
                    if (data && data.id > 0) {
                        this.selectedAdminOrder = data;
                        $('#order_list').modal('show');
                    }
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
                this.getAdminOrderList();
                this.getLoggedUserInfo();
            }

        });
    </script>
@endsection
