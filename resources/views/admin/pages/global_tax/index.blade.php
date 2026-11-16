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
                                    <h4 class="page-title">Global Tax</h4>
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
                                                @input="getTaxList">
                                        </div>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="text-sm-end">
                                            <a href="javascript:void(0);" class="btn btn-primary "
                                                v-on:click="showEditTaxModal(0)"><i
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
                                                <th>Tax %</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!taxList || !taxList.length">
                                                <tr>
                                                    <td colspan="5">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in taxList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>

                                                    <td>@{{ data.name }}</td>
                                                    <td>@{{ parseInt(data.tax) }}</td>

                                                    <td>
                                                        <span
                                                            :class="data.status == 1 ? 'badge bg-success' :
                                                                'badge bg-danger'">
                                                            <p v-if="data.status==1">Active</p>
                                                            <p v-else>Inactive</p>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown table-action"><a href="#"
                                                                data-bs-toggle="dropdown" aria-expanded="false"
                                                                class="action-icon"><i class="fa fa-ellipsis-v"></i></a>
                                                            <div class="dropdown-menu dropdown-menu-right" style="">
                                                                <a class="dropdown-item"
                                                                    v-on:click="showEditTaxModal(data)"><i
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

        <div class="modal fade" id="add_tax" role="dialog">
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
                                        <label class="form-label">Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name" v-model="name"
                                            v-validate.initial="name" data-rules="required" id="name"
                                            placeholder="Name">
                                        <span v-if="formErrors['name']"
                                            class="error text-danger">@{{ formErrors['name'] }}</span>
                                        <p class="text-danger" v-if="errors.has('name')">@{{ errors.first('name') }}
                                        </p>
                                    </div>

                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Tax</label>
                                        <input type="number" class="form-control" name="tax" v-model="tax"
                                            id="brand" placeholder="Tax" v-validate.initial="tax"
                                            data-rules="required">
                                        <span v-if="formErrors['tax']"
                                            class="error text-danger">@{{ formErrors['tax'] }}</span>
                                        <p class="text-danger" v-if="errors.has('tax')">@{{ errors.first('tax') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="col-form-label">Status</label>
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <input type="radio" class="status-radio" id="add-active" name="status"
                                            value="1" v-model="status">
                                        <label for="add-active">Active</label>
                                    </div>
                                    <div>
                                        <input type="radio" class="status-radio" id="add-inactive" name="status"
                                            value="0" v-model="status">
                                        <label for="add-inactive">Inactive</label>
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

                taxList: [],
                name: '',
                tax: '',
                status: '',
                user_id: '',
                id: '',
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
                        this.createTax();
                    }
                },
                showEditTaxModal(data) {
                    if (data.id > 0) {
                        app.status = data.status;
                        app.name = data.name;
                        app.tax = data.tax;
                        app.id = data.id;

                        app.formSubmitted = false;
                        app.formErrors = {};

                        app.modelHeading = "Edit Tax";
                        app.modelBtn = "Update";

                        $('#add_tax').modal('show');

                    } else {
                        app.resetForm();
                        app.modelHeading = "Add Tax";
                        app.modelBtn = "Create";

                        $('#add_tax').modal('show');
                    }

                },
                createTax() {
                    const formData = new FormData();

                    formData.append("tax", app.tax);
                    formData.append("status", app.status);
                    formData.append("name", app.name);
                    formData.append("id", app.id);
                    formData.append("user_id", this.user_id);

                    app.submit_button_flag = true;

                    if (app.id > 0) {
                        formData.append("_method", "put");
                        formData.append("id", app.id);

                        axios.post("/api/tax/update", formData)
                            .then(response => {
                                app.showAPIResponse(response);
                            })
                            .catch(error => {
                                app.submit_button_flag = false;
                                console.error("Error updating tax:", error);
                            });

                    } else {
                        axios.post("/api/tax/store", formData)
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

                        this.getTaxList();
                        app.resetForm();
                        $('#add_tax').modal('hide');
                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        app.submit_button_flag = false;
                    }
                    app.submit_button_flag = false;
                },
                resetForm() {
                    app.status = 1;
                    app.tax = '';
                    app.name = '';
                    app.id = '';

                    app.formSubmitted = false;
                    app.formErrors = {};

                    if (this.errors && this.errors.clear) {
                        this.errors.clear();
                    }

                },
                 getTaxList: function(page = 1) {
                    const url = "/api/tax/list";

                    axios.get(url, {
                        params: {
                            page: page,
                            page_length: this.page_length,
                            search: this.search,
                        }
                    }).then(response => {
                        app.taxList = response.data.data.tax;
                        app.pagination = response.data.data.pagination;
                        app.loading = false;
                    });
                },
                deleteItem(data) {
                    Swal.fire({
                        title: "Are you sure you want to remove " + data.name + "?",
                        text: "You won't be able to revert this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, delete it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/delete/tax", {
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
                getLoggedUserInfo() {
                    const userJson = window.localStorage.getItem('user');
                    if (userJson) {
                        const user = JSON.parse(userJson);
                        this.user_id = user.data.id;
                        $('#user_name').text(user.data.name || 'Unknown');
                        if (user.data.user_type === "super_admin") {
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
            },

            mounted() {
                this.getLoggedUserInfo();
                this.getTaxList();
            },
        });
    </script>
@endsection
