@extends('admin.layout.base')
@section('main-content')
    <div id="app">
        <div class="page-wrapper">
            <div class="content">
                <div class="page-header">
                    <div class="page-title">
                        <h4>Commission Management</h4>
                        <h6>Process and manage employee commissions</h6>
                    </div>
                    <div class="col-sm-12 mt-3 d-flex justify-content-sm-end gap-2">

                        <button class="btn btn-added" @click="openModal()">
                            <i class="ti ti-plus me-1"></i> Add Commission
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Search</label>
                                <input type="text" class="form-control" v-model="search" @input="loadPayrolls"
                                    placeholder="Search...">
                            </div>
                            <div class="col-md-2">
                                <label>Month</label>
                                <select class="form-select" v-model="filterMonth" @change="loadPayrolls">
                                    <option value="">All Months</option>
                                    <option v-for="m in months" :key="m.value" :value="m.value">
                                        @{{ m.name }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Year</label>
                                <select class="form-select" v-model="filterYear" @change="loadPayrolls">
                                    <option v-for="y in years" :key="y" :value="y">
                                        @{{ y }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Status</label>
                                <select class="form-select" v-model="filterStatus" @change="loadPayrolls">
                                    <option value="">All Status</option>
                                    <option value="draft">Draft</option>
                                    <option value="approved">Approved</option>
                                    <option value="paid">Paid</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-secondary" @click="resetFilters">Reset</button>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Payroll Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Commission No</th>
                                        <th>Employee</th>
                                        <th>Period</th>
                                        <th class="text-end">Commission</th>
                                        <th>Status</th>
                                        <th>Payment Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <template v-if="!payrolls|| !payrolls.length">
                                        <tr v-if="payrolls.length === 0">
                                            <td colspan="8" class="text-center">No payroll records found</td>
                                        </tr>
                                    </template>
                                    <template v-else v-for="(payroll, index) in payrolls ">
                                        <tr>
                                            <td><strong>@{{ payroll.commission_no }}</strong></td>
                                            <td>@{{ payroll.employee.name }}</td>
                                            <td>@{{ getMonthName(payroll.month) }} @{{ payroll.year }}</td>

                                            <td class="text-end text-primary fw-bold">@{{ formatCurrency(payroll.commission) }}</td>
                                            <td>
                                                <span class="badge" :class="getStatusClass(payroll.status)">
                                                    @{{ payroll.status }}
                                                </span>
                                            </td>
                                            <td>@{{ payroll.payment_date ? formatDate(payroll.payment_date) : '-' }}</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info" @click="viewPayroll(payroll)">
                                                    <i class="ti ti-eye"></i>
                                                </button>
                                                <button v-if="payroll.status === 'draft'" class="btn btn-sm btn-success"
                                                    @click="openPayModal(payroll)">
                                                    <i class="ti ti-wallet"></i> Pay
                                                </button>
                                                <button v-if="payroll.status === 'draft'"
                                                    class="btn btn-sm btn-outline-danger"
                                                    @click="cancelPayroll(payroll.id)">
                                                    <i class="ti ti-x"></i>
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

            <!-- Add Payroll Modal -->
            <div class="modal fade" id="payrollModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">@{{ modelHeading }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form @submit.prevent="savePayroll" v-if="!formPayrollSubmitted" method="post">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>Employee <span class="text-danger">*</span></label>
                                        <select class="form-select" v-model="user_id" {{-- @change="onEmployeeChange" --}} required>
                                            <option value="">Select Employee</option>
                                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                                @{{ emp.name }} - @{{ emp.email }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label>Month <span class="text-danger">*</span></label>
                                        <select class="form-select" v-model="month" required>
                                            <option v-for="m in months" :key="m.value" :value="m.value">
                                                @{{ m.name }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label>Year <span class="text-danger">*</span></label>
                                        <select class="form-select" v-model="year" required>
                                            <option v-for="y in years" :key="y" :value="y">
                                                @{{ y }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label>Commission</label>
                                        <input type="number" step="0.01" class="form-control"
                                            v-model="total_commission">
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label>Notes</label>
                                        <textarea class="form-control" v-model="notes" rows="2"></textarea>
                                    </div>
                                </div>
                                <hr>


                                <div class="modal-footer">
                                    <div class="d-flex align-items-center justify-content-end m-0">
                                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                                        <button type="submit" class="btn btn-primary"
                                            v-bind:disabled="submit_payroll_button_flag">@{{ modelBtn }}</button>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            <!-- Pay Modal -->
            <div class="modal fade" id="payModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">@{{ modelHeading }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form @submit.prevent="processPayment" v-if="!formPaySubmitted" method="post">
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <strong>Employee:</strong> @{{ staff_name }}<br>
                                    <strong>Period:</strong> @{{ getMonthName(month) }} @{{ year }}<br>
                                    <strong>Commission:</strong> <span class="fw-bold">@{{ formatCurrency(commission) }}</span>
                                </div>
                                <div class="mb-3">
                                    <label>Payment Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" v-model="payment_date" required>
                                </div>
                                <div class="mb-3">
                                    <label>Payment Mode <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="payment_mode" required>

                                        <option value="{{ config('constants.CASH') }}">
                                            Cash</option>
                                        <option value="{{ config('constants.BANK_TRANSFER_ALRAJHI') }}">
                                            TRANSFER ALRAJHI</option>
                                        <option value="{{ config('constants.BANK_TRANSFER_SAB') }}">
                                            TRANSFER SAB</option>
                                        <option value="{{ config('constants.POS_ALRAJHIS') }}">
                                            POS ALRAJHIS</option>
                                        <option value="{{ config('constants.POS_SAB') }}">
                                            POS SAB</option>
                                        </option>
                                    </select>
                                </div>

                                <div class="modal-footer">
                                    <div class="d-flex align-items-center justify-content-end m-0">
                                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                                        <button type="submit" class="btn btn-primary"
                                            v-bind:disabled="submit_pay_button_flag">@{{ modelBtn }}</button>
                                    </div>
                                </div>

                            </div>

                        </form>
                    </div>
                </div>
            </div>


            <!-- View Modal -->
            <div class="modal fade" id="viewModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">@{{ modelHeading }} - @{{ selectedPayroll?.payroll_no }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" v-if="selectedPayroll">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">Employee</th>
                                            <td>@{{ selectedPayroll.staff_name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Period</th>
                                            <td>@{{ getMonthName(selectedPayroll.month) }} @{{ selectedPayroll.year }}</td>
                                        </tr>

                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">Commission</th>
                                            <td class="text-end">@{{ formatCurrency(selectedPayroll.commission) }}</td>
                                        </tr>


                                    </table>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Status:</strong>
                                        <span class="badge" :class="getStatusClass(selectedPayroll.status)">
                                            @{{ selectedPayroll.status }}
                                        </span>
                                    </p>
                                    <p v-if="selectedPayroll.payment_date">
                                        <strong>Payment Date:</strong> @{{ formatDate(selectedPayroll.payment_date) }}
                                    </p>
                                    <p v-if="selectedPayroll.payment_mode">
                                        <strong>Payment Mode:</strong> @{{ selectedPayroll.payment_mode }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p v-if="selectedPayroll.journal_entry_id">
                                        <strong>Journal Entry:</strong>
                                        <span class="badge bg-success">JE-@{{ selectedPayroll.journal_entry_id }}</span>
                                    </p>
                                    <p v-if="selectedPayroll.notes">
                                        <strong>Notes:</strong> @{{ selectedPayroll.notes }}
                                    </p>
                                </div>
                            </div>
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
                payrolls: [],
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

                formPayrollSubmitted: false,
                submit_payroll_button_flag: false,

                modelBtn: '',
                modelHeading: '',
                summary: {},
                months: [{
                        value: 1,
                        name: 'January'
                    },
                    {
                        value: 2,
                        name: 'February'
                    },
                    {
                        value: 3,
                        name: 'March'
                    },
                    {
                        value: 4,
                        name: 'April'
                    },
                    {
                        value: 5,
                        name: 'May'
                    },
                    {
                        value: 6,
                        name: 'June'
                    },
                    {
                        value: 7,
                        name: 'July'
                    },
                    {
                        value: 8,
                        name: 'August'
                    },
                    {
                        value: 9,
                        name: 'September'
                    },
                    {
                        value: 10,
                        name: 'October'
                    },
                    {
                        value: 11,
                        name: 'November'
                    },
                    {
                        value: 12,
                        name: 'December'
                    }
                ],
                years: [],


                month: new Date().getMonth() + 1,
                year: new Date().getFullYear(),
                user_id: '',
                commission: 0,
                total_commission: 0,

                notes: '',


                payment_date: new Date().toISOString().split('T')[0],
                payment_mode: 'cash',


                selectedPayroll: null
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
                generateYears() {
                    const currentYear = new Date().getFullYear();
                    for (let i = currentYear - 2; i <= currentYear + 1; i++) {
                        this.years.push(i);
                    }
                },
                formatCurrency(amount) {
                    return new Intl.NumberFormat('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(amount || 0);
                },
                formatDate(date) {
                    return new Date(date).toLocaleDateString('en-IN');
                },
                getMonthName(month) {
                    return this.months.find(m => m.value == month)?.name || '';
                },
                getStatusClass(status) {
                    const classes = {
                        'draft': 'bg-secondary',
                        'approved': 'bg-info',
                        'paid': 'bg-success',
                        'cancelled': 'bg-danger'
                    };
                    return classes[status] || 'bg-secondary';
                },
                loadEmployees(page = 1) {

                    axios.get("/api/employees/mainlist", {
                        params: {

                            {{-- "employee_id": this.employee_id, --}} "platform": "payroll",

                        }
                    }).then(response => {

                        this.employees = response.data.data.employees;

                        this.loading = false;

                    }).catch(() => {
                        this.loading = false;
                    });
                },
                async loadPayrolls() {

                    axios.get("/api/commission/list", {
                        params: {

                            search: this.search,
                            month: this.filterMonth,
                            year: this.filterYear,
                            status: this.filterStatus,
                            page: this.pagination.current_page || 1
                        }
                    }).then(response => {

                        this.payrolls = response.data.data.payrolls;
                        this.pagination = response.data.data.pagination;

                    }).catch((error) => {
                        console.error('Error loading payrolls:', error);
                        this.loading = false;
                    });
                    this.loadEmployees();
                },

                {{-- onEmployeeChange() {
                    const emp = this.employees.find(e => e.id == this.user_id);
                    if (emp) {
                        this.total_commission = emp.commission || 0;

                    }
                }, --}}

                openModal() {

                    this.user_id = '';
                    this.employee_id = '';
                    this.month = new Date().getMonth() + 1;
                    this.year = new Date().getFullYear();
                    this.total_commission = 0;

                    this.notes = '';

                    this.modelHeading = 'Create Commission Payroll';
                    this.modelBtn = 'Save Commission Payroll';
                    $('#payrollModal').modal('show');

                },
                async savePayroll() {
                    if (!this.user_id || !this.month || !this.year) {
                        alert('Please fill all required fields');
                        return;
                    }

                    if (this.total_commission <= 0) {
                        alert('Total commission must be greater than zero');
                        return;
                    }
                    this.commission = this.total_commission;
                    this.saving = true;
                    try {

                        const payload = {
                            user_id: this.user_id,
                            month: this.month,
                            year: this.year,
                            total_commission: this.total_commission,

                            notes: this.notes
                        };
                        const response = await axios.post('/api/commission/store', payload);
                        if (response.data.status === 'Success') {
                            bootstrap.Modal.getInstance(document.getElementById('payrollModal')).hide();
                            this.loadPayrolls();


                            alert('Payroll created successfully!');
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
                openPayModal(payroll) {

                    this.payment_date = new Date().toISOString().split('T')[0];
                    this.payment_mode = 'cash';
                    this.payroll_id = payroll.id;
                    this.employee_id = payroll.employee_id;
                    this.staff_name = payroll.employee.name;
                    this.month = payroll.month;
                    this.year = payroll.year;
                    this.commission = payroll.commission;


                    this.modelHeading = "Process Payment";
                    this.modelBtn = "Pay Now";

                    this.formPaySubmitted = false;
                    this.formErrors = {};

                    $('#payModal').modal('show');
                },
                async processPayment() {
                    if (!this.payment_date || !this.payment_mode) {
                        alert('Please fill payment details');
                        return;
                    }



                    const formData = new FormData();
                    modalName = 'payModal';

                    formData.append('payroll_id', this.payroll_id);
                    formData.append('employee_id', this.employee_id);
                    formData.append('month', this.month);
                    formData.append('year', this.year);
                    formData.append('commission', this.commission);
                    formData.append('payment_mode', this.payment_mode);
                    formData.append('payment_date', this.payment_date);
                    this.submit_pay_button_flag = true;


                    axios.post(`/api/commission/${this.payroll_id}/pay`, formData)
                        .then(response => {
                            this.showAPIResponse(response, modalName);
                        })
                        .catch(error => {
                            this.submit_button_flag = false;
                            this.formErrors = error.response?.data?.errors || {};
                        });
                },



                resetForm(modalName) {

                    if (modalName == 'payModal') {
                        this.total_commission = '';

                        this.employee_id = '';
                        this.month = new Date().getMonth() + 1;
                        this.year = new Date().getFullYear();
                        this.payment_date = new Date().toISOString().split('T')[0];
                        this.payment_mode = 'cash';
                    }


                },
                showAPIResponse(response, modalName) {
                    if (response.data.code == 0) {

                        Vue.$toast.success(response.data.message, 'Success Alert');

                        if (modalName == 'payModal') {

                            this.submit_pay_button_flag = true;
                            this.formPaySubmitted = true;

                            this.formErrors = {};

                            this.loadPayrolls();
                            this.resetForm(modalName);
                            $('#payModal').modal('hide');
                        }

                        if (modalName == 'payrollModal') {

                            this.submit_payroll_button_flag = true;
                            this.formPayrollSubmitted = true;
                            this.formErrors = {};

                            this.loadPayrolls();
                            this.resetForm(modalName);
                            $('#payrollModal').modal('hide');
                        }

                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        this.submit_button_flag = false;
                        this.submit_pay_button_flag = false;
                    }
                    this.submit_button_flag = false;
                    this.submit_pay_button_flag = false;
                },

                viewPayroll(payroll) {
                    this.selectedPayroll = payroll;

                    this.modelHeading = 'Payroll Details';

                    $('#viewModal').modal('show');
                },
                async cancelPayroll(id) {
                    if (!confirm('Are you sure you want to cancel this commission?')) return;

                    try {
                        const response = await axios.post(`/api/commission/${id}/cancel`);

                        if (response.data.code == 0) {
                            await this.loadPayrolls();
                            Vue.$toast.success(response.data.message, 'Success');
                        } else {
                            Vue.$toast.error(response.data.message, 'Error');
                        }

                    } catch (error) {
                        Vue.$toast.error(
                            error.response?.data?.message || 'Error cancelling commission',
                            'Error'
                        );
                    }
                },
                changePage(page) {
                    this.pagination.current_page = page;
                    this.loadPayrolls();
                },
                resetFilters() {
                    this.search = '';
                    this.filterMonth = '';
                    this.filterYear = new Date().getFullYear();
                    this.filterStatus = '';
                    this.loadPayrolls();
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
                this.generateYears();
                this.loadEmployees();
                this.loadPayrolls();

                this.getLoggedUserInfo();


            },
        });
    </script>
@endsection
