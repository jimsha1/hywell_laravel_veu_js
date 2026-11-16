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
                                    <h4 class="page-title">Commision Type</h4>
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
                                            <input type="text" class="form-control" placeholder="Search"
                                                v-model="search" @input="getCommissionTypeList">
                                        </div>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="text-sm-end">
                                            <a href="javascript:void(0);" class="btn btn-primary "
                                                v-on:click="showEditCommissionTypeModal(0)"><i
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

                                                <th>Name</th>
                                                <th>Sales Man Commission</th>
                                                <th>Sales Executive Commission</th>
                                                <th>Delivery Vehicle Commission</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!commissionTypeList || !commissionTypeList.length">
                                                <tr>
                                                    <td colspan="7">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in commissionTypeList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>

                                                    <td>@{{ data.name }}</td>
                                                    <td>@{{ data.salesman_commission }}</td>
                                                    <td>@{{ data.salesexecutive_commission }}</td>
                                                    <td>@{{ data.delivery_vehicle_commission }}</td>
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
                                                                    v-on:click="showEditCommissionTypeModal(data)"><i
                                                                        class="ti ti-edit text-blue"></i>Edit</a>

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

        <div class="modal fade" id="add_commission_type" role="dialog">
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
                                        <label class="col-form-label">Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name" v-model="name"
                                            placeholder="Name" name="name" v-validate.initial="name"
                                            data-rules="required" >
                                        <span v-if="formErrors['name']"
                                            class="error text-danger">@{{ formErrors['name'] }}</span>
                                        <p class="text-danger" v-if="errors.has('name')">@{{ errors.first('name') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Sales Man Commission<span
                                                class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="salesman_commission"
                                            v-model="salesman_commission" placeholder="Sales Man Commission"
                                            name="salesman_commission" v-validate.initial="salesman_commission"
                                            data-rules="required" step="any">
                                        <span v-if="formErrors['salesman_commission']"
                                            class="error text-danger">@{{ formErrors['salesman_commission'] }}</span>
                                        <p class="text-danger" v-if="errors.has('salesman_commission')">
                                            @{{ errors.first('salesman_commission') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Sales Executive Commission<span
                                                class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="salesexecutive_commission"
                                            v-validate.initial="salesexecutive_commission" data-rules="required"
                                            v-model="salesexecutive_commission" placeholder="Sales Executive Commission" step="any">
                                        <span v-if="formErrors['salesexecutive_commission']"
                                            class="error text-danger">@{{ formErrors['salesexecutive_commission'] }}</span>
                                        <p class="text-danger" v-if="errors.has('salesexecutive_commission')">
                                            @{{ errors.first('salesexecutive_commission') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Delivery Vehicle Commission<span
                                                class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="delivery_vehicle_commission"
                                            v-validate.initial="delivery_vehicle_commission" data-rules="required"
                                            v-model="delivery_vehicle_commission" placeholder="Sales Executive Commission" step="any">
                                        <span v-if="formErrors['delivery_vehicle_commission']"
                                            class="error text-danger">@{{ formErrors['delivery_vehicle_commission'] }}</span>
                                        <p class="text-danger" v-if="errors.has('delivery_vehicle_commission')">
                                            @{{ errors.first('delivery_vehicle_commission') }}
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

                name: '',
                salesman_commission: '',
                salesexecutive_commission: '',
                delivery_vehicle_commission: '',
                commissionTypeList: [],
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
                    console.log(this.errors);
                    if (!this.errors.any()) {
                        this.createCommissionType();
                    }
                },

                createCommissionType() {
                    const formData = new FormData();

                    formData.append("name", app.name);
                    formData.append("salesman_commission", app.salesman_commission);
                    formData.append("salesexecutive_commission", app.salesexecutive_commission);
                    formData.append("delivery_vehicle_commission", app.delivery_vehicle_commission);
                    formData.append("status", app.status);
                    formData.append("id", app.id);
                    formData.append("user_id", this.user_id);

                    app.submit_button_flag = true;

                    if (app.id > 0) {
                        formData.append("_method", "put");
                        formData.append("id", app.id);

                        axios.post("/api/commission-type/update", formData)
                            .then(response => {
                                app.showAPIResponse(response);
                            })
                            .catch(error => {
                                app.submit_button_flag = false;
                            });

                    } else {
                        axios.post("/api/commission-type/store", formData)
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

                        this.getCommissionTypeList();
                        app.resetForm();
                        $('#add_commission_type').modal('hide');
                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        app.submit_button_flag = false;
                    }
                    app.submit_button_flag = false;
                },

                showEditCommissionTypeModal(data) {
                    if (data.id > 0) {
                        app.name = data.name;
                        app.salesman_commission = data.salesman_commission;
                        app.salesexecutive_commission = data.salesexecutive_commission;
                        app.delivery_vehicle_commission = data.delivery_vehicle_commission;
                        app.id = data.id;
                        app.status = data.status;

                        app.formSubmitted = false;
                        app.formErrors = {};

                        app.modelHeading = "Edit Commission Type";
                        app.modelBtn = "Update";

                        $('#add_commission_type').modal('show');

                    } else {
                        app.resetForm();
                        app.modelHeading = "Add Commission Type";
                        app.modelBtn = "Create";
                        app.submit_button_flag = false;

                        $('#add_commission_type').modal('show');
                    }

                },

                getCommissionTypeList: function(page = 1) {
                    const url = "/api/commission-type/list";

                    axios.get(url, {
                        params: {
                            page: page,
                            page_length: this.page_length,
                            search: this.search,
                        }
                    }).then(response => {
                        app.commissionTypeList = response.data.data.commi_type;
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
                            $('#user_image_header').attr('src', this.base_url + '/storage/' + userImage);
                        } else {
                            const defaultImg = 'https://cdn-icons-png.freepik.com/512/9703/9703596.png';
                            $('#user_image').attr('src', defaultImg);
                            $('#user_image_header').attr('src', defaultImg);
                        }
                    }
                },

                resetForm() {
                    app.name = '';
                    app.salesman_commission = '';
                    app.salesexecutive_commission = '';
                    app.delivery_vehicle_commission = '';
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
                this.getCommissionTypeList();
            },
        });
    </script>
@endsection
