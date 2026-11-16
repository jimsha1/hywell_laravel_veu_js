@extends('admin.layout.base')

@section('main-content')
    <div id="app">
        <div class="page-wrapper">
            <div class="content">
                <div class="page-header">
                    <div class="page-title">
                        <h4>Expense Management</h4>
                        <h6>Record and manage business expenses</h6>
                    </div>


                    <div class="col-sm-12 mt-3 d-flex justify-content-sm-end gap-2">


                        <a href="javascript:void(0);" class="btn btn-primary" @click="openModal(0)">
                            <i class="ti ti-square-rounded-plus me-2"></i>Add Expense
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Search</label>
                                <input type="text" class="form-control" v-model="search" @input="loadExpenses"
                                    placeholder="Search...">
                            </div>
                            {{-- <div class="col-md-2">
                                <label>Account</label>
                                <select class="form-select" v-model="filterCategory" @change="loadExpenses">
                                    <option value="">All Categories</option>
                                    <option v-for="cat in accounts" :key="cat.id" :value="cat.id">
                                        @{{ cat.name }}</option>
                                </select>
                            </div> --}}
                            <div class="col-md-2">
                                <label>Start Date</label>
                                <input type="date" class="form-control" v-model="startDate" @change="loadExpenses">
                            </div>
                            <div class="col-md-2">
                                <label>End Date</label>
                                <input type="date" class="form-control" v-model="endDate" @change="loadExpenses">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-secondary" @click="resetFilters">Reset</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="alert-danger text-danger alert">
                            <div class="card-body text-center">
                                <h6 class="text-danger">Total Expenses (This Month)</h6>
                                <h4 class="text-danger">@{{ formatCurrency(total_expenses || 0) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expenses Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Expense No</th>
                                        <th>Expense Title</th>
                                        <th>Account</th>
                                        <th>Date</th>

                                        <th>Description</th>
                                        <th>Payment Mode</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>


                                    <template v-if="!expenses || !expenses.length">
                                        <tr>
                                            <td colspan="7">No data found</td>
                                        </tr>
                                    </template>
                                    <template v-else v-for="(data, index) in expenses">
                                        <tr>
                                            <td>@{{ index + 1 }}</td>

                                            <td>@{{ data.expense_no }}</td>
                                            <td>@{{ data.expense_type }}</td>
                                            <td>@{{ data.cr_account.name }}</td>
                                            <td>@{{ formatDate(data.expense_date) }}</td>
                                            <td>@{{ data.description }}</td>

                                            <td>@{{ data.payment_mode }}</td>
                                            <td>@{{ data.amount }}</td>


                                            <td>
                                                <span class="badge" :class="getStatusClass(data.status)">
                                                    @{{ data.status }}
                                                </span>
                                            </td>

                                            <td v-if="data.status === 'pending'">
                                                <button class="btn btn-transparent p-0" @click="openModal(data)">
                                                    <i class="ti ti-edit text-blue"></i>
                                                </button>
                                            </td>
                                            {{-- <td v-else-if="data.status === 'approved'">
                                                <button class="btn btn-primary" @click="payExpenseModal(data)">
                                                    Pay
                                                </button>
                                            </td> --}}
                                            {{-- <td v-else-if="data.status === 'paid'">
                                                <button class="btn btn-success">
                                                    Paid
                                                </button>
                                            </td> --}}
                                            <td v-else>
                                                {{-- <button class="btn btn-warning">
                                                    Rejected
                                                </button> --}}
                                            </td>
                                            {{-- <td>
                                                <div class="d-flex gap-2">

                                                    <button class="btn btn-transparent p-0" @click="viewExpense(data)">
                                                        <i class="ti ti-edit text-blue"></i>
                                                    </button>

                                                    <button class="btn btn-transparent p-0"
                                                        @click="viewExpense(expense)">
                                                        <i class="ti ti-edit text-blue"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger"
                                                        @click="deleteExpense(expense.id)">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </div>
                                            </td> --}}
                                        </tr>
                                    </template>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            {{-- <nav aria-label="pagination">
                                <ul class="pagination justify-content-end">
                                    <li class="page-item" v-if="pagination.current_page > 1">
                                        <a class="page-link" href="#"
                                            @click.prevent="changePage(pagination.current_page - 1)">
                                            <span aria-hidden="true">«</span>
                                        </a>
                                    </li>
                                    <li class="page-item" v-for="page in pagesNumber"
                                        :class="{ active: page === isActived }">
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
                            </nav> --}}
                            <!-- End Pagination -->
                        </div>


                    </div>
                </div>
            </div>

        </div>
        <div class="modal fade" id="expenseModal" role="dialog">
            <div class="modal-dialog modal-dialog-centered 	modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{ modelHeading }}</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="saveExpense" v-if="!formSubmitted" method="post">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Expense Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" v-model="expense_type" required>
                                </div>
                                <div class="col-md-6 mb-3">

                                    <label>Account Name(Dr) <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="dr_account_id" required>
                                        <option value="">Select Account</option>
                                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                                            @{{ account.name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label>Account Name(cr) <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="cr_account_id" required>
                                        <option value="">Select Account</option>
                                        <option v-for="account in liabilityAccounts" :key="account.id" :value="account.id">
                                            @{{ account.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Expense Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" v-model="expense_date">
                                    {{-- required :max="todayDate"> --}}
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" v-model="amount" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Payment Mode <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="payment_mode" required>
                                        <option value="CASH">CASH</option>
                                        <option value="ALRAJHI">ALRAJHI</option>
                                        <option value="SAB"> SAB</option>
                                        
                                    </select>
                                </div>

                                <div class="col-12 mb-3">
                                    <label>Description</label>
                                    <textarea class="form-control" v-model="description" rows="2" placeholder="Expense details..."></textarea>
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

        <div class="modal fade" id="expensePaymentModal" role="dialog">
            <div class="modal-dialog modal-dialog-centered 	modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{ modelHeading }}</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="payExpense" v-if="!formPaySubmitted" method="post">
                        <div class="modal-body">
                            <div class="row">

                                <div class="col-md-6 mb-3">
                                    <label>Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" v-model="amount" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Payment Mode <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="payment_mode" required>
                                        <option value="CASH">CASH</option>
                                        <option value="ALRAJHI"> ALRAJHI</option>
                                        <option value="SAB"> SAB</option>
                                       
                                    </select>
                                </div>

                            </div>
                            <div class="modal-footer">
                                <div class="d-flex align-items-center justify-content-end m-0">
                                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                                    <button type="submit" class="btn btn-primary"
                                        v-bind:disabled="submitPay_button_flag">@{{ modelBtn }}</button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <!-- View Modal -->
        {{-- <div class="modal fade" id="viewModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Expense Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" v-if="selectedExpense">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Expense No</th>
                                <td>@{{ selectedExpense.expense_no }}</td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td>@{{ formatDate(selectedExpense.expense_date) }}</td>
                            </tr>
                            <tr>
                                <th>Category</th>
                                <td>@{{ selectedExpense.category?.name }}</td>
                            </tr>
                            <tr>
                                <th>Amount</th>
                                <td class="text-danger fw-bold">@{{ formatCurrency(selectedExpense.amount) }}</td>
                            </tr>
                            <tr>
                                <th>Payment Mode</th>
                                <td>@{{ selectedExpense.payment_mode }}</td>
                            </tr>

                            <tr>
                                <th>Description</th>
                                <td>@{{ selectedExpense.description || '-' }}</td>
                            </tr>
                            <tr>
                                <th>Created By</th>
                                <td>@{{ selectedExpense.creator?.name || '-' }}</td>
                            </tr>
                            <tr v-if="selectedExpense.journal_entry_id">
                                <th>Journal Entry</th>
                                <td><span class="badge bg-success">JE-@{{ selectedExpense.journal_entry_id }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div> --}}
    </div>
@endsection


@section('script')
    <script>
        const app = new Vue({
            el: '#app',
            data: {
                loading: false,
                base_url: "{{ url('/') }}",
                expenses: [],
                accounts: [],
                liabilityAccounts: [],


                saving: false,
                search: '',
                filterCategory: '',
                startDate: new Date(new Date().setDate(1)).toISOString().split('T')[0],
                endDate: new Date().toISOString().split('T')[0],

                summary: {},

                expense_type: '',
                dr_account_id: '',
                cr_account_id: '',
                expense_date: '',
                amount: '',

                payment_mode: 'CASH',
                total_expenses: 0,
                description: '',

                selectedExpense: null,
                pagination: {
                    total: 0,
                    per_page: 10,
                    from: 1,
                    to: 0,
                    current_page: 1,
                    last_page: 0
                },
                id: '',
                modelBtn: '',
                modelHeading: '',
                offset: 4,
                formErrors: {},
                formSubmitted: false,
                submit_button_flag: false,
                formPaySubmitted: false,
                submitPay_button_flag: false,
            },

            computed: {
                isActived() {
                    return this.pagination.current_page;
                },
                todayDate() {
                    return new Date().toISOString().split('T')[0];
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


                formatCurrency(amount) {
                    return new Intl.NumberFormat('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(amount || 0);
                },
                // formatDate(date) {
                //     {{-- return new Date(date).toLocaleDateString('en-IN'); --}}

                //     const d = new Date(date);

                //     const day = String(d.getDate()).padStart(2, '0');
                //     const month = String(d.getMonth() + 1).padStart(2, '0');
                //     const year = d.getFullYear();

                //     return `${day}-${month}-${year}`;
                // },
                formatDate(date) {
                    if (!date) return '';
                    const parts = date.substring(0, 10).split('-');
                    return `${parts[2]}-${parts[1]}-${parts[0]}`;
                },
                getStatusClass(status) {
                    const classes = {
                        'approved': 'bg-success',
                        'pending': 'bg-warning',
                        'rejected': 'bg-danger',
                        'paid': 'bg-success'
                    };
                    return classes[status] || 'bg-secondary';
                },

                loadCategories(page = 1) {

                    axios.get("/api/expenses/accounts/list", {
                        params: {
                            page: page,
                            page_length: this.pagination.per_page,
                            search: this.search,
                        }
                    }).then(response => {

                        this.accounts = response.data.data;
                        {{-- this.pagination = response.data.data.pagination; --}}
                        this.loading = false;

                    }).catch(() => {
                        this.loading = false;
                    });
                },
                loadExpenses(page = 1) {

                    this.loading = true;

                    axios.get("/api/expenses/list", {
                        params: {
                            page: page,
                            page_length: this.pagination.per_page,


                            search: this.search,
                            category_id: this.filterCategory,
                            start_date: this.startDate,
                            end_date: this.endDate,

                        }
                    }).then(response => {

                        this.expenses = response.data.data.expenses;
                        this.total_expenses = response.data.data.total_expenses;
                        this.pagination = response.data.data.pagination;
                        this.loading = false;

                    }).catch(() => {
                        this.loading = false;
                    });

                },
                loadLiabilityAccounts() {
                    axios.get("/api/expenses/liability/list")
                        .then(response => {
                            this.liabilityAccounts = response.data.data;
                        })
                        .catch(() => {
                            console.error('Failed to load liability accounts');
                        });
                },
                async loadSummary() {
                    try {
                        const response = await axios.get('/api/expenses/summary/report', {
                            params: {
                                start_date: this.startDate,
                                end_date: this.endDate
                            }
                        });
                        if (response.data.status === 'success') {
                            this.summary = response.data.data;
                        }
                    } catch (error) {
                        console.error('Error loading summary:', error);
                    }
                },
                resetForm() {
                    this.id = '';
                    this.expense_type = '';
                    this.dr_account_id = '';
                    this.cr_account_id = '';
                    this.expense_date = '';
                    this.amount = '';

                    this.payment_mode = 'CASH';

                    this.description = '';
                },
                openModal(data = null) {

                    if (data && data.id) {

                        this.id = data.id;
                        this.expense_type = data.expense_type;
                        this.dr_account_id = data.account_id;
                        this.cr_account_id = data.cr_account_id;
                        this.expense_date = data.expense_date ? data.expense_date.substring(0, 10) : '';

                        this.amount = data.amount;
                        this.payment_mode = data.payment_mode;
                        this.description = (data.description && data.description !== 'null') ? data.description :
                            '';
                        this.modelHeading = "Edit Expense";
                        this.modelBtn = "Update";

                    } else {

                        this.resetForm();
                        this.modelHeading = "Add Expense";
                        this.modelBtn = "Create";

                    }

                    this.formSubmitted = false;
                    this.formErrors = {};

                    $('#expenseModal').modal('show');

                },
                payExpenseModal(data = null) {



                    this.id = data.id;
                    this.amount = data.amount;
                    this.payment_mode = 'CASH';

                    this.modelHeading = "Pay Expense";
                    this.modelBtn = "Pay";


                    this.formSubmitted = false;
                    this.formErrors = {};

                    $('#expensePaymentModal').modal('show');

                },
                async saveExpense() {


                    const formData = new FormData();

                    formData.append("expense_type", this.expense_type);
                    formData.append("dr_account_id", this.dr_account_id);
                    formData.append("cr_account_id", this.cr_account_id);
                    formData.append("expense_date", this.expense_date);
                    formData.append("amount", this.amount);
                    formData.append("payment_mode", this.payment_mode);
                    formData.append("description", this.description);



                    this.submit_button_flag = true;

                    if (this.id) {

                        formData.append("_method", "put");
                        formData.append("id", this.id);

                        axios.post("/api/expenses/update", formData)
                            .then(response => {
                                app.showAPIResponse(response);
                            })
                            .catch(error => {
                                app.submit_button_flag = false;
                                app.formErrors = error.response.data.errors;
                            });

                    } else {

                        axios.post("/api/expenses/store", formData)
                            .then(response => {
                                this.showAPIResponse(response);
                            })
                            .catch(error => {
                                this.submit_button_flag = false;
                                this.formErrors = error.response?.data?.errors || {};
                            });
                    }
                },

                async payExpense() {


                    const formData = new FormData();



                    this.submitPay_button_flag = true;


                    formData.append("_method", "put");
                    formData.append("id", this.id);
                    formData.append("payment_mode", this.payment_mode);

                    axios.post("/api/expenses/pay", formData)
                        .then(response => {
                            if (response.data.code == 0) {

                                Vue.$toast.success(response.data.message, 'Success Alert');
                                app.submitPay_button_flag = false;

                                app.formPaymentSubmitted = true;
                                app.formErrors = {};

                                this.loadExpenses();
                                app.resetForm();

                                $('#expensePaymentModal').modal('hide');
                            } else {
                                Vue.$toast.error(response.data.message, 'Error Alert');
                                app.submitPay_button_flag = false;
                            }
                            app.submitPay_button_flag = false;
                        })
                        .catch(error => {
                            app.submitPay_button_flag = false;
                            app.formErrors = error.response.data.errors;
                        });


                },
                showAPIResponse(response) {
                    if (response.data.code == 0) {

                        Vue.$toast.success(response.data.message, 'Success Alert');
                        app.submit_button_flag = false;

                        app.formSubmitted = true;
                        app.formErrors = {};

                        this.loadExpenses();
                        app.resetForm();

                        $('#expenseModal').modal('hide');
                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        app.submit_button_flag = false;
                    }
                    app.submit_button_flag = false;
                },
                viewExpense(expense) {
                    this.selectedExpense = expense;
                    new bootstrap.Modal(document.getElementById('viewModal')).show();
                },
                {{-- async deleteExpense(id) {
                    if (!confirm('Are you sure you want to delete this expense?')) return;

                    try {
                        const response = await axios.delete(`/api/expenses/${id}`);
                        if (response.data.status === 'success') {
                            this.loadExpenses();
                            this.loadSummary();
                            alert('Expense deleted successfully');
                        }
                    } catch (error) {
                        alert(error.response?.data?.message || 'Error deleting expense');
                    }
                }, --}}
                changePage(page) {
                    this.pagination.current_page = page;
                    this.loadExpenses();
                },
                resetFilters() {
                    this.search = '';
                    this.filterCategory = '';
                    this.startDate = new Date(new Date().setDate(1)).toISOString().split('T')[0];
                    this.endDate = new Date().toISOString().split('T')[0];
                    this.loadExpenses();
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


                this.loadCategories();
                this.loadLiabilityAccounts();
                this.loadExpenses();
                this.loadSummary();
                this.getLoggedUserInfo();
                $('#expenseModal').on('hidden.bs.modal', () => {
                    this.resetForm();
                });
            }
        });
    </script>
@endsection
