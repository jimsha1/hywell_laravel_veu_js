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
                                    <h4 class="page-title">Purchase Return</h4>
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
                                                placeholder="Search by Return No, Purchase No, Supplier" v-model="search"
                                                @input="getPurchaseReturnList" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <select class="form-select" v-model="return_status" @change="getPurchaseReturnList">
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
                                <!-- Purchase Return List -->
                                <div class="table-responsive custom-table">
                                    <table class="table" id="purchase_return_list">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Return No</th>
                                                <th>Purchase No</th>
                                                <th>Supplier</th>
                                                <th>Return Date</th>
                                                <th>Quantity</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!purchaseReturnList || !purchaseReturnList.length">
                                                <tr>
                                                    <td colspan="9" class="text-center">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in purchaseReturnList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    <td>
                                                        <a href="javascript:void(0);" @click="showReturnDetailsModal(data)"
                                                            style="color: blue; font-weight: 500;">
                                                            @{{ data.return_no || '-' }}
                                                        </a>
                                                    </td>
                                                    <td>@{{ data.purchase ? data.purchase.purchase_no : '-' }}</td>
                                                    <td>@{{ data.supplier ? data.supplier.supplier_name : '-' }}</td>
                                                    <td>@{{ formatDate(data.return_date) }}</td>
                                                    <td>@{{ data.total_quantity }}</td>
                                                    <td>@{{ parseFloat(data.total_amount || 0).toFixed(2) }}</td>
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
                        </div>

                        {{-- pagination --}}
                        <nav aria-label="pagination" v-if="pagination.last_page > 1">
                            <ul class="pagination justify-content-end">
                                <li class="page-item" v-if="pagination.current_page > 1">
                                    <a class="page-link" href="#" aria-label="Previous"
                                        @click.prevent="changePage(pagination.current_page - 1)">
                                        <span aria-hidden="true">&laquo;</span>
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
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        {{-- end pagination --}}
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Purchase Return Modal -->
        <div class="modal fade" id="create_return_modal" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg" style="min-width: 950px; max-height: 90vh;">
                <div class="modal-content" style="max-height: 90vh; display: flex; flex-direction: column;">
                    <div class="modal-header" style="flex-shrink: 0;">
                        <h5 class="modal-title">Create Purchase Return</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="modal-body" style="overflow-y: auto; flex: 1 1 auto; max-height: calc(90vh - 120px);">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Select Purchase</label>
                                <select class="form-control" id="select2-purchase-search" style="width: 100%;">
                                    <option value="">Select Purchase</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3" v-if="selectedPurchase && selectedPurchase.purchase_item">
                                <label class="form-label">Return Items</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Purchased Qty</th>
                                                <th>Available Qty</th>
                                                <th>Return Qty</th>
                                                <th>Price</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in selectedPurchase.purchase_item" :key="item.id">
                                                <td>@{{ item.product ? item.product.product_name : '-' }}</td>
                                                <td>@{{ item.qty }}</td>
                                                <td>@{{ getMaxReturnableQty(item) }}</td>
                                                <td>
                                                    <input type="number" class="form-control"
                                                        :class="{
                                                            'is-invalid': returnItems[item.id] > getMaxReturnableQty(item)
                                                        }"
                                                        v-model.number="returnItems[item.id]"
                                                        :max="getMaxReturnableQty(item)" min="0"
                                                        @input="validateReturnQty(item)" style="width: 120px;">
                                                    <small class="text-danger"
                                                        v-if="returnItems[item.id] > getMaxReturnableQty(item)">
                                                        Max: @{{ getMaxReturnableQty(item) }}
                                                    </small>
                                                </td>
                                                <td>@{{ getEffectiveItemPrice(item).toFixed(2) }}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" @click="addReturnItem(item)"
                                                        :disabled="!returnItems[item.id] || returnItems[item.id] <= 0 || returnItems[item.id] > getMaxReturnableQty(item)"
                                                        v-if="returnItems[item.id] > 0">
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
                                                <th>Price</th>
                                                <th>Total</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, index) in returnItemsList" :key="index">
                                                <td>@{{ item.product_name }}</td>
                                                <td>@{{ item.return_quantity }}</td>
                                                <td>@{{ parseFloat(item.price || 0).toFixed(2) }}</td>
                                                <td>@{{ parseFloat(item.total || 0).toFixed(2) }}</td>
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
                                                <th colspan="3" class="text-end">Total</th>
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
                        <button type="button" class="btn btn-primary" @click="createPurchaseReturn"
                            :disabled="returnItemsList.length === 0 || submitting">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            Create Return
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Return Details Modal -->
        <div class="modal fade" id="return_details_modal" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Purchase Return Details</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <template v-if="selectedReturn">
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <strong>Return No:</strong> @{{ selectedReturn.return_no || '-' }}
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Purchase No:</strong> @{{ selectedReturn.purchase ? selectedReturn.purchase.purchase_no : '-' }}
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Supplier:</strong> @{{ selectedReturn.supplier ? selectedReturn.supplier.supplier_name : '-' }}
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Status:</strong>
                                    <span v-if="selectedReturn.return_status === 'approved'"
                                        class="badge bg-success">Approved</span>
                                    <span v-else-if="selectedReturn.return_status === 'rejected'"
                                        class="badge bg-danger">Rejected</span>
                                    <span v-else class="badge bg-warning">Pending</span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Return Date:</strong> @{{ formatDate(selectedReturn.return_date) }}
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Total Quantity:</strong> @{{ selectedReturn.total_quantity }}
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Total Amount:</strong> @{{ parseFloat(selectedReturn.total_amount || 0).toFixed(2) }}
                                </div>
                                <div class="col-md-6 mb-2" v-if="selectedReturn.approved_by">
                                    <strong>Approved By:</strong> @{{ selectedReturn.approved_by ? (selectedReturn.approved_by.name || 'Admin') : '-' }}
                                </div>
                            </div>
                            <div class="row mb-3" v-if="selectedReturn.return_reason">
                                <div class="col-md-12">
                                    <strong>Return Reason:</strong>
                                    <p class="text-muted mb-0">@{{ selectedReturn.return_reason }}</p>
                                </div>
                            </div>
                            <div class="row mb-3" v-if="selectedReturn.rejection_reason">
                                <div class="col-md-12">
                                    <strong class="text-danger">Rejection Reason:</strong>
                                    <p class="text-danger mb-0">@{{ selectedReturn.rejection_reason }}</p>
                                </div>
                            </div>
                            <h6 class="mb-2">Returned Items</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
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
                                            <td>@{{ parseFloat(item.price || 0).toFixed(2) }}</td>
                                            <td>@{{ parseFloat(item.total || 0).toFixed(2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
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
                        <h5 class="modal-title">Approve Purchase Return</h5>
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
                                <strong>Supplier:</strong> @{{ returnToApprove.supplier ? returnToApprove.supplier.supplier_name : '-' }}
                            </div>
                            <div class="mb-3">
                                <strong>Return Amount:</strong> @{{ parseFloat(returnToApprove.total_amount || 0).toFixed(2) }}
                            </div>
                            <div class="mb-3" v-if="returnToApprove.supplier">
                                <strong>Supplier Balance:</strong> 
                                <span :class="returnToApprove.supplier.balance < 0 ? 'text-danger' : 'text-success'">
                                    @{{ parseFloat(returnToApprove.supplier.balance || 0).toFixed(2) }}
                                </span>
                            </div>
                            <div class="mb-3" v-if="returnToApprove.supplier">
                                <small class="text-muted d-block">
                                    @{{ parseFloat(returnToApprove.total_amount || 0).toFixed(2) }} will be deducted from supplier balance
                                </small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" v-model="approvePassword"
                                    placeholder="Enter password to approve" autocomplete="new-password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Refund Details</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Refund Mode</th>
                                                <th>Amount</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        {{-- <tbody>
                                            <tr v-for="(refund, index) in refundRows" :key="index">
                                                <td>
                                                    <select class="form-select" v-model="refund.refund_mode">
                                                        <option value="{{ config('constants.CASH') }}">Cash</option>
                                                        <option value="{{ config('constants.BANK_TRANSFER_ALRAJHI') }}">TRANSFER ALRAJHI</option>
                                                        <option value="{{ config('constants.BANK_TRANSFER_SAB') }}">TRANSFER SAB</option>
                                                        <option value="{{ config('constants.POS_ALRAJHIS') }}">POS ALRAJHIS</option>
                                                        <option value="{{ config('constants.POS_SAB') }}">POS SAB</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control"
                                                        :class="{ 'is-invalid': !isRefundAmountValid(refund, index) }"
                                                        v-model.number="refund.refund_amount"
                                                        :max="getMaxRefundAmountForRow(index)" min="0"
                                                        step="0.01" @input="validateRefundAmounts">
                                                    <small class="text-danger" v-if="!isRefundAmountValid(refund, index)">
                                                        Invalid amount
                                                    </small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-danger" @click="removeRefundRow(index)"
                                                        :disabled="refundRows.length === 1">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody> --}}
                                        <tfoot>
                                            <tr>
                                                <th>Total</th>
                                                <th>
                                                    @{{ parseFloat(calculateRefundTotal()).toFixed(2) }}
                                                    <small class="text-danger d-block"
                                                        v-if="calculateRefundTotal() > parseFloat(returnToApprove.total_amount)">
                                                        Exceeds return amount!
                                                    </small>
                                                    <small class="text-danger d-block"
                                                        v-else-if="calculateRefundTotal() < parseFloat(returnToApprove.total_amount)">
                                                        Total must equal return amount (@{{ parseFloat(returnToApprove.total_amount || 0).toFixed(2) }})
                                                    </small>
                                                    <small class="text-success d-block"
                                                        v-else-if="calculateRefundTotal() === parseFloat(returnToApprove.total_amount)">
                                                        ✓ Total matches return amount
                                                    </small>
                                                </th>
                                                <th>
                                                    <button class="btn btn-sm btn-primary" @click="addRefundRow"
                                                        :disabled="calculateRefundTotal() >= parseFloat(returnToApprove.total_amount)">
                                                        <i class="ti ti-plus"></i>
                                                    </button>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" @click="approvePurchaseReturn"
                            :disabled="submitting || !approvePassword || !isRefundTotalValid()">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
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
                        <h5 class="modal-title">Reject Purchase Return</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <template v-if="returnToReject">
                            <div class="mb-3">
                                <strong>Return No:</strong> @{{ returnToReject.return_no || '-' }}
                            </div>
                            <div class="mb-3">
                                <strong>Supplier:</strong> @{{ returnToReject.supplier ? returnToReject.supplier.supplier_name : '-' }}
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rejection Reason</label>
                                <textarea class="form-control" v-model="rejectReason" rows="2" placeholder="Enter reason for rejection"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password (Optional)</label>
                                <input type="password" class="form-control" v-model="rejectPassword"
                                    placeholder="Enter password if required" autocomplete="new-password">
                            </div>
                        </template>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" @click="rejectPurchaseReturn" :disabled="submitting">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
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
                submitting: false,
                base_url: "{{ url('/') }}",
                purchaseReturnList: [],
                selectedReturn: null,
                returnToApprove: null,
                returnToReject: null,
                refundRows: [{ refund_mode: 'cash', refund_amount: 0 }],
                search: '',
                return_status: '',
                pagination: {
                    total: 0,
                    per_page: 10,
                    from: 1,
                    to: 0,
                    current_page: 1,
                    last_page: 1
                },
                offset: 4,
                // Create Return Modal
                selectedPurchaseId: null,
                selectedPurchase: null,
                returnItems: {},
                returnItemsList: [],
                returnReason: '',
                // Approve & Reject
                approvePassword: '',
                rejectPassword: '',
                rejectReason: '',
                user_id: null
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
                }
            },
            methods: {
                getPurchaseReturnList: function(page = 1) {
                    var self = this;
                    self.loading = true;
                    var url = "{{ route('purchase-return.list') }}";
                    axios.get(url, {
                        params: {
                            page: page,
                            search: this.search,
                            return_status: this.return_status,
                            platform: 'web'
                        }
                    }).then(function(response) {
                        if (response.data.status === 'Success') {
                            self.purchaseReturnList = response.data.data.purchase_returns || [];
                            self.pagination = response.data.data.pagination || self.pagination;
                        }
                        self.loading = false;
                    }).catch(function(error) {
                        console.error('Error:', error);
                        self.loading = false;
                    });
                },
                // getSupplierList: function() {
                //     var self = this;
                //     axios.get('/api/supplier/list').then(function(response) {
                //         if (response.data.status === 'Success') {
                //             self.supplierList = response.data.data.suppliers || response.data.data || [];
                //         }
                //     }).catch(function(error) {
                //         console.error('Error fetching suppliers:', error);
                //     });
                // },
                showCreateReturnModal: function() {
                    this.resetCreateReturnForm();
                    $('#create_return_modal').modal('show');
                    this.$nextTick(function() {
                        this.initSelect2Purchase();
                    });
                },
                initSelect2Purchase: function() {
                    const $sel = $('#select2-purchase-search');
                    if ($sel.hasClass('select2-hidden-accessible')) {
                        $sel.select2('destroy');
                    }
                    $sel.removeData('select2');
                    $sel.off('select2:open select2:close select2:select change');
                    $sel.empty().append('<option value="">Select Purchase</option>');

                    var self = this;
                    $sel.select2({
                        dropdownParent: $('#create_return_modal'),
                        placeholder: 'Type to search purchase by purchase number or supplier',
                        allowClear: true,
                        width: '100%',
                        minimumInputLength: 0,
                        language: {
                            inputTooShort: function() { return 'Type to search...'; },
                            searching: function() { return 'Searching...'; },
                            noResults: function() { return 'No purchases found'; }
                        },
                        ajax: {
                            url: '/api/purchases-for-return',
                            dataType: 'json',
                            delay: 300,
                            type: 'GET',
                            data: function(params) {
                                return {
                                    search: params.term || '',
                                    platform: 'api',
                                    page: params.page || 1,
                                    per_page: 50
                                };
                            },
                            processResults: function(data, params) {
                                if (data.status === 'Success') {
                                    var purchases = data.data.purchases || [];
                                    var pagination = data.data.pagination || {};
                                    return {
                                        results: purchases.map(function(p) {
                                            return {
                                                id: p.id,
                                                text: (p.purchase_no || 'PUR-' + p.id) + ' - ' + (p.supplier ? p.supplier.supplier_name : 'N/A') + ' (Amount: ' + parseFloat(p.total || 0).toFixed(2) + ')',
                                                purchase: p
                                            };
                                        }),
                                        pagination: {
                                            more: pagination.more === true
                                        }
                                    };
                                }
                                return { results: [], pagination: { more: false } };
                            },
                            cache: false
                        }
                    });

                    $sel.on('select2:open', function() {
                        setTimeout(function() {
                            var $searchInput = $('.select2-container--open .select2-search__field');
                            if ($searchInput.length) {
                                $searchInput.focus();
                            }
                        }, 50);
                    });

                    $sel.on('change', function() {
                        var selectedData = $sel.select2('data');
                        if (selectedData && selectedData.length > 0 && selectedData[0].id) {
                            var purchase = selectedData[0].purchase;
                            self.selectedPurchaseId = selectedData[0].id;
                            self.selectedPurchase = purchase;
                            if (purchase && purchase.supplier_id) {
                                self.selectedSupplierId = purchase.supplier_id;
                            }
                            if (purchase && (!purchase.purchase_item || purchase.purchase_item.length === 0)) {
                                self.loadPurchaseDetails();
                            } else {
                                self.setDefaultReturnQuantities();
                            }
                        } else {
                            self.selectedPurchaseId = null;
                            self.selectedPurchase = null;
                            self.returnItems = {};
                            self.returnItemsList = [];
                        }
                    });
                },
                loadPurchaseDetails: function() {
                    if (!this.selectedPurchaseId) return;
                    var self = this;
                    axios.get('/api/purchases-for-return', {
                        params: {
                            platform: 'api',
                            search: this.selectedPurchaseId.toString(),
                            per_page: 100
                        }
                    }).then(function(response) {
                        if (response.data.status === 'Success') {
                            var purchases = response.data.data.purchases || [];
                            var purchase = purchases.find(p => p.id == self.selectedPurchaseId);
                            if (purchase) {
                                self.selectedPurchase = purchase;
                                if (purchase.supplier_id) {
                                    self.selectedSupplierId = purchase.supplier_id;
                                }
                                self.setDefaultReturnQuantities();
                            }
                        }
                    });
                },
                setDefaultReturnQuantities: function() {
                    var self = this;
                    this.returnItems = {};
                    if (this.selectedPurchase && this.selectedPurchase.purchase_item) {
                        this.selectedPurchase.purchase_item.forEach(function(item) {
                            self.$set(self.returnItems, item.id, 0);
                        });
                    }
                },
                getMaxReturnableQty: function(item) {
                    if (item && item.available_qty !== undefined && item.available_qty !== null) {
                        return parseInt(item.available_qty) || 0;
                    }
                    return parseInt(item.qty) || 0;
                },
                getEffectiveItemPrice: function(item) {
                    var qty = parseFloat(item.qty || 1);
                    var total = parseFloat(item.total || 0);
                    var taxAmount = parseFloat(item.tax_amount || 0);

                    if (qty > 0 && total > 0) {
                        return total / qty;
                    }

                    return parseFloat(item.unit_price || 0) + (qty > 0 ? (taxAmount / qty) : 0);
                },
                validateReturnQty: function(item) {
                    var max = this.getMaxReturnableQty(item);
                    var val = this.returnItems[item.id];
                    if (val > max) {
                        this.returnItems[item.id] = max;
                    } else if (val < 0) {
                        this.returnItems[item.id] = 0;
                    }
                },
                addReturnItem: function(item) {
                    var returnQty = parseInt(this.returnItems[item.id]) || 0;
                    if (returnQty <= 0) return;

                    var price = this.getEffectiveItemPrice(item);
                    var total = returnQty * price;

                    var existingIndex = this.returnItemsList.findIndex(r => r.purchase_item_id === item.id);
                    if (existingIndex > -1) {
                        this.returnItemsList[existingIndex].return_quantity = returnQty;
                        this.returnItemsList[existingIndex].price = price;
                        this.returnItemsList[existingIndex].total = total;
                    } else {
                        this.returnItemsList.push({
                            purchase_item_id: item.id,
                            product_id: item.product_id,
                            product_name: item.product ? item.product.product_name : 'Product #' + item.product_id,
                            return_quantity: returnQty,
                            price: price,
                            total: total
                        });
                    }
                },
                removeReturnItem: function(index) {
                    this.returnItemsList.splice(index, 1);
                },
                calculateReturnTotal: function() {
                    return this.returnItemsList.reduce(function(acc, item) {
                        return acc + (parseFloat(item.total) || 0);
                    }, 0);
                },
                resetCreateReturnForm: function() {
                    this.selectedPurchaseId = null;
                    this.selectedPurchase = null;
                    this.returnItems = {};
                    this.returnItemsList = [];
                    this.returnReason = '';
                    const $sel = $('#select2-purchase-search');
                    if ($sel.hasClass('select2-hidden-accessible')) {
                        $sel.val(null).trigger('change');
                    }
                },
                createPurchaseReturn: function() {
                    if (this.returnItemsList.length === 0) {
                        if (window.Vue && Vue.$toast) Vue.$toast.error('Please add items to return', 'Error');
                        else alert('Please add items to return');
                        return;
                    }
                    if (!this.selectedSupplierId) {
                        if (window.Vue && Vue.$toast) Vue.$toast.error('Please select a supplier', 'Error');
                        else alert('Please select a supplier');
                        return;
                    }

                    var self = this;
                    self.submitting = true;
                    var url = "{{ route('purchase-return.create') }}";
                    var data = {
                        purchase_id: this.selectedPurchaseId,
                        return_items: JSON.stringify(this.returnItemsList.map(item => ({
                            purchase_item_id: item.purchase_item_id,
                            product_id: item.product_id,
                            return_quantity: item.return_quantity,
                            price: item.price
                        }))),
                        return_reason: this.returnReason,
                        user_id: this.user_id,
                        returned_by: this.user_id
                    };

                    axios.post(url, data).then(function(response) {
                        self.submitting = false;
                        if (response.data.status === 'Success') {
                            if (window.Vue && Vue.$toast) Vue.$toast.success('Purchase return created successfully', 'Success');
                            $('#create_return_modal').modal('hide');
                            self.getPurchaseReturnList();
                            self.resetCreateReturnForm();
                        } else {
                            if (window.Vue && Vue.$toast) Vue.$toast.error(response.data.message || 'Failed to create return', 'Error');
                            else alert(response.data.message || 'Failed to create return');
                        }
                    }).catch(function(error) {
                        self.submitting = false;
                        console.error('Error:', error);
                        var msg = error.response?.data?.message || error.message || 'Error creating return';
                        if (window.Vue && Vue.$toast) Vue.$toast.error(msg, 'Error');
                        else alert(msg);
                    });
                },
                showReturnDetailsModal: function(data) {
                    var self = this;
                    var url = "{{ route('purchase-return.details') }}";
                    axios.get(url, {
                        params: { return_id: data.id }
                    }).then(function(response) {
                        if (response.data.status === 'Success') {
                            self.selectedReturn = response.data.data;
                            $('#return_details_modal').modal('show');
                        }
                    }).catch(function(error) {
                        console.error('Error:', error);
                    });
                },
                showApproveReturnModal: function(data) {
                    this.returnToApprove = data;
                    this.approvePassword = '';
                    // Auto-populate refund row with full return amount
                    var returnAmount = parseFloat(data.total_amount || 0);
                    this.refundRows = [{ refund_mode: 'cash', refund_amount: returnAmount }];
                    $('#approve_return_modal').modal('show');
                },

                addRefundRow: function() {
                    this.refundRows.push({ refund_mode: 'cash', refund_amount: 0 });
                },

                removeRefundRow: function(index) {
                    if (this.refundRows.length > 1) {
                        this.refundRows.splice(index, 1);
                    }
                },

                calculateRefundTotal: function() {
                    return this.refundRows.reduce(function(sum, row) {
                        return sum + parseFloat(row.refund_amount || 0);
                    }, 0);
                },

                getMaxRefundAmountForRow: function(index) {
                    if (!this.returnToApprove) return 0;
                    var totalReturnAmount = parseFloat(this.returnToApprove.total_amount || 0);
                    var currentTotal = this.calculateRefundTotal();
                    var currentRowAmount = parseFloat(this.refundRows[index].refund_amount || 0);
                    return Math.max(0, totalReturnAmount - (currentTotal - currentRowAmount));
                },

                isRefundAmountValid: function(refund, index) {
                    if (!this.returnToApprove) return true;
                    var amount = parseFloat(refund.refund_amount || 0);
                    var maxForRow = this.getMaxRefundAmountForRow(index);
                    return amount >= 0 && amount <= maxForRow;
                },

                isRefundTotalValid: function() {
                    if (!this.returnToApprove) return false;
                    var totalReturnAmount = parseFloat(this.returnToApprove.total_amount || 0);
                    return Math.abs(this.calculateRefundTotal() - totalReturnAmount) < 0.01;
                },

                validateRefundAmounts: function() {
                    // Real-time validation handled by isRefundAmountValid
                },

                // approvePurchaseReturn: function() {
                //     var self = this;
                //     self.submitting = true;
                //     var url = "{{ route('purchase-return.approve') }}";
                //     var data = {
                //         return_id: this.returnToApprove.id,
                //         user_id: this.user_id,
                //         password: this.approvePassword
                //     };
                //     axios.post(url, data).then(function(response) {
                //         self.submitting = false;
                //         if (response.data.status === 'Success') {
                //             if (window.Vue && Vue.$toast) Vue.$toast.success('Purchase return approved successfully', 'Success');
                //             $('#approve_return_modal').modal('hide');
                //             self.getPurchaseReturnList();
                //         } else {
                //             if (window.Vue && Vue.$toast) Vue.$toast.error(response.data.message || 'Failed to approve return', 'Error');
                //         }
                //     }).catch(function(error) {
                //         self.submitting = false;
                //         console.error('Error:', error);
                //         var msg = error.response?.data?.message || error.message || 'Error approving return';
                //         if (window.Vue && Vue.$toast) Vue.$toast.error(msg, 'Error');
                //     });
                // },

                approvePurchaseReturn: function() {
                if (!this.approvePassword) {
                    if (window.Vue && Vue.$toast) Vue.$toast.error('Please enter password', 'Error');
                    return;
                }
                if (!this.isRefundTotalValid()) {
                    var total = this.calculateRefundTotal();
                    var returnAmount = parseFloat(this.returnToApprove.total_amount || 0);
                    var msg = total > returnAmount
                        ? 'Total refund amount exceeds return amount'
                        : 'Total refund amount must equal return amount (' + returnAmount.toFixed(2) + ')';
                    if (window.Vue && Vue.$toast) Vue.$toast.error(msg, 'Error');
                    return;
                }

                var self = this;
                self.submitting = true;
                var url = "{{ route('purchase-return.approve') }}";
                var data = {
                    return_id: this.returnToApprove.id,
                    user_id: this.user_id,
                    password: this.approvePassword,
                    refund_rows: JSON.stringify(this.refundRows.map(function(row) {
                        return {
                            refund_mode: row.refund_mode,
                            refund_amount: row.refund_amount
                        };
                    }))
                };

                axios.post(url, data).then(function(response) {
                    self.submitting = false;
                    if (response.data.status === 'Success') {
                        if (window.Vue && Vue.$toast) Vue.$toast.success('Purchase return approved successfully', 'Success');
                        $('#approve_return_modal').modal('hide');
                        self.getPurchaseReturnList();
                    } else {
                        if (window.Vue && Vue.$toast) Vue.$toast.error(response.data.message || 'Failed to approve return', 'Error');
                    }
                }).catch(function(error) {
                    self.submitting = false;
                    var msg = error.response?.data?.message || error.message || 'Error approving return';
                    if (window.Vue && Vue.$toast) Vue.$toast.error(msg, 'Error');
                });
            },
                showRejectReturnModal: function(data) {
                    this.returnToReject = data;
                    this.rejectPassword = '';
                    this.rejectReason = '';
                    $('#reject_return_modal').modal('show');
                },
                rejectPurchaseReturn: function() {
                    var self = this;
                    self.submitting = true;
                    var url = "{{ route('purchase-return.reject') }}";
                    var data = {
                        return_id: this.returnToReject.id,
                        user_id: this.user_id,
                        password: this.rejectPassword,
                        rejection_reason: this.rejectReason
                    };
                    axios.post(url, data).then(function(response) {
                        self.submitting = false;
                        if (response.data.status === 'Success') {
                            if (window.Vue && Vue.$toast) Vue.$toast.success('Purchase return rejected successfully', 'Success');
                            $('#reject_return_modal').modal('hide');
                            self.getPurchaseReturnList();
                        } else {
                            if (window.Vue && Vue.$toast) Vue.$toast.error(response.data.message || 'Failed to reject return', 'Error');
                        }
                    }).catch(function(error) {
                        self.submitting = false;
                        console.error('Error:', error);
                        var msg = error.response?.data?.message || error.message || 'Error rejecting return';
                        if (window.Vue && Vue.$toast) Vue.$toast.error(msg, 'Error');
                    });
                },
                formatDate: function(date) {
                    if (!date) return '-';
                    if (typeof moment !== 'undefined') {
                        return moment(date).format('M/D/YYYY');
                    }
                    var d = new Date(date);
                    return isNaN(d.getTime()) ? date : (d.getMonth() + 1) + '/' + d.getDate() + '/' + d.getFullYear();
                },
                changePage: function(page) {
                    this.pagination.current_page = page;
                    this.getPurchaseReturnList(page);
                },
                getLoggedUserInfo: function() {
                    const userJson = window.localStorage.getItem('user');
                    if (userJson) {
                        try {
                            const user = JSON.parse(userJson);
                            this.user_id = user.data ? user.data.id : (user.id || null);
                        } catch (e) {}
                    }
                }
            },
            mounted: function() {
                this.getPurchaseReturnList();
                this.getLoggedUserInfo();
            }
        });
    </script>
@endsection
