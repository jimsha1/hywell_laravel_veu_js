@extends('admin.layout.base')
@section('main-content')
    <div id="app">
        <div class="page-wrapper">
            <div class="content">
                <div class="page-header">
                    <div class="page-title">
                        <h4>Advance Salary Management</h4>
                        <h6>Process and manage employee advance salaries</h6>
                    </div>
                    <div class="col-sm-12 mt-3 d-flex justify-content-sm-end gap-2">

                        <button class="btn btn-added" @click="openModal(0)">
                            <i class="ti ti-plus me-1"></i> Add Addvance Salary
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Search</label>
                                <input type="text" class="form-control" v-model="search" @input="loadAdvances"
                                    placeholder="Search...">
                            </div>


                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-secondary" @click="resetFilters">Reset</button>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Advance Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Employee</th>
                                        <th>Date</th>
                                        <th>Amount</th>

                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <template v-if="!advances|| !advances.length">
                                        <tr v-if="advances.length === 0">
                                            <td colspan="8" class="text-center">No payroll records found</td>
                                        </tr>
                                    </template>
                                    <template v-else v-for="(advance, index) in advances ">
                                        <tr>
                                            <td><strong>@{{ index + 1 }}</strong></td>
                                            <td>@{{ advance.employee.name }}[@{{ advance.employee.id }}]</td>
                                            <td>@{{ formatDate(advance.date) }}</td>
                                            <td>@{{ formatCurrency(advance.amount) }}</td>


                                            <td>
                                                <button class="btn btn-sm btn-outline-info" @click="openModal(advance)">
                                                    <i class="ti ti-pencil"></i>
                                                </button>


                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="pagination">
                            <ul class="pagination justify-content-end">
                                <li class="page-item" v-if="pagination.current_page > 1">
                                    <a class="page-link" href="#"
                                        @click.prevent="changePage(pagination.current_page - 1)">
                                        <span aria-hidden="true">«</span>
                                    </a>
                                </li>
                                <li class="page-item" v-for="page in pagesNumber" :class="{ active: page === isActived }">
                                    <a class="page-link" href="#" @click.prevent="changePage(page)">
                                        @{{ page }}
                                    </a>
                                </li>
                                <li class="page-item" v-if="pagination.current_page < pagination.last_page">
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

            <!-- Add Advance Modal -->
            <div class="modal fade" id="advanceModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">@{{ modelHeading }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form @submit.prevent="saveAdvance" v-if="!formAdvanceSubmitted" method="post">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>Employee <span class="text-danger">*</span></label>
                                        <select class="form-select" v-model="user_id" @change="onEmployeeChange" required>
                                            <option value="">Select Employee</option>
                                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                                @{{ emp.name }} - @{{ emp.email }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label>Advance</label>
                                        <input type="number" step="0.01" class="form-control" v-model="advance_salary">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Payment Mode <span class="text-danger">*</span></label>
                                        <select class="form-select" v-model="payment_mode">
                                            <option value="">Choose</option>

                                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                                @{{ acc.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Date</label>
                                        <input type="date" class="form-control" v-model="advance_date">

                                    </div>

                                    <div class="col-12 mb-3">
                                        <label>Notes</label>
                                        <textarea class="form-control" v-model="notes" rows="2"></textarea>
                                    </div>
                                </div>



                                <div class="modal-footer">
                                    <div class="d-flex align-items-center justify-content-end m-0">
                                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                                        <button type="submit" class="btn btn-primary"
                                            v-bind:disabled="submit_advance_button_flag">@{{ modelBtn }}</button>
                                    </div>
                                </div>
                            </div>

                        </form>
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
                advances: [],
                accounts: [],
                employees: [],
                loading: false,
                saving: false,
                paying: false,
                generating: false,
                search: '',
                filterMonth: '',
                filterYear: new Date().getFullYear(),
                filterStatus: '',
                staff_name: '',
                base_url: "{{ url('/') }}",

                employee_id: '',
                advance_date: '',

                payment_mode: '',
                user_id: '',
                advance_salary: 0,

                notes: '',

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

                formPaySubmitted: false,
                submit_pay_button_flag: false,

                formAdvanceSubmitted: false,
                submit_advance_button_flag: false,

                modelBtn: '',
                modelHeading: '',
                summary: {},

                selectedAdvance: null
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

                async loadAccounts() {
                    try {
                        const response = await axios.get('/api/accounting/cash-bank-accounts-list');
                        if (response.data.status === 'Success') {
                            this.accounts = response.data.data;
                        }
                    } catch (error) {
                        console.error('Error loading accounts:', error);
                    }
                },
                formatCurrency(advance_salary) {
                    return new Intl.NumberFormat('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(advance_salary || 0);
                },
                formatDate(date) {
                    return new Date(date).toLocaleDateString('en-IN');
                },


                loadEmployees(page = 1) {

                    axios.get("/api/employees/mainlist", {
                        params: {

                            "platform": "payroll",

                        }
                    }).then(response => {

                        this.employees = response.data.data.employees;

                        this.loading = false;

                    }).catch(() => {
                        this.loading = false;
                    });
                },
                async loadAdvances() {

                    axios.get("/api/advances/list", {
                        params: {

                            search: this.search,

                            status: this.filterStatus,
                            page: this.pagination.current_page || 1
                        }
                    }).then(response => {

                        this.advances = response.data.data.advances;
                        this.pagination = response.data.data.pagination;

                    }).catch((error) => {
                        console.error('Error loading advances:', error);
                        this.loading = false;
                    });
                },

                onEmployeeChange() {
                    const emp = this.employees.find(e => e.id == this.user_id);
                    if (emp) {
                        this.advance_salary = emp.advance_salary || 0;
                        this.allowances = emp.allowance || 0;
                    }
                },

                openModal(advance) {
                    if (advance.id > 0) {

                        this.user_id = advance.employee.user_id;
                        this.employee_id = advance.employee_id;
                        this.user_id = advance.employee_id;

                        this.advance_salary = advance.amount;
                        this.payment_mode = advance.payment_mode;;
                        this.advance_date = advance.date;
                        this.notes = advance.remarks;
                        this.modelHeading = 'Edit Advance';
                        this.modelBtn = 'Update Advance';
                        $('#advanceModal').modal('show');
                    } else {

                        this.user_id = '';
                        this.employee_id = '';

                        this.advance_salary = 0;
                        this.payment_mode = '';

                        this.notes = '';

                        this.modelHeading = 'Create Advance';
                        this.modelBtn = 'Save Advance';
                        $('#advanceModal').modal('show');
                    }
                },
                async saveAdvance() {
                    if (!this.user_id || !this.advance_salary || !this.payment_mode) {
                        alert('Please fill all required fields');
                        return;
                    }

                    this.saving = true;
                    try {

                        const payload = {
                            user_id: this.user_id,

                            advance_salary: this.advance_salary,
                            payment_mode: this.payment_mode,
                            advance_date: this.advance_date,
                            

                            notes: this.notes
                        };
                        const response = await axios.post('/api/advances/store', payload);
                        if (response.data.status === 'Success') {
                            bootstrap.Modal.getInstance(document.getElementById('advanceModal')).hide();
                            this.loadAdvances();

                            alert('Advance created successfully!');
                        }
                        if (response.data.status === 'Error') {
                            alert(response.data.message);
                        }
                    } catch (error) {
                        alert(error.response?.data?.message || 'Error creating payroll');
                    } finally {
                        this.saving = false;
                    }
                },

                changePage(page) {
                    this.pagination.current_page = page;
                    this.loadAdvances();
                },
                resetFilters() {
                    this.search = '';

                    this.loadAdvances();
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
                this.loadAccounts();
                this.loadAdvances();

                this.getLoggedUserInfo();


            },
        });
    </script>
@endsection
