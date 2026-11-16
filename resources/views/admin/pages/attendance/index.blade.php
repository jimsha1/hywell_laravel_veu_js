@extends('admin.layout.base')

@section('main-content')
    <div id="app">
        <div class="page-wrapper">
            <div class="content">
                <div class="page-header">
                    <div class="page-title">
                        <h4>Attendance Management</h4>
                        <h6>Record and manage Employee Attendance</h6>
                    </div>

                </div>

                <!-- Filters -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">

                            <div class="col-md-2">
                                <label>Start Date</label>
                                <input type="date" class="form-control" v-model="filter_date" @change="loadEmployees">

                                {{-- <input type="date" class="form-control" v-model="filter_date"
                                    v-validate.initial="filter_date" id="filter_date" placeholder="Date "> --}}

                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-secondary" @click="resetFilters">Reset</button>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-primary"
                                    onclick="window.location.href='{{ route('monthly_attendance.index') }}'">
                                    <i class="ti ti-plus me-1"></i> Monthly Attendance
                                </button>
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
                                        <th>Employee Name</th>

                                        <th>Check In</th>
                                        <th>Check Out</th>

                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>


                                    <template v-if="!employees  || !employees .length">
                                        <tr>
                                            <td colspan="7">No data found</td>
                                        </tr>
                                    </template>
                                    <template v-else v-for="(data, index) in employees">
                                        <tr>
                                            <td>@{{ index + 1 }}</td>

                                            <td>@{{ data.employee_name }} - @{{ data.employee_code }}</td>
                                            <td v-if="data.check_in != null">
                                                @{{ convertTo12Hour(data.check_in) }}
                                            </td>
                                            <td v-else>--:--</td>

                                            <td v-if="data.check_out != null">
                                                @{{ convertTo12Hour(data.check_out) }}
                                            </td>
                                            <td v-else>--:--</td>

                                            <td
                                                v-if="data.attendance_status == 'Present' || data.attendance_status == 'Absent'">
                                                <span
                                                    v-bind:style="{ color: data.attendance_status == 'Present' ? 'green' : 'red' }">
                                                    @{{ data.attendance_status.toUpperCase() }}
                                                </span><br>last updated at:<br>@{{ getDateTimeFromCompleteFormat(data.attendance_updated) }}
                                            </td>
                                            <td v-else>
                                                <span style="color: red">Not Added</span>
                                            </td>


                                            {{-- <td><a class="btn btn-info btn-sm" href="javascript:void(0)"
                                                    v-on:click="popupModal(data)">
                                                    <i class="fas fa-pencil-alt">
                                                    </i>
                                                    Manage Attendance
                                                </a>
                                            </td> --}}

                                            <td>
                                                <div class="d-flex gap-2">

                                                    <button class="btn btn-primary" @click="openModal(data)">
                                                        <i class="ti ti-plus me-1"></i> Add Attendance
                                                    </button>
                                                    {{-- <button class="btn btn-sm btn-outline-danger"
                                                        @click="deleteExpense(expense.id)">
                                                        <i class="ti ti-trash"></i>
                                                    </button>  --}}
                                                </div>
                                            </td>
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
        <div class="modal fade" id="attendanceModal" role="dialog">
            <div class="modal-dialog modal-dialog-centered 	modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{ modelHeading }}</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="markAttendance" v-if="!formSubmitted" method="post">
                        <div class="modal-body">
                            <div class="row">
                               <!-- Check In & Check Out: only show when Present -->
<div class="col-md-4 mb-3" v-if="status === 'Present'">
    <label>Checkin <span class="text-danger">*</span></label>
    <input type="time" class="form-control" v-model="check_in" id="check_in">
    <span v-if="formErrors['check_in']" class="error text-danger">@{{ formErrors['check_in'] }}</span>
</div>
<div class="col-md-4 mb-3" v-if="status === 'Present'">
    <label>Checkout <span class="text-danger">*</span></label>
    <input type="time" class="form-control" v-model="check_out" id="check_out">
    <span v-if="formErrors['check_out']" class="error text-danger">@{{ formErrors['check_out'] }}</span>
