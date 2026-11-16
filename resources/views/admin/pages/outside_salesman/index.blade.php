@extends('admin.layout.base')

@section('main-content')
    <style>
        /* Highest priority z-index */
        .swal2-container {
            z-index: 99999 !important;
        }
    </style>

    <div id="app">

        <div class="page-wrapper">
            <div class="content">

                <div class="row">
                    <div class="col-md-12">

                        <!-- Page Header -->
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h4 class="page-title">Customer</h4>
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

                        <div class="row">
                            <div id="content_tab" class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="row align-items-center">
                                            <div class="col-sm-3">
                                                <label class="mb-1">Start Date</label>
                                                <input class="form-control" type="date" v-model="filter_start_date">
                                            </div>
                                            <div class="col-sm-3">
                                                <label class="mb-1">End Date</label>
                                                <input class="form-control" type="date" v-model="filter_end_date">
                                            </div>
                                            <div class="col-sm-3">
                                                <label class="mb-1">Added By</label>
                                                <input class="form-control" type="text" v-model="filter_added_by"
                                                    placeholder="Added By">
                                            </div>

                                            <div class="col-sm-3">
                                                <label class="mb-1">Customer Info</label>
                                                <div class="icon-form mb-3 mb-sm-0">
                                                    <span class="form-icon"><i class="ti ti-search"></i></span>
                                                    <input type="text" class="form-control" placeholder="Search Customer"
                                                        v-model="search">
                                                </div>
                                            </div>
                                            <div class="col-sm-3 mt-2">
                                                <label class="mb-1">Customer Type</label>
                                                <select v-model="filter_customer_type" class="form-select"
                                                    @change="getEmployeesList">
                                                    <option value="in">Company Customers</option>
                                                    <option value="out">Outside Customers</option>

                                                </select>
                                            </div>
                                            <div class="col-sm-3 mt-2">
                                                <label class="mb-1">Employee</label>
                                                <select v-model="filter_employee_id" class="form-select">
                                                    <option value="">All Employees</option>
                                                    <option v-for="employee in employeesList" :key="employee.id"
                                                        :value="employee.id">
                                                        @{{ employee.name }} (@{{ getEmployeeType(employee.user_type) }})
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="col-sm-12 d-flex gap-2 justify-content-end mt-2">
                                                <button class="btn btn-primary" v-on:click="getCustomerList()">
                                                    <i class="ti ti-search"></i> Search
                                                </button>
                                                <button class="btn btn-secondary" v-on:click="resetFilter()">
                                                    <i class="ti ti-reload"></i> Reset
                                                </button>

                                            </div>


                                            <div class="col-sm-12 mt-3 d-flex justify-content-sm-end gap-2">
                                                <a href="javascript:void(0);" class="btn btn-primary"
                                                    @click="showEditCustomerModal(0)">
                                                    <i class="ti ti-square-rounded-plus me-2"></i>Add Customer
                                                </a>
                                                <div class="dropdown ms-3">
                                                    <a href="javascript:void(0);" class="dropdown-toggle"
                                                        data-bs-toggle="dropdown">
                                                        <i class="ti ti-package-export me-2"></i>Export
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <ul>
                                                            <li>
                                                                <a :href="getExportPdfUrl()" class="dropdown-item">
                                                                    <i
                                                                        class="ti ti-file-type-pdf text-danger me-1"></i>Export
                                                                    as
                                                                    PDF
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:void(0);" class="dropdown-item"
                                                                    v-on:click="exportToExcel()">
                                                                    <i
                                                                        class="ti ti-file-type-xls text-green me-1"></i>Export
                                                                    as
                                                                    Excel
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-body">
                                        <div class="table-responsive custom-table">
                                            <table class="table" id="country_list">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th class="no-sort">
                                                            <label class="checkboxs">
                                                                <input type="checkbox" id="select-all">
                                                                <span class="checkmarks"></span>
                                                            </label>
                                                        </th>
                                                        <th>Code</th>
                                                        <th>Customer Name</th>
                                                        <th>Phone</th>
                                                        <th>Account No</th>
                                                        <th>Employee Name</th>
                                                        <th>Status</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template v-if="!customerList || !customerList.length">
                                                        <tr>
                                                            <td colspan="7">No data found</td>
                                                        </tr>
                                                    </template>
                                                    <template v-else v-for="(data, index) in customerList"
                                                        :key="data.id">
                                                        <tr>
                                                            <td>@{{ index + 1 }}</td>
                                                            <td>
                                                                <a :href="`customer-info/${data.id}`" style="color: blue;">
                                                                    @{{ data.customer_code }}
                                                                </a>
                                                            </td>
                                                            <td>@{{ data.customer_name }}</td>
                                                            <td>@{{ data.phone_number }}</td>
                                                            <td>@{{ data.account_no }}</td>
                                                            <td v-if="data.employee">@{{ data.employee.name }}</td>
                                                            <td v-else></td>
                                                            {{-- <td>@{{ data.created_by?.name || 'N/A' }}</td> --}}
                                                            <td>
                                                                <span
                                                                    :class="data.status == 1 ? 'badge bg-success' :
                                                                        'badge bg-danger'">
                                                                    <p v-if="data.status==1">Active</p>
                                                                    <p v-else>Inactive</p>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex gap-2">
                                                                    <button class="btn btn-primary btn-sm"
                                                                        @click="showAssignPopup(data)">+ Special
                                                                        Price</button>
                                                                    <button class="btn btn-transparent p-0"
                                                                        @click="showEditCustomerModal(data)">
                                                                        <i class="ti ti-edit text-blue"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>

                                            <!-- Pagination -->
                                            <nav aria-label="pagination">
                                                <ul class="pagination justify-content-end">
                                                    <li class="page-item" v-if="pagination.current_page > 1">
                                                        <a class="page-link" href="#"
                                                            @click.prevent="changePage(pagination.current_page - 1)">
                                                            <span aria-hidden="true">«</span>
                                                        </a>
                                                    </li>
                                                    <li class="page-item" v-for="page in pagesNumber"
                                                        :class="{ active: page === isActived }">
                                                        <a class="page-link" href="#"
                                                            @click.prevent="changePage(page)">
                                                            @{{ page }}
                                                        </a>
                                                    </li>
                                                    <li class="page-item"
                                                        v-if="pagination.current_page < pagination.last_page">
                                                        <a class="page-link" href="#"
                                                            @click.prevent="changePage(pagination.current_page + 1)">
                                                            <span aria-hidden="true">»</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </nav>
                                            <!-- End Pagination -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <div class="modal fade" id="add_customer" role="dialog">
            <div class="modal-dialog modal-dialog-centered 	modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{ modelHeading }}</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="validateBeforeSubmit" v-if="!formSubmitted" method="post">
                        <div class="modal-body">

                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label"> Customer Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="customer_name"
                                            v-model="customer_name" placeholder="Customer Name"
                                            v-validate.initial="customer_name" data-rules="required">
                                        <span v-if="formErrors['customer_name']"
                                            class="error text-danger">@{{ formErrors['customer_name'] }}</span>
                                        <p class="text-danger" v-if="errors.has('customer_name')">@{{ errors.first('customer_name') }}
                                        </p>
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Customer Name(ar)</label>
                                        <input type="text" class="form-control" name="customer_name_ar"
                                            placeholder="Customer Name(ar)" v-model="customer_name_ar"
                                            @input="customer_name_ar = customer_name_ar.replace(/[^\u0600-\u06FF\s]/g, '')">
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Email</label>
                                        <input type="email" class="form-control" name="email" v-model="email"
                                            placeholder="Email" @blur="validateEmail()">
                                        <span v-if="formErrors['email']"
                                            class="error text-danger">@{{ formErrors['email'] }}</span>

                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Phone <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="phone_number"
                                            v-model="phone_number" placeholder="Phone" v-validate.initial="phone_number"
                                            data-rules="required">
                                        <span v-if="formErrors['phone_number']"
                                            class="error text-danger">@{{ formErrors['phone_number'] }}</span>
                                        <p class="text-danger" v-if="errors.has('phone_number')">@{{ errors.first('phone_number') }}
                                        </p>
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Customer Type <span
                                                class="text-danger">*</span></label>
                                        <select v-model="customer_type" class="form-select"
                                            v-validate.initial="customer_type" data-rules="required">
                                            <option value="">Choose</option>
                                            <option value="{{ config('constants.WHOLESALE') }}">
                                                {{ config('constants.WHOLESALE') }}
                                            </option>
                                            <option value="{{ config('constants.RETAIL') }}">
                                                {{ config('constants.RETAIL') }}
                                            </option>
                                            <option value="{{ config('constants.OTHERS') }}">
                                                {{ config('constants.OTHERS') }}
                                            </option>
                                        </select>
                                        <span v-if="formErrors['customer_type']"
                                            class="error text-danger">@{{ formErrors['customer_type'] }}</span>
                                        <p class="text-danger" v-if="errors.has('customer_type')">@{{ errors.first('customer_type') }}
                                        </p>
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Postal Code</label>
                                        <input type="number" class="form-control" name="postal_code"
                                            v-model="postal_code" placeholder="Postal Code">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Commercial Reg.No</label>
                                        <input type="text" class="form-control" name="commercial_reg_no"
                                            v-model="commercial_reg_no" placeholder="Commercial Reg.No"
                                            @input="commercial_reg_no = commercial_reg_no.replace(/[^0-9]/g, '')">
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Vat Reg.No</label>
                                        <input type="text" class="form-control" name="vat_reg_no"
                                            v-model="vat_reg_no" placeholder="Vat Reg.No"
                                            @input="vat_reg_no = vat_reg_no.replace(/[^0-9]/g, '')">
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Country</label>
                                        <select v-model="country_name" class="form-select">
                                            <option value="">Choose</option>
                                            <option value="{{ config('constants.UAE') }}">
                                                {{ config('constants.UAE') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">City</label>
                                        <input type="text" class="form-control" name="city" v-model="city"
                                            placeholder="City">
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label clacol-md-4 mb-6ss="col-form-label">National Address No</label>
                                        <input type="text" class="form-control" name="national_address_no"
                                            v-model="national_address_no" placeholder="National Address No">
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label clacol-md-4 mb-6ss="col-form-label">Account No</label>
                                        <input type="text" class="form-control" name="account_no"
                                            v-model="account_no" placeholder="Account No">
                                    </div>


                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4 mb-6">
                                        <label clacol-md-4 mb-6ss="col-form-label">CR Number</label>
                                        <input type="text" class="form-control" name="cr_no" v-model="cr_no"
                                            placeholder="CR Number">
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Remarks</label>
                                        <input type="text" class="form-control" name="remarks" v-model="remarks"
                                            placeholder="remarks">
                                    </div>
                                    <div class="col-md-4 mb-6 ">
                                        <label class="col-form-label">Address</label>
                                        <textarea class="form-control" rows="1" v-model="address" placeholder="Address"></textarea>
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Latitude</label>
                                        <input type="number" class="form-control" name="latitude" v-model="latitude"
                                            placeholder="Latitude" step="any">
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Longitude</label>
                                        <input type="number" class="form-control" name="longitude" v-model="longitude"
                                            placeholder="Longitude" step="any">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="col-form-label">Employee</label>
                                        <select v-model="employee_id" class="form-select">
                                            <option value="">Select Employee</option>
                                            <option v-for="employee in employeesList" :key="employee.id"
                                                :value="employee.id">
                                                @{{ employee.name }} (@{{ getEmployeeType(employee.user_type) }})
                                            </option>
                                        </select>
                                    </div>

                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="col-form-label">Registration Date</label>
                                        <input type="date" class="form-control" name="registration_date"
                                            v-model="registration_date">
                                    </div>


                                    <div class="col-md-4 mb-6" v-if="!hasPositiveBalance">
                                        <label class="col-form-label">Opening Balance</label>
                                        <input type="number" class="form-control" name="opening_balance"
                                            v-model="opening_balance" placeholder="Opening Balance" step="any">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="col-form-label">Status</label>
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <input type="radio" class="status-radio" id="add-active" name="status"
                                            value="1" v-model="status">
                                        <label for="add-active">Active</label>
                                    </div>
                                    <div>
                                        <input type="radio" class="status-radio" id="add-inactive" name="status"
                                            value="0" v-model="status">
                                        <label for="add-inactive">Inactive</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="d-flex align-items-center justify-content-end m-0">
                                <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                                <button type="submit" class="btn btn-primary"
                                    v-bind:disabled="submit_button_flag">@{{ modelBtn }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- assign special price --}}
        <modal name="assign_product_popup" width="650" height="auto" :scrollable="true" :adaptive="false"
            :resizable="false">
            <div class="m-2 p-2 h-100 d-flex flex-column">
                <div class="modal-header d-flex justify-content-between align-items-center">
                    <h4 class="modal-title" id="modelHeading">@{{ modelHeading }}</h4>
                    <button type="button" class="btn-close" aria-label="Close" v-on:click="hideAssignPopup()"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3 mt-2 row row-gap-2">
                        <div class="col-md-6 mb-6">
                            <label class="col-form-label">Product<span class="text-danger">*</span></label>
                            <select v-model="selected_product" class="form-select" v-on:click="getSpecialPriceList">
                                <option value="">Choose</option>
                                <option v-for="item in productList" :value="item.id">
                                    @{{ item.product_name }}[@{{ item.product_code }}]
                                </option>
                            </select>
                            <span v-if="formErrors['selected_product']"
                                class="error text-danger">@{{ formErrors['selected_product'] }}</span>
                            <p class="text-danger" v-if="errors.has('selected_product')">@{{ errors.first('selected_product') }}
                            </p>
                        </div>

                        <div class="col-md-6 mb-6">
                            <label class="col-form-label">Special Price<span class="text-danger">*</span></label>
                            <select v-model="selected_special_price" class="form-select">
                                <option value="">Choose</option>
                                <option v-for="data in specialPriceList" :value="data.id">
                                    @{{ data.price }}
                                </option>
                            </select>
                            <span v-if="formErrors['selected_special_price']"
                                class="error text-danger">@{{ formErrors['selected_special_price'] }}</span>
                            <p class="text-danger" v-if="errors.has('selected_special_price')">@{{ errors.first('selected_special_price') }}</p>
                        </div>

                        <div class="col-md-12">
                            <label class="col-form-label">Status</label>
                            <div class="d-flex align-items-center">
                                <div class="me-2">
                                    <input type="radio" class="status-radio" id="price-active"
                                        name="special_price_status" value="1" v-model="special_price_status" />
                                    <label for="price-active">Active</label>
                                </div>
                                <div>
                                    <input type="radio" class="status-radio" id="price-inactive"
                                        name="special_price_status" value="0" v-model="special_price_status" />
                                    <label for="price-inactive">Inactive</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-end pb-3 pt-5">
                    <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal"
                        v-on:click="hideAssignPopup()">Cancel</button>
                    <button type="button" class="btn btn-primary" :disabled="submit_button_flag"
                        v-on:click="assignProduct()">
                        Submit
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive custom-table">
                        <div id="roles_list_wrapper" class="dataTables_wrapper dt-bootstrap5 no-footer">

                            <div class="row dt-row">
                                <div class="col-sm-12 table-responsive">
                                    <table class="table" id="country_list">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="no-sort">
                                                    <label class="checkboxs">
                                                        <input type="checkbox" id="select-all"><span
                                                            class="checkmarks"></span>
                                                    </label>
                                                </th>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!assignedSpecialPriceList || !assignedSpecialPriceList.length">
                                                <tr>
                                                    <td colspan="4">No products assigned</td>
                                                </tr>
                                            </template>
                                            <template v-else="">
                                                <tr v-for="(data, index) in assignedSpecialPriceList"
                                                    :key="data.id">
                                                    <td>
                                                        <label class="checkboxs">
                                                            <input type="checkbox" :value="data.id">
                                                            <span class="checkmarks"></span>
                                                        </label>
                                                    </td>
                                                    <td>@{{ data.product?.product_name }}</td>
                                                    <td>@{{ data.special_price }}</td>
                                                    <td>
                                                        <span
                                                            :class="data.status == 1 ? 'badge bg-success' :
                                                                'badge bg-danger'">
                                                            <p v-if="data.status==1">Active</p>
                                                            <p v-else>Inactive</p>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a class="border-0 bg-transparent shadow-none ps-0"
                                                            v-on:click="editAssignedProduct(data)">
                                                            <i class="ti ti-edit text-blue"></i>
                                                        </a>
                                                        <a class="border-0 bg-transparent shadow-none ps-0"
                                                            v-on:click="deleteAssignedProduct(data, index)">
                                                            <i class="ti ti-trash text-red"></i>
                                                        </a>
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
                    </div>
                </div>
            </div>



        </modal>
        {{-- assign special price --}}

    </div>
@endsection

@section('script')
    <script>
        Vue.use(window["vue-js-modal"].default);
        var app = new Vue({
            el: '#app',
            data: {
                loading: false,
                base_url: "{{ url('/') }}",

                customerList: [],
                productList: [],
                specialPriceList: [],
                assignedSpecialPriceList: [],
                employeesList: [],

                //
                id: "",
                customer_name: "",
                customer_name_ar: "",
                email: "",
                phone_number: "",
                customer_type: "",
                postal_code: "",
                commercial_reg_no: "",
                vat_reg_no: "",
                country_name: "{{ config('constants.UAE') }}",
                city: "",
                account_no: "",
                cr_no: "",
                national_address_no: "",
                remarks: "",
                address: "",
                status: "",
                latitude: "",
                longitude: "",
                opening_balance: "",
                open_balance: 0,
                registration_date: "",
                logged_user_id: "",
                user_id: "",
                employee_id: "",
                salesman_type: "",

                //
                product: "",
                modelBtn: '',
                modelHeading: '',
                product_id: '',
                isAssignSideOpened: false,

                formSubmitted: false,
                submit_button_flag: false,

                filter_start_date: '',
                filter_end_date: '',
                filter_added_by: '',
                filter_employee_id: '',
                filter_customer_type: 'in',
                search: '',
                page_length: '10',

                selected_product: '',
                selected_special_price: "",
                special_price_status: '',
                customer_id: "",

                selected_dispenser: '',
                name: "",
                dispenser_type: "",
                payment_type: "",
                price: "",


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
                hasPositiveBalance() {
                    const balance = parseFloat(this.open_balance);
                    return !isNaN(balance) && balance > 0;
                }
            },

            methods: {
                changePage: function(page) {
                    this.getCustomerList(page);
                },
                formatProductLabel(product) {
                    if (!product) return '';
                    return `${product.product_code}[${product.product_name}]`;
                },

                formatSpecialPriceLabel(specialPrice) {
                    if (!specialPrice) return '';
                    return `${specialPrice.price}`;
                },

                goToCustomerInfo(id) {
                    window.location.href = `customer-info/${id}`;
                },

                // Customer Section
                validateBeforeSubmit() {
                    this.$validator.validateAll();
                    console.log(this.errors);
                    if (!this.errors.any()) {
                        this.createCustomer();
                    }
                },

                createCustomer() {
                    const formData = new FormData();

                    formData.append("customer_name", app.customer_name);
                    formData.append("customer_name_ar", app.customer_name_ar);
                    formData.append("postal_code", app.postal_code);
                    formData.append("customer_type", app.customer_type);
                    formData.append("commercial_reg_no", app.commercial_reg_no);
                    formData.append("vat_reg_no", app.vat_reg_no);
                    formData.append("email", app.email);
                    formData.append("phone_number", app.phone_number);
                    formData.append("country_name", app.country_name ? app.country_name : '');
                    formData.append("city", app.city ? app.city : '');
                    formData.append("account_no", app.account_no ? app.account_no : '');
                    formData.append("cr_no", app.cr_no ? app.cr_no : '');
                    formData.append("national_address_no", app.national_address_no ? app.national_address_no : '');
                    formData.append("remarks", app.remarks);
                    formData.append("address", app.address ? app.address : '');
                    formData.append("status", app.status);
                    formData.append("latitude", app.latitude);
                    formData.append("longitude", app.longitude);
                    formData.append("opening_balance", app.opening_balance);
                    formData.append("registration_date", app.registration_date);
                    formData.append("employee_id", app.employee_id || '');
                    formData.append("id", app.id);
                    formData.append("user_id", app.user_id);
                    formData.append("logged_user_id", this.logged_user_id);

                    app.submit_button_flag = true;

                    if (app.id > 0) {
                        formData.append("_method", "put");
                        formData.append("id", app.id);

                        axios.post("/api/customer/update", formData)
                            .then(response => {
                                app.showAPIResponse(response);
                            })
                            .catch(error => {
                                app.submit_button_flag = false;
                                app.formErrors = error.response.data.errors;
                            });

                    } else {
                        axios.post("/api/customer/store", formData)
                            .then(response => {
                                app.showAPIResponse(response);
                            })
                            .catch(error => {
                                app.submit_button_flag = false;
                                app.formErrors = error.response.data.errors;
                            });
                    }
                },

                showAPIResponse(response) {
                    if (response.data.code == 0) {

                        Vue.$toast.success(response.data.message, 'Success Alert');
                        app.submit_button_flag = false;
                        app.formSubmitted = true;
                        app.formErrors = {};

                        this.getCustomerList();
                        app.resetForm();
                        $('#add_customer').modal('hide');
                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        app.submit_button_flag = false;
                    }
                    app.submit_button_flag = false;
                },

                showEditCustomerModal(data) {
                    if (data.id > 0) {
                        // Helper function to convert null/undefined to empty string
                        const nullToEmpty = (value) => (value === null || value === undefined || value === 'null') ?
                            '' : value;

                        app.customer_name = nullToEmpty(data.customer_name);
                        app.customer_name_ar = nullToEmpty(data.customer_name_ar);
                        app.email = nullToEmpty(data.email);
                        app.phone_number = nullToEmpty(data.phone_number);
                        app.customer_type = nullToEmpty(data.customer_type);
                        app.postal_code = nullToEmpty(data.postal_code);
                        app.commercial_reg_no = nullToEmpty(data.commercial_reg_no);
                        app.vat_reg_no = nullToEmpty(data.vat_reg_no);
                        app.country_name = nullToEmpty(data.country) || "{{ config('constants.UAE') }}";
                        app.city = nullToEmpty(data.city);
                        app.account_no = nullToEmpty(data.account_no);
                        app.cr_no = nullToEmpty(data.CR_no);
                        app.national_address_no = nullToEmpty(data.national_address_no);
                        app.remarks = nullToEmpty(data.remarks);
                        app.address = nullToEmpty(data.address);
                        app.status = data.status !== null && data.status !== undefined ? data.status : '1';
                        app.user_id = nullToEmpty(data.user_id);
                        app.id = data.id;
                        app.employee_id = data.employee_id || '';
                        const balanceAmount = parseFloat(data.balance_amount);
                        app.open_balance = isNaN(balanceAmount) ? 0 : balanceAmount;

                        // Format registration_date for date input (extract date part if datetime)
                        if (data.registration_date) {
                            const dateStr = data.registration_date;
                            // If it's a datetime string, extract just the date part
                            app.registration_date = dateStr.includes(' ') ? dateStr.split(' ')[0] : dateStr;
                        } else {
                            app.registration_date = this.getCurrentDate();
                        }

                        app.formSubmitted = false;
                        app.formErrors = {};

                        app.modelHeading = "Edit Customer";
                        app.modelBtn = "Update";

                        $('#add_customer').modal('show');

                    } else {
                        app.resetForm();
                        app.modelHeading = "Add Customer";
                        app.modelBtn = "Create";
                        app.open_balance = 0;
                        app.registration_date = this.getCurrentDate();
                        $('#add_customer').modal('show');
                    }

                },

                getCustomerList: function(page = 1) {
                    const url = "/api/customer/list";

                    axios.get(url, {
                        params: {
                            page: page,
                            page_length: this.page_length,
                            start_date: this.filter_start_date,
                            end_date: this.filter_end_date,
                            added_by: this.filter_added_by,
                            employee_id: this.filter_employee_id,
                            customer_type: this.filter_customer_type,
                            search: this.search,
                        }
                    }).then(response => {
                        app.customerList = response.data.data.customers;
                        app.pagination = response.data.data.pagination;
                        app.loading = false;
                    });
                },

                deleteItem(data) {
                    Swal.fire({
                        title: "Are you sure you want to remove " + data.customer_code + "?",
                        text: "You won't be able to revert this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, delete it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/delete/customer", {
                                    data: {
                                        id: data.id,
                                        user_id: this.logged_user_id
                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.lo("Error deleting tag:", error);
                                });
                        }
                    });
                },

                resetForm() {
                    app.customer_name = '';
                    app.customer_name_ar = '';
                    app.email = '';
                    app.phone_number = '';
                    app.customer_type = '';
                    app.postal_code = '';
                    app.commercial_reg_no = '';
                    app.vat_reg_no = '',
                        app.country_name = "{{ config('constants.UAE') }}";
                    app.city = '';
                    app.account_no = '';
                    app.cr_no = '';
                    app.national_address_no = '';
                    app.remarks = '';
                    app.address = '';
                    app.status = '1';
                    app.user_id = '';
                    app.id = '';
                    app.employee_id = '';
                    app.opening_balance = '';
                    app.open_balance = 0;
                    app.registration_date = this.getCurrentDate();

                    app.formSubmitted = false;
                    app.formErrors = {};

                    if (this.errors && this.errors.clear) {
                        this.errors.clear();
                    }
                },


                // Assign Special Price
                showAssignPopup: function(data) {
                    app.customer_id = data.id;
                    app.modelHeading = "Add Special Price - " + data.customer_name;
                    app.selected_product = '';
                    app.selected_special_price = '';
                    app.special_price_status = "1";
                    app.getAssignedSpecialPriceList();
                    app.$modal.show('assign_product_popup');
                },

                assignProduct: function() {

                    app.submit_button_flag = true;
                    url = "/api/customer/assign-product";

                    let formData = new FormData();
                    formData.append('id', this.id);
                    formData.append('customer_id', this.customer_id);
                    formData.append('product_id', this.selected_product);
                    formData.append('special_price', this.selected_special_price);
                    formData.append('special_price_status', this.special_price_status);
                    formData.append('logged_user_id', this.logged_user_id);

                    axios.post(url, formData).then(function(response) {

                        console.log(response);
                        if (response.data.code == 0) {
                            app.loading = false;

                            app.id = '';
                            app.product_id = '';
                            app.selected_special_price = '';
                            app.selected_product = '';
                            Vue.$toast.success(response.data.message, 'Success Alert');
                            app.submit_button_flag = false;
                            app.formErrors = {};

                            app.getAssignedSpecialPriceList();
                        } else {
                            app.loading = false;
                            Vue.$toast.error('Something went wrong.', 'Danger Alert');

                            app.id = '';
                            app.product_id = '';
                            app.special_price = '';
                            app.selected_product = '';
                            app.submit_button_flag = false;
                            app.$modal.hide('assign_product_popup');
                            app.getAssignedSpecialPriceList();
                        }

                    }).catch(function(err) {
                        app.submit_button_flag = false;
                        console.log(err.response.data.errors);
                        app.formErrors = err.response.data.errors;
                    });
                },

                getSpecialPriceList() {
                    return axios.get('/api/special-price/list', {
                        params: {
                            platform: 'api',
                            product_id: this.selected_product
                        }
                    }).then(response => {
                        app.specialPriceList = response.data.data;
                    }).catch(error => {
                        console.log("Error fetching special prices:", error);
                    });
                },

                getAssignedSpecialPriceList: function() {
                    const url = "/api/customer/assign-product/list";

                    axios.get(url, {
                        params: {
                            customer_id: this.customer_id
                        }
                    }).then(response => {
                        app.assignedSpecialPriceList = response.data.data.special_prices;
                        app.loading = false;
                    });
                },

                editAssignedProduct(data) {
                    if (data.id > 0) {
                        app.id = data.id;
                        app.customer_id = data.customer_id;
                        app.selected_product = data.product_id;
                        app.special_price_status = data.status;
                        app.formSubmitted = false;
                        app.formErrors = {};
                        app.modelHeading = "Edit Special Price";

                        app.selected_special_price = data.special_price_id.toString();
                        app.getSpecialPriceList().then(() => {
                            app.selected_special_price = data.special_price_id.toString();
                        }).catch(error => {
                            console.log("Error fetching special price list:", error);
                        });

                        app.$modal.show('assign_product_popup');
                    }
                },

                deleteAssignedProduct(data) {
                    Swal.fire({
                        title: "Are you sure you want to remove " + "?",
                        text: "You won't be able to revert this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, delete it!",
                        customClass: {
                            popup: 'custom-swal-zindex'
                        }

                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/customer/assign-product/delete", {
                                    data: {
                                        id: data.id
                                    }
                                })
                                .then(response => {
                                    app.getAssignedSpecialPriceList();
                                })
                                .catch(error => {
                                    console.log("Error deleting tag:", error);
                                });
                        }
                    });
                },

                hideAssignPopup() {
                    app.$modal.hide('assign_product_popup');
                    app.selected_product = '';
                    app.selected_special_price = '';
                    app.special_price = '';
                    app.price_id = '';
                },

                resetFilter: function() {
                    app.search = '';
                    app.filter_added_by = '';
                    app.filter_end_date = '';
                    app.filter_start_date = '';
                    app.filter_employee_id = '';
                    app.filter_customer_type = 'in';
                    app.getCustomerList();
                },

                getExportPdfUrl: function() {
                    const baseUrl = "{{ route('customer.list.pdf') }}";
                    const params = new URLSearchParams();

                    if (this.search) {
                        params.append('search', this.search);
                    }
                    if (this.filter_start_date) {
                        params.append('start_date', this.filter_start_date);
                    }
                    if (this.filter_end_date) {
                        params.append('end_date', this.filter_end_date);
                    }
                    if (this.filter_added_by) {
                        params.append('added_by', this.filter_added_by);
                    }
                    if (this.filter_employee_id) {
                        params.append('employee_id', this.filter_employee_id);
                    }

                    const queryString = params.toString();
                    return queryString ? `${baseUrl}?${queryString}` : baseUrl;
                },

                exportToExcel() {
                    const baseUrl = "{{ route('customer.list.excel') }}";
                    const params = new URLSearchParams();

                    if (this.search) params.append('search', this.search);
                    if (this.filter_start_date) params.append('start_date', this.filter_start_date);
                    if (this.filter_end_date) params.append('end_date', this.filter_end_date);
                    if (this.filter_added_by) params.append('added_by', this.filter_added_by);
                    if (this.filter_employee_id) params.append('employee_id', this.filter_employee_id);
                    if (this.filter_customer_type) params.append('customer_type', this.filter_customer_type);

                    const url = `${baseUrl}?${params.toString()}`;

                    window.open(url, '_blank');
                },


                getProductList(platform) {
                    axios.get('/api/product/list', {
                            params: {
                                platform: 'api',
                                productType: 'Product'
                            }
                        })
                        .then(response => {
                            app.productList = response.data.data.products;
                        })
                        .catch(error => {
                            console.log(error);
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
                getCurrentDate() {
                    const today = new Date();
                    const year = today.getFullYear();
                    const month = String(today.getMonth() + 1).padStart(2, '0');
                    const day = String(today.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                },

                getEmployeeType(userType) {
                    const types = {
                        'salesman': 'Salesman',
                        'sales_man': 'Salesman',
                        'sales_executive': 'Sales Executive',
                        // 'superadmin': 'Admin',
                        // 'admin': 'Admin'
                    };
                    return types[userType] || userType;
                },

                validateEmail() {
                    if (this.email) {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(this.email)) {
                            this.$set(this.formErrors, 'email', 'Please enter a valid email address.');
                        } else {
                            this.$delete(this.formErrors, 'email');
                        }
                    } else {
                        this.$delete(this.formErrors, 'email');
                    }
                },

                validateBeforeSubmit() {
                    this.$validator.validateAll();

                    // Validate email format if provided
                    if (this.email) {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(this.email)) {
                            this.$set(this.formErrors, 'email', 'Please enter a valid email address.');
                            return; // Stop submission
                        }
                    }

                    console.log(this.errors);
                    if (!this.errors.any()) {
                        this.createCustomer();
                    }
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
                getCurrentDate() {
                    const today = new Date();
                    const year = today.getFullYear();
                    const month = String(today.getMonth() + 1).padStart(2, '0');
                    const day = String(today.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                },
                {{-- getEmployeesList() {
                    axios.get('/api/get-users', {
                        params: {
                            platform: 'api',
                            user_type: ['salesman', 'sales_executive', 'superadmin', 'admin','outside_salesman'],
                            customer_type: this.filter_customer_type??'in'

                            // user_type: ['salesman', 'sales_executive']
                        }
                    }).then((response) => {
                        if (response.data.code === 0) {
                            this.employeesList = response.data.data || [];
                        }
                    }).catch((error) => {
                        console.error('Error loading employees:', error);
                    });
                }, --}}


                getEmployeesList() {
                    const userTypes = this.filter_customer_type === 'in' ?
                        ['salesman', 'sales_executive', 'superadmin', 'admin'] :
                        ['outside_salesman'];

                    axios.get('/api/get-users', {
                        params: {
                            platform: 'api',
                            user_type: userTypes,
                            customer_type: this.filter_customer_type ?? 'in'
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
                        // 'superadmin': 'Admin',
                        // 'admin': 'Admin'
                    };
                    return types[userType] || userType;
                },
            },

            mounted() {
                this.getLoggedUserInfo();
                this.getCustomerList();
                this.getProductList();
                this.getEmployeesList();
                this.registration_date = this.getCurrentDate();

                $('#add_customer').on('hidden.bs.modal', () => {
                    this.resetForm();
                });
            }


        });
    </script>
@endsection
