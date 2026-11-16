@extends('admin.layout.base')

@section('main-content')
    <div id="app">
        <div class="page-wrapper">
            <div class="content">
                <div class="page-header">
                    <div class="page-title">
                        <h4>Leave Management</h4>
                        {{-- <h6>Record and manage Employee Attendance</h6> --}}
                    </div>

                </div>

                <!-- Filters -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row mt-3">

                            <div class="col-md-4 mt-3">

                                <label>Employee</label>
                                <select v-model="filter_employee_name" class="form-control"
                                    v-validate.initial="filter_employee_name" @change="loadLeaveRequestList">
                                    <option value="" selected>-Select-</option>
                                    <template v-for="(data,index) in employeeList">
                                        <option v-bind:value="data.id">
                                            @{{ data.name }}- @{{ data.employee_code }}</option>
                                    </template>

                                </select>

                            </div>
                            <div class="col-md-4 mt-3">
                                <label>Leave Start Date</label>
                                <input type="date" class="form-control" v-model="filter_leave_start_date"
                                    @change="loadLeaveRequestList">

                                {{-- <input type="date" class="form-control" v-model="filter_leave_start_date"
                                    v-validate.initial="filter_leave_start_date" id="filter_leave_start_date" placeholder="Date "> --}}
                            </div>
                            <div class="col-md-4 mt-3">
                                <label>Leave End Date</label>
                                <input type="date" class="form-control" v-model="filter_leave_end_date"
                                    @change="loadLeaveRequestList ">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4 mb-3">
                                <label>Leave Type</label>
                                <select v-model="filter_leave_type" class="form-control" @change="loadLeaveRequestList">
                                    <option value="" selected>-Select-</option>

                                    @foreach (Config::get('constants.LEAVE_TYPE') as $leave_type)
                                        <option value="{{ $leave_type }}">
                                            {{ $leave_type }}</option>
                                    @endforeach



                                </select>

                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Leave Status</label>
                                <select v-model="filter_leave_status" class="form-control" @change="loadLeaveRequestList">
                                    <option value="" selected>-Select-</option>
                                    @foreach (Config::get('constants.LEAVE_STATUS', 'PENDING') as $leave_status)
                                        <option value="{{ $leave_status }}">
                                            {{ $leave_status }}</option>
                                    @endforeach




                                </select>

                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-secondary" @click="resetFilters">Reset</button>
                        </div>
                        {{-- <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-primary"
                                    onclick="window.location.href='{{ route('monthly_attendance.index') }}'">
                                    <i class="ti ti-plus me-1"></i> Monthly Attendance
                                </button>
                            </div> --}}

                    </div>
                </div>



                <!-- Leave Request Table -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Requested Leave List</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Employee Name</th>

                                        <th>Leave Start Date</th>
                                        <th>Leave End Date</th>

                                        <th>Leave Type</th>
                                        <th>Status of Leave</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>


                                    <template v-if="!leaveReportList   || !leaveReportList  .length">
                                        <tr>
                                            <td colspan="7">No data found</td>
                                        </tr>
                                    </template>
                                    <template v-else v-for="(data, index) in leaveReportList ">
                                        <tr>
                                            <td>@{{ index + 1 }}</td>

                                            <td>@{{ data.employee.name }} - @{{ data.employee.employee_code }}</td>
                                            <td>@{{ getDateFromCompleteFormat(data.leave_start) }}</td>
                                            <td>@{{ getDateFromCompleteFormat(data.leave_end) }}</td>
                                            <td>@{{ data.leave_type }}</td>
                                            <td v-if="data.status=='pending'">
                                                <span style="color:blue">PENDING</span>
                                            </td>
                                            <td v-if="data.status=='approved'"><span style="color:green">APPROVED</span>
                                            </td>
                                            <td v-if="data.status=='rejected'"><span style="color:red">REJECTED</span>
                                            </td>
                                            <td v-if="data.status=='cancelled'"><span style="color:orange">CANCELLED</span>
                                            </td>

                                            <td>

                                                <button class="btn btn-primary" @click="openModal(data)">
                                                    <i class="fas fa-pencil-alt"></i> Change Status
                                                </button>
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
            </div>

        </div>
        <div class="modal fade" id="leaveRequestModal" role="dialog">
            <div class="modal-dialog modal-dialog-centered 	modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{ modelHeading }}</h5>
                        <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                            aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="changeLeaveRequestStatus" v-if="!formSubmitted" method="post">
                        <div class="modal-body">
                            <div class="row">

                                <div class="col-md-6 mb-3">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select v-model="status" class="form-control">
                                        <option value="">-Select-</option>
                                        @foreach (Config::get('constants.LEAVE_STATUS') as $leave_status)
                                            <option value="{{ $leave_status }}">
                                                {{ strtoupper($leave_status) }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Comment <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="comments" v-model="comments" rows="3" value=""
                                        placeholder="Comment"></textarea>

                                    <span v-if="formErrors['comments']"
                                        class="error text-danger">@{{ formErrors['comments'] }}</span>

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
                leaveReportList: [],
                employeeList: [],

                employee_id: '',
                leave_request_id: '',
                comments: '',
                status: '',
                modelBtn: '',
                modelHeading: '',


                filter_leave_start_date: '',
                filter_leave_end_date: '',
                filter_employee_name: '',
                filter_leave_type: '',
                filter_leave_status: '',

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

                getDateFromCompleteFormat: function(dateTimeString) {
                    const dateTime = new Date(dateTimeString);
                    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
                        'August', 'September', 'October', 'November', 'December'
                    ];
                    const year = dateTime.getFullYear();
                    const month = monthNames[dateTime.getMonth()];
                    const day = String(dateTime.getDate()).padStart(2, '0');



                    return `${month}-${day}/${year}`;
                },


                loadEmployees(page = 1) {

                    axios.get("/api/employees/mainlist", {
                        params: {
                            "page": page,
                            "page_length": this.pagination.per_page,
                            "employee_id": this.filter_employee_id,
                            "platform": "leave",

                        }
                    }).then(response => {

                        this.employeeList = response.data.data.employees;

                        this.loading = false;

                    }).catch(() => {
                        this.loading = false;
                    });
                },


                loadLeaveRequestList(page = 1) {

                    axios.get("/api/leave-request/list", {
                        params: {
                            "page": page,
                            "page_length": this.pagination.per_page,

                            "employee_name": this.filter_employee_name,
                            "leave_type": this.filter_leave_type,
                            "leave_start_date": this.filter_leave_start_date,
                            "leave_end_date": this.filter_leave_end_date,
                            "leave_status": this.filter_leave_status,


                        }
                    }).then(response => {

                        this.leaveReportList = response.data.data.data.data;

                        this.loading = false;

                    }).catch(() => {
                        this.loading = false;
                    });
                },


                resetForm() {

                    this.leave_request_id = '';

                    this.employee_id = '';
                    this.status = '';

                    this.comments = '';

                },
                openModal(data = null) {

                    this.leave_request_id = data.id;
                    this.status = data.status;
                    this.comments = data.comments != 'null' ? data.comments : '';

                    this.modelHeading = "Change Leave Request Status";
                    this.modelBtn = "Change";

                    this.formSubmitted = false;
                    this.formErrors = {};

                    $('#leaveRequestModal').modal('show');

                },

                async changeLeaveRequestStatus() {


                    const formData = new FormData();

                    formData.append('leave_request_id', this.leave_request_id);
                    formData.append('leave_comment', this.comments);
                    formData.append('leave_status', this.status);
                    this.submit_button_flag = true;


                    axios.post("/api/leave-status/change", formData)
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
                        this.submit_button_flag = false;
                        this.formSubmitted = true;
                        this.formErrors = {};

                        this.loadLeaveRequestList();
                        this.resetForm();

                        $('#leaveRequestModal').modal('hide');
                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        this.submit_button_flag = false;
                    }
                    this.submit_button_flag = false;
                },


                changePage(page) {
                    this.pagination.current_page = page;
                    this.loadLeaveRequestList();
                },
                resetFilters() {

                    this.filter_employee_name = '';
                    this.filter_leave_type = '';
                    this.filter_leave_start_date = '';
                    this.filter_leave_end_date = '';
                    this.filter_leave_status = '';
                    this.loadLeaveRequestList();
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
                this.loadLeaveRequestList();
                this.getLoggedUserInfo();


                $('#leaveRequestModal').on('hidden.bs.modal', () => {
                    this.resetForm();
                });
            }
        });
    </script>
@endsection
