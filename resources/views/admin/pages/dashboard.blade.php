@extends('admin.layout.base')
@section('main-content')
    <style>
        /* Highest priority z-index */
        .swal2-container {
            z-index: 99999 !important;
        }

        #chart-line {
            background: #fff;
            border-radius: 15px;
            padding: 15px;
        }

        .card .card-body canvas {
            height: 300px !important;
        }
    </style>
    <!-- Page Wrapper -->

    <div id="app">

        <div class="page-wrapper">

            <div class="content">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="card counter-box card-red">
                            <div class="card-body">
                                <h5 class="mb-2">Total Sales <br><span class="text-muted fw-normal"
                                        style="font-size: 12px">(This
                                        Month)</span></h5>
                                <h6 style="font-size: 22px">SR @{{ currentMonthSales }}</h6>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="card counter-box card-green">
                            <div class="card-body">
                                <h5 class="mb-2">Total Customers <br><span class="text-muted fw-normal"
                                        style="font-size: 12px">(This Month)</span></h5>
                                <h6 style="font-size: 22px">@{{ currentMonthCustomers }}</h6>
                            </div>
                        </div>
                    </div>


                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Sales Man(Today) </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="max-height: 25vh; overflow: auto">
                                    <table class="table table-nowrap mb-0">
                                        <thead style="position: sticky; top: 0;">
                                            <tr>
                                                <th>#</th>
                                                <th>Sales Man</th>
                                                <th>Sales Amount</th>
                                                <th>Sales</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(salesman, index) in salesManList" :key="index">
                                                <td>@{{ index + 1 }}</td>
                                                <td class="d-flex align-items-center gap-1">
                                                    <span
                                                        v-if="
                                                    (salesman.today_opened_vehicle_load_stocks == null || 0)"
                                                        class="bg-danger d-block rounded"
                                                        style="width: 10px; height: 10px;"></span>
                                                    <span v-else class="bg-success d-block rounded"
                                                        style="width: 10px; height: 10px;"></span>
                                                    @{{ salesman.name }}
                                                </td>
                                                <td class="fw-semibold">
                                                    SR @{{ salesman.today_sales_amount ?? "0" }}
                                                </td>
                                                <td>
                                                    <span
                                                        v-if="
                                                    (salesman.today_opened_vehicle_load_stocks == null || 0)"
                                                        class="text-danger fw-bold">
                                                        0
                                                    </span>
                                                    <span v-else class="text-success fw-bold">
                                                        @{{ salesman.total_available_quantity || 0 }} /
                                                        @{{ salesman.total_vehicle_quantity || 0 }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Employees</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="max-height: 25vh; overflow: auto">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Total Commission</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(employee, index) in EmployeeCommission" :key="index">
                                                <td>@{{ employee.username }}</td>
                                                <td>SR @{{ employee.total_commission }}</td>
                                            </tr>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-lg-12">
                        <h4 class="mb-3">Stock</h4>
                    </div>
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title">Godown Stock</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-nowrap mb-0">
                                        <thead style="position: sticky;top: 0;">
                                            <tr>
                                                <th>#</th>
                                                <th>Products</th>
                                                <th>Stock</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(stock, index) in godownStockList" :key="index">
                                                <td>@{{ index + 1 }}</td>
                                                <td class="d-flex align-items-center gap-1 fw-bold text-oxion">
                                                    @{{ stock.product_name }}
                                                </td>
                                                <td class="fw-bold">
                                                    @{{ stock.total_stock }}
                                                    <span class="text-oxion fw-bold" style="font-size: 10px;">
                                                        @{{ stock.uom }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 ">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title">Vehicle Stock</h5>
                            </div>
                            <div class="card-body story-design">
                                <!-- Tabs -->
                                <ul class="nav nav-tabs nav-tabs-top mb-3" role="tablist">
                                    <li v-for="(salesman, index) in vehicleStockList" :key="salesman.id"
                                        class="nav-item" role="presentation">
                                        <a class="nav-link" :class="{ active: index === 0 }" :href="'#tab-' + salesman.id"
                                            data-bs-toggle="tab" role="tab" :aria-selected="index === 0">
                                            <div class="hds-ds">
                                                {{-- <img :src="base_url+ '/storage/+'salesman.profile_image ? (salesman.profile_image ? salesman.profile_image : '{{ url('') }}' + salesman.profile_image) : '{{ asset('assets/img/empty.png') }}'" alt=""> --}}
                                                <img :src="salesman.profile_image ?
                                                    base_url + '/storage/' + salesman.profile_image :
                                                    '{{ asset('assets/img/empty.png') }}'"
                                                    alt="">
                                                <span><i class="ti ti-truck"></i></span>
                                            </div>
                                            <p>@{{ salesman.name }} <span
                                                    v-if="salesman.today_opened_vehicle_load_stocks && salesman.today_opened_vehicle_load_stocks.vehicle">
                                                    | @{{ salesman.today_opened_vehicle_load_stocks.vehicle.vehicle_name }}
                                                </span><span v-else>
                                                    | N/A
                                                </span></p>
                                        </a>
                                    </li>
                                </ul>

                                <!-- Tab Content -->
                                <div class="tab-content">
                                    <div v-for="(salesman, index) in vehicleStockList" :key="salesman.id"
                                        class="tab-pane" :class="{ 'active show': index === 0 }" :id="'tab-' + salesman.id"
                                        role="tabpanel">

                                        <!-- If vehicle stock exists -->
                                        <div v-if="salesman.today_opened_vehicle_load_stocks">
                                            <div class="row align-items-center">
                                                <div class="col-12 col-sm tab-content-conjs">
                                                    <div class="d-flex align-items-center gap-2 pe-2">
                                                        <img :src="base_url + '/storage/' + salesman.profile_image ? base_url +
                                                            '/storage/' + salesman.profile_image :
                                                            '{{ asset('assets/img/empty.png') }}'"
                                                            alt="">
                                                        <p>@{{ salesman.name }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm">
                                                    <h6 class="vehcle-num"><i class="ti ti-truck"></i>
                                                        @{{ salesman.today_opened_vehicle_load_stocks.v_stock_code }}</h6>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <h6 class="fw-bold color-dark mt-3 mb-1" style="font-size: 16px">Sale</h6>
                                                <div class="col-sm-7">
                                                    <table class="stock-vehcile-table">
                                                        <tr v-for="item in salesman.today_opened_vehicle_load_stocks.vehicle_load_stock_items"
                                                            :key="item.id">
                                                            <td>@{{ item.product?.product_name || 'N/A' }}</td>
                                                            <td width="70px" class="text-end">
                                                                <span
                                                                    :class="item.available_qty === 0 ? 'text-success' :
                                                                        'text-danger'"
                                                                    class="fw-bold">
                                                                    @{{ item.total_qty - item.available_qty }}
                                                                </span> /
                                                                <span
                                                                    class="text-success fw-bold">@{{ item.total_qty }}</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                {{-- <div class="col-sm-5 d-flex flex-column justify-content-space-evenly">
                                                <table width="100%">
                                                    <tr>
                                                        <td class="fw-semibold text-dark d-flex align-items-center gap-1">
                                                            <span
                                                                style="display: inline-block; height: 12px; width: 12px; background-color: #43a047; border-radius: 50%;"></span>
                                                            Cash
                                                        </td>
                                                        <td class="text-end fw-semibold text-dark">SR 250</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-dark d-flex align-items-center gap-1">
                                                            <span
                                                                style="display: inline-block; height: 12px; width: 12px; background-color: #fb8c00; border-radius: 50%;"></span>Online
                                                        </td>
                                                        <td class="text-end fw-semibold text-dark">SR 320</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-dark d-flex align-items-center gap-1">
                                                            <span
                                                                style="display: inline-block; height: 12px; width: 12px; background-color: #0092e4; border-radius: 50%;"></span>Card
                                                        </td>
                                                        <td class="text-end fw-semibold text-dark">SR 326</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-semibold text-dark d-flex align-items-center gap-1">
                                                            <span
                                                                style="display: inline-block; height: 12px; width: 12px; background-color: #177b72; border-radius: 50%;"></span>Credit
                                                        </td>
                                                        <td class="text-end fw-semibold text-dark">SR 681</td>
                                                    </tr>
                                                </table>
                                                <p class="text-oxion fw-bold text-end" style="font-size: 18px">SR 2026</p>
                                </div> --}}
                                            </div>
                                        </div>

                                        <!-- If NO stock -->
                                        <div v-else class="text-center">
                                            <img width="30%" src="{{ asset('assets/img/empty.png') }}" alt="">
                                            <p class="text-muted">No Vehicle Stock</p>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="card counter-box card-red">
                            <div class="card-body">
                                <h5 class="mb-2">Total Sales <br><span class="text-muted fw-normal"
                                        style="font-size: 12px">(This
                                        Month)</span></h5>
                                <h6 style="font-size: 22px">SR @{{ currentMonthSales }}</h6>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="col-lg-12 mt-3">
                    <h4 class="mb-3">Today</h4>
                </div>
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">

                                    <div class="col-lg-12 col-md-12 col-12">
                                        <div class=" counter-box card-green">
                                            <div class="card-body">
                                                <h5 class="mb-2">Sales</h5>
                                                <h6 style="font-size: 22px">SR @{{ todaySales.todayTotalSales }}</h6>
                                            </div>
                                        </div>
                                        <div class="row mt-4 row-gap-3">
                                            <div class="col-xl-3  col-lg-4 col-md col-sm col-6">
                                                <div
                                                    style="background-color: #0d6efd30; border-radius: 8px; padding: 8px 12px;">
                                                    <h6 style="color: #0d6efd;">Cash</h6>
                                                    <p>SR @{{ formatCurrency(todaySales.todayCashSales) }}</p>
                                                </div>
                                            </div>
                                            {{-- <div class="col-xl-3  col-lg-4 col-md col-sm col-6">
                                                <div
                                                    style="background-color: #19875430; border-radius: 8px; padding: 8px 12px;">
                                                    <h6 style="color: #198754;">Card</h6>
                                                    <p>SR @{{ formatCurrency(todaySales.todayCardSales) }}</p>
                                                </div>
                                            </div>
                                            <div class="col-xl-3  col-lg-4 col-md col-sm col-6">
                                                <div
                                                    style="background-color: rgba(251, 138, 0, 0.12); border-radius: 8px; padding: 8px 12px;">
                                                    <h6 style="color: rgb(251, 140, 0);">Online</h6>
                                                    <p>SR @{{ formatCurrency(todaySales.todayOnlineSales) }}</p>
                                                </div>
                                            </div> --}}
                                            <div class="col-xl-3  col-lg-4 col-md col-sm col-6">
                                                <div
                                                    style="background-color: rgba(126, 87, 194, 0.145); border-radius: 8px; padding: 8px 12px;">
                                                    <h6 style="color: rgb(126, 87, 194);">Alrajhi (Bank)</h6>
                                                    <p>SR @{{ formatCurrency(todaySales.todayBankTransferAlrajhi) }}</p>
                                                </div>
                                            </div>
                                            <div class="col-xl-3  col-lg-4 col-md col-sm col-6">
                                                <div
                                                    style="background-color: #17a2b830; border-radius: 8px; padding: 8px 12px;">
                                                    <h6 style="color: #17a2b8;">SAB (Bank)</h6>
                                                    <p>SR @{{ formatCurrency(todaySales.todayBankTransferSab) }}</p>
                                                </div>
                                            </div>
                                            <div class="col-xl-3  col-lg-4 col-md col-sm col-6">
                                                <div
                                                    style="background-color: #0dcaf030; border-radius: 8px; padding: 8px 12px;">
                                                    <h6 style="color: #0dcaf0;">Alrajhi (POS)</h6>
                                                    <p>SR @{{ formatCurrency(todaySales.todayPosAlrajhis) }}</p>
                                                </div>
                                            </div>
                                            <div class="col-xl-3  col-lg-4 col-md col-sm col-6">
                                                <div
                                                    style="background-color: #19875430; border-radius: 8px; padding: 8px 12px;">
                                                    <h6 style="color: #198754;">SAB (POS)</h6>
                                                    <p>SR @{{ formatCurrency(todaySales.todayPosSab) }}</p>
                                                </div>
                                            </div>
                                            <div class="col-xl-3  col-lg-4 col-md col-sm col-6">
                                                <div
                                                    style="background-color: #c2185b30; border-radius: 8px; padding: 8px 12px;">
                                                    <h6 style="color: #c61b5d;">Credit</h6>
                                                    <p>SR @{{ formatCurrency(todaySales.todayCreditSales) }}</p>
                                                </div>
                                            </div>

                                            <div class="col-xl-3  col-lg-4 col-md col-sm col-6">
                                                <div
                                                    style="background-color: rgba(251, 138, 0, 0.12); border-radius: 8px; padding: 8px 12px;">
                                                    <h6 style="color: rgb(251, 140, 0);">Return</h6>
                                                    <p>SR @{{ formatCurrency(todaySales.todaySalesReturn) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-12 ">
                                <div class="card counter-box card-pink">
                                    <div class="card-body">
                                        <h5 class="mb-2">Number of Sales</h5>
                                        <h6 style="font-size: 22px">@{{ todayOrdersCount }}</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12 col-12">
                                <div class="card counter-box card-red">
                                    <div class="card-body">
                                        <h5 class="mb-2">New Customer</h5>
                                        <h6 style="font-size: 22px">@{{ newCustomers }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
                {{-- second row --}}



                {{-- <div class="col-lg-12">
                    <h4 class="mb-3">Wallet</h4>
                </div>
                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">

                                    <div class="col-lg-12 col-md-12 col-12">
                                        <div class=" counter-box card-blue">
                                            <div class="card-body">
                                                <h5 class="mb-2">Total Advance Amount</h5>
                                                <h6 style="font-size: 22px">SR @{{ totalAdvanceAmount }}</h6>
                                            </div>
                                        </div>
                                        <div class="row mt-4 row-gap-2">
                                            <div class="col-6">
                                                <div
                                                    style="background-color: #007a8328; border-radius: 8px; padding: 8px 12px;">
                                                    <h6 style="color: #007B83;">Wallet</h6>
                                                    <p>SR @{{ totalAdvanceWalletAmount }}</p>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div
                                                    style="background-color: #4caf4f25; border-radius: 8px; padding: 8px 12px;">
                                                    <h6 style="color: #4CAF50;">Credit</h6>
                                                    <p>SR @{{ totalAdvancedCreditAmount }} </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">

                                    <div class="col-lg-12 col-md-12 col-12">
                                        <div class=" counter-box card-pink">
                                            <div class="card-body">
                                                <h5 class="mb-2">Total Outstanding Amount</h5>
                                                <h6 style="font-size: 22px">SR @{{ totalOutstandingAmount }}</h6>
                                            </div>
                                        </div>
                                        <div class="row mt-4 row-gap-2">

                                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-6">
                                                <div
                                                    style="background-color: #03a8f425; border-radius: 8px; padding: 8px 12px;">
                                                    <h6 style="color: #03A9F4;">Wallet</h6>
                                                    <p>SR @{{ totalOutstandingWalletAmount }}</p>
                                                </div>
                                            </div>
                                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-6">
                                                <div
                                                    style="background-color: #7e57c225; border-radius: 8px; padding: 8px 12px;">
                                                    <h6 style="color: #7E57C2;">Credit</h6>
                                                    <p>SR @{{ totalOutstandingCreditAmount }}</p>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-12 ">
                                <div class="card counter-box card-orange">
                                    <div class="card-body">
                                        <h5 class="mb-2">Today Credit Payment</h5>
                                        <h6 style="font-size: 22px">@{{ todayCreditPaymentAmount }}</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12 col-12">
                                <div class="card counter-box card-green">
                                    <div class="card-body">
                                        <h5 class="mb-2">Today Wallet Recharge</h5>
                                        <h6 style="font-size: 22px">SR @{{ todayWalletRechargeAmount }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> --}}

                <div class="row ">
                    <div class="col-md-12 d-flex">
                        <div class="card w-100">
                            <div class="card-header border-0 pb-0">
                                <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                    <h4><i class="ti ti-grip-vertical me-1"></i>Monthly Sales</h4>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas id="chart-line" class="chart-canvas" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex">
                        <div class="card w-100">
                            <div class="card-header border-0 pb-0">
                                <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                    <h4><i class="ti ti-grip-vertical me-1"></i>Weekly Sales</h4>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="weekly-sales-chart"></div>
                            </div>
                        </div>
                    </div>
                    {{-- <div class="col-md-6 d-flex">
                        <div class="card w-100">
                            <div class="card-header border-0 pb-0">
                                <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                    <h4><i class="ti ti-grip-vertical me-1"></i>Today's Payments</h4>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="today-payments-chart"></div>
                            </div>
                        </div>
                    </div> --}}
                    <div class="col-md-6 d-flex">
                        <div class="card w-100">
                            <div class="card-header border-0 pb-0">
                                <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                    <h4><i class="ti ti-grip-vertical me-1"></i>Monthly Payments</h4>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="yearly-sales-chart"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- /Page Wrapper -->
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        var app = new Vue({
            el: '#app',
            data: {
                base_url: "{{ url('/') }}",
                latestTenLeadsList: [],
                employeeList: [],
                filter_emp_id: "",

                salesManList: [],
                godownStockList: [],
                EmployeeCommission: [],
                vehicleStockList: [],


                // totalSales: 0,
                // totalCustomers: 0,
                // todayEmptyBottles: 0,
                // totalAdvanceWalletAmount: 0,
                // totalSalesExecutives: 0,
                // totalSalesman: 0,

                // new added

                currentMonthSales: 0,
                currentMonthCustomers: 0,
                todayOrdersCount: 0,
                newCustomers: 0,

                totalAdvanceAmount: 0,
                totalAdvanceWalletAmount: 0,
                totalOutstandingWalletAmount: 0,

                todayWalletRechargeAmount: 0,
                todayCreditPaymentAmount: 0,

                totalOutstandingAmount: 0,
                totalAdvancedCreditAmount: 0,
                totalOutstandingCreditAmount: 0,
                // processedCountData: {},
                todaySales: {},
                todaySalesReturnData: {},

            },
            mounted() {
                this.getLoggedUserInfo();
                this.getDashboardData();
                this.getSalesManList();
                this.getGodownStockList();
                this.getVehicleStockList();
                this.getEmployeeCommission();
            },
            methods: {
                formatCurrency(value) {
                    if (!value) return '0.00';
                    return parseFloat(value).toFixed(2);
                },

                getLoggedUserInfo() {
                    const userJson = window.localStorage.getItem('user');
                    if (userJson) {
                        const user = JSON.parse(userJson);
                        $('#user_name').text(user.data.name || 'Unknown');
                        if (user.data.user_type === "superadmin") {
                            $('#user_type').text('Super Admin');
                        } else if (user.data.user_type === "sales_head") {
                            $('#user_type').text('Sales Head');
                        } else {
                            $('#user_type').text('Employee');
                        }

                        let userImage = user.data.user_image;
                        const defaultImg = 'https://cdn-icons-png.freepik.com/512/9703/9703596.png';
                        const userImageUrl = userImage ? this.base_url + '/storage/' + userImage : defaultImg;
                        $('#user_image').attr('src', userImageUrl);
                        $('#user_image_header').attr('src', userImageUrl);
                    }
                },
                getDashboardData() {
                    axios.get('/api/get-dashboard-data')
                        .then(response => {
                            if (response.data.code === 0) {
                                app.processCountData(response.data.data.processedCountData);
                                // app.processedCountData = response.data.data.processedCountData;
                                app.initMonthlyGraph(response.data.data.monthlySalesData);

                                const graphInput = {
                                    labels: Object.keys(response.data.data.weeklySalesData),
                                    data: Object.values(response.data.data.weeklySalesData)
                                };
                                app.initWeeklySalesGraph(response.data.data.weeklySalesData);

                                // Initialize today's payment chart
                                app.initTodayPaymentsChart(response.data.data.processedCountData.todaySales);

                                // Initialize monthly payment chart
                                app.initYearPie(response.data.data.monthlyPaymentSales);
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching dashboard data:', error);
                        });
                },
                getEmployeeCommission() {
                    axios.get('/api/employee-commission-list', {
                            params: {
                                platform: 'api'
                            }
                        })
                        .then(response => {
                            if (response.data.status === 'success') {
                                this.EmployeeCommission = response.data.data;
                                console.log("Employee Commission List", this.EmployeeCommission);
                            }
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },
                getVehicleStockList() {
                    axios.get('/api/vehicle-stock-list', {
                            params: {
                                platform: 'api'
                            }
                        })
                        .then(response => {
                            if (response.data.code === 0) {
                                // populate your list
                                this.vehicleStockList = response.data.data;
                                console.log("Vehicle Stock List", this.vehicleStockList);
                            }
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },
                getSalesManList() {
                    axios.get('/api/sales-man-list-main', {
                            params: {
                                platform: 'api'
                            }
                        })
                        .then(response => {
                            app.salesManList = response.data.data;
                            console.log("salesManList");
                            console.log(app.salesManList);
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },
                getGodownStockList() {
                    axios.get('/api/godown-stock-list', {
                            params: {
                                platform: 'api'
                            }
                        })
                        .then(response => {
                            app.godownStockList = response.data.data;
                            console.log("godownStockList", app.godownStockList);
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },


                initTodayPaymentsChart(data) {
                    const todayCash = parseFloat(data.todayCashSales) || 0;
                    const todayCard = parseFloat(data.todayCardSales) || 0;
                    const todayOnline = parseFloat(data.todayOnlineSales) || 0;
                    const todayBankTransferAlrajhi = parseFloat(data.todayBankTransferAlrajhi) || 0;
                    const todayBankTransferSab = parseFloat(data.todayBankTransferSab) || 0;
                    const todayPosAlrajhis = parseFloat(data.todayPosAlrajhis) || 0;
                    const todayPosSab = parseFloat(data.todayPosSab) || 0;
                    const todayCredit = parseFloat(data.todayCreditSales) || 0;

                    var options = {
                        series: [
                            todayCash,
                            todayCard,
                            todayOnline,
                            todayBankTransferAlrajhi,
                            todayBankTransferSab,
                            todayPosAlrajhis,
                            todayPosSab,
                            todayCredit
                        ],
                        chart: {
                            type: 'donut',
                            height: 450
                        },
                        colors: ['#0d6efd', '#198754', 'rgb(251, 140, 0)', 'rgb(126, 87, 194)', '#17a2b8',
                            '#0dcaf0', '#198754', '#c61b5d'
                        ],
                        labels: ['Cash', 'Card', 'Online', 'Alrajhi (Bank)', 'SAB (Bank)', 'Alrajhi (POS)',
                            'SAB (POS)', 'Credit'
                        ],
                        plotOptions: {
                            pie: {
                                startAngle: -90,
                                endAngle: 270
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: function(val) {
                                return parseFloat(val).toFixed(2) + "%"
                            }
                        },
                        legend: {
                            position: 'bottom',
                            formatter: function(val, opts) {
                                return val + " - SR " + parseFloat(opts.w.globals.series[opts.seriesIndex])
                                    .toFixed(2);
                            }
                        },
                        tooltip: {
                            y: {
                                formatter: function(value) {
                                    return "SR " + parseFloat(value).toFixed(2);
                                }
                            }
                        },
                        responsive: [{
                            breakpoint: 480,
                            options: {
                                chart: {
                                    width: 200
                                },
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }]
                    };

                    var chart = new ApexCharts(document.querySelector("#today-payments-chart"), options);
                    chart.render();
                },

                initYearPie(data) {
                    const monthlyCashSales = parseFloat(data.monthlyCashSales) || 0;
                    const monthlyCardSales = parseFloat(data.monthlyCardSales) || 0;
                    const monthlyOnlineSales = parseFloat(data.monthlyOnlineSales) || 0;
                    const monthlyBankTransferAlrajhi = parseFloat(data.monthlyBankTransferAlrajhi) || 0;
                    const monthlyBankTransferSab = parseFloat(data.monthlyBankTransferSab) || 0;
                    const monthlyPosAlrajhis = parseFloat(data.monthlyPosAlrajhis) || 0;
                    const monthlyPosSab = parseFloat(data.monthlyPosSab) || 0;
                    const monthlyCreditSales = parseFloat(data.monthlyCreditSales) || 0;

                    var options = {
                        series: [
                            monthlyCashSales,
                            // monthlyCardSales,
                            // monthlyOnlineSales,
                            monthlyBankTransferAlrajhi,
                            monthlyBankTransferSab,
                            monthlyPosAlrajhis,
                            monthlyPosSab,
                            // monthlyCreditSales
                        ],
                        chart: {
                            type: 'donut',
                            height: 450
                        },
                        // colors: ['#0d6efd', '#198754', 'rgb(251, 140, 0)', 'rgb(126, 87, 194)', '#17a2b8',
                        //     '#0dcaf0', '#198754', '#c61b5d'
                        // ],
                        // labels: ['Cash', 'Card', 'Online', 'Alrajhi (Bank)', 'SAB (Bank)', 'Alrajhi (POS)',
                        //     'SAB (POS)', 'Credit'
                        // ],
                        colors: ['#c61b5d', 'rgb(126, 87, 194)', '#17a2b8',
                            'rgb(251, 140, 0)', '#198754'
                        ],
                        labels: ['Cash', 'Alrajhi (Bank)', 'SAB (Bank)', 'Alrajhi (POS)',
                            'SAB (POS)'
                        ],
                        plotOptions: {
                            pie: {
                                startAngle: -90,
                                endAngle: 270
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: function(val) {
                                return parseFloat(val).toFixed(2) + "%"
                            }
                        },
                        legend: {
                            position: 'bottom',
                            formatter: function(val, opts) {
                                return val + " - SR " + parseFloat(opts.w.globals.series[opts.seriesIndex])
                                    .toFixed(2);
                            }
                        },
                        tooltip: {
                            y: {
                                formatter: function(value) {
                                    return "SR " + parseFloat(value).toFixed(2);
                                }
                            }
                        },
                        responsive: [{
                            breakpoint: 480,
                            options: {
                                chart: {
                                    width: 200
                                },
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }]
                    };

                    var chart = new ApexCharts(document.querySelector("#yearly-sales-chart"), options);
                    chart.render();

                },
                processCountData(data) {
                    app.totalSales = data.totalSales || 0;
                    app.totalCustomers = data.totalCustomers || 0;
                    app.todayEmptyBottles = data.todayEmptyBottles || 0;
                    app.totalAdvanceWalletAmount = data.totalAdvanceWalletAmount || 0;
                    app.totalSalesman = data.totalSalesman || 0;
                    app.totalSalesExecutives = data.totalSalesExecutives || 0;
                    app.currentMonthSales = data.currentMonthSales || 0;
                    app.currentMonthCustomers = data.currentMonthCustomers || 0;
                    app.todayOrdersCount = data.todayOrdersCount || 0;
                    app.newCustomers = data.newCustomers || 0;


                    app.totalAdvanceAmount = data.totalAdvanceAmount || 0;
                    app.totalAdvanceWalletAmount = data.totalAdvanceWalletAmount || 0;
                    app.totalOutstandingWalletAmount = data.totalOutstandingWalletAmount || 0;

                    app.todayWalletRechargeAmount = data.todayWalletRechargeAmount || 0;
                    app.todayCreditPaymentAmount = data.todayCreditPaymentAmount || 0;

                    app.totalOutstandingAmount = data.totalOutstandingAmount || 0;
                    app.totalAdvancedCreditAmount = data.totalAdvancedCreditAmount || 0;
                    app.totalOutstandingCreditAmount = data.totalOutstandingCreditAmount || 0;
                    app.todaySalesReturnData = data.todaySalesReturnData || {};

                    // app.processedCountData = data || {};
                    app.todaySales = data.todaySales || {};
                },

                initMonthlyGraph(data) {
                    var ctx2 = document.getElementById("chart-line").getContext("2d");

                    new Chart(ctx2, {
                        type: "line",
                        data: {
                            datasets: [{
                                label: "Sales",
                                tension: 0,
                                borderWidth: 2,
                                pointRadius: 3,
                                pointBackgroundColor: "#43A047",
                                pointBorderColor: "transparent",
                                borderColor: "#43A047",
                                backgroundColor: "transparent",
                                fill: true,
                                data: data,
                                maxBarThickness: 6
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                tooltip: {
                                    callbacks: {
                                        title: function(context) {
                                            const fullMonths = ["January", "February", "March", "April",
                                                "May", "June",
                                                "July", "August", "September", "October",
                                                "November", "December"
                                            ];
                                            return fullMonths[context[0].dataIndex];
                                        }
                                    }
                                }
                            },
                            interaction: {
                                intersect: false,
                                mode: 'index',
                            },
                            scales: {
                                y: {
                                    grid: {
                                        drawBorder: false,
                                        display: true,
                                        drawOnChartArea: true,
                                        drawTicks: false,
                                        borderDash: [4, 4],
                                        color: '#e5e5e5'
                                    },
                                    ticks: {
                                        display: true,
                                        color: '#23282c',
                                        padding: 10,
                                        font: {
                                            size: 12,
                                            lineHeight: 2
                                        },
                                    }
                                },
                                x: {
                                    grid: {
                                        drawBorder: false,
                                        display: false,
                                        drawOnChartArea: false,
                                        drawTicks: false,
                                        borderDash: [5, 5]
                                    },
                                    ticks: {
                                        display: true,
                                        color: '#23282c',
                                        padding: 10,
                                        font: {
                                            size: 12,
                                            lineHeight: 2
                                        },
                                    }
                                },
                            },
                        },
                    });
                },
                initWeeklyGraph(data) {
                    const ctx = document.getElementById("weekly-sales-chart").getContext("2d");

                    new Chart(ctx, {
                        type: "bar",
                        data: {
                            labels: data,
                            datasets: [{
                                label: "Weekly Sales",
                                data: data,
                                backgroundColor: "#ff9800",
                                borderColor: "#ff9800",
                                borderWidth: 1,
                                borderRadius: 6,
                                maxBarThickness: 40
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        title: function(context) {
                                            return weeklyLabels[context[0].dataIndex];
                                        }
                                    }
                                }
                            },
                            interaction: {
                                intersect: false,
                                mode: 'index'
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        drawBorder: false,
                                        color: '#e0e0e0',
                                        borderDash: [4, 4]
                                    },
                                    ticks: {
                                        color: '#23282c',
                                        padding: 10,
                                        font: {
                                            size: 12
                                        }
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: '#23282c',
                                        padding: 10,
                                        font: {
                                            size: 12
                                        }
                                    }
                                }
                            }
                        }
                    });
                },
                initWeeklySalesGraph(data) {

                    var options = {
                        series: [{
                            name: "Reports",
                            data: data
                        }],
                        chart: {
                            type: 'bar',
                            height: 500,
                        },
                        plotOptions: {
                            bar: {
                                borderRadiusApplication: 'around',
                                columnWidth: '50%',
                            }
                        },
                        colors: ['#00918E', '#5CB85C', '#FF0000', '#00918E', '#FFC38F'],
                        xaxis: {
                            type: 'category',
                            group: {
                                style: {
                                    fontSize: '0px',
                                    fontWeight: 700,
                                },
                            }
                        },
                    };
                    var chart = new ApexCharts(document.querySelector("#weekly-sales-chart"), options);
                    chart.render();

                },
            },
        });
    </script>
@endsection
