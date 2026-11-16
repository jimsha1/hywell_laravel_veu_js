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
                                    <h4 class="page-title">Open Close Report</h4>
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
                                <div class="row align-items-center justify-content-left row-gap-3">

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Start Date</Label>
                                        <input class="form-control" type="date" v-model="filter_start_date">
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">End Date</Label>
                                        <input class="form-control" type="date" v-model="filter_end_date">
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Stock Code</Label>
                                        <input class="form-control" type="text" v-model="filter_stock_code"
                                            placeholder="Stock Code">
                                    </div>

                                    <div class="col-sm-4">
                                        <Label class="mb-1">Vehicle</Label>
                                        <select v-model="filter_vehicle_id" class="form-select">
                                            <option value="">Choose</option>
                                            <option v-for="item in vehicleList" :value="item.id">
                                                @{{ item.vehicle_name }}[@{{ item.vehicle_code }}]
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <!-- Search & Reset Buttons -->
                                <div class="col-sm-12 d-flex gap-2 justify-content-end">
                                    <button class="btn btn-primary" v-on:click="getSummaryDetailsPopup()">
                                        View Summary
                                    </button>
                                    <button class="btn btn-primary" v-on:click="getOpenCloseList()">
                                        <i class="ti ti-search"></i> Search
                                    </button>
                                    <button class="btn btn-secondary" v-on:click="resetFilter()">
                                        <i class="ti ti-reload"></i> Reset
                                    </button>

                                    <div class="dropdown ms-3">
                                        <a href="javascript:void(0);" class="dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="ti ti-package-export me-2"></i>Export
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <ul>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item"
                                                        v-on:click="exportToPDF()">
                                                        <i class="ti ti-file-type-pdf text-danger me-1"></i>Export as
                                                        PDF
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item"
                                                        v-on:click="exportToExcel()">
                                                        <i class="ti ti-file-type-xls text-green me-1"></i>Export as
                                                        Excel
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Export Button -->

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
                                                    <input type="checkbox" id="select-all"><span class="checkmarks"></span>
                                                </label>
                                            </th>

                                            <th>Stock Code</th>
                                            <th>Open Date</th>
                                            <th>Close Date</th>
                                            <th>Vehicle</th>
                                            <th>Stock By</th>
                                            <th>Total Load</th>
                                            <th>Balance Load</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-if="!openCloseList || !openCloseList.length">
                                            <tr>
                                                <td colspan="5">No data found</td>
                                            </tr>
                                        </template>
                                        <template v-else v-for="(data, index) in openCloseList">
                                            <tr>
                                                <td>@{{ index + 1 }}</td>
                                                <td>
                                                    <a href="javascript:void(0);" @click="showOpenCloseListModal(data)"
                                                        style="color: blue;">
                                                        @{{ data.v_stock_code }}
                                                    </a>
                                                </td>
                                                <td>@{{ formatDate(data.approved_date) }}</td>
                                                <td>@{{ formatDate(data.closing_req_date) }}</td>
                                                <td>@{{ data.vehicle.vehicle_name }}</td>
                                                <td>@{{ data.user.name }}</td>
                                                <td>
                                                    @{{ data.vehicle_load_stock_items ? data.vehicle_load_stock_items.reduce((sum, item) => sum + Number(item.total_qty || 0), 0) : 0 }}
                                                </td>
                                                <td>
                                                    @{{ data.vehicle_load_stock_items ? data.vehicle_load_stock_items.reduce((sum, item) => sum + Number(item.available_qty || 0), 0) : 0 }}
                                                </td>
                                                <td>
                                                    <span class="badge"
                                                        :class="{
                                                            'bg-success': data.day_status === 'started',
                                                            'bg-danger': data.day_status === 'closed'
                                                        }">
                                                        @{{ data.day_status }}
                                                    </span>
                                                </td>

                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot v-if="openCloseList && openCloseList.length">
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Total:</strong></td>
                                            <td><strong>@{{ getGrandTotalLoad }}</strong></td>
                                            <td><strong>@{{ getGrandBalanceLoad }}</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>

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

        <!-- View Open close -->
        <div class="modal fade" id="openclose_list" role="dialog">
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
                            <template v-if="selectedOpenClose">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Total Load</th>
                                            <th>Balance Load</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in selectedOpenClose.vehicle_load_stock_items"
                                            :key="item.id">
                                            <td>@{{ item.product.product_name || 'N/A' }}</td>
                                            <td>@{{ item.total_qty }}</td>
                                            <td>@{{ item.available_qty }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </template>
                            <template v-else>
                                <p>No purchase data available.</p>
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


        <div class="modal fade" id="openCloseSummary" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Summary</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card-body overflow-hidden">

                            <table class="table">

                                <tbody>
                                    <tr>
                                        <th>Total Load</th>
                                        <td>
                                            @{{ totalLoad ? totalLoad : 0 }}

                                    </tr>

                                    <tr>
                                        <th>Balance Load</th>
                                        <td>
                                            @{{ balanceLoad ? balanceLoad : 0 }}</td>
                                    </tr>


                                </tbody>
                            </table>


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

        <!-- /View Open close -->

    </div>
@endsection

@section('script')
    <script>
        var app = new Vue({
            el: '#app',
            data: {
                loading: false,

                base_url: "{{ url('/') }}",

                openCloseList: [],
                vehicleList: [],

                filter_start_date: '',
                filter_end_date: '',
                filter_stock_code: '',
                filter_vehicle_id: '',
                selectedOpenClose: '',

                totalLoad: '',
                balanceLoad: '',
                search: '',
                page_length: '10',

                pagination: {
                    total: 0,
                    per_page: 10,
                    from: 1,
                    to: 0,
                    current_page: 1,
                    last_page: 1
                },
                offset: 4,
                formErrors: {},
            },

            computed: {
                isActived: function() {
                    return app.pagination.current_page;
                },
                pagesNumber: function() {
                    if (!this.pagination.last_page) {
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
                getGrandTotalLoad() {
                    if (!this.openCloseList) return 0;
                    return this.openCloseList.reduce((sum, data) => {
                        return sum + (data.vehicle_load_stock_items ?
                            data.vehicle_load_stock_items.reduce((s, item) => s + Number(item
                                .total_qty || 0), 0) :
                            0);
                    }, 0);
                },
                getGrandBalanceLoad() {
                    if (!this.openCloseList) return 0;
                    return this.openCloseList.reduce((sum, data) => {
                        return sum + (data.vehicle_load_stock_items ?
                            data.vehicle_load_stock_items.reduce((s, item) => s + Number(item
                                .available_qty || 0), 0) :
                            0);
                    }, 0);
                }
            },
            mounted() {
                this.getOpenCloseList();
                this.getVehicleList();
                this.getLoggedUserInfo();
            },

            methods: {
                // formatDate(dateString) {
                //     if (!dateString) return '';
                //     const date = new Date(dateString);
                //     return date.toLocaleDateString('en-GB').replaceAll('/', '-');
                // },

                getSummaryDetailsPopup() {
                    app.getOpenCloseList();
                    $('#openCloseSummary').modal('show');


                },
                changePage(page) {
                    if (page === this.pagination.current_page) return;

                    this.pagination.current_page = page;
                    this.getOpenCloseList(page);
                },
                formatDate(dateString) {
                    if (!dateString) return '';
                    const date = new Date(dateString);
                    return date
                        .toLocaleString('en-GB', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            // hour: '2-digit',
                            // minute: '2-digit',
                            // hour12: false
                        })
                        .replace(',', '')
                        .replace(/\//g, '-');
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




                getOpenCloseList: function(page = 1) {

                    url = "{{ route('open-close-report.list') }}";

                    axios.post(url, {
                        page: page,
                        page_length: this.page_length,
                        search: this.search,
                        start_date: this.filter_start_date,
                        end_date: this.filter_end_date,
                        stock_code: this.filter_stock_code,
                        vehicle_id: this.filter_vehicle_id,
                    }).then(function(response) {
                        app.openCloseList = response.data.data.data;
                        app.pagination = response.data.pagination;
                        app.totalLoad = response.data.totals.totalLoad;
                        app.balanceLoad = response.data.totals.balanceLoad;

                        app.loading = false;
                    });

                },
                getVehicleList() {
                    axios.get('/api/vehicle/list', {
                            params: {
                                platform: 'api'
                            }
                        })
                        .then(response => {
                            app.vehicleList = response.data.data;
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },
                showOpenCloseListModal(data) {
                    if (data && data.id > 0) {
                        this.selectedOpenClose = data;
                        console.log(this.selectedOpenClose);

                        $('#openclose_list').modal('show');
                    }
                },
                resetFilter: function() {
                    app.search = '';
                    app.filter_vehicle_id = '';
                    app.filter_stock_code = '';
                    app.filter_end_date = '';
                    app.filter_start_date = '';
                    app.getOpenCloseList();
                },
                exportToPDF() {
                    var url = "{{ route('open-close-report-export-to-pdf') }}?search=" + this.search +
                        "&vehicle_id=" + (this.filter_vehicle_id ?? '') +
                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '') +
                        "&stock_code=" + (this.filter_stock_code ?? '');

                    console.log(url);

                    window.open(url, '_self');
                },
                exportToExcel() {
                    var url = "{{ route('open-close-report-export-to-excel') }}?search=" + this.search +
                        "&vehicle_id=" + (this.filter_vehicle_id ?? '') +
                        "&start_date=" + (this.filter_start_date ?? '') +
                        "&end_date=" + (this.filter_end_date ?? '') +
                        "&stock_code=" + (this.filter_stock_code ?? '');

                    console.log(url);

                    window.open(url, '_self');
                },

            },


        });
    </script>
@endsection
