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
                                    <h4 class="page-title">Sales Return</h4>
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
                                <div class="row align-items-center">
                                    <div class="col-sm-4">
                                        <div class="icon-form mb-3 mb-sm-0">
                                            <span class="form-icon"><i class="ti ti-search"></i></span>
                                            <input type="text" class="form-control"
                                                placeholder="Search by Return No, Order No, Customer" v-model="search"
                                                @input="getSalesReturnList">
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <select class="form-select" v-model="return_status" @change="getSalesReturnList">
                                            <option value="">All Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-5 text-end">
                                        <a href="javascript:void(0);" class="btn btn-primary"
                                            @click="showCreateReturnModal">
                                            <i class="ti ti-square-rounded-plus me-2"></i>Create Return
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Sales Return List -->
                                <div class="table-responsive custom-table">
                                    <table class="table" id="sales_return_list">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Return No</th>
                                                <th>Order No</th>
                                                <th>Customer</th>
                                                <th>Return Date</th>
                                                <th>Quantity</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!salesReturnList || !salesReturnList.length">
                                                <tr>
                                                    <td colspan="9">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in salesReturnList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    <td>
                                                        <a href="javascript:void(0);" @click="showReturnDetailsModal(data)"
                                                            style="color: blue;">
                                                            @{{ data.return_no }}
                                                        </a>
                                                    </td>
                                                    <td>@{{ data.order ? data.order.order_no : '-' }}</td>
                                                    {{-- <td>@{{ data.customer ? data.customer.customer_name : '-' }}</td> --}}
                                                    <td>@{{ data.display_customer ? data.display_customer.name : '-' }}</td>
                                                    <td>@{{ formatDate(data.return_date) }}</td>
                                                    <td>@{{ data.total_quantity }}</td>
                                                    <td>@{{ parseFloat(data.total_amount).toFixed(2) }}</td>
                                                    <td>
                                                        <span v-if="data.return_status === 'approved'"
                                                            class="badge bg-success">Approved</span>
                                                        <span v-else-if="data.return_status === 'rejected'"
                                                            class="badge bg-danger">Rejected</span>
                                                        <span v-else class="badge bg-warning">Pending</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            <button class="btn btn-sm btn-info"
                                                                @click="showReturnDetailsModal(data)" title="View Details">
                                                                <i class="ti ti-eye"></i>
                                                            </button>
                                                            <button v-if="data.return_status === 'pending'"
                                                                class="btn btn-sm btn-success"
                                                                @click="showApproveReturnModal(data)" title="Approve">
                                                                <i class="ti ti-check"></i>
                                                            </button>
                                                            <button v-if="data.return_status === 'pending'"
                                                                class="btn btn-sm btn-danger"
                                                                @click="showRejectReturnModal(data)" title="Reject">
                                                                <i class="ti ti-x"></i>
                                                            </button>
                                                        </div>
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

        <!-- Create Return Modal -->
        <div class="modal fade" id="create_return_modal" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg" style="min-width: 1000px; max-height: 90vh;">
                <div class="modal-content" style="max-height: 90vh; display: flex; flex-direction: column;">
                    <div class="modal-header" style="flex-shrink: 0;">
                        <h5 class="modal-title">Create Sales Return</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="modal-body" style="overflow-y: auto; flex: 1 1 auto; max-height: calc(90vh - 120px);">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Select Order</label>
                                <select class="form-control" id="select2-order-search" style="width: 100%;">
                                    <option value="">Select Order</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Returned By <span class="text-danger">*</span></label>
                                <select class="form-select" v-model="returnedById" required>
                                    <option value="">Select Employee</option>
                                    <option v-for="employee in returnedByList" :key="employee.id"
                                        :value="employee.id">
                                        @{{ employee.name }} (@{{ getEmployeeType(employee.user_type) }})
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3" v-if="selectedOrder && selectedOrder.order_items">
                                <label class="form-label">Return Items</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Ordered Qty</th>
                                                <th>Free Qty</th>
                                                <th>Return Qty</th>
                                                <th>Return Free Qty</th>
                                                <th>Price</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in selectedOrder.order_items" :key="item.id">
                                                <td>@{{ item.product ? item.product.product_name : '-' }}</td>
                                                <td>@{{ item.quantity }}</td>
                                                <td>@{{ item.free_quantity || 0 }}</td>
                                                <td>
                                                    <input type="number" class="form-control"
                                                        :class="{
                                                            'is-invalid': returnItems[item.id] > getMaxReturnableQty(
                                                                item)
                                                        }"
                                                        v-model.number="returnItems[item.id]"
                                                        :max="getMaxReturnableQty(item)" min="0"
                                                        @input="validateReturnQty(item)" style="width: 100px;">
                                                    <small class="text-danger"
                                                        v-if="returnItems[item.id] > getMaxReturnableQty(item)">
                                                        Max: @{{ getMaxReturnableQty(item) }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control"
                                                        :class="{
                                                            'is-invalid': returnFreeItems[item.id] >
                                                                getMaxReturnableFreeQty(
                                                                    item)
                                                        }"
                                                        v-model.number="returnFreeItems[item.id]"
                                                        :max="getMaxReturnableFreeQty(item)" min="0"
                                                        @input="validateReturnFreeQty(item)" style="width: 100px;">
                                                    <small class="text-danger"
                                                        v-if="returnFreeItems[item.id] > getMaxReturnableFreeQty(item)">
                                                        Max: @{{ getMaxReturnableFreeQty(item) }}
                                                    </small>
                                                </td>
                                                <td>@{{ parseFloat(item.price).toFixed(2) }}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" @click="addReturnItem(item)"
                                                        :disabled="(returnItems[item.id] <= 0 && returnFreeItems[item.id] <= 0) ||
                                                        returnItems[item.id] >
                                                            getMaxReturnableQty(item) || returnFreeItems[item.id] >
                                                            getMaxReturnableFreeQty(item)"
                                                        v-if="returnItems[item.id] > 0 || returnFreeItems[item.id] > 0">
                                                        Add
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3" v-if="returnItemsList.length > 0">
                                <label class="form-label">Selected Return Items</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Return Qty</th>
                                                <th>Return Free Qty</th>
                                                <th>Price</th>
                                                <th>Total</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, index) in returnItemsList" :key="index">
                                                <td>@{{ item.product_name }}</td>
                                                <td>@{{ item.return_quantity }}</td>
                                                <td>@{{ item.free_quantity || 0 }}</td>
                                                <td>@{{ parseFloat(item.price).toFixed(2) }}</td>
                                                <td>@{{ parseFloat(item.total).toFixed(2) }}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-danger"
                                                        @click="removeReturnItem(index)">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3">Total</th>
                                                <th>@{{ parseFloat(calculateReturnTotal()).toFixed(2) }}</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Return Reason</label>
                                <textarea class="form-control" v-model="returnReason" rows="3" placeholder="Enter reason for return"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="flex-shrink: 0; border-top: 1px solid #dee2e6;">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="createSalesReturn"
                            :disabled="returnItemsList.length === 0 || !returnedById">
                            Create Return
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Return Details Modal -->
        <div class="modal fade" id="return_details_modal" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Return Details</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <template v-if="selectedReturn">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Return No:</strong> @{{ selectedReturn.return_no }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Order No:</strong> @{{ selectedReturn.order ? selectedReturn.order.order_no : '-' }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Customer:</strong> @{{ selectedReturn.customer ? selectedReturn.customer.customer_name : '-' }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Status:</strong>
                                    <span v-if="selectedReturn.return_status === 'approved'"
                                        class="badge bg-success">Approved</span>
                                    <span v-else-if="selectedReturn.return_status === 'rejected'"
                                        class="badge bg-danger">Rejected</span>
                                    <span v-else class="badge bg-warning">Pending</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Return Date:</strong> @{{ formatDate(selectedReturn.return_date) }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Total Amount:</strong> @{{ parseFloat(selectedReturn.total_amount).toFixed(2) }}
                                </div>
                            </div>
                            <div class="row mb-3" v-if="selectedReturn.return_reason">
                                <div class="col-md-12">
                                    <strong>Return Reason:</strong> @{{ selectedReturn.return_reason }}
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Return Qty</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in selectedReturn.return_items" :key="item.id">
                                            <td>@{{ item.product ? item.product.product_name : '-' }}</td>
                                            <td>@{{ item.return_quantity }}</td>
                                            <td>@{{ parseFloat(item.price).toFixed(2) }}</td>
                                            <td>@{{ parseFloat(item.total).toFixed(2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-if="selectedReturn.return_payments && selectedReturn.return_payments.length > 0"
                                class="mt-3">
                                <h6>Refund Details</h6>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Refund Amount</th>
                                                <th>Refund Mode</th>
                                                <th>Refund Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="payment in selectedReturn.return_payments" :key="payment.id">
                                                <td>@{{ parseFloat(payment.refund_amount).toFixed(2) }}</td>
                                                <td>
                                                    <span v-if="payment.refund_mode === 'order_balance'"
                                                        class="badge bg-info">Order Balance</span>
                                                    <span v-else-if="payment.refund_mode === 'cash'"
                                                        class="badge bg-success">Cash</span>
                                                    <span v-else-if="payment.refund_mode === 'bank_transfer'"
                                                        class="badge bg-primary">Bank Transfer</span>
                                                    <span v-else>@{{ payment.refund_mode }}</span>

                                                </td>
                                                <td>@{{ formatDate(payment.refund_date) }}</td>
                                            </tr>
                                        </tbody>
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

        <!-- Approve Return Modal -->
        <div class="modal fade" id="approve_return_modal" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Approve Sales Return</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <template v-if="returnToApprove">
                            <div class="mb-3">
                                <strong>Return No:</strong> @{{ returnToApprove.return_no || 'Pending' }}
                            </div>
                            <div class="mb-3">
                                <strong>Return Amount:</strong> @{{ parseFloat(returnToApprove.total_amount).toFixed(2) }}
                            </div>
                            <div class="mb-3" v-if="orderForApprove">
                                <div class="alert alert-info">
                                    <strong>Order Balance Amount:</strong> @{{ parseFloat(orderForApprove.balance_amount || 0).toFixed(2) }}
                                    <br>
                                    {{-- <small v-if="orderForApprove.balance_amount > 0">
                                        <i class="ti ti-info-circle"></i> Refund will be taken from balance amount first.
                                    </small> --}}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Return Destination</label>
                                <select class="form-select" v-model="returnDestination"
                                    @change="onReturnDestinationChange">
                                    <option value="godown">Godown</option>
                                    <option value="vehicle">Vehicle</option>
                                </select>
                            </div>
                            <div class="mb-3" v-if="returnDestination === 'vehicle'">
                                <label class="form-label">Select Salesman</label>
                                <select class="form-select" v-model="selectedSalesmanId">
                                    <option value="">Select Salesman</option>
                                    <option v-for="salesman in salesmanList" :value="salesman.id">
                                        @{{ salesman.name }} (@{{ salesman.phone_number || 'N/A' }})
                                    </option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" v-model="approvePassword"
                                    placeholder="Enter password to approve">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Refund Details</label>
                                <div class="alert alert-warning mb-2"
                                    v-if="orderForApprove && orderForApprove.balance_amount > 0 && orderForApprove.balance_amount>=returnAmount ">
                                    <small>
                                        <i class="ti ti-alert-circle"></i>
                                        Refund amount (@{{ parseFloat(returnToApprove.total_amount).toFixed(2) }}) will be deducted from order balance amount
                                        (@{{ parseFloat(orderForApprove.balance_amount || 0).toFixed(2) }}).
                                        {{-- <span
                                            v-if="parseFloat(returnToApprove.total_amount) > parseFloat(orderForApprove.balance_amount || 0)">
                                            Remaining @{{ (parseFloat(returnToApprove.total_amount) - parseFloat(orderForApprove.balance_amount || 0)).toFixed(2) }} will be refunded from paid amount.
                                        </span> --}}
                                    </small>
                                </div>
                                <div class="alert alert-warning mb-2"
                                    v-else-if="orderForApprove && orderForApprove.balance_amount > 0 && orderForApprove.balance_amount<returnAmount ">
                                    <small>
                                        <i class="ti ti-alert-circle"></i>
                                        Refund amount (@{{ parseFloat(returnToApprove.total_amount).toFixed(2) }}) will be deducted from order balance amount
                                        (@{{ parseFloat(orderForApprove.balance_amount || 0).toFixed(2) }}).
                                        <span
                                            v-if="parseFloat(returnToApprove.total_amount) > parseFloat(orderForApprove.balance_amount || 0)">
                                            Remaining @{{ (parseFloat(returnToApprove.total_amount) - parseFloat(orderForApprove.balance_amount || 0)).toFixed(2) }} will be refunded later or deducted from
                                            previous pending orders.
                                        </span>
                                    </small>
                                </div>
                                <div class="alert alert-warning mb-2"
                                    v-else-if="orderForApprove && orderForApprove.balance_amount == 0">
                                    <small>
                                        <i class="ti ti-alert-circle"></i>
                                        Retrun amount (@{{ parseFloat(returnToApprove.total_amount).toFixed(2) }}) will be refunded later or deducted from
                                        other pending orders.
                                        (@{{ parseFloat(orderForApprove.balance_amount || 0).toFixed(2) }})
                                    </small>
                                </div>

                            </div>
                        </template>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" @click="approveSalesReturn"
                            :disabled="!approvePassword  || (
                                returnDestination === 'vehicle' && !
                                selectedSalesmanId)">
                            Approve Return
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Return Modal -->
        <div class="modal fade" id="reject_return_modal" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Sales Return</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <template v-if="returnToReject">
                            <div class="mb-3">
                                <strong>Return No:</strong> @{{ returnToReject.return_no }}
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" v-model="rejectPassword"
                                    placeholder="Enter password to reject">
                            </div>
                        </template>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" @click="rejectSalesReturn"
                            :disabled="!rejectPassword">
                            Reject Return
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
                salesReturnList: [],
                selectedReturn: null,
                returnToApprove: null,
                returnToReject: null,
                search: '',
                return_status: '',
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
                // Create Return
                availableOrders: [],
                selectedOrderId: null,
                selectedOrder: null,
                returnItems: {},
                returnFreeItems: {},
                returnItemsList: [],
                returnReason: '',
                returnedById: null,
                returnedByList: [],
                // Approve Return
                approvePassword: '',
                refundRows: [{
                    refund_mode: 'cash',
                    refund_amount: 0
                }],
                returnDestination: 'godown',
                selectedSalesmanId: null,
                salesmanList: [],
                orderForApprove: null,
                // Reject Return
                rejectPassword: '',
                user_id: null,
                returnAmount: 0,
                actualReturnAmount:0,
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
                getSalesReturnList: function(page = 1) {
                    var self = this;
                    var url = "{{ route('sales-return.list') }}";
                    axios.get(url, {
                        params: {
                            page: page,
                            search: this.search,
                            return_status: this.return_status,
                            platform: 'web'
                        }
                    }).then(function(response) {
                        if (response.data.status === 'Success') {
                            app.salesReturnList = response.data.data.sales_returns || [];
                            app.pagination = response.data.data.pagination || {};
                        }
                        app.loading = false;
                    }).catch(function(error) {
                        console.error('Error:', error);
                        app.loading = false;
                    });
                },
                changePage: function(page) {
                    this.getSalesReturnList(page);
                },
                formatDate(value) {
                    if (!value) return '';
                    const date = new Date(value);
                    return date.toLocaleDateString();
                },
                showCreateReturnModal() {
                    this.resetCreateReturnForm();
                    this.loadReturnedByList();
                    $('#create_return_modal').modal('show');

                    // Initialize Select2 after modal is fully shown
                    var self = this;
                    $('#create_return_modal').one('shown.bs.modal', function() {
                        setTimeout(function() {
                            self.initOrderSelect2();
                        }, 200);
                    });
                },
                loadReturnedByList() {
                    var self = this;
                    axios.get('/api/get-users', {
                        params: {
                            platform: 'api',
                            user_type: ['salesman', 'sales_man', 'sales_executive', 'superadmin', 'admin']
                        }
                        
                    }).then(function(response) {
                        if (response.data.code === 0) {
                            app.returnedByList = response.data.data || [];
                        }
                    }).catch(function(error) {
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
                initOrderSelect2() {
                    const $sel = $('#select2-order-search');

                    // Destroy any existing Select2 instance completely
                    if ($sel.hasClass('select2-hidden-accessible')) {
                        $sel.select2('destroy');
                    }

                    // Remove any existing Select2 data and events
                    $sel.removeData('select2');
                    $sel.off('select2:open select2:close select2:select');

                    // Clear the select element but keep the placeholder option
                    if ($sel.find('option').length === 0 || ($sel.find('option').length === 1 && $sel.find('option')
                            .val() !== '')) {
                        $sel.empty().append('<option value="">Select Order</option>');
                    }

                    var self = this;

                    $sel.select2({
                        dropdownParent: $('#create_return_modal'),
                        placeholder: 'Type to search order by order number or customer name',
                        allowClear: true,
                        width: '100%',
                        minimumInputLength: 0,
                        language: {
                            inputTooShort: function() {
                                return 'Type to search...';
                            },
                            searching: function() {
                                return 'Searching...';
                            },
                            noResults: function() {
                                return 'No orders found';
                            }
                        },
                        ajax: {
                            url: '/api/orders-for-return',
                            dataType: 'json',
                            delay: 300,
                            type: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            data: function(params) {
                                var query = {
                                    search: params.term || '',
                                    platform: 'api',
                                    page: params.page || 1,
                                    per_page: 50
                                };
                                console.log('Select2 AJAX Request:', query);
                                return query;
                            },
                            processResults: function(data, params) {
                                params.page = params.page || 1;

                                console.log('Select2 AJAX Response:', data);

                                if (data.status === 'Success') {
                                    var orders = data.data.orders || [];
                                    var pagination = data.data.pagination || {};

                                    return {
                                        results: orders.map(function(order) {
                                            return {
                                                id: order.id,
                                                text: order.order_no + ' - ' + (order.customer ?
                                                        order.customer.customer_name : 'N/A') +
                                                    ' (Amount: ' + parseFloat(order
                                                        .total_amount || 0).toFixed(2) + ')',
                                                order: order
                                            };
                                        }),
                                        pagination: {
                                            more: pagination.more === true
                                        }
                                    };
                                }
                                return {
                                    results: [],
                                    pagination: {
                                        more: false
                                    }
                                };
                            },
                            error: function(xhr, status, error) {
                                console.error('Select2 AJAX Error:', error, xhr);
                            },
                            cache: false
                        }
                    });

                    // Ensure search input is enabled when dropdown opens
                    $sel.on('select2:open', function() {
                        setTimeout(function() {
                            var $searchInput = $('.select2-container--open .select2-search__field');
                            if ($searchInput.length) {
                                $searchInput.prop('disabled', false);
                                $searchInput.attr('readonly', false);
                                $searchInput.focus();
                            }
                        }, 50);
                    });

                    // Listen to Select2 change event and update Vue
                    $sel.on('change', function(e) {
                        var selectedData = $sel.select2('data');
                        if (selectedData && selectedData.length > 0) {
                            var selectedOrder = selectedData[0].order;
                            if (selectedOrder) {
                                self.selectedOrderId = selectedData[0].id;
                                self.selectedOrder = selectedOrder;
                                // If order_items not loaded, fetch full details
                                if (!self.selectedOrder.order_items || self.selectedOrder.order_items
                                    .length === 0) {
                                    self.loadOrderDetails();
                                } else {
                                    // Set default return quantities
                                    self.setDefaultReturnQuantities();
                                }
                            } else {
                                // If order not in data, fetch it
                                self.selectedOrderId = selectedData[0].id;
                                self.loadOrderDetails();
                            }
                        } else {
                            self.selectedOrderId = null;
                            self.selectedOrder = null;
                            self.returnItems = {};
                        }
                    });
                },
                loadOrderDetails() {
                    if (!this.selectedOrderId) {
                        this.selectedOrder = null;
                        return;
                    }
                    // Fetch full order details with order_items
                    var self = this;
                    axios.get('/api/orders-for-return', {
                        params: {
                            platform: 'api',
                            search: this.selectedOrderId.toString(),
                            per_page: 100
                        }
                    }).then(function(response) {
                        if (response.data.status === 'Success') {
                            var orders = response.data.data.orders || [];
                            var order = orders.find(o => o.id == self.selectedOrderId);
                            if (order) {
                                self.selectedOrder = order;
                                // Set default return quantities after order is loaded
                                self.setDefaultReturnQuantities();
                            }
                        }
                    }).catch(function(error) {
                        console.error('Error loading order details:', error);
                    });
                },
                setDefaultReturnQuantities() {
                    if (!this.selectedOrder || !this.selectedOrder.order_items) {
                        return;
                    }

                    // Reset return items object
                    this.returnItems = {};
                    this.returnFreeItems = {};

                    // Set default return quantity to ordered quantity for each item
                    var self = this;
                    this.selectedOrder.order_items.forEach(function(item) {
                        var maxReturnable = self.getMaxReturnableQty(item);
                        // Set default to max returnable (ordered qty - already returned)
                        self.$set(self.returnItems, item.id, maxReturnable);

                        var maxReturnableFree = self.getMaxReturnableFreeQty(item);
                        self.$set(self.returnFreeItems, item.id, maxReturnableFree);
                    });
                },
                getMaxReturnableQty(item) {
                    // Get the maximum quantity that can be returned
                    // This considers already returned quantity if any
                    var orderedQty = parseInt(item.quantity) || 0;
                    var alreadyReturned = parseInt(item.returned_quantity) || 0;
                    return Math.max(0, orderedQty - alreadyReturned);
                },
                getMaxReturnableFreeQty(item) {
                    // Get the maximum free quantity that can be returned
                    var freeQty = parseInt(item.free_quantity) || 0;
                    var alreadyReturnedQty = parseInt(item.returned_free_quantity) || 0;
                    // TODO: Track returned free quantity if needed in future
                    return Math.max(0, freeQty - alreadyReturnedQty);
                },
                validateReturnFreeQty(item) {
                    var maxQty = this.getMaxReturnableFreeQty(item);
                    var currentQty = this.returnFreeItems[item.id] || 0;

                    // If entered quantity exceeds max, clamp it to max
                    if (currentQty > maxQty) {
                        this.returnFreeItems[item.id] = maxQty;
                        Vue.$toast.warning('Return free quantity cannot exceed free quantity (' + maxQty + ')',
                            'Warning');
                    }

                    // Ensure it's not negative
                    if (currentQty < 0) {
                        this.returnFreeItems[item.id] = 0;
                    }
                },
                validateReturnQty(item) {
                    var maxQty = this.getMaxReturnableQty(item);
                    var currentQty = this.returnItems[item.id] || 0;

                    // If entered quantity exceeds max, clamp it to max
                    if (currentQty > maxQty) {
                        this.returnItems[item.id] = maxQty;
                        Vue.$toast.warning('Return quantity cannot exceed ordered quantity (' + maxQty + ')',
                            'Warning');
                    }

                    // Ensure it's not negative
                    if (currentQty < 0) {
                        this.returnItems[item.id] = 0;
                    }
                },
                addReturnItem(item) {
                    var qty = this.returnItems[item.id] || 0;
                    var freeQty = this.returnFreeItems[item.id] || 0;

                    if (qty <= 0 && freeQty <= 0) return;

                    // Validate quantity doesn't exceed maximum returnable
                    var maxQty = this.getMaxReturnableQty(item);
                    if (qty > maxQty) {
                        Vue.$toast.error('Return quantity (' + qty + ') cannot exceed ordered quantity (' + maxQty +
                            ')', 'Error');
                        this.returnItems[item.id] = maxQty;
                        return;
                    }

                    // Validate free quantity doesn't exceed maximum returnable
                    var maxFreeQty = this.getMaxReturnableFreeQty(item);
                    if (freeQty > maxFreeQty) {
                        Vue.$toast.error('Return free quantity (' + freeQty + ') cannot exceed free quantity (' +
                            maxFreeQty +
                            ')', 'Error');
                        this.returnFreeItems[item.id] = maxFreeQty;
                        return;
                    }

                    var existingIndex = this.returnItemsList.findIndex(ri => ri.order_item_id === item.id);
                    if (existingIndex >= 0) {
                        this.returnItemsList[existingIndex].return_quantity = qty;
                        this.returnItemsList[existingIndex].free_quantity = freeQty;
                        this.returnItemsList[existingIndex].total = qty * item.price;
                    } else {
                        this.returnItemsList.push({
                            order_item_id: item.id,
                            product_id: item.product_id,
                            product_name: item.product ? item.product.product_name : 'N/A',
                            return_quantity: qty,
                            free_quantity: freeQty,
                            price: item.price,
                            total: qty * item.price
                        });
                    }
                    this.returnItems[item.id] = 0;
                    this.returnFreeItems[item.id] = 0;
                },
                removeReturnItem(index) {
                    this.returnItemsList.splice(index, 1);
                },
                calculateReturnTotal() {
                    return this.returnItemsList.reduce((sum, item) => sum + parseFloat(item.total || 0), 0);
                },
                resetCreateReturnForm() {
                    this.selectedOrderId = null;
                    this.selectedOrder = null;
                    this.returnItems = {};
                    this.returnFreeItems = {};
                    this.returnItemsList = [];
                    this.returnReason = '';
                    this.returnedById = null;
                    // Reset Select2 dropdown
                    const $sel = $('#select2-order-search');
                    if ($sel.hasClass('select2-hidden-accessible')) {
                        $sel.val(null).trigger('change');
                    }
                },
                createSalesReturn() {
                    if (this.returnItemsList.length === 0) {
                        Vue.$toast.error('Please add items to return', 'Error');
                        return;
                    }
                    if (!this.returnedById) {
                        Vue.$toast.error('Please select who returned the order', 'Error');
                        return;
                    }
                    var self = this;
                    var url = "{{ route('sales-return.create') }}";
                    var data = {
                        order_id: this.selectedOrderId,
                        return_items: JSON.stringify(this.returnItemsList.map(item => ({
                            order_item_id: item.order_item_id,
                            product_id: item.product_id,
                            return_quantity: item.return_quantity,
                            free_quantity: item.free_quantity || 0
                        }))),
                        return_reason: this.returnReason,
                        user_id: this.user_id,
                        returned_by: this.returnedById
                    };
                    axios.post(url, data).then(function(response) {
                        if (response.data.status === 'Success') {
                            Vue.$toast.success('Sales return created successfully', 'Success');
                            $('#create_return_modal').modal('hide');
                            app.getSalesReturnList();
                            app.resetCreateReturnForm();
                        } else {
                            Vue.$toast.error(response.data.message || 'Failed to create return', 'Error');
                        }
                    }).catch(function(error) {
                        console.error('Error:', error);
                        Vue.$toast.error(error.response?.data?.message || error.message ||
                            'Error creating return', 'Error');
                    });
                },
                showReturnDetailsModal(data) {
                    var self = this;
                    var url = "{{ route('sales-return.details') }}";
                    axios.get(url, {
                        params: {
                            return_id: data.id
                        }
                    }).then(function(response) {
                        if (response.data.status === 'Success') {
                            app.selectedReturn = response.data.data;
                            $('#return_details_modal').modal('show');
                        }
                    }).catch(function(error) {
                        console.error('Error:', error);
                    });
                },
                showApproveReturnModal(data) {
                    var self = this;
                    this.returnToApprove = data;
                    this.approvePassword = '';
                    this.returnDestination = 'godown';
                    this.selectedSalesmanId = null;
                    this.orderForApprove = data.order;
                    

                    this.returnAmount = parseFloat(data.total_amount) || 0;
                    if (this.returnToApprove.total_amount > data.order.balance_amount) {
                        this.actualReturnAmount = this.returnToApprove.total_amount - data.order.balance_amount;
                    } else {
                        this.actualReturnAmount = data.order.balance_amount;
                    }


                    var url = "{{ route('customer-credit-balance') }}";
                    axios.get(url, {
                        params: {
                            customer_id: data.customer_id
                        }
                    }).then(function(response) {
                        if (response.data.status === 'Success' && response.data.data) {
                            app.customerBalance = response.data.data;

                        }
                    }).catch(function(error) {
                        console.error('Error loading order details:', error);

                    });

                    $('#approve_return_modal').modal('show');
                },
                isRefundFromBalance(index) {
                    if (!this.orderForApprove || !this.orderForApprove.balance_amount) {
                        return false;
                    }
                    var refund = this.refundRows[index];
                    return refund && refund.refund_mode === 'order_balance';
                },
                getMaxRefundAmount() {
                    if (!this.returnToApprove) return 0;
                    var totalReturnAmount = parseFloat(this.returnToApprove.total_amount) || 0;
                    return totalReturnAmount;
                },
                getMaxRefundAmountForRow(index) {
                    if (!this.returnToApprove) return 0;
                    var totalReturnAmount = parseFloat(this.returnToApprove.total_amount) || 0;
                    var currentTotal = this.calculateRefundTotal();
                    var currentRowAmount = parseFloat(this.refundRows[index].refund_amount || 0);
                    // Allow up to the return amount minus what's already in other rows (excluding current row)
                    var maxForRow = totalReturnAmount - (currentTotal - currentRowAmount);
                    return Math.max(0, maxForRow);
                },
                isRefundAmountValid(refund, index) {
                    if (!this.returnToApprove) return true;
                    var amount = parseFloat(refund.refund_amount || 0);
                    var maxForRow = this.getMaxRefundAmountForRow(index);
                    // Check if amount is within valid range (0 to max for this row)
                    return amount >= 0 && amount <= maxForRow;
                },
                validateRefundAmounts() {
                    // This method is called on input to provide real-time feedback
                    // The validation is handled by isRefundAmountValid
                },
                validateRefundAmount(index) {
                    var refund = this.refundRows[index];
                    var maxAmount = this.getMaxRefundAmountForRow(index);
                    if (refund.refund_amount > maxAmount) {
                        refund.refund_amount = maxAmount;
                    }
                },
                isRefundTotalValid() {
                    if (!this.returnToApprove) return false;
                    var totalReturnAmount = parseFloat(this.returnToApprove.total_amount) || 0;
                    var currentTotal = this.returnAmount;
                    // Must equal exactly (allow small floating point differences)
                    return Math.abs(currentTotal - totalReturnAmount) < 0.01;
                },
                onReturnDestinationChange() {
                    if (this.returnDestination !== 'vehicle') {
                        this.selectedSalesmanId = null;
                    }
                },
                loadSalesmanList() {
                    var self = this;
                    axios.get('/api/get-users', {
                        params: {
                            platform: 'api',
                            user_type: ['salesman', 'sales_man']
                        }
                        
                    }).then(function(response) {
                        if (response.data.code === 0) {
                            app.salesmanList = response.data.data || [];
                        }
                    }).catch(function(error) {
                        console.error('Error loading salesmen:', error);
                    });
                },
                addRefundRow() {
                    this.refundRows.push({
                        refund_mode: 'cash',
                        refund_amount: 0
                    });
                },
                removeRefundRow(index) {
                    if (this.refundRows.length > 1) {
                        this.refundRows.splice(index, 1);
                    }
                },
                calculateRefundTotal() {
                    return this.refundRows.reduce((sum, row) => sum + parseFloat(row.refund_amount || 0), 0);
                },
                approveSalesReturn() {
                    if (!this.approvePassword) {
                        Vue.$toast.error('Please enter password', 'Error');
                        return;
                    }
                    {{-- if (this.refundRows.length === 0) {
                        Vue.$toast.error('Please add refund details', 'Error');
                        return;
                    } --}}

                    // Validate refund total equals return amount exactly
                    {{-- var totalRefund = this.calculateRefundTotal();
                    var returnAmount = parseFloat(this.returnToApprove.total_amount) || 0; --}}

                    {{-- if (Math.abs(totalRefund - returnAmount) >= 0.01) {
                        if (totalRefund > returnAmount) {
                            Vue.$toast.error('Total refund amount (' + totalRefund.toFixed(2) +
                                ') cannot exceed return amount (' +
                                returnAmount.toFixed(2) + ')', 'Error');
                        } else {
                            Vue.$toast.error('Total refund amount (' + totalRefund.toFixed(2) +
                                ') must equal return amount (' +
                                returnAmount.toFixed(2) + ')', 'Error');
                        }
                        return;
                    } --}}

                    if (this.returnDestination === 'vehicle' && !this.selectedSalesmanId) {
                        Vue.$toast.error('Please select a salesman when return destination is vehicle', 'Error');
                        return;
                    }

                    // Keep order_balance mode - backend will handle it correctly
                    {{-- var refundRowsToSend = this.refundRows.map(function(row) {
                        var refundRow = {
                            refund_mode: row.refund_mode, // Keep order_balance mode
                            refund_amount: row.refund_amount,
                            description: row.description || null
                        };
                        return refundRow;
                    }); --}}

                    var self = this;
                    var url = "{{ route('sales-return.approve') }}";
                    var data = {
                        return_id: this.returnToApprove.id,
                        user_id: this.user_id,
                        password: this.approvePassword,
                        return_destination: this.returnDestination,
                        salesman_id: this.selectedSalesmanId,
                        returnAmount: this.returnAmount
                        {{-- refund_rows: JSON.stringify(refundRowsToSend) --}}
                    };
                    axios.post(url, data).then(function(response) {
                        if (response.data.status === 'Success') {
                            Vue.$toast.success('Sales return approved successfully', 'Success');
                            $('#approve_return_modal').modal('hide');
                            app.getSalesReturnList();
                        } else {
                            Vue.$toast.error(response.data.message || 'Failed to approve return', 'Error');
                        }
                    }).catch(function(error) {
                        console.error('Error:', error);
                        Vue.$toast.error(error.response?.data?.message || error.message ||
                            'Error approving return', 'Error');
                    });
                },
                showRejectReturnModal(data) {
                    this.returnToReject = data;
                    this.rejectPassword = '';
                    $('#reject_return_modal').modal('show');
                },
                rejectSalesReturn() {
                    if (!this.rejectPassword) {
                        Vue.$toast.error('Please enter password', 'Error');
                        return;
                    }
                    var self = this;
                    var url = "{{ route('sales-return.reject') }}";
                    var data = {
                        return_id: this.returnToReject.id,
                        user_id: this.user_id,
                        password: this.rejectPassword
                    };
                    axios.post(url, data).then(function(response) {
                        if (response.data.status === 'Success') {
                            Vue.$toast.success('Sales return rejected successfully', 'Success');
                            $('#reject_return_modal').modal('hide');
                            app.getSalesReturnList();
                        } else {
                            Vue.$toast.error(response.data.message || 'Failed to reject return', 'Error');
                        }
                    }).catch(function(error) {
                        console.error('Error:', error);
                        Vue.$toast.error(error.response?.data?.message || error.message ||
                            'Error rejecting return', 'Error');
                    });
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
                this.getSalesReturnList();
                this.getLoggedUserInfo();
                this.loadSalesmanList();
            }
        });
    </script>
@endsection