</div>
                                <div class="col-md-4 mb-3">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select v-model="status" class="form-control" v-validate.initial="status"
                                        data-rules="required">

                                        <option value="Present">
                                            PRESENT</option>
                                        <option value="Absent">
                                            ABSENT
                                        </option>
                                    </select>
                                    <span v-if="formErrors['status']"
                                        class="error text-danger">@{{ formErrors['status'] }}</span>
                                    <p class="text-danger" v-if="errors.has('status')">@{{ errors.first('status') }}
                                    </p>


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
                loading: false,
                base_url: "{{ url('/') }}",
                employees: [],

                employee_id: '',
                check_in: '',
                check_out: '',
                status: 'Present',
                modelBtn: '',
                modelHeading: '',


                filter_date: new Date().toISOString().split('T')[0],

                pagination: {
                    total: 0,
                    per_page: 10,
                    from: 1,
                    to: 0,
                    current_page: 1,
                    last_page: 0
                },
                id: '',

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

                convertTo12Hour: function(time24h) {
                    let [hours, minutes] = time24h.split(':');
                    let modifier = 'AM';

                    if (hours >= 12) {
                        modifier = 'PM';
                        hours = hours % 12 || 12; // Handle 12 PM
                        if (hours <= 9) {
                            hours = '0' + hours;
                        }
                    }

                    if (hours === '00') {
                        hours = 12; // Convert 00:00 to 12:00 AM

                    }

                    return hours + ':' + minutes + ' ' + modifier;
                },

                getDateTimeFromCompleteFormat: function(dateTimeString) {
                    const dateTime = new Date(dateTimeString);
                    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
                        'August', 'September', 'October', 'November', 'December'
                    ];
                    const year = dateTime.getFullYear();
                    const month = monthNames[dateTime.getMonth()];
                    const day = String(dateTime.getDate()).padStart(2, '0');

                    let hours = dateTime.getHours();
                    const ampm = hours >= 12 ? 'PM' : 'AM'; // Determine AM or PM
                    hours = hours % 12; // Convert to 12-hour format
                    hours = hours ? hours : 12; // Handle midnight
                    const minutes = String(dateTime.getMinutes()).padStart(2, '0');
                    const seconds = String(dateTime.getSeconds()).padStart(2, '0');

                    return `${month}-${day}/${year} : ${hours}:${minutes}:${seconds} ${ampm}`;
                },



                loadEmployees(page = 1) {

                    axios.get("/api/attendance/employees/list", {
                        params: {
                            "page": page,
                            "page_length": this.pagination.per_page,
                            "employee_id": this.filter_employee_id,
                            "date": this.filter_date,

                        }
                    }).then(response => {

                        this.employees = response.data.data.employees;

                        this.loading = false;

                    }).catch(() => {
                        this.loading = false;
                    });
                },


                resetForm() {

                    this.id = '';
                    this.filter_date = new Date().toISOString().split('T')[0];
                    this.employee_id = '';
                    this.check_in = '';

                    this.check_out = '';
                    this.status = 'Present';
                },
                openModal(data = null) {

                    if (data && data.id) {

                        this.check_in = data.check_in;
                        this.check_out = data.check_out;
                        this.employee_id = data.id;
                        this.status = data.attendance_status;
                        this.modelHeading = "Edit Attendance";
                        this.modelBtn = "Update";

                    } else {

                        this.resetForm();
                        this.status = 'Present';
                        this.modelHeading = "Add Attendance";
                        this.modelBtn = "Add";

                    }

                    this.formSubmitted = false;
                    this.formErrors = {};

                    $('#attendanceModal').modal('show');

                },

                async markAttendance() {


                    const formData = new FormData();

                    formData.append('id', this.id);
                    formData.append('employee_id', this.employee_id);

                    formData.append('date', this.filter_date);
                    formData.append('check_in', this.check_in);
                    formData.append('check_out', this.check_out);

                    formData.append('status', this.status);


                    this.submit_button_flag = true;


                    axios.post("/api/attendance/store", formData)
                        .then(response => {
                            this.showAPIResponse(response);
                        })
                        .catch(error => {
                            this.submit_button_flag = false;
                            this.formErrors = error.response?.data?.errors || {};
                        });

                },

                showAPIResponse(response) {
                    if (response.data.code == 0) {

                        Vue.$toast.success(response.data.message, 'Success Alert');
                        app.submit_button_flag = false;
                        app.formSubmitted = true;
                        app.formErrors = {};

                        this.loadEmployees();
                        this.resetForm();

                        $('#attendanceModal').modal('hide');
                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        app.submit_button_flag = false;
                    }
                    app.submit_button_flag = false;
                },


                changePage(page) {
                    this.pagination.current_page = page;
                    this.loadEmployees();
                },
                resetFilters() {

                    this.filter_date = new Date().toISOString().split('T')[0];

                    this.loadEmployees();
                },
                monthlyAttendance() {

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

                this.getLoggedUserInfo();
                this.loadEmployees();


                $('#attendanceModal').on('hidden.bs.modal', () => {
                    this.resetForm();
                });
            },
            watch: {
    status(newVal) {
        if (newVal === 'Absent') {
            this.check_in  = '';
            this.check_out = '';
        }
    }
},
        });
    </script>
@endsection
