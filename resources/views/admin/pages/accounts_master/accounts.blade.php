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
                                    <h4 class="page-title">Accounts</h4>
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
                                                @input="getAccountsList">
                                        </div>
                                    </div>
                                    <!-- Type -->
                                    <div class="col-md-3">
                                        <select class="form-select" v-model="filter_account_type"
                                            @change="getAccountsList()">
                                            <option value="">All Types</option>
                                            <option v-for="type in accountTypeList" :key="type.id"
                                                :value="type.id">
                                                @{{ type.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Parent Account -->
                                    <div class="col-md-3">
                                        <select class="form-select" v-model="filter_parent_account"
                                            @change="getAccountsList()">
                                            <option value="">All Parent Accounts</option>

                                            <option v-for="parent in filterParentAccountList" :key="parent.id"
                                                :value="parent.id">

                                                @{{ parent.name }}

                                            </option>
                                        </select>
                                    </div>

                                    <!-- Status -->
                                    <div class="col-md-2">
                                        <select class="form-select" v-model="filter_status" @change="getAccountsList()">
                                            <option value="">All Status</option>
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                    </div>



                                </div>
                                <div class="row mt-3">

                                    <!-- Balance From -->
                                    <div class="col-md-2">
                                        <input type="number" class="form-control" placeholder="Balance From"
                                            v-model="filter_balance_from">
                                    </div>

                                    <div class="col-md-2">
                                        <input type="number" class="form-control" placeholder="Balance To"
                                            v-model="filter_balance_to">
                                    </div>

                                    <!-- Right Side Buttons -->
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-primary" @click="getAccountsList()">
                                                <i class="ti ti-search me-1"></i> Search
                                            </button>

                                            <button type="button" class="btn btn-secondary" @click="resetFilters">
                                                <i class="ti ti-refresh me-1"></i> Reset
                                            </button>

                                            <a href="javascript:void(0);" class="btn btn-primary"
                                                @click="showEditAccountModal(0)">
                                                <i class="ti ti-square-rounded-plus me-2"></i>Create
                                            </a>

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


                                                <th> Name</th>
                                                <th>Type</th>
                                                <th>Parent Account</th>
                                                <th>Balance</th>
                                                <th>Status</th>
                                                <th>Is System Defined</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!accountList || !accountList.length">
                                                <tr>
                                                    <td colspan="7">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in accountList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    <td>@{{ data.name }} - @{{ data.code }}</td>
                                                    <td>@{{ data.account_type.name }}</td>
                                                    <td>@{{ data.parent ? data.parent.name : 'None' }}</td>
                                                    <td>@{{ data.current_balance }}</td>
                                                    <td>
                                                        <span
                                                            :class="data.is_active === true ? 'badge bg-success' :
                                                                'badge bg-danger'">
                                                            <p v-if="data.is_active==true">Active</p>
                                                            <p v-else>Inactive</p>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span
                                                            :class="data.is_system === true ? 'badge bg-success' :
                                                                'badge bg-danger'">
                                                            <p v-if="data.is_system==true">YES</p>
                                                            <p v-else>NO</p>
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <div class="d-flex align-items-center gap-3">
                                                            <a class=" text-bg-transparent" style="cursor:pointer;"
                                                                v-on:click="showEditAccountModal(data)">
                                                                <i class="ti ti-edit text-blue"></i>
                                                            </a>

                                                            {{-- <a class=" text-bg-transparent" style="cursor:pointer;"
                                                            v-on:click="deleteItem(data,index)">
                                                            <i class="ti ti-trash text-red"></i>
                                                        </a> --}}
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
        <!-- /Page Wrapper -->

        <div class="modal fade" id="add_account" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
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
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label"> Account Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="account_name"
                                            v-model="account_name" v-validate.initial="account_name"
                                            data-rules="required" id="account_name" placeholder="Account Name">
                                        <span v-if="formErrors['account_name']"
                                            class="error text-danger">@{{ formErrors['account_name'] }}</span>
                                        <p class="text-danger" v-if="errors.has('account_name')">@{{ errors.first('account_name') }}
                                        </p>
                                    </div>

                                    {{-- <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Account Name(ar)</label>
                                        <input type="text" class="form-control" name="ar_account_name"
                                            v-model="ar_account_name" placeholder="Account Name(ar)">
                                    </div> --}}

                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Type</label>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <select class="form-select form-select-sm" v-model="account_type" required
                                                    @change="fetchParentAccounts">
                                                    <option value="">Select Account Type</option>
                                                    <option v-for="type in accountTypeList" :key="type.id"
                                                        :value="type.id">
                                                        @{{ type.name }}
                                                    </option>
                                                </select>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="row mt-3">

                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Parent Account</label>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <select class="form-select form-select-sm" v-model="parent_id">
                                                    <option value="">None (Top Level)</option>
                                                    <option v-for="accnt in parentAccountList" :key="accnt.id"
                                                        :value="accnt.id">
                                                        @{{ accnt.name }}
                                                    </option>
                                                </select>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-6">


                                        <label class="col-form-label">Status</label>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <input type="radio" class="status-radio" id="add-active"
                                                    name="status" value="1" v-model="status">
                                                <label for="add-active">Active</label>
                                            </div>
                                            <div>
                                                <input type="radio" class="status-radio" id="add-inactive"
                                                    name="status" value="0" v-model="status">
                                                <label for="add-inactive">Inactive</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- <div class="col-md-12">
                                <div class="row mt-3">

                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Reference Type(if any)</label>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <select class="form-select form-select-sm" v-model="reference_type"
                                                    @change="getTypeList(reference_type)">
                                                    <option value="">Select Reference Type</option>

                                                    <option value="customer">Customer</option>
                                                    <option value="employee">Employee</option>
                                                    <option value="supplier">Supplier</option>
                                                    <option value="commission">Commission</option>
                                                    <option value="advance">Advance</option>

                                                </select>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-6" v-if="reference_type">


                                        <label class="col-form-label">Reference Data</label>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <select class="form-select form-select-sm" v-model="reference_id">
                                                    <option value="">Select Account Type</option>
                                                    <option v-for="type in referenceTypeList" :key="type.id"
                                                        :value="type.id">
                                                        @{{ type.name }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> --}}
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
        <!-- /Add New Source -->


    </div>
@endsection

@section('script')
    <script>
        var app = new Vue({
            el: '#app',
            data: {
                loading: false,

                base_url: "{{ url('/') }}",

                accountList: [],
                accountTypeList: [],
                id: '',
                code: '',
                account_name: "",
                ar_account_name: "",
                // filter_account_type: '',
                account_type: "",
                parent_id: "",
                status: "",
                user_id: "",
                reference_id: "",
                reference_type: "",
                referenceTypeList: [],
                modelBtn: '',
                modelHeading: '',
                parentAccountList: [],
                filterParentAccountList: [],
                formSubmitted: false,
                submit_button_flag: false,
                filter_account_type: '',
                filter_parent_account: '',
                filter_status: '',
                filter_balance_from: '',
                filter_balance_to: '',

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
            mounted() {
                this.getLoggedUserInfo();
                this.getAccountsList();
                this.getAccountTypesList();
                this.fetchParentAccounts();
                this.getFilterParentAccounts();
                $('#add_account').on('hidden.bs.modal', () => {
                    this.resetForm();
                });
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
                }
            },
            methods: {

                changePage: function(page) {
                    this.getAccountsList(page);
                },
                showEditAccountModal(data) {
                    if (data.id > 0) {

                        console.log(data);
                        app.id = data.id;
                        app.code = data.code;
                        app.account_name = data.name;
                        app.parent_id = data.parent_id ?? '';
                        app.account_type = data.account_type_id;
                        app.reference_type = data.reference_type ?? '';
                        app.getTypeList(app.reference_type);
                        app.reference_id = data.reference_name?.id ?? '';

                        if (data.is_active) {
                            app.status = 1;
                        } else {
                            app.status = 0;
                        }

                        app.id = data.id;

                        app.formSubmitted = false;
                        app.formErrors = {};

                        app.modelHeading = "Edit Account";
                        app.modelBtn = "Update";

                        app.getAccountTypesList();
                        app.fetchParentAccounts(data.id);
                        $('#add_account').modal('show');

                    } else {
                        app.resetForm();
                        app.modelHeading = "Add Account";
                        app.modelBtn = "Create";

                        app.getAccountTypesList();
                        app.fetchParentAccounts();
                        $('#add_account').modal('show');
                    }

                },
                resetForm() {
                    app.code = '';
                    app.account_name = '';
                    app.parent_id = '';
                    app.account_type = '';
                    app.status = 1;
                    app.id = '';
                    app.reference_type = ''; // clear the reference type dropdown
                    app.reference_id = '';
                    app.referenceTypeList = [];

                    app.search = '';
                    app.filter_account_type = '';
                    app.filter_parent_account = '';
                    app.filter_status = '';

                    app.filter_balance_from = '';
                    app.filter_balance_to = '';

                    app.formSubmitted = false;
                    app.formErrors = {};

                    if (this.errors && this.errors.clear) {
                        this.errors.clear();
                    }

                },
                resetFilters() {

                    app.search = '';

                    app.filter_account_type = '';
                    app.filter_parent_account = '';
                    app.filter_status = '';

                    app.filter_balance_from = '';
                    app.filter_balance_to = '';

                    app.pagination.current_page = 1;

                    app.getAccountsList();
                },

                getTypeList() {

                    if (!this.reference_type) return;



                    const url = `/api/reference-data/${this.reference_type}`;


                    axios.get(url, {
                        params: {

                            reference_type: this.reference_type,
                        }
                    }).then(response => {
                        this.referenceTypeList = response.data.data;

                    });
                },
                validateBeforeSubmit() {
                    this.$validator.validateAll();
                    console.log(this.errors);
                    if (!this.errors.any()) {
                        this.createAccount();
                    }
                },
                createAccount() {
                    const formData = new FormData();

                    formData.append("name", app.account_name);
                    formData.append("code", app.code);

                    formData.append("account_type_id", app.account_type);
                    formData.append("parent_id", app.parent_id);
                    formData.append("status", app.status);
                    formData.append("id", app.id);
                    formData.append("user_id", this.user_id);
                    formData.append("reference_type", this.reference_type);
                    formData.append("reference_id", this.reference_id);

                    app.submit_button_flag = true;

                    if (app.id > 0) {
                        formData.append("_method", "put");
                        formData.append("id", app.id);

                        axios.post("/api/accounts/update", formData)
                            .then(response => {
                                app.showAPIResponse(response);
                            })
                            .catch(error => {
                                app.submit_button_flag = false;
                                console.error("Error updating supplier:", error);
                            });

                    } else {
                        axios.post("/api/accounts/store", formData)
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

                        this.getAccountsList();
                        app.resetForm();

                        $('#add_account').modal('hide');
                    } else {

                        Vue.$toast.error(response.data.message, 'Error Alert');
                        app.submit_button_flag = false;
                    }
                    app.submit_button_flag = false;
                },
                deleteItem(data) {
                    Swal.fire({
                        title: "Are you sure you want to remove " + "?",
                        text: "You won't be able to revert this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, delete it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/delete/category", {
                                    data: {
                                        id: data.id,
                                        user_id: this.user_id
                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.error("Error deleting tag:", error);
                                });
                        }
                    });
                },
                getAccountsList: function(page = 1) {
                    const url = "/api/accounts/list";

                    axios.get(url, {
                        params: {
                            page: page,
                            page_length: this.page_length,
                            search: this.search,
                            account_type: this.filter_account_type,
                            // account_type: this.filter_account_type,
                            parent_account: this.filter_parent_account,
                            status: this.filter_status,

                            balance_from: this.filter_balance_from,
                            balance_to: this.filter_balance_to,
                        }
                    }).then(response => {
                        app.accountList = response.data.data;
                        app.pagination = response.data.pagination;
                        app.loading = false;
                    });
                },

                getAccountTypesList: function() {
                    const url = "/api/accountTypes/list";

                    axios.get(url, {

                    }).then(response => {
                        app.accountTypeList = response.data.data;

                        app.loading = false;
                    });
                },
                fetchParentAccounts(excludeId = null) {
                    {{-- const url = `${this.base_url}/admin/parent-accounts`;

                    axios.get(url, {
                        params: {
                            exclude_id: excludeId
                            account_type: this.account_type,
                        }
                    }).then(response => {
                        if (response.data.success) {
                            this.parentAccountList = response.data.data;
                        }
                    }).catch(error => {
                        console.error("Error fetching parent accounts:", error);
                    }); --}}


                   
                    const url = "/api/parent-accounts/list";

                    axios.get(url, {
                        params: {

                            exclude_id: excludeId,
                            account_type_id: this.account_type,
                        }
                    }).then(response => {
                        app.parentAccountList = response.data.data;

                        app.loading = false;
                    });
                },
                getFilterParentAccounts() {

                    axios.get("/api/parent-accounts/list")
                        .then(response => {

                            this.filterParentAccountList = response.data.data;

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
            }
        });
    </script>
@endsection
