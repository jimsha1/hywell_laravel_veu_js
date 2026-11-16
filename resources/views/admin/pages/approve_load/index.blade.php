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
                                    {{-- <h4 class="page-title">Load Approved</h4> --}}
                                    <h4 class="page-title">Vehicle Load</h4>
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
                                            <input type="text" class="form-control" placeholder="Search" v-model="search"
                                                @input="getLoadList">
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

                                                <th>Stock Code</th>
                                                <th>Sales Man</th>
                                                <th>Vehicle</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!loadList || !loadList.length">
                                                <tr>
                                                    <td colspan="7">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in loadList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    <td>
                                                        <a href="javascript:void(0);"
                                                            @click="showApproveLoadListModal(data)" style="color: blue;">
                                                            @{{ data.v_stock_code }}
                                                        </a>
                                                    </td>
                                                    <td>@{{ data.user?.name }}</td>

                                                    <td>@{{ data.vehicle?.vehicle_name || 'N/A' }}</td>

                                                    <td>
                                                        <span
                                                            v-if="data.day_status == 'started' && data.load_status === 'approved'"
                                                            class="badge bg-success">
                                                            Approved
                                                        </span>
                                                        <span
                                                            v-else-if="data.day_status == 'closed' && data.load_status === 'approved'"
                                                            class="badge bg-primary">
                                                            Closed
                                                        </span>
                                                        <span v-else-if="data.load_status === 'requested'"
                                                            class="badge bg-warning">
                                                            Requested
                                                        </span>
                                                        <span v-else-if="data.load_status === 'rejected'"
                                                            class="badge bg-danger">
                                                            Rejected
                                                        </span>

                                                        <span v-else class="badge bg-secondary">
                                                            Closing requested
                                                        </span>
                                                    </td>


                                                    <td>
                                                        <!-- Approve button -->
                                                        <button v-if="data.load_status === 'requested'"
                                                            class="btn btn-sm btn-outline-success me-1"
                                                            @click="showApproveLoadInitialModal(data)">
                                                            <i class="ti ti-check"></i> Approve
                                                        </button>


                                                        {{-- <button v-if="data.load_status === 'requested'"
                                                            class="btn btn-sm btn-success me-1"
                                                            @click="showApproveReStockLoadListModal(data)">
                                                            <i class="ti ti-check"></i> Approve
                                                        </button> --}}


                                                        <!-- Reject button -->
                                                        <button v-if="data.load_status === 'requested'"
                                                            class="btn btn-sm btn-outline-danger me-1"
                                                            @click="rejectItem(data, index)">
                                                            <i class="ti ti-x"></i> Reject
                                                        </button>


                                                        {{-- <button
                                                            v-if="data.is_re_requested === true && data.load_status === 'approved' && data.day_status === 'started'"
                                                            class="btn btn-sm btn-success me-1"
                                                            @click="approveReStockItem(data, index)">
                                                            <i class="ti ti-check"></i> Re Stock
                                                        </button> --}}

                                                        <button
                                                            v-if="data.is_re_requested === true && data.load_status === 'approved' && data.day_status === 'started'"
                                                            class="btn btn-sm btn-outline-success me-1"
                                                            @click="showApproveReStockLoadListModal(data)"
                                                            title="View re-stock items">
                                                            {{-- <i class="ti ti-eye"></i> --}}
                                                            <i class="ti ti-check"></i> Re Stock
                                                        </button>

                                                        <!-- Approve Return Stock button -->
                                                        <button
                                                            v-if="data.is_return_requested === true && data.load_status === 'approved' && data.day_status === 'started'"
                                                            class="btn btn-sm btn-outline-warning me-1"
                                                            @click="showApproveReturnStockLoadListModal(data)"
                                                            title="Approve return stock">
                                                            <i class="ti ti-rotate-2"></i> Approve Return
                                                        </button>

                                                        <!-- Return load button -->
                                                        <button v-if="data.day_status === 'closing_requested'"
                                                            class="btn btn-sm btn-outline-primary me-1"
                                                            @click="showReturnLoadModal(data)" title="Return load">
                                                            <i class="ti ti-arrow-back"></i> Return Load
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

        <!-- View commission -->
        <div class="modal fade" id="approve_load" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;">
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
                            <template v-if="selectedApproveLoad">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Available Qty</th>
                                            <th>Total Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- <tr v-for="item in selectedApproveLoad.vehicle_load_stock_items"
                                            :key="item.id">
                                            <td>@{{ item.product.product_name || 'N/A' }}</td>
                                            <td>@{{ item.available_qty }}</td>
                                            <td>@{{ item.total_qty }}</td>
                                        </tr> --}}
                                        <tr v-if="selectedApproveLoad.vehicle_load_stock_items.length === 0">
                                            <td colspan="3" class="text-center text-muted">No product available</td>
                                        </tr>
                                        <tr v-else v-for="item in selectedApproveLoad.vehicle_load_stock_items"
                                            :key="item.id">
                                            <td>@{{ item.product?.product_name || 'N/A' }}</td>
                                            <td>@{{ item.available_qty }}</td>
                                            <td>@{{ item.total_qty }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </template>
                            <template v-else>
                                <p>No data available.</p>
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
        <!-- /View commission -->


        <div class="modal fade" id="approve_load_re_stock" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;">
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
                            <template v-if="selectedApproveLoad">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Requested Qty</th>
                                            <th>Approved Qty</th>
                                            <th v-if="selectedApproveLoad.user.user_type=='outside_salesman'">Selling Price
                                            </th>
                                            <th v-if="selectedApproveLoad.user.user_type=='outside_salesman'">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in selectedApproveLoad.re_requested_stock_items"
                                            :key="item.id">
                                            <td>@{{ (item.product && item.product.product_name) ? item.product.product_name: 'N/A' }}</td>
                                            <td>@{{ item.requested_qty }}</td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm"
                                                    v-model="approvedQuantities[item.id]" :min="0"
                                                    :max="item.requested_qty" style="width: 100px;">
                                            </td>
                                            <td v-if="selectedApproveLoad.user.user_type=='outside_salesman'">
                                                @{{ item.product?.outside_selling_price }}</td>
                                            <td v-if="selectedApproveLoad.user.user_type=='outside_salesman'">
                                                @{{ getRowAmount(item).toFixed(2) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot v-if="selectedApproveLoad.user.user_type=='outside_salesman'">
                                        <tr class="fw-bold">
                                            <td colspan="4" class="text-end">Total</td>
                                            <td>@{{ getReRequestedGrandTotal().toFixed(2) }}</td>
                                        </tr>
                                        <tr class="fw-bold" v-if="outsidesalesmanBalanceAmount>0">
                                            <td colspan="4" class="text-end">Amount to be refunded from prev orders
                                            </td>
                                            <td>@{{ outsidesalesmanBalanceAmount }}</td>
                                        </tr>
                                    </tfoot>
                                </table>

                                <div class="col-md-6 mb-3" v-if="selectedApproveLoad.user.user_type=='outside_salesman'">
                                    <label class="col-form-label">Order Date</label>
                                    <input type="date" class="form-control" name="re_order_date"
                                        v-model="re_order_date">
                                </div>
                                <div class="col-md-12 mb-3" v-if="selectedApproveLoad.user.user_type=='outside_salesman'">
                                    <div class="card bg-light p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 fw-bold">Payment Details</h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                @click="addRePaymentMode">
                                                <i class="ti ti-plus"></i> Add Payment Mode
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm mb-2">
                                                <thead>
                                                    <tr>
                                                        <th>Payment Mode <span class="text-danger">*</span></th>
                                                        <th>Amount <span class="text-danger">*</span></th>
                                                        <th style="width: 50px;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(payment, index) in re_paymentModes" :key="index">
                                                        <td>
                                                            <select class="form-select form-select-sm"
                                                                v-model="payment.payment_mode">
                                                                <option value="">-Select Payment Mode-</option>
                                                                <option value="CASH">CASH</option>
                                                                <option value="ALRAJHI">ALRAJHI</option>
                                                                <option value="SAB">SAB</option>
                                                                <option value="Credit">Credit</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm"
                                                                v-model.number="payment.amount" :min="0"
                                                                step="any" placeholder="0.00">
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                                @click="removeRePaymentMode(index)"
                                                                v-if="re_paymentModes.length > 1">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td class="text-end fw-bold">Total Payment:</td>
                                                        <td colspan="2" class="fw-bold text-success">
                                                            @{{ getReTotalPayment().toFixed(2) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-end fw-bold">Remaining Credit:</td>
                                                        <td colspan="2" class="fw-bold text-danger">
                                                            @{{ getReRemainingBalance().toFixed(2) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template v-else>
                                <p>No data available.</p>
                            </template>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="d-flex align-items-center justify-content-end m-0">
                            <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                            <button class="btn btn-success" @click="approveReStockItemFromModal">Approve</button>
                            <button class="btn btn-danger" @click="rejectReStockItemFromModal">Reject</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Initial Approve Load Modal -->
        <div class="modal fade" id="approve_load_initial" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Approve Vehicle Load</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card-body overflow-hidden">
                            <template v-if="selectedApproveLoad">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Requested Qty</th>
                                            <th>Approved Qty</th>
                                            <th v-if="selectedApproveLoad.user.user_type=='outside_salesman'">Selling Price
                                            </th>
                                            <th v-if="selectedApproveLoad.user.user_type=='outside_salesman'">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-if="!selectedApproveLoad.vehicle_load_stock_items || selectedApproveLoad.vehicle_load_stock_items.length === 0">
                                            <td colspan="3" class="text-center text-muted">No product available</td>
                                        </tr>
                                        <tr v-else v-for="item in selectedApproveLoad.vehicle_load_stock_items"
                                            :key="item.id">
                                            <td>@{{ item.product?.product_name || 'N/A' }}</td>
                                            <td>@{{ item.total_qty }}</td>

                                            <td>
                                                <input type="number" class="form-control form-control-sm"
                                                    v-model="approvedQuantities[item.id]" :min="0"
                                                    :max="item.total_qty" style="width: 100px;">
                                            </td>

                                            <td v-if="selectedApproveLoad.user.user_type=='outside_salesman'">
                                                @{{ item.product?.outside_selling_price }}</td>
                                            <td v-if="selectedApproveLoad.user.user_type=='outside_salesman'">
                                                @{{ getRowAmount(item).toFixed(2) }}
                                            </td>

                                        </tr>


                                    </tbody>
                                    <tfoot v-if="selectedApproveLoad.user.user_type=='outside_salesman'">
                                        <tr class="fw-bold">
                                            <td colspan="4" class="text-end">Total</td>
                                            <td>@{{ getGrandTotal(selectedApproveLoad.user.id).toFixed(2) }}</td>
                                        </tr>
                                        <tr class="fw-bold" v-if="outsidesalesmanBalanceAmount>0">
                                            <td colspan="4" class="text-end">Amount to be refunded from prev orders
                                            </td>
                                            <td>@{{ outsidesalesmanBalanceAmount }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <div class="col-md-6 mb-3">
                                    <label class="col-form-label">Approval Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="order_date" v-model="order_date">
                                </div>
                                <div class="col-md-12 mb-3" v-if="selectedApproveLoad.user.user_type=='outside_salesman'">
                                    <div class="card bg-light p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 fw-bold">Payment Details</h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                @click="addPaymentMode">
                                                <i class="ti ti-plus"></i> Add Payment Mode
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm mb-2">
                                                <thead>
                                                    <tr>
                                                        <th>Payment Mode <span class="text-danger">*</span></th>
                                                        <th>Amount <span class="text-danger">*</span></th>
                                                        <th style="width: 50px;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(payment, index) in paymentModes" :key="index">
                                                        <td>
                                                            <select class="form-select form-select-sm"
                                                                v-model="payment.payment_mode">
                                                                <option value="">-Select Payment Mode-</option>
                                                                <option value="CASH">CASH</option>
                                                                <option value="ALRAJHI">ALRAJHI</option>
                                                                <option value="SAB">SAB</option>
                                                                <option value="Credit">Credit</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm"
                                                                v-model.number="payment.amount" :min="0"
                                                                step="any" placeholder="0.00">
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                                @click="removePaymentMode(index)"
                                                                v-if="paymentModes.length > 1">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td class="text-end fw-bold">Total Payment:</td>
                                                        <td colspan="2" class="fw-bold text-success">
                                                            @{{ getTotalPayment().toFixed(2) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-end fw-bold">Remaining Credit:</td>
                                                        <td colspan="2" class="fw-bold text-danger">
                                                            @{{ getRemainingBalance().toFixed(2) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template v-else>
                                <p>No data available.</p>
                            </template>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="d-flex align-items-center justify-content-end m-0">
                            <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                            <button class="btn btn-success" @click="approveItemFromModal">Approve</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Return Load Modal -->
        <div class="modal fade" id="return_load" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Return Vehicle Load</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card-body overflow-hidden">
                            <template v-if="selectedApproveLoad">
                                <table class="table">
                                    <thead v-if="selectedApproveLoad.user.user_type!='outside_salesman'">
                                        <tr>
                                            <th>Product</th>
                                            <th>Return Qty</th>
                                            {{-- <th v-if="selectedApproveLoad.user.user_type=='outside_salesman'">Selling Price
                                            </th>
                                            <th v-if="selectedApproveLoad.user.user_type=='outside_salesman'">Amount</th> --}}
                                        </tr>

                                    </thead>
                                    <thead v-if="selectedApproveLoad.user.user_type=='outside_salesman'">
                                        <tr>
                                            <th>Order</th>
                                            <th>Total Amount</th>
                                            <th>Paid Amount</th>
                                            <th>Balance Amount</th>
                                            <th>Return Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody v-if="selectedApproveLoad.user.user_type!='outside_salesman'">
                                        <tr
                                            v-if="!selectedApproveLoad.vehicle_load_stock_items || selectedApproveLoad.vehicle_load_stock_items.length === 0">
                                            <td colspan="2" class="text-center text-muted">No product available</td>
                                        </tr>
                                        <tr v-else v-for="item in selectedApproveLoad.vehicle_load_stock_items"
                                            :key="item.id">
                                            <td>@{{ item.product?.product_name || 'N/A' }}</td>
                                            <td>@{{ item.available_qty }}</td>
                                            {{-- <td v-if="selectedApproveLoad.user.user_type=='outside_salesman'">
                                                @{{ item.product?.outside_selling_price || 'N/A' }}
                                            </td>
                                            <td v-if="selectedApproveLoad.user.user_type=='outside_salesman'">
                                                @{{ (item.available_qty * (item.product?.outside_selling_price || 0)).toFixed(2) }}
                                            </td> --}}

                                        </tr>

                                    </tbody>
                                    <tbody v-if="selectedApproveLoad.user.user_type=='outside_salesman'">
                                        <tr v-for="order in returnedOrders">
                                            <td>
                                                <a href="javascript:void(0)" class="text-primary"
                                                    @click="knowTheDetails(order)">@{{ order.order_no }} </a>
                                            </td>
                                            <td>
                                                @{{ order.order_total_amount }}
                                            </td>
                                            <td>
                                                @{{ order.order_paid_amount }}
                                            </td>
                                            <td>
                                                @{{ order.order_balance_amount }}
                                            </td>
                                            <td>
                                                @{{ order.totalPrice }}
                                            </td>

                                        </tr>
                                    </tbody>
                                    <tfoot v-if="selectedApproveLoad.user.user_type=='outside_salesman'">
                                        <tr class="fw-bold">
                                            <td colspan="4" class="text-end">Total</td>
                                            <td>@{{ ordersTotalAmount.toFixed(2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <div class="col-md-6 mb-3" v-if="selectedApproveLoad.user.user_type=='outside_salesman'">
                                    <label class="col-form-label">Return Date</label>
                                    <input type="date" class="form-control" name="order_date" v-model="return_date">
                                </div>
                                {{-- <div class="col-md-6 mb-3"
                                    v-if="selectedApproveLoad.user.user_type=='outside_salesman' && customerCreditBalance>0">
                                    <label>Payment Mode <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="refund_mode" required>
                                        <option value="">-Select Payment Mode</option>
                                        <option value="CASH">CASH</option>
                                        <option value="ALRAJHI">ALRAJHI</option>
                                        <option value="SAB"> SAB</option>
                                        <option value="Credit">Credit</option>

                                    </select>

                                </div>
                                <div class="col-md-12 mb-3"
                                    v-if="selectedApproveLoad.user.user_type=='outside_salesman' && payment_mode!='Credit'">
                                    <label>Refund Amount <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control form-control-sm" v-model="return_amount"
                                        :min="0" style="width: 100px;" disabled>
                                   
                                </div> --}}


                                {{-- <div class="col-md-12 mb-3"
                                    v-if="selectedApproveLoad.user.user_type == 'outside_salesman'">

                                    <div class="card bg-light p-3">

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 fw-bold">Refund Payment Details</h6>

                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                @click="addRefundPaymentMode">

                                                <i class="ti ti-plus"></i> Add Payment Mode
                                            </button>
                                        </div>

                                        <div class="table-responsive">

                                            <table class="table table-bordered table-sm mb-2">

                                                <thead>
                                                    <tr>
                                                        <th>
                                                            Payment Mode
                                                            <span class="text-danger">*</span>
                                                        </th>

                                                        <th>
                                                            Amount
                                                            <span class="text-danger">*</span>
                                                        </th>

                                                        <th style="width: 50px;">
                                                            Action
                                                        </th>
                                                    </tr>
                                                </thead>

                                                <tbody>

                                                    <tr v-for="(payment, index) in refundPaymentModes"
                                                        :key="index">

                                                        <td>

                                                            <select class="form-select form-select-sm"
                                                                v-model="payment.payment_mode">

                                                                <option value="">
                                                                    -Select Payment Mode-
                                                                </option>

                                                                <option value="CASH">
                                                                    CASH
                                                                </option>

                                                                <option value="ALRAJHI">
                                                                    ALRAJHI
                                                                </option>

                                                                <option value="SAB">
                                                                    SAB
                                                                </option>

                                                            </select>

                                                        </td>

                                                        <td>

                                                            <input type="number" class="form-control form-control-sm"
                                                                v-model.number="payment.amount" min="0"
                                                                step="any" placeholder="0.00">

                                                        </td>

                                                        <td class="text-center">

                                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                                @click="removeRefundPaymentMode(index)"
                                                                v-if="refundPaymentModes.length > 1">

                                                                <i class="ti ti-trash"></i>

                                                            </button>

                                                        </td>

                                                    </tr>

                                                </tbody>

                                                <tfoot>
                                                    <tr>
                                                        <td class="text-end fw-bold">
                                                            Refund Amount:
                                                        </td>

                                                        <td colspan="2" class="fw-bold">
                                                            @{{ Number(totalOrdersRefundAmount || 0).toFixed(2) }}
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <td class="text-end fw-bold">
                                                            Total Payment:
                                                        </td>

                                                        <td colspan="2" class="fw-bold text-success">
                                                            @{{ getRefundPaymentTotal().toFixed(2) }}
                                                        </td>
                                                    </tr>
                                                </tfoot>

                                            </table>

                                        </div>

                                    </div>

                                </div> --}}
                                <div class="col-md-12 mb-3"
                                    v-if="selectedApproveLoad.user.user_type == 'outside_salesman'">
                                    <label class="form-label">Refund Details</label>
                                    <div class="alert alert-warning mb-2" v-for="order in returnedOrders">
                                        <small>
                                            <i class="ti ti-alert-circle"></i>
                                            For order @{{ order.order_no }}, @{{ order.orderMessage }}
                                        </small>
                                    </div>

                                    <div class="alert alert-warning mb-2"
                                        v-if="customerCreditBalance > 0 && orderRefundAmount>0 && customerCreditBalance-orderRefundAmount>0 ">
                                        <small>
                                            <i class="ti ti-alert-circle"></i>
                                            Remaining amount @{{ orderRefundAmount }} deducted from the remaining credit
                                            balance
                                            {{-- @{{ customerCreditBalance }} --}}

                                        </small>
                                    </div>
                                    <div class="alert alert-warning mb-2"
                                        v-else-if="customerCreditBalance > 0 && orderRefundAmount>0 && customerCreditBalance-orderRefundAmount<0 ">
                                        <small>
                                            <i class="ti ti-alert-circle"></i>
                                            Prevoius Credit Balance @{{ customerCreditBalance }} deducted from the return amount
                                            @{{ orderRefundAmount }}

                                        </small>
                                    </div>
                                    <div v-else-if="customerCreditBalance>0 && orderRefundAmount==0"
                                        class="alert alert-warning mb-2">
                                        <small>
                                            <i class="ti ti-alert-circle"></i>
                                            No Return Amount
                                        </small>
                                    </div>
                                    <div class="alert alert-warning mb-2"
                                        v-else-if="customerCreditBalance==0 && orderRefundAmount>0 &&
                                        returnedOrders.length==0">
                                        <small>
                                            <i class="ti ti-alert-circle"></i>
                                            @{{ orderRefundAmount }} will be refunded to the customer.
                                        </small>
                                    </div>
                                </div>

                            </template>
                            <template v-else>
                                <p>No data available.</p>
                            </template>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="d-flex align-items-center justify-content-end m-0">
                            <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                            <button class="btn btn-primary" @click="returnLoadFromModal">Return Load</button>
                            <button class="btn btn-danger" @click="rejectReturnLoad">Reject</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approve Return Stock Modal -->
        <div class="modal fade" id="approve_load_return_stock" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Approve Return Stock</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card-body overflow-hidden">
                            <template v-if="selectedApproveLoad">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Requested Return Qty</th>
                                            <th>Approved Return Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in selectedApproveLoad.return_requested_stock_items"
                                            :key="item.id">
                                            <td>@{{ (item.product && item.product.product_name) ? item.product.product_name: 'N/A' }}</td>
                                            <td>@{{ item.requested_qty }}</td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm"
                                                    v-model="approvedReturnQuantities[item.id]" :min="0"
                                                    :max="item.requested_qty" style="width: 100px;">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </template>
                            <template v-else>
                                <p>No data available.</p>
                            </template>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="d-flex align-items-center justify-content-end m-0">
                            <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                            <button class="btn btn-warning" @click="approveReturnStockFromModal">Approve Return</button>
                            <button class="btn btn-danger" @click="rejectReturnLoadFromModal">Reject</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="showDetails" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            Returned Products
                        </h5>

                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>

                    <div class="modal-body">

                        <!-- Order information -->
                        <div v-if="selectedReturnOrder" class="mb-3">
                            <strong>Order No:</strong>
                            @{{ selectedReturnOrder.order_no }}
                        </div>

                        <table class="table table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Ordered Qty</th>
                                    <th>Returned Qty</th>
                                    <th>Price</th>
                                </tr>
                            </thead>

                            <tbody>

                                <template
                                    v-if="selectedReturnOrder &&
                                   selectedReturnOrder.items &&
                                   selectedReturnOrder.items.length">

                                    <tr v-for="(item, index) in selectedReturnOrder.items" :key="item.product_id">

                                        <td>
                                            @{{ index + 1 }}
                                        </td>

                                        <td>
                                            @{{ item.product_name }}
                                        </td>

                                        <td>
                                            @{{ item.ordered_qty ?? 0 }}
                                        </td>

                                        <td>
                                            @{{ item.return_qty ?? 0 }}
                                        </td>

                                        <td>
                                            @{{ item.total_price ?? '--' }}
                                        </td>

                                    </tr>

                                </template>

                                <tr v-else>
                                    <td colspan="5" class="text-center text-muted">
                                        No returned products available
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                    <div class="modal-footer">

                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            Close
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

                loadList: [],
                user_id: '',
                modelBtn: '',
                modelHeading: '',
                selectedApproveLoad: '',
                approvedQuantities: {},
                selectedDataSumAmount: 0,
                approvedReturnQuantities: {},
                order_date: '',
                re_order_date: '',
                payment_mode: '',
                re_payment_mode: '',
                payment_amount: 0,
                re_payment_amount: 0,
                outsidesalesmanBalanceAmount: 0,
                paymentModes: [{
                    payment_mode: '',
                    amount: 0
                }],
                re_paymentModes: [{
                    payment_mode: '',
                    amount: 0
                }],
                grand_total: 0,
                re_request_grand_total: 0,
                grand_reverse_total: 0,
                balance_amount: 0,
                returnedOrders: [],
                paidOrders: [],
                customerCreditBalance: 0,
                completeData: {},
                orderRefundAmount: 0,
                totalOrdersRefundAmount: 0,

                return_date: '',
                refund_mode: '',
                return_amount: 0,
                refundPaymentModes: [{
                    payment_mode: '',
                    amount: 0
                }],
                ordersTotalAmount: 0,
                orderPaidAmount: 0,
                ordersBalanceAmount: 0,
                paid: 0,
                search: '',
                page_length: '10',
                selectedReturnOrder: [],

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

                {{-- addRefundPaymentMode() {
                    this.refundPaymentModes.push({
                        payment_mode: '',
                        amount: 0
                    });
                }, --}}

                {{-- removeRefundPaymentMode(index) {
                    if (this.refundPaymentModes.length > 1) {
                        this.refundPaymentModes.splice(index, 1);
                    }
                }, --}}

                {{-- getRefundPaymentTotal() {
                    return this.refundPaymentModes.reduce((sum, payment) => {
                        if (
                            payment.payment_mode &&
                            payment.payment_mode !== 'Credit'
                        ) {
                            return sum + (Number(payment.amount) || 0);
                        }

                        return sum;
                    }, 0);
                }, --}}


                knowTheDetails: function(order) {
                    this.selectedReturnOrder = order;

                    $('#showDetails').modal('show');
                },
                changePage: function(page) {
                    this.getLoadList(page);
                },

                getLoadList: function(page = 1) {
                    const url = "/api/vehicle-load/list";

                    axios.get(url, {
                        params: {
                            page: page,
                            page_length: this.page_length,
                            search: this.search,
                        }
                    }).then(response => {
                        app.loadList = response.data.data.vehicleLoad;
                        app.pagination = response.data.data.pagination;
                        app.loading = false;
                    });
                },


                approveReStockItem(data) {
                    Swal.fire({
                        title: "Are you sure you want to approve " + "?",
                        text: "You won't be able to approve this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, approve it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/approve_extra_load_to_vehicle", {
                                    data: {
                                        id: data.id,
                                        data: data,
                                        user_id: this.user_id
                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.log("Error approve load:", error);
                                });
                        }
                    });
                },

                rejectItem(data) {
                    Swal.fire({
                        title: "Are you sure you want to reject " + "?",
                        text: "You won't be able to reject this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, reject it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/reject/vehicleLoad", {
                                    data: {
                                        id: data.id,
                                        user_id: this.user_id
                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.log("Error approve load:", error);
                                });
                        }
                    });
                },

                showAPIResponse(response) {
                    if (response.data.code == 0) {
                        Vue.$toast.success(response.data.message, 'Success Alert');
                        this.getLoadList();
                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                    }
                },

                showApproveLoadListModal(data) {
                    if (data && data.id > 0) {
                        this.selectedApproveLoad = data;
                        $('#approve_load').modal('show');
                    }
                },

                showApproveLoadInitialModal(data) {
                    if (data && data.id > 0) {
                        this.selectedApproveLoad = data;
                        this.paymentModes = [{
                            payment_mode: '',
                            amount: 0
                        }];
                        this.order_date = '';
                        // Initialize approved quantities with requested quantities as default
                        this.approvedQuantities = {};
                        this.selectedDataSumAmount = 0;
                        if (data.vehicle_load_stock_items && data.vehicle_load_stock_items.length > 0) {
                            data.vehicle_load_stock_items.forEach(item => {
                                this.approvedQuantities[item.id] = item.total_qty || 0;
                                this.selectedDataSumAmount += Number(item.total_qty * item.product
                                    .outside_selling_price);

                            });
                        }



                        axios.get('/api/get-customer-pending-amount', {
                            params: {
                                selectedSalesmanId: data.user_id
                            }
                        }).then(function(response) {

                            app.outsidesalesmanBalanceAmount = response.data.data;
                            if (app.outsidesalesmanBalanceAmount < 0)
                                app.outsidesalesmanBalanceAmount = -app.outsidesalesmanBalanceAmount;
                            else app.outsidesalesmanBalanceAmount = 0;

                            $('#approve_load_initial').modal('show');

                        }).catch(error => {
                            console.error('Error loading salesmen:', error);
                            Vue.$toast.error('Failed to load salesmen.', 'Error');
                        });




                    }
                },

                getTotalPaymentAmount(approvedQuantities, outside_selling_price, total_qty) {
                    console.log(approvedQuantities);
                    deducted_qty = total_qty - approvedQuantity;

                    deducted_amount = deducted_qty * outside_selling_price;

                    this.selectedDataSumAmount = this.selectedDataSumAmount - deducted_amount;


                },
                getRowAmount(item) {
                    const qty = Number(this.approvedQuantities[item.id]) || 0;
                    const price = Number(item.product?.outside_selling_price) || 0;

                    return qty * price;
                },
                getGrandTotal(user_id) {
                    if (!this.selectedApproveLoad?.vehicle_load_stock_items) {
                        return 0;
                    }

                    const credit = Number(this.outsidesalesmanBalanceAmount) || 0;


                    return this.selectedApproveLoad.vehicle_load_stock_items.reduce((total, item, credit) => {
                        const qty = Number(this.approvedQuantities[item.id]) || 0;
                        const price = Number(item.product?.outside_selling_price) || 0;


                        this.grand_total = total + (qty * price);

                        return total + (qty * price);

                    }, 0);
                },

                getReverseGrandTotal() {
                    console.log(this.selectedApproveLoad?.vehicle_load_stock_items);
                    if (!this.selectedApproveLoad?.vehicle_load_stock_items) {
                        return 0;
                    }

                    return this.selectedApproveLoad.vehicle_load_stock_items.reduce((total, item) => {
                        console.log("item", item);
                        const qty = Number(item.available_qty) || 0;
                        const price = Number(item.product?.outside_selling_price) || 0;

                        this.grand_reverse_total = total + (qty * price);
                        this.balance_amount = this.grand_reverse_total - this.return_amount;


                        return total + (qty * price);
                    }, 0);


                },
                getReRequestedGrandTotal() {
                    if (!this.selectedApproveLoad?.re_requested_stock_items) {
                        return 0;
                    }
                    const credit = Number(this.outsidesalesmanBalanceAmount) || 0;

                    return this.selectedApproveLoad.re_requested_stock_items.reduce((total, item) => {
                        const qty = Number(this.approvedQuantities[item.id]) || 0;
                        const price = Number(item.product?.outside_selling_price) || 0;

                        this.re_request_grand_total = total + (qty * price);
                        return total + (qty * price);
                    }, 0);
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
                    }
                },
                getTotalPayment() {
                    return this.paymentModes.reduce((sum, item) => {
                        if (item.payment_mode && item.payment_mode !== 'Credit') {
                            return sum + (Number(item.amount) || 0);
                        }
                        return sum;
                    }, 0);
                },
                getRemainingBalance() {
                    const total = this.getGrandTotal();
                    const paid = this.getTotalPayment();
                    const credit = Number(this.outsidesalesmanBalanceAmount) || 0;
                    app.paid = paid;

                    return Math.max(0, total - paid - credit);
                },

                addRePaymentMode() {
                    this.re_paymentModes.push({
                        payment_mode: '',
                        amount: 0
                    });
                },
                removeRePaymentMode(index) {
                    if (this.re_paymentModes.length > 1) {
                        this.re_paymentModes.splice(index, 1);
                    }
                },
                getReTotalPayment() {
                    return this.re_paymentModes.reduce((sum, item) => {
                        if (item.payment_mode && item.payment_mode !== 'Credit') {
                            return sum + (Number(item.amount) || 0);
                        }
                        return sum;
                    }, 0);
                },
                getReRemainingBalance() {

                    const total = this.getReRequestedGrandTotal();
                    const paid = this.getReTotalPayment();
                    const credit = Number(this.outsidesalesmanBalanceAmount) || 0;

                    return Math.max(0, total - paid - credit);
                },

                approveItemFromModal() {
                    if (!this.selectedApproveLoad || !this.selectedApproveLoad.id) {
                        Vue.$toast.error('Please select a vehicle load', 'Error Alert');
                        return;
                    }

                    let validPayments = [];
                    if (this.selectedApproveLoad.user.user_type == 'outside_salesman') {
                        if (!this.order_date) {
                            Vue.$toast.error('Please choose order date', 'Error Alert');
                            return;
                        }
                        const totalPaid = this.getTotalPayment();
                        const amountToPay = Math.max(0, this.grand_total - this.outsidesalesmanBalanceAmount);
                        validPayments = this.paymentModes.filter(p => p.payment_mode && p.payment_mode !== '');
                        if (validPayments.length === 0 && totalPaid > 0) {
                            Vue.$toast.error('Please select at least one payment mode', 'Error Alert');
                            return;
                        }

                        for (let p of validPayments) {
                            if (p.payment_mode !== 'Credit' && (!p.amount || Number(p.amount) <= 0)) {
                                Vue.$toast.error('Please enter a valid amount for selected payment mode (' + p
                                    .payment_mode + ')', 'Error Alert');
                                return;
                            }
                        }


                        {{-- alert(totalPaid);
                        alert(this.grand_total);
                        alert(this.outsidesalesmanBalanceAmount);
                        alert(amountToPay); --}}
                        if (totalPaid > amountToPay) {


                            Vue.$toast.error(
                                'Payment amount cannot be greater than amount to pay',
                                'Error Alert'
                            );
                            return;
                        }

                    }

                    const totalPaidAmount = this.getTotalPayment();
                    const flag = 0;
                    // Prepare approved items array
                    const approvedItems = [];
                    if (this.selectedApproveLoad.vehicle_load_stock_items && this.selectedApproveLoad
                        .vehicle_load_stock_items.length > 0) {
                        this.selectedApproveLoad.vehicle_load_stock_items.forEach(item => {
                            const approvedQty = this.approvedQuantities[item.id] || 0;

                            if (approvedQty > item.total_qty) {
                                Vue.$toast.error(
                                    'Approved quantity cannot exceed requested quantity for product: ' +
                                    (item.product?.product_name || 'N/A'), 'Error Alert');
                                const flag = 1;
                            }

                            {{-- if (approvedQty > 0) { --}}
                            approvedItems.push({
                                id: item.id,
                                product_id: item.product_id,
                                approved_qty: approvedQty,
                                requested_qty: item.total_qty || 0,

                            });
                            {{-- } --}}
                        });
                    }

                    if (approvedItems.length === 0) {
                        Vue.$toast.error('Please enter at least one approved quantity', 'Error Alert');
                        return;
                    }
                    if (flag == 1) {
                        return;
                    }

                    // Close modal first
                    $('#approve_load_initial').modal('hide');

                    // Show confirmation
                    Swal.fire({
                        title: "Are you sure you want to approve?",
                        text: "You won't be able to undo this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, approve it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/approve/vehicleLoad", {
                                    data: {
                                        id: this.selectedApproveLoad.id,
                                        approved_items: approvedItems,
                                        user_id: this.user_id,
                                        created_user: this.selectedApproveLoad.user.id,
                                        payment_mode: validPayments[0]?.payment_mode ?? 'NA',
                                        payment_amount: totalPaidAmount,
                                        credit: Number(this.outsidesalesmanBalanceAmount),
                                        payment_modes: validPayments,
                                        order_date: this.order_date
                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.log("Error approve load:", error);
                                    Vue.$toast.error('Failed to approve load', 'Error Alert');
                                });
                        }
                    });
                },

                showApproveReStockLoadListModal(data) {
                    if (data && data.id > 0) {
                        this.selectedApproveLoad = data;
                        this.re_paymentModes = [{
                            payment_mode: '',
                            amount: 0
                        }];
                        this.re_order_date = '';
                        // Initialize approved quantities with requested quantities as default
                        this.approvedQuantities = {};
                        if (data.re_requested_stock_items && data.re_requested_stock_items.length > 0) {
                            data.re_requested_stock_items.forEach(item => {
                                this.approvedQuantities[item.id] = item.requested_qty || 0;
                            });
                        }



                        axios.get('/api/get-customer-pending-amount', {
                            params: {
                                selectedSalesmanId: data.user_id
                            }
                        }).then(function(response) {

                            app.outsidesalesmanBalanceAmount = response.data.data;
                            if (app.outsidesalesmanBalanceAmount < 0)
                                app.outsidesalesmanBalanceAmount = -app.outsidesalesmanBalanceAmount;
                            else app.outsidesalesmanBalanceAmount = 0;
                            $('#approve_load_re_stock').modal('show');

                        }).catch(error => {
                            console.error('Error loading salesmen:', error);
                            Vue.$toast.error('Failed to load salesmen.', 'Error');
                        });


                    }
                },
                rejectReturnLoadFromModal() {

                    if (!this.selectedApproveLoad || !this.selectedApproveLoad.id) {
                        Vue.$toast.error('Please select a vehicle load', 'Error Alert');
                        return;
                    }

                    // Prepare approved items array
                    const approvedItems = [];
                    if (this.selectedApproveLoad.return_requested_stock_items && this.selectedApproveLoad
                        .return_requested_stock_items.length > 0) {
                        this.selectedApproveLoad.return_requested_stock_items.forEach(item => {
                            const approvedQty = this.approvedReturnQuantities[item.id] || 0;
                            if (approvedQty >= 0) {
                                approvedItems.push({
                                    id: item.id,
                                    product_id: item.product_id,
                                    approved_qty: approvedQty,
                                    requested_qty: item.requested_qty || 0
                                });
                            }
                        });
                    }

                    if (approvedItems.length === 0) {
                        Vue.$toast.error('Please enter at least one rejected quantity', 'Error Alert');
                        return;
                    }

                    // Close modal first
                    $('#approve_load_return_stock').modal('hide');

                    // Show confirmation
                    Swal.fire({
                        title: "Are you sure you want to reject return?",
                        text: "You won't be able to undo this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, reject it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/reject_return_stock", {
                                    data: {
                                        id: this.selectedApproveLoad.id,
                                        approved_items: approvedItems,
                                        user_id: this.user_id
                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.log("Error approve return stock:", error);
                                    Vue.$toast.error('Failed to approve return stock', 'Error Alert');
                                });
                        }
                    });

                },
                rejectReStockItemFromModal() {
                    if (!this.selectedApproveLoad || !this.selectedApproveLoad.id) {
                        Vue.$toast.error('Please select a vehicle load', 'Error Alert');
                        return;
                    }
                    let validPayments = [];
                    const totalPaidAmount = this.getReTotalPayment();


                    // Prepare approved items array
                    const approvedItems = [];
                    if (this.selectedApproveLoad.re_requested_stock_items && this.selectedApproveLoad
                        .re_requested_stock_items.length > 0) {
                        this.selectedApproveLoad.re_requested_stock_items.forEach(item => {
                            const approvedQty = this.approvedQuantities[item.id] || 0;

                            if (approvedQty > item.total_qty) {
                                Vue.$toast.error(
                                    'Approved quantity cannot exceed requested quantity for product: ' +
                                    (item.product?.product_name || 'N/A'), 'Error Alert');
                                return;
                            }
                            if (approvedQty >= 0) {
                                approvedItems.push({
                                    id: item.id,
                                    product_id: item.product_id,
                                    approved_qty: approvedQty,
                                    requested_qty: item.requested_qty || 0
                                });
                            }
                        });
                    }

                    if (approvedItems.length === 0) {
                        Vue.$toast.error('Please enter at least one approved quantity', 'Error Alert');
                        return;
                    }

                    // Close modal first
                    $('#approve_load_re_stock').modal('hide');

                    // Show confirmation
                    Swal.fire({
                        title: "Are you sure you want to reject?",
                        text: "You won't be able to undo this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, reject it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/reject_extra_load_to_vehicle", {
                                    data: {
                                        id: this.selectedApproveLoad.id,
                                        approved_items: approvedItems,
                                        user_id: this.user_id,
                                        created_user: this.selectedApproveLoad.user.id,
                                        payment_mode: validPayments[0]?.payment_mode ?? 'NA',
                                        payment_amount: totalPaidAmount,
                                        payment_modes: validPayments,
                                        order_date: this.re_order_date
                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.log("Error reject load:", error);
                                    Vue.$toast.error('Failed to reject load', 'Error Alert');
                                });

                        }
                    });
                },
                approveReStockItemFromModal() {
                    if (!this.selectedApproveLoad || !this.selectedApproveLoad.id) {
                        Vue.$toast.error('Please select a vehicle load', 'Error Alert');
                        return;
                    }

                    let validPayments = [];
                    if (this.selectedApproveLoad.user.user_type == 'outside_salesman') {
                        if (!this.re_order_date) {
                            Vue.$toast.error('Please choose order date', 'Error Alert');
                            return;
                        }
                        const totalPaid = this.getReTotalPayment();
                        const amountToPay = Math.max(0, this.re_request_grand_total - this
                            .outsidesalesmanBalanceAmount);
                        validPayments = this.re_paymentModes.filter(p => p.payment_mode && p.payment_mode !== '');
                        if (validPayments.length === 0 && totalPaid > 0) {
                            Vue.$toast.error('Please select at least one payment mode', 'Error Alert');
                            return;
                        }

                        for (let p of validPayments) {
                            if (p.payment_mode !== 'Credit' && (!p.amount || Number(p.amount) <= 0)) {
                                Vue.$toast.error('Please enter a valid amount for selected payment mode (' + p
                                    .payment_mode + ')', 'Error Alert');
                                return;
                            }
                        }


                        {{-- alert(totalPaid);
                        alert(this.re_request_grand_total);
                        alert(this.outsidesalesmanBalanceAmount);
                        alert(amountToPay); --}}
                        if (totalPaid > amountToPay) {

                            Vue.$toast.error(
                                'Payment amount cannot be greater than amount to pay',
                                'Error Alert'
                            );
                            return;
                        }

                    }

                    const totalPaidAmount = this.getReTotalPayment();

                    // Prepare approved items array
                    const approvedItems = [];
                    if (this.selectedApproveLoad.re_requested_stock_items && this.selectedApproveLoad
                        .re_requested_stock_items.length > 0) {
                        this.selectedApproveLoad.re_requested_stock_items.forEach(item => {
                            const approvedQty = this.approvedQuantities[item.id] || 0;

                            if (approvedQty > item.total_qty) {
                                Vue.$toast.error(
                                    'Approved quantity cannot exceed requested quantity for product: ' +
                                    (item.product?.product_name || 'N/A'), 'Error Alert');
                                return;
                            }
                            if (approvedQty >= 0) {
                                approvedItems.push({
                                    id: item.id,
                                    product_id: item.product_id,
                                    approved_qty: approvedQty,
                                    requested_qty: item.requested_qty || 0
                                });
                            }
                        });
                    }

                    if (approvedItems.length === 0) {
                        Vue.$toast.error('Please enter at least one approved quantity', 'Error Alert');
                        return;
                    }

                    // Close modal first
                    $('#approve_load_re_stock').modal('hide');

                    // Show confirmation
                    Swal.fire({
                        title: "Are you sure you want to approve?",
                        text: "You won't be able to undo this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, approve it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/approve_extra_load_to_vehicle", {
                                    data: {
                                        id: this.selectedApproveLoad.id,
                                        approved_items: approvedItems,
                                        user_id: this.user_id,
                                        created_user: this.selectedApproveLoad.user.id,
                                        payment_mode: validPayments[0]?.payment_mode ?? 'NA',
                                        payment_amount: totalPaidAmount,
                                        credit: Number(this.outsidesalesmanBalanceAmount),
                                        payment_modes: validPayments,
                                        order_date: this.re_order_date
                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.log("Error approve load:", error);
                                    Vue.$toast.error('Failed to approve load', 'Error Alert');
                                });
                        }
                    });
                },

                showApproveReturnStockLoadListModal(data) {
                    if (data && data.id > 0) {
                        this.selectedApproveLoad = data;
                        // Initialize approved return quantities with requested quantities as default
                        this.approvedReturnQuantities = {};
                        if (data.return_requested_stock_items && data.return_requested_stock_items.length > 0) {
                            data.return_requested_stock_items.forEach(item => {
                                this.approvedReturnQuantities[item.id] = item.requested_qty || 0;
                            });
                        }
                        $('#approve_load_return_stock').modal('show');
                    }
                },

                approveReturnStockFromModal() {
                    if (!this.selectedApproveLoad || !this.selectedApproveLoad.id) {
                        Vue.$toast.error('Please select a vehicle load', 'Error Alert');
                        return;
                    }

                    // Prepare approved items array
                    const approvedItems = [];
                    if (this.selectedApproveLoad.return_requested_stock_items && this.selectedApproveLoad
                        .return_requested_stock_items.length > 0) {
                        this.selectedApproveLoad.return_requested_stock_items.forEach(item => {
                            const approvedQty = this.approvedReturnQuantities[item.id] || 0;
                            if (approvedQty >= 0) {
                                approvedItems.push({
                                    id: item.id,
                                    product_id: item.product_id,
                                    approved_qty: approvedQty,
                                    requested_qty: item.requested_qty || 0
                                });
                            }
                        });
                    }

                    if (approvedItems.length === 0) {
                        Vue.$toast.error('Please enter at least one approved quantity', 'Error Alert');
                        return;
                    }

                    // Close modal first
                    $('#approve_load_return_stock').modal('hide');

                    // Show confirmation
                    Swal.fire({
                        title: "Are you sure you want to approve return?",
                        text: "You won't be able to undo this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, approve it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/approve_return_stock", {
                                    data: {
                                        id: this.selectedApproveLoad.id,
                                        approved_items: approvedItems,
                                        user_id: this.user_id
                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.log("Error approve return stock:", error);
                                    Vue.$toast.error('Failed to approve return stock', 'Error Alert');
                                });
                        }
                    });
                },

                showReturnLoadModal(data) {
                    if (data && data.id > 0) {
                        this.selectedApproveLoad = data;

                        const returnItems = [];
                        if (this.selectedApproveLoad.vehicle_load_stock_items && this.selectedApproveLoad
                            .vehicle_load_stock_items.length > 0) {
                            this.selectedApproveLoad.vehicle_load_stock_items.forEach(item => {
                                const returnQty = item.available_qty || 0;
                                if (returnQty >= 0) {
                                    returnItems.push({
                                        id: item.id,
                                        product_id: item.product_id,
                                        return_qty: returnQty,
                                        available_qty: returnQty
                                    });
                                }
                            });
                        }

                        if (returnItems.length === 0) {
                            Vue.$toast.error('No items to return', 'Error Alert');
                            return;
                        }

                        const url = "/api/get-pending-amount-for-orders";

                        axios.get(url, {
                            params: {
                                id: this.selectedApproveLoad.id,
                                return_items: returnItems,
                                user_id: this.user_id,
                            }
                        }).then(response => {
                            app.orderPaidAmount = response.data.data.orderPaidAmount;
                            app.ordersTotalAmount = response.data.data.ordersTotalAmount;

                            app.ordersBalanceAmount = response.data.data.ordersBalanceAmount;



                            app.returnedOrders = response.data.data.orders;
                            app.customerCreditBalance = response.data.data.customerCreditBalance;

                            app.orderRefundAmount = response.data.data.orderRefundAmount;
                            if (app.customerCreditBalance > 0) {
                                app.totalOrdersRefundAmount = 0;
                            } else {
                                app.totalOrdersRefundAmount = app.orderRefundAmount;
                            }
                            {{-- app.return_amount = response.data.data.orderRefundAmount; --}}
                            app.return_amount = response.data.data.ordersBalanceAmount + response.data.data
                                .customerCreditBalance;;
                            if (app.customerCreditBalance < 0) {
                                app.return_amount = app.orderRefundAmount - app.customerCreditBalance;
                            }
                            if (app.customerCreditBalance > 0) {
                                app.return_amount = app.customerCreditBalance - app.orderRefundAmount;
                            }


                            app.completeData = response.data.data;

                        });
                        $('#return_load').modal('show');
                    }
                },
                rejectReturnLoad() {
                    if (!this.selectedApproveLoad || !this.selectedApproveLoad.id) {
                        Vue.$toast.error('Please select a vehicle load', 'Error Alert');
                        return;
                    }

                    // Prepare return items array using available_qty
                    const returnItems = [];
                    if (this.selectedApproveLoad.vehicle_load_stock_items && this.selectedApproveLoad
                        .vehicle_load_stock_items.length > 0) {
                        this.selectedApproveLoad.vehicle_load_stock_items.forEach(item => {
                            const returnQty = item.available_qty || 0;
                            if (returnQty >= 0) {
                                returnItems.push({
                                    id: item.id,
                                    product_id: item.product_id,
                                    return_qty: returnQty,
                                    available_qty: returnQty
                                });
                            }
                        });
                    }

                    if (returnItems.length === 0) {
                        Vue.$toast.error('No items to return', 'Error Alert');
                        return;
                    }

                    // Close modal first
                    $('#return_load').modal('hide');

                    // Show confirmation
                    Swal.fire({
                        title: "Are you sure you want to reject return this load?",
                        text: "You won't be able to undo this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, reject it!"
                    }).then((result) => {
                        if (result.isConfirmed) {

                            axios.delete("/api/reject_closing_request", {
                                    data: {
                                        id: this.selectedApproveLoad.id,
                                        return_items: returnItems,
                                        user_id: this.user_id,
                                        return_date: this.return_date,
                                        refund_mode: this.refund_mode,
                                        return_amount: this.return_amount,

                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.log("Error return load:", error);
                                    Vue.$toast.error('Failed to return load', 'Error Alert');
                                });
                        }
                    });
                },
                returnLoadFromModal() {
                    if (!this.selectedApproveLoad || !this.selectedApproveLoad.id) {
                        Vue.$toast.error('Please select a vehicle load', 'Error Alert');
                        return;
                    }

                    // Prepare return items array using available_qty
                    const returnItems = [];
                    if (this.selectedApproveLoad.vehicle_load_stock_items && this.selectedApproveLoad
                        .vehicle_load_stock_items.length > 0) {
                        this.selectedApproveLoad.vehicle_load_stock_items.forEach(item => {
                            const returnQty = item.available_qty || 0;
                            if (returnQty >= 0) {
                                returnItems.push({
                                    id: item.id,
                                    product_id: item.product_id,
                                    return_qty: returnQty,
                                    available_qty: returnQty
                                });
                            }
                        });
                    }

                    if (returnItems.length === 0) {
                        Vue.$toast.error('No items to return', 'Error Alert');
                        return;
                    }
                    const refundAmount = 0;
                    if (this.selectedApproveLoad.user.user_type == 'outside_salesman') {
                        if (!this.return_date) {
                            Vue.$toast.error('Please choose return date', 'Error Alert');
                            return;
                        }




                    }
                    // Close modal first
                    $('#return_load').modal('hide');

                    // Show confirmation
                    Swal.fire({
                        title: "Are you sure you want to return this load?",
                        text: "You won't be able to undo this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, return it!"
                    }).then((result) => {
                        if (result.isConfirmed) {

                            axios.delete("/api/return/vehicleLoad", {
                                    data: {
                                        id: this.selectedApproveLoad.id,
                                        completeData: this.completeData,
                                        return_items: returnItems,
                                        user_id: this.user_id,
                                        return_date: this.return_date,

                                        return_date: this.return_date,


                                        // Old total field
                                        return_amount: this.return_amount,


                                        balance_amount: this.balance_amount
                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.log("Error return load:", error);
                                    Vue.$toast.error('Failed to return load', 'Error Alert');
                                });
                        }
                    });
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
                this.getLoadList();
            },
        });
    </script>
@endsection
