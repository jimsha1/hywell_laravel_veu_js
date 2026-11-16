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
                                    <h4 class="page-title">Financial Years</h4>
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
                                            <input type="text" class="form-control" placeholder="Search Financial Year"
                                                v-model="search" @input="getFinancialYearList">
                                        </div>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="text-sm-end">
                                            <a href="javascript:void(0);" class="btn btn-primary "
                                                v-on:click="showEditFinancialYearModal(0)"><i
                                                    class="ti ti-square-rounded-plus me-2"></i>Create</a>
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

                                                <th>Code</th>
                                                <th>Name</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Status</th>
                                                <th>Closed Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!financialYearList || !financialYearList.length">
                                                <tr>
                                                    <td colspan="7">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in financialYearList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    <td>@{{ data.code }}</td>
                                                    <td>@{{ data.name }}</td>
                                                    <td>@{{ formatDate(data.start_date) }}</td>
                                                    <td>@{{ formatDate(data.end_date) }}</td>

                                                    <td>
                                                        <span
                                                            :class="data.is_active == 1 ? 'badge bg-success' :
                                                                'badge bg-danger'">
                                                            <p v-if="data.is_active==1">Active</p>
                                                            <p v-else>Inactive</p>
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <span
                                                            :class="data.is_closed == 0 ? 'badge bg-success' :
                                                                'badge bg-danger'">
                                                            <p v-if="data.is_closed==0">Opened</p>
                                                            <p v-else>Closed</p>
                                                        </span>
                                                    </td>
                                                    {{-- <td> --}}
                                                    {{-- <div class="dropdown table-action"><a href="#"
                                                                data-bs-toggle="dropdown" aria-expanded="false"
                                                                class="action-icon"><i class="fa fa-ellipsis-v"></i></a>
                                                            <div class="dropdown-menu dropdown-menu-right" style="">
                                                                <a class="dropdown-item"
                                                                    v-on:click="showEditFinancialYearModal(data)"><i
                                                                        class="ti ti-edit text-blue"></i>Edit</a>
                                                                <a class="dropdown-item"
                                                                    v-on:click="deleteItem(data,index)"><i
                                                                        class="ti ti-trash text-red"></i>Close</a>
                                                            </div>
                                                        </div> --}}

                                                    <td class="text-end pe-4">
                                                        <div class="d-flex justify-content-end gap-2">
                                                            <button class="btn btn-sm btn-outline-info"
                                                                @click="showEditFinancialYearModal(data)" title="Edit">
                                                                <i class="ti ti-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger"
                                                                @click="deleteItem(data, index)" title="Close">
                                                                <i class="ti ti-lock"></i>
                                                            </button>

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

        <div class="modal fade" id="add_financial_year" role="dialog">
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
                                        <label class="col-form-label">Start Date</label>
                                        <input type="date" class="form-control" name="start_date"
                                            v-model="start_date" placeholder="Start Date">
                                        <span v-if="formErrors['start_date']"
                                            class="error text-danger">@{{ formErrors['start_date'] }}</span>
                                        <p class="text-danger" v-if="errors.has('start_date')">@{{ errors.first('start_date') }}
                                        </p>
                                    </div>

                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">End Date</label>
                                        <input type="date" class="form-control" name="end_date" v-model="end_date"
                                            placeholder="End Date">
                                        <span v-if="formErrors['end_date']"
                                            class="error text-danger">@{{ formErrors['end_date'] }}</span>
                                        <p class="text-danger" v-if="errors.has('end_date')">@{{ errors.first('end_date') }}
                                        </p>
                                    </div>



                                </div>
                            </div>


                            <div class="col-md-12">
                                <div class="row">
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
                                <div class="row">
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Status</label>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <input type="radio" class="is_closed-radio" id="add-active"
                                                    name="is_closed" value="1" v-model="is_closed">
                                                <label for="add-active">Opened</label>
                                            </div>
                                            <div>
                                                <input type="radio" class="is_closed-radio" id="add-inactive"
                                                    name="is_closed" value="0" v-model="is_closed">
                                                <label for="add-inactive">Closed</label>
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

                financialYearList: [],

                financial_year_id: '',
                end_date: '',
                start_date: '',

                status: 1,
                is_closed: 1,

                modelBtn: '',
                modelHeading: '',

                formSubmitted: false,
                submit_button_flag: false,

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
                validateBeforeSubmit() {
                    this.$validator.validateAll();
                    console.log(this.errors);
                    if (!this.errors.any()) {
                        this.enterFinancialYear();
                    }
                },

                enterFinancialYear() {


                    const formData = new FormData();


                    formData.append("start_date", this.start_date);
                    formData.append("end_date", this.end_date);
                    formData.append("status", this.status);
                    formData.append("is_closed", this.is_closed);


                    this.submit_button_flag = true;

                    if (this.financial_year_id > 0) {
                        formData.append("_method", "put");
                        formData.append("id", this.financial_year_id);


                        axios.post("/api/financial-year/update", formData)
                            .then(response => {
                                this.showAPIResponse(response);
                            })
                            .catch(error => {
                                this.submit_button_flag = false;
                                this.formErrors = error.response.data.errors;
                            });

                    } else {
                        axios.post("/api/financial-year/store", formData)
                            .then(response => {
                                this.showAPIResponse(response);
                            })
                            .catch(error => {
                                this.submit_button_flag = false;
                                this.formErrors = error.response.data.errors;
                            });
                    }
                },



                showAPIResponse(response) {
                    if (response.data.code == 0) {

                        Vue.$toast.success(response.data.message, 'Success Alert');
                        this.submit_button_flag = false;
                        this.formSubmitted = true;
                        this.formErrors = {};

                        this.getFinancialYearList();
                        this.resetForm();
                        $('#add_financial_year').modal('hide');
                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        this.submit_button_flag = false;
                    }
                    this.submit_button_flag = false;
                },

                showEditFinancialYearModal(data) {
                    if (data.id > 0) {

                        this.start_date = this.formatDate(data.start_date);

                        this.end_date = this.formatDate(data.end_date);
                        if (data.is_active == true) {
                            this.status = 1;
                        } else {
                            this.status = 0;
                        }
                        if (data.is_closed == true) {
                            this.is_closed = 1;
                        } else {
                            this.is_closed = 0;
                        }
                        this.financial_year_id = data.id;



                        this.formSubmitted = false;
                        this.formErrors = {};

                        this.modelHeading = "Edit Financial Year";
                        this.modelBtn = "Update";

                        $('#add_financial_year').modal('show');

                    } else {
                        this.resetForm();
                        this.modelHeading = "Add Financial Year";
                        this.modelBtn = "Create";

                        $('#add_financial_year').modal('show');
                    }

                },

                deleteItem(data) {
                    Swal.fire({
                        title: "Are you sure you want to close this financial year " + data.id + "?",
                        text: "You won't be able to revert this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, Close it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/financial-year/close", {
                                    data: {
                                        id: data.id,

                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.error("Error Closing Financial Year:", error);
                                });
                        }
                    });
                },

                getFinancialYearList: function(page = 1) {
                    const url = "/api/financial-year/list";

                    axios.get(url, {
                        params: {
                            page: page,
                            page_length: this.page_length,
                            search: this.search,
                        }
                    }).then(response => {
                        app.financialYearList = response.data.data.financial_years;
                        app.pagination = response.data.data.pagination;
                        app.loading = false;
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
                            $('#user_image_header').attr('src', this.base_url + '/storage/' +
                                userImage);
                        } else {
                            const defaultImg = 'https://cdn-icons-png.freepik.com/512/9703/9703596.png';
                            $('#user_image').attr('src', defaultImg);
                            $('#user_image_header').attr('src', defaultImg);
                        }
                    }
                },

                resetForm() {

                    app.start_date = '';
                    app.end_date = '';

                    app.status = 1;
                    app.is_closed = 1;
                    app.financial_year_id = '';

                    app.formSubmitted = false;
                    app.formErrors = {};


                    if (this.errors && this.errors.clear) {
                        this.errors.clear();
                    }
                },

                formatDate(date) {
                    const d = new Date(date);
                    const year = d.getFullYear();
                    const month = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');

                    return `${year}-${month}-${day}`;
                },
            },

            mounted() {
                this.getLoggedUserInfo();
                this.getFinancialYearList();
                $('#add_financial_year').on('hidden.bs.modal', () => {
                    this.resetForm();
                });

            },
        });
    </script>
@endsection
