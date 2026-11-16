@extends('admin.layout.base')

@section('main-content')
    <div id="app">

        <div class="page-wrapper">
            <div class="content">

                <div class="row">
                    <div class="col-md-12">

                        <!-- Page Header -->


                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h4 class="page-title">Employee</h4>
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
                                                <label class="mb-1">Search</label>
                                                <input type="text" class="form-control" v-model="search"
                                                    @input="loadEmployees" placeholder="Search by name, email, phone">
                                            </div>
                                            <div class="col-sm-3 mt-2">
                                                <label class="mb-1">Employee Type</label>
                                                <select v-model="filter_employee_type" class="form-select"
                                                    @change="loadEmployees">
                                                    <option value="in">Company Employee</option>
                                                    <option value="out">Outside Employee</option>

                                                </select>
                                            </div>
                                            <div class="col-sm-3 mt-2">
                                                <label class="mb-1">Status</label>
                                                <select v-model="status_filter" class="form-select" @change="loadEmployees">
                                                    <option value="">All</option>
                                                    <option value="1">Active</option>
                                                    <option value="0">Inactive</option>
                                                </select>
                                            </div>
                                            <div class="col-sm-12 d-flex gap-2 justify-content-end mt-2">
                                                <button class="btn btn-primary" v-on:click="loadEmployees()">
                                                    <i class="ti ti-search"></i> Search
                                                </button>
                                                <button class="btn btn-secondary" v-on:click="loadEmployees()">
                                                    <i class="ti ti-reload"></i> Reset
                                                </button>
                                            </div>

                                            <div class="col-sm-12 mt-3 d-flex justify-content-sm-end gap-2">
                                                <a href="javascript:void(0);" class="btn btn-primary"
                                                    @click="openEmployeeModal(0)">
                                                    <i class="ti ti-square-rounded-plus me-2"></i>Add Employee
                                                </a>


                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive custom-table">
                                            <table class="table" id="country_list">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Name</th>
                                                        <th>Contacts</th>
                                                        <th>Role</th>
                                                        <th width="100" v-if="filter_employee_type=='in'">Salary Details
                                                        </th>
                                                        <th width="150" v-if="filter_employee_type=='in'">Bank Details
                                                        </th>
                                                        <th width="150" v-if="filter_employee_type=='out'">Credit Limit
                                                        </th>
                                                        <th width="150" v-if="filter_employee_type=='out'">Credited
                                                            Amount
                                                        </th>
                                                        <th>Status</th>
                                                        <th width="150">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>

                                                    <template v-if="!employees || !employees.length">
                                                        <tr>
                                                            <td colspan="9">No data found</td>
                                                        </tr>
                                                    </template>

                                                    <tr v-else v-for="(data, index) in employees" :key="data.id">
                                                        <td>@{{ index + 1 }}</td>
                                                        <td>

                                                            <img :src="getProfileImage(data.profile_image)" alt="Profile"
                                                                style="width:40px; height:40px; border-radius:50%; object-fit:cover;"><br>
                                                            @{{ data.name }}

                                                        </td>
                                                        <td>Email : @{{ data.email }} <br>
                                                            Phone : @{{ data.phone }}
                                                        </td>

                                                        <td>@{{ data.designation }}</td>
                                                        <td v-if="filter_employee_type=='in'">Basic : @{{ data.basic_salary }}
                                                            <br>Allowance : @{{ data.allowance }}
                                                        </td>

                                                        <td v-if="filter_employee_type=='in'">
                                                            Bank: @{{ data.bank_name }} <br>
                                                            Acct No: @{{ data.bank_account_no }} <br>
                                                            IFSC: @{{ data.bank_ifsc }}
                                                        </td>

                                                        <td v-if="filter_employee_type=='out'">
                                                            @{{ data.credit_limit }}
                                                        </td>
                                                        <td v-if="filter_employee_type=='out'">
                                                            @{{ data.total_balance }}
                                                        </td>
                                                        <td>
                                                            <span
                                                                :class="data.is_active == 1 ? 'badge bg-success' :
                                                                    'badge bg-danger'">
                                                                @{{ data.is_active == 1 ? 'Active' : 'Inactive' }}
                                                            </span>
                                                        </td>

                                                        <td>
                                                            <button class="btn btn-transparent p-0"
                                                                @click="openEmployeeModal(data)">
                                                                <i class="ti ti-edit text-blue"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
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

        <div class="modal fade" id="employeeModal" role="dialog">
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
                                        <label class="col-form-label">Employee Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="employee_name"
                                            v-model="employee_name" placeholder="Employee Name"
                                            v-validate.initial="employee_name" data-rules="required">
                                        <span v-if="formErrors['employee_name']"
                                            class="error text-danger">@{{ formErrors['employee_name'] }}</span>
                                        <p class="text-danger" v-if="errors.has('employee_name')">@{{ errors.first('employee_name') }}
                                        </p>
                                    </div>
                                    <div class="col-md-4 mb-6">
                                        <label class="col-form-label">Employee Name(ar)</label>
                                        <input type="text" class="form-control" name="employee_name_ar"
                                            placeholder="Employee Name(ar)" v-model="employee_name_ar"
                                            @input="employee_name_ar = employee_name_ar.replace(/[^\u0600-\u06FF\s]/g, '')">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="col-form-label">Profile Image</label>
                                        <input type="file" class="form-control" name="profile_image"
                                            @change="handleImageUpload" accept="image/*">
                                        <span v-if="formErrors['profile_image']"
                                            class="error text-danger">@{{ formErrors['profile_image'] }}</span>
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
                                        <label class="col-form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" name="email" v-model="email"
                                            placeholder="Email" v-validate.initial="email" data-rules="required">
                                        <span v-if="formErrors['email']"
                                            class="error text-danger">@{{ formErrors['email'] }}</span>
                                        <p class="text-danger" v-if="errors.has('email')">@{{ errors.first('email') }}
                                        </p>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="col-form-label">
                                            Password <span class="text-danger">*</span>
                                        </label>
                                        <input type="password" class="form-control" v-model="password"
                                            placeholder="Enter Password">

                                        <span v-if="formErrors['password']" class="error text-danger">
                                            @{{ formErrors['password'] }}
                                        </span>
                                    </div>

                                </div>
                                <div class="col-md-12">
                                    <div class="row">

                                        <!-- Designation -->
                                        <div class="col-md-4 mb-3">
                                            <label class="col-form-label">
                                                Designation <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control" name="designation" v-model="designation">
                                                <option value="">Select Designation</option>
                                                <option value="salesman">Salesman</option>
                                                <option value="sales_executive">Sales Executive</option>
                                                <option value="outside_salesman">Outside Salesman</option>
                                            </select>

                                            <span v-if="formErrors['designation']"
                                                class="error text-danger">@{{ formErrors['designation'] }}</span>
                                            <p class="text-danger" v-if="errors.has('designation')">
                                                @{{ errors.first('designation') }}
                                            </p>
                                        </div>

                                        <!-- Basic Salary -->
                                        <div class="col-md-4 mb-3" v-if="designation!='outside_salesman'">
                                            <label class="col-form-label">Basic Salary <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="basic_salary"
                                                v-model="basic_salary" placeholder="Basic Salary">
                                            <span v-if="formErrors['basic_salary']"
                                                class="error text-danger">@{{ formErrors['basic_salary'] }}</span>
                                            <p class="text-danger" v-if="errors.has('basic_salary')">
                                                @{{ errors.first('basic_salary') }}
                                            </p>
                                        </div>
                                        <!-- Allowance -->
                                        <div class="col-md-4 mb-3" v-if="designation!='outside_salesman'">
                                            <label class="col-form-label">Allowances</label>
                                            <input type="number" class="form-control" name="allowances"
                                                v-model="allowances" placeholder="Allowances">

                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="col-form-label">Status</label>
                                            <select class="form-control" name="status" v-model="status">
                                                <option value="">Select Status</option>
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                        </div>


                                        <!-- Credit Limit -->
                                        <div class="col-md-4 mb-3" v-if="designation=='outside_salesman'">
                                            <label class="col-form-label">Credit Limit<span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="credit_limit"
                                                v-model="credit_limit" placeholder="Credit Limit">
                                            <span v-if="formErrors['credit_limit']"
                                                class="error text-danger">@{{ formErrors['credit_limit'] }}</span>
                                            <p class="text-danger" v-if="errors.has('credit_limit')">
                                                @{{ errors.first('credit_limit') }}
                                            </p>
                                        </div>
                                    </div>

                                </div>


                            </div>

                            <hr>

                            <!-- ================= BANK DETAILS ================= -->
                            <h6 class="mb-3" v-if="designation!='outside_salesman'">Bank Details</h6>

                            <div class="row">

                                <div class="col-md-4 mb-3" v-if="designation!='outside_salesman'">
                                    <label class="col-form-label">Bank Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="bank_name" v-model="bank_name"
                                        placeholder="Bank Name">
                                    <span v-if="formErrors['bank_name']"
                                        class="error text-danger">@{{ formErrors['bank_name'] }}</span>

                                </div>

                                <div class="col-md-4 mb-3" v-if="designation!='outside_salesman'">
                                    <label class="col-form-label">Bank Account Number <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="bank_account_no"
                                        v-model="bank_account_no" placeholder="Account Number">
                                    <span v-if="formErrors['bank_account_no']"
                                        class="error text-danger">@{{ formErrors['bank_account_no'] }}</span>
                                </div>

                                <div class="col-md-4 mb-3" v-if="designation!='outside_salesman'">
                                    <label class="col-form-label">Bank IFSC <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="bank_ifsc" v-model="bank_ifsc"
                                        placeholder="IFSC Code">
                                    <span v-if="formErrors['bank_ifsc']"
                                        class="error text-danger">@{{ formErrors['bank_ifsc'] }}</span>
                                </div>

                            </div>
                            <div class="modal-footer">
                                <div class="d-flex align-items-center justify-content-end m-0">
                                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                                    <button type="submit" class="btn btn-primary"
                                        v-bind:disabled="submit_button_flag">@{{ modelBtn }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('script')
    <script>
        const app = new Vue({
            el: '#app',

            data: {
                employees: [],
                loading: false,
                search: '',
                filter_employee_type: 'in',

                employee_id: null,
                employee_name: '',
                employee_name_ar: '',
                email: '',
                phone_number: '',
                designation: '',
                basic_salary: 0,
                allowances: '',
                bank_name: '',
                bank_account_no: '',
                bank_ifsc: '',
                status: 1,
                credit_limit: 0,
                profile_image: '',
                base_url: "{{ url('/') }}",
                modelHeading: '',
                modelBtn: '',
                password: '',
                status_filter: '',

                pagination: {
                    total: 0,
                    per_page: 10,
                    from: 1,
                    to: 0,
                    current_page: 1,
                    last_page: 0
                },

                offset: 4,
                formErrors: {},
                formSubmitted: false,
                submit_button_flag: false,
            },

            computed: {
                isActived() {
                    return this.pagination.current_page;
                },

                pagesNumber() {
                    if (!this.pagination.to) return [];

                    let from = this.pagination.current_page - this.offset;
                    if (from < 1) from = 1;

                    let to = from + (this.offset * 2);
                    if (to >= this.pagination.last_page)
                        to = this.pagination.last_page;

                    let pagesArray = [];
                    while (from <= to) {
                        pagesArray.push(from);
                        from++;
                    }
                    return pagesArray;
                },
            },

            methods: {


                getProfileImage(image) {
                    if (!image || image === 'null') {
                        return 'https://cdn-icons-png.freepik.com/512/9703/9703596.png';
                    }

                    return this.base_url + '/storage/' + image;
                },
                validateBeforeSubmit() {
                    this.$validator.validateAll();
                    if (!this.errors.any()) {
                        this.createEmployee();
                    }
                },

                createEmployee() {

                    const formData = new FormData();

                    formData.append("name", this.employee_name);
                    formData.append("employee_name_ar", this.employee_name_ar);
                    formData.append("email", this.email);
                    formData.append("phone_number", this.phone_number);
                    formData.append("basic_salary", this.basic_salary);
                    formData.append("allowances", this.allowances);
                    formData.append("designation", this.designation);
                    formData.append("bank_name", this.bank_name);
                    formData.append("bank_account_no", this.bank_account_no);
                    formData.append("bank_ifsc", this.bank_ifsc);
                    formData.append("profile_image", this.profile_image);
                    formData.append("status", this.status);
                    formData.append("credit_limit", this.credit_limit);
                    formData.append("password", this.password);

                    this.submit_button_flag = true;

                    if (this.employee_id) {

                        formData.append("_method", "PUT");

                        axios.post(`/api/employees/update/${this.employee_id}`, formData)
                            .then(response => {
                                this.showAPIResponse(response);
                            })
                            .catch(error => {
                                this.submit_button_flag = false;
                                this.formErrors = error.response?.data?.errors || {};
                                if (error.response?.data?.message) {
                                    Vue.$toast.error(error.response.data.message, 'Error');
                                }
                            });

                    } else {

                        axios.post("/api/employee/store", formData)
                            .then(response => {
                                this.showAPIResponse(response);
                            })
                            .catch(error => {
                                this.submit_button_flag = false;
                                this.formErrors = error.response?.data?.errors || {};
                                if (error.response?.data?.message) {
                                    Vue.$toast.error(error.response.data.message, 'Error');
                                }
                            });
                    }
                },

                showAPIResponse(response) {

                    if (response.data.code == 0) {

                        Vue.$toast.success(response.data.message, 'Success Alert');

                        this.submit_button_flag = false;
                        this.formSubmitted = true;
                        this.formErrors = {};

                        this.loadEmployees();
                        this.resetForm();

                        $('#employeeModal').modal('hide');

                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        this.submit_button_flag = false;
                    }
                },

                loadEmployees(page = 1) {

                    axios.get("/api/employees/mainlist", {
                        params: {
                            page: page,
                            page_length: this.pagination.per_page,
                            search: this.search,
                            employee_type: this.filter_employee_type,
                            status: this.status_filter,
                            platform: 'web'
                        }
                    }).then(response => {

                        this.employees = response.data.data.employees;
                        this.pagination = response.data.data.pagination;
                        this.loading = false;

                    }).catch(() => {
                        this.loading = false;
                    });
                },

                openEmployeeModal(data = null) {

                    if (data && data.id) {

                        const nullToEmpty = (value) =>
                            (value === null || value === undefined || value === 'null') ? '' : value;

                        this.employee_name = nullToEmpty(data.name);
                        this.employee_name_ar = nullToEmpty(data.employee_name_ar);
                        this.email = nullToEmpty(data.email);
                        this.designation = nullToEmpty(data.designation);
                        this.phone_number = nullToEmpty(data.phone);
                        this.basic_salary = nullToEmpty(data.basic_salary);
                        this.allowances = nullToEmpty(data.allowance);
                        this.bank_name = nullToEmpty(data.bank_name);
                        this.bank_account_no = nullToEmpty(data.bank_account_no);
                        this.bank_ifsc = nullToEmpty(data.bank_ifsc);
                        this.status = nullToEmpty(data.is_active);
                        this.employee_id = data.id;
                        this.credit_limit = data.credit_limit;
                        this.password=data.password_without_hash;

                        this.modelHeading = "Edit Employee";
                        this.modelBtn = "Update";

                    } else {

                        this.resetForm();
                        this.modelHeading = "Add Employee";
                        this.modelBtn = "Create";
                    }

                    this.formSubmitted = false;
                    this.formErrors = {};

                    $('#employeeModal').modal('show');
                },

                // openEmployeeModal(data = null) {

                //     // ✅ reset first
                //     this.formSubmitted = false;
                //     this.formErrors = {};

                //     if (data && data.id) {

                //         const nullToEmpty = (value) =>
                //             (value === null || value === undefined || value === 'null') ? '' : value;

                //         this.employee_name = nullToEmpty(data.name);
                //         this.employee_name_ar = nullToEmpty(data.employee_name_ar);
                //         this.email = nullToEmpty(data.email);
                //         this.designation = nullToEmpty(data.designation);
                //         this.phone_number = nullToEmpty(data.phone);
                //         this.basic_salary = nullToEmpty(data.basic_salary);
                //         this.bank_name = nullToEmpty(data.bank_name);
                //         this.bank_account_no = nullToEmpty(data.bank_account_no);
                //         this.bank_ifsc = nullToEmpty(data.bank_ifsc);
                //         this.status = nullToEmpty(data.is_active);
                //         this.employee_id = data.id;

                //         this.modelHeading = "Edit Employee";
                //         this.modelBtn = "Update";

                //     } else {

                //         this.resetForm();

                //         this.modelHeading = "Add Employee";
                //         this.modelBtn = "Create";
                //     }

                //     // ✅ important fix
                //     this.$nextTick(() => {
                //         $('#employeeModal').modal('show');
                //     });
                // }


                resetForm() {

                    this.employee_name = '';
                    this.employee_name_ar = '';
                    this.email = '';
                    this.designation = '';
                    this.phone_number = '';
                    this.basic_salary = '';
                    this.allowances = '';
                    this.bank_name = '';
                    this.bank_account_no = '';
                    this.bank_ifsc = '';
                    this.profile_image = '';
                    this.password = '';
                    this.status = 1;
                    this.employee_id = null;
                    this.credit_limit = 0;

                    this.formSubmitted = false;
                    this.formErrors = {};

                    if (this.errors && this.errors.clear) {
                        this.errors.clear();
                    }
                },

                handleImageUpload(event) {
                    this.profile_image = event.target.files[0];
                },

                changePage(page) {
                    this.loadEmployees(page);
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
                }
            },

            mounted() {
                this.loadEmployees();
                this.getLoggedUserInfo();

                $('#employeeModal').on('hidden.bs.modal', () => {
                    this.resetForm();
                });
            }
        });
    </script>
@endsection
