@extends('admin.layout.base')
@section('main-content')
    <div id="app">

        <div class="page-wrapper" style="min-height: 1223px;">
            <div class="content">

                <div class="row">
                    <div class="col-md-12">

                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h4 class="page-title">User<span class="count-title">@{{ users.length }}</span></h4>
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

                        <div class="card">

                            <div class="card-header">
                                <!-- Search -->
                                <div class="row align-items-center">
                                    <div class="col-sm-4">
                                        <div class="icon-form mb-3 mb-sm-0">
                                            <span class="form-icon"><i class="ti ti-search"></i></span>
                                            <input type="text" class="form-control" placeholder="Search User"
                                                v-model="search" @input="getUsers">
                                        </div>
                                    </div>
                                    {{-- <div class="col-sm-8">
                                        <div class="d-flex align-items-center flex-wrap row-gap-2 justify-content-sm-end">
                                            <a href="javascript:void(0);" class="btn btn-primary"
                                                v-on:click="showAddUserPopup(0)"><i
                                                    class="ti ti-square-rounded-plus me-2"></i>Add User</a>
                                        </div>
                                    </div> --}}
                                </div>
                                <!-- /Search -->
                            </div>

                            <div class="card-body">

                                <!-- /Filter -->

                                <!-- Manage Users List -->
                                <div class="table-responsive custom-table">
                                    <div id="manage-users-list_wrapper" class="dataTables_wrapper dt-bootstrap5 ">
                                        <div class="row">
                                            <div class="col-sm-12 col-md-6"></div>
                                            <div class="col-sm-12 col-md-6"></div>
                                        </div>
                                        <div class="row dt-row">
                                            <div class="col-sm-12 table-responsive">
                                                <table class="table" style="width: 1184px;">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th class="no-sort sorting sorting_asc" tabindex="0"
                                                                aria-controls="manage-users-list" rowspan="1"
                                                                colspan="1" aria-sort="ascending"
                                                                aria-label=": activate to sort column descending"
                                                                style="width: 25px;">
                                                                <label class="checkboxs">
                                                                    <input type="checkbox" id="select-all"><span
                                                                        class="checkmarks"></span>
                                                                </label>
                                                            </th>

                                                            <th class="sorting" tabindex="0"
                                                                aria-controls="manage-users-list" rowspan="1"
                                                                colspan="1"
                                                                aria-label="Name: activate to sort column ascending"
                                                                style="width: 142px;">Name</th>

                                                            <th class="sorting" tabindex="0"
                                                                aria-controls="manage-users-list" rowspan="1"
                                                                colspan="1"
                                                                aria-label="Email: activate to sort column ascending"
                                                                style="width: 173px;">Email</th>
                                                            <th class="sorting" tabindex="0"
                                                                aria-controls="manage-users-list" rowspan="1"
                                                                colspan="1"
                                                                aria-label="Email: activate to sort column ascending"
                                                                style="width: 173px;">Password</th>
                                                            <th class="sorting" tabindex="0"
                                                                aria-controls="manage-users-list" rowspan="1"
                                                                colspan="1"
                                                                aria-label="Location: activate to sort column ascending"
                                                                style="width: 70px;">Role</th>
                                                            <th class="sorting" tabindex="0"
                                                                aria-controls="manage-users-list" rowspan="1"
                                                                colspan="1"
                                                                aria-label="Status: activate to sort column ascending"
                                                                style="width: 55px;">Status</th>
                                                            {{-- <th class="sorting" tabindex="0"
                                                                aria-controls="manage-users-list" rowspan="1"
                                                                colspan="1"
                                                                aria-label="Status: activate to sort column ascending"
                                                                style="width: 55px;">Manage</th> --}}

                                                        </tr>
                                                    </thead>
                                                    <tbody>

                                                        <template v-if="!users || !users.length">
                                                            <tr>
                                                                <td colspan="10">No data found</td>
                                                            </tr>
                                                        </template>
                                                        <template v-else v-for="(user, index) in users">
                                                            <tr>
                                                                <td>@{{ index + 1 }}</td>
                                                                <td>@{{ user.name }} </td>
                                                                <td>@{{ user.email }}</td>
                                                                <td>@{{ user.password_without_hash ?? '-' }}</td>
                                                                <td><span class="badge badge-info text-white mr-1">
                                                                        @{{ user.user_type }}
                                                                    </span></td>
                                                                <td>
                                                                    <span
                                                                        :class="user.status === 1 ? 'badge bg-success' :
                                                                            'badge bg-danger'">
                                                                        <p v-if="user.status==1">Active</p>
                                                                        <p v-else>Inactive</p>
                                                                    </span>
                                                                </td>
                                                                {{-- <td>
                                                                    <div class="dropdown table-action"><a href="#"
                                                                            data-bs-toggle="dropdown"
                                                                            aria-expanded="false" class="action-icon"><i
                                                                                class="fa fa-ellipsis-v"></i></a>
                                                                        <div class="dropdown-menu dropdown-menu-right"
                                                                            style="">
                                                                            <a class="dropdown-item"
                                                                                v-on:click="showAddUserPopup(user)"><i
                                                                                    class="ti ti-edit text-blue"></i>Edit</a> --}}
                                                                            {{-- <a class="dropdown-item"
                                                                                v-on:click="deleteItem(user,index)"><i
                                                                                    class="ti ti-trash text-red"></i>Delete</a> --}}
                                                                        {{-- </div> --}}
                                                                    </div>
                                                                </td>
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

            </div>
        </div>
        <div class="modal fade" id="add_user_popup" role="dialog">
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

                            <div class="row justify-content-center mb-3">
                                <div class="col-md-4 text-center">
                                    <!-- Profile Image Preview -->
                                    <label for="profileInput" style="cursor: pointer;">
                                        <img :src="previewImage || '/assets/img/img-1.jpg'" class="rounded-circle border"
                                            style="width: 100px; height: 100px; object-fit: cover;"
                                            alt="Profile Preview" />
                                    </label>

                                    <!-- Hidden File Input -->
                                    <input type="file" id="profileInput" name="profile_image" class="d-none"
                                        @change="handleProfileImageUpload" />
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label class="col-form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" v-model="name"
                                        v-validate.initial="name" data-rules="required" placeholder="Name">
                                    <span v-if="formErrors['name']"
                                        class="error text-danger">@{{ formErrors['name'] }}</span>
                                    <p class="text-danger" v-if="errors.has('name')">@{{ errors.first('name') }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <label class="col-form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" v-model="email"
                                        v-validate.initial="email" data-rules="required" placeholder="Email">
                                    <span v-if="formErrors['email']"
                                        class="error text-danger">@{{ formErrors['email'] }}</span>
                                    <p class="text-danger" v-if="errors.has('email')">@{{ errors.first('email') }}
                                    </p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label class="col-form-label">Role<span class="text-danger">*</span></label>
                                    <select v-model="role_id" class="form-select" v-validate.initial="role_id"
                                        data-rules="required">
                                        <option value="">Choose</option>
                                        <option v-for="item in roleList" :value="item.id">@{{ item.name }}
                                        </option>
                                    </select> <span v-if="formErrors['role_id']"
                                        class="error text-danger">@{{ formErrors['role_id'] }}</span>
                                    <p class="text-danger" v-if="errors.has('role_id')">@{{ errors.first('role_id') }}
                                    </p>

                                </div>
                                {{-- <div v-if="isPassword" class="col-md-6">
                                    <label class="col-form-label">Password <span class="text-danger">*</span></label>
                                    <input v-if="isPassword" type="password" class="form-control" name="password"
                                        v-model="password" v-validate.initial="password"
                                        :data-rules="isPassword ? 'required|min:6' : ''">
                                    <span v-if="formErrors['password']"
                                        class="error text-danger">@{{ formErrors['password'] }}</span>
                                    <p class="text-danger" v-if="errors.has('password')">@{{ errors.first('password') }}
                                    </p>
                                </div> --}}
                                <div class="col-md-6">
                                    <label class="col-form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="password"
                                        v-model="password" v-validate.initial="password"
                                        :data-rules="required">
                                    <span v-if="formErrors['password']"
                                        class="error text-danger">@{{ formErrors['password'] }}</span>
                                    <p class="text-danger" v-if="errors.has('password')">@{{ errors.first('password') }}
                                    </p>
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

    </div>
@endsection

@section('script')
    <script>
        var app = new Vue({
            el: '#app',
            data: {
                users: [],
                modelBtn: '',
                modelHeading: '',
                name: '',
                email: '',
                role_id: null,
                user_id: null,
                status: '',
                password: '',
                isPassword: '',
                profile_image: '',
                search: '',
                base_url: "{{ url('/') }}",
                roleList: [],

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

                previewImage: null,
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
                formatDate(date) {
                    let options = {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    };
                    return new Date(date.replace(' ', 'T')).toLocaleString('en-US', options);
                },

                handleProfileImageUpload(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = e => {
                        this.previewImage = e.target.result; // show preview
                    };
                    reader.readAsDataURL(file);

                    this.profile_image = file; // send to backend via FormData
                },

                changePage: function(page) {
                    this.pagination.current_page = page;
                    app.getUsers(page);
                },

                handleChangePage(e) {
                    console.log(this.page_length)
                    app.getUsers();
                },

                validateBeforeSubmit() {
                    this.$validator.validateAll();
                    console.log(this.errors);
                    if (!this.errors.any()) {
                        this.createUser();
                    }
                },

                createUser() {
                    let formData = new FormData();
                    formData.append('full_name', app.name);
                    formData.append('email', app.email);
                    formData.append('role_id', app.role_id);
                    formData.append('status', app.status);
                    formData.append("id", app.id);

                    if (this.isPassword && this.password) {
                        formData.append('password', this.password);
                    }

                    if (this.profile_image) {
                        formData.append('profile_image', this.profile_image); // Use correct key
                    }

                    app.submit_button_flag = true;

                    if (app.id > 0) {
                        formData.append("_method", "put");
                        formData.append("id", app.id);
                        formData.append('password', this.password);

                        axios.post('/api/update-user', formData)
                            .then(response => {
                                app.showAPIResponse(response);
                            })
                            .catch(error => {
                                app.submit_button_flag = false;
                                console.error("Error updating user:", error);
                                app.formErrors = error.response.data.errors;
                            });

                    } else {

                        axios.post('/api/add-user', formData)
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
                    console.log(response);
                    if (response.data.code == 0) {

                        Vue.$toast.success(response.data.message, 'Success Alert');
                        app.submit_button_flag = false;
                        app.formSubmitted = true;
                        app.formErrors = {};

                        this.getUsers();
                        app.resetForm();
                        $('#add_user_popup').modal('hide');

                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        app.submit_button_flag = false;
                    }
                    app.submit_button_flag = false;
                },

                showAddUserPopup(user) {

                    if (user.id > 0) {
                        app.id = user.id;
                        app.name = user.name;
                        app.email = user.email;
                        app.role_id = user.roles && user.roles.length > 0 ? user.roles[0].id : '';
                        app.previewImage = user.profile_image ? this.base_url + '/storage/' + user.profile_image :
                            '/assets/img/img-1.jpg';
                        app.status = user.status;
                        app.isPassword = false;

                        app.formSubmitted = false;
                        app.formErrors = {};

                        app.modelHeading = 'Edit User';
                        app.modelBtn = 'Update User';

                        $('#add_user_popup').modal('show');
                    } else {
                        app.resetForm();
                        app.modelHeading = 'Add User';
                        app.modelBtn = 'Add User';

                        $('#add_user_popup').modal('show');
                    }
                },

                getUsers: function(page = 1) {
                    const url = "/api/get-users";

                    axios.get(url, {
                        params: {
                            page: page,
                            page_length: this.page_length,
                            search: this.search,
                        }
                    }).then(response => {
                        app.users = response.data.data.users;
                        app.pagination = response.data.data.pagination;
                        app.loading = false;
                    });
                },

                getRoles(platform) {
                    axios.get('/api/roles/list', {
                            params: {
                                platform: 'api'
                            }
                        })
                        .then(response => {
                            app.roleList = response.data.data;
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },

                deleteItem(user) {
                    Swal.fire({
                        title: "Are you sure you want to remove " + user.name + "?",
                        text: "You won't be able to revert this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, delete it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/delete-user", {
                                    data: {
                                        id: user.id,
                                        user_id: this.user_id
                                    }
                                })
                                .then(response => {
                                    app.showAPIResponse(response);
                                })
                                .catch(error => {
                                    console.lo("Error deleting tag:", error);
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
                    app.id = '';
                    app.name = '';
                    app.email = '';
                    app.role_id = null;
                    app.user_id = null;
                    app.status = 1;
                    app.password = null;
                    app.isPassword = true;

                    app.formSubmitted = false;
                    app.formErrors = {};

                    if (this.errors && this.errors.clear) {
                        this.errors.clear();
                    }
                },
            },

            mounted() {
                this.getUsers();
                this.getRoles();
                this.getLoggedUserInfo();
                $('#add_user_popup').on('hidden.bs.modal', () => {
                    this.resetForm();
                });
            }

        });
    </script>
@endsection
