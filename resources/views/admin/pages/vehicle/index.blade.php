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
                                    <h4 class="page-title">Vehicle</h4>
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
                                            <input type="text" class="form-control" placeholder="Search Vehicle"
                                                v-model="search" @input="getVehicleList">
                                        </div>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="text-sm-end">
                                            <a href="javascript:void(0);" class="btn btn-primary "
                                                v-on:click="showEditVehicleModal(0)"><i
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

                                                <th>Vehicle Code</th>
                                                <th>Vehicle Name</th>
                                                <th>Vehicle Plate No</th>
                                                <th>Vehicle Type</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!vehicleList || !vehicleList.length">
                                                <tr>
                                                    <td colspan="7">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in vehicleList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>

                                                    <td>@{{ data.vehicle_code }}</td>
                                                    <td>@{{ data.vehicle_name }}</td>
                                                    <td>@{{ data.vehicle_plate_no }}</td>
                                                    <td>@{{ data.vehicle_type }}</td>
                                                    <td>
                                                        <span
                                                            :class="data.status === '1' ? 'badge bg-success' :
                                                                'badge bg-danger'">
                                                            <p v-if="data.status=='1'">Active</p>
                                                            <p v-else>Inactive</p>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown table-action"><a href="#"
                                                                data-bs-toggle="dropdown" aria-expanded="false"
                                                                class="action-icon"><i class="fa fa-ellipsis-v"></i></a>
                                                            <div class="dropdown-menu dropdown-menu-right" style="">
                                                                <a class="dropdown-item"
                                                                    v-on:click="showEditVehicleModal(data)"><i
                                                                        class="ti ti-edit text-blue"></i>Edit</a>
                                                                <a class="dropdown-item"
                                                                    v-on:click="deleteItem(data,index)"><i
                                                                        class="ti ti-trash text-red"></i>Delete</a>
                                                            </div>
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

        <div class="modal fade" id="add_vehicle" role="dialog">
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
                                        <label class="col-form-label">Vehicle Type<span
                                                class="text-danger">*</span></label>
                                        <select v-model="vehicle_type" class="form-select"
                                            v-validate.initial="vehicle_type" data-rules="required" name="vehicle_type">
                                            <option value="">Choose</option>
                                            <option value="{{ config('constants.TRUCK') }}">
                                                {{ config('constants.TRUCK') }}</option>
                                            <option value="{{ config('constants.VAN') }}">
                                                {{ config('constants.VAN') }}</option>
                                        </select>
                                        <span v-if="formErrors['vehicle_type']"
                                            class="error text-danger">@{{ formErrors['vehicle_type'] }}</span>
                                        <p class="text-danger" v-if="errors.has('vehicle_type')">@{{ errors.first('vehicle_type') }}
                                        </p>
                                    </div>

                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Vehicle Name<span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="vehicle_name"
                                            v-model="vehicle_name" placeholder="Vehicle Name" name="vehicle_name"
                                            v-validate.initial="vehicle_name" data-rules="required">
                                        <span v-if="formErrors['vehicle_name']"
                                            class="error text-danger">@{{ formErrors['vehicle_name'] }}</span>
                                        <p class="text-danger" v-if="errors.has('vehicle_name')">@{{ errors.first('vehicle_name') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Vehicle Plate No <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="vehicle_plate_no"
                                            v-validate.initial="vehicle_plate_no" data-rules="required"
                                            v-model="vehicle_plate_no" placeholder="Vehicle Reg No">
                                        <span v-if="formErrors['vehicle_plate_no']"
                                            class="error text-danger">@{{ formErrors['vehicle_plate_no'] }}</span>
                                        <p class="text-danger" v-if="errors.has('vehicle_plate_no')">
                                            @{{ errors.first('vehicle_plate_no') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Date of Manufacture</label>
                                        <input type="date" class="form-control" name="manufacture_date"
                                            v-model="manufacture_date" placeholder="Date of Manufacture"
                                            name="unit_price" :max="todayDate">
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Fuel Type</label>
                                        <select v-model="fuel_type" class="form-select" v-validate.initial="fuel_type"
                                            data-rules="required" name="fuel_type">
                                            <option value="">Choose</option>
                                            <option value="{{ config('constants.PETROL') }}">
                                                {{ config('constants.PETROL') }}</option>
                                            <option value="{{ config('constants.DIESEL') }}">
                                                {{ config('constants.DIESEL') }}</option>
                                            <option value="{{ config('constants.ELECTRICAL') }}">
                                                {{ config('constants.ELECTRICAL') }}</option>
                                            <option value="{{ config('constants.HYBRID') }}">
                                                {{ config('constants.HYBRID') }}</option>
                                        </select>
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

                vehicle_plate_no: '',
                fuel_type: 'Diesel',
                vehicle_type: 'Truck',
                vehicle_name: '',
                manufacture_date: '',

                vehicleList: [],
                status: 1,
                user_id: '',
                modelBtn: '',
                modelHeading: '',
                id: '',

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

                    const today = new Date().toISOString().split('T')[0];

                    if (this.manufacture_date && this.manufacture_date > today) {
                        alert("Manufacture date cannot be in the future");
                        return;
                    }

                    //console.log(this.errors);
                    if (!this.errors.any()) {
                        this.createVehicle();
                    }
                },

                createVehicle() {
                    const formData = new FormData();

                    formData.append("vehicle_plate_no", app.vehicle_plate_no);
                    formData.append("fuel_type", app.fuel_type);
                    formData.append("vehicle_type", app.vehicle_type);
                    formData.append("vehicle_name", app.vehicle_name);
                    formData.append("manufacture_date", app.manufacture_date);
                    formData.append("status", app.status);
                    formData.append("id", app.id);
                    formData.append("user_id", this.user_id);

                    app.submit_button_flag = true;

                    if (app.id > 0) {
                        formData.append("_method", "put");
                        formData.append("id", app.id);

                        axios.post("/api/vehicle/update", formData)
                            .then(response => {
                                app.showAPIResponse(response);
                            })
                            .catch(error => {
                                app.submit_button_flag = false;
                                console.error("Error updating vehicle:", error);
                            });

                    } else {
                        axios.post("/api/vehicle/store", formData)
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

                        this.getVehicleList();
                        app.resetForm();
                        $('#add_vehicle').modal('hide');
                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        app.submit_button_flag = false;
                    }
                    app.submit_button_flag = false;
                },

                showEditVehicleModal(data) {
                    if (data.id > 0) {
                        app.vehicle_plate_no = data.vehicle_plate_no;
                        app.fuel_type = data.fuel_type;
                        app.vehicle_name = data.vehicle_name;
                        app.vehicle_type = data.vehicle_type;
                        app.manufacture_date = data.manufacture_date;
                        app.id = data.id;
                        app.status = data.status;

                        app.formSubmitted = false;
                        app.formErrors = {};

                        app.modelHeading = "Edit Vehicle";
                        app.modelBtn = "Update";

                        $('#add_vehicle').modal('show');

                    } else {
                        app.resetForm();
                        app.modelHeading = "Add Vehicle";
                        app.modelBtn = "Create";
                        app.submit_button_flag = false;

                        $('#add_vehicle').modal('show');
                    }

                },

                getVehicleList: function(page = 1) {
                    const url = "/api/vehicle/list";

                    axios.get(url, {
                        params: {
                            page: page,
                            page_length: this.page_length,
                            search: this.search,
                        }
                    }).then(response => {
                        app.vehicleList = response.data.data.vehicles;
                        app.pagination = response.data.data.pagination;
                        app.loading = false;
                    });
                },

                deleteItem(data) {
                    Swal.fire({
                        title: "Are you sure you want to remove " + data.vehicle_code + "?",
                        text: "You won't be able to revert this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, delete it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/delete/vehicle", {
                                    data: {
                                        id: data.id,
                                        user_id: this.user_id
                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.log("Error deleting tag:", error);
                                });
                        }
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

                resetForm() {
                    app.vehicle_name = '';
                    app.vehicle_type = 'Truck';
                    app.vehicle_plate_no = '';
                    app.fuel_type = 'Diesel';
                    app.manufacture_date = '';
                    app.status = 1;
                    app.id = '';

                    app.formSubmitted = false;
                    app.formErrors = {};

                    if (this.errors && this.errors.clear) {
                        this.errors.clear();
                    }

                },
            },

            mounted() {
                this.getLoggedUserInfo();
                this.getVehicleList();
            },
        });
    </script>
@endsection
