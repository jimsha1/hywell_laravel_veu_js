@extends('admin.layout.base')
@section('main-content')
    <div id="app">
        <div class="page-wrapper">
            <div class="content">
                <div class="row">
                    <div class="col-md-12">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h4 class="page-title">Permission</h4>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4>Role Name: <span class="text-danger">@{{ roleName }}</span></h4>
                                <label class="checkboxs">
                                    <input type="checkbox" v-model="allSelected" v-on:change="toggleAllPermissions">
                                    <span class="checkmarks"></span> Allow All
                                </label>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>
                                                    <label class="checkboxs">
                                                        <input type="checkbox" v-model="allSelected"
                                                            v-on:change="toggleAllPermissions">
                                                        <span class="checkmarks"></span>
                                                    </label>
                                                </th>
                                                <th>Permissions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="Object.keys(allPermissions).length === 0">
                                                <td colspan="2" class="text-center">No data found</td>
                                            </tr>

                                            <template v-for="(permissions, group) in allPermissions">
                                                <tr>
                                                    <td colspan="2" class="fw-bold bg-light">@{{ group.toUpperCase() }}</td>
                                                </tr>
                                                <tr v-for="permission in permissions" :key="`${group}-${permission.id}`">
                                                    <td>
                                                        <label class="checkboxs">
                                                            <input type="checkbox" v-model="permission.selected">
                                                            <span class="checkmarks"></span>
                                                        </label>
                                                    </td>
                                                    <td>@{{ permission.description }}</td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary" v-on:click="assignPermissionToRole()">Save Changes</button>
                            </div>

                        </div> <!-- End Card -->
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
                allPermissions: {},
                allSelected: false,
                encryptedRoleId: '',
                roleName: '',
                base_url: "{{ url('/') }}",
            },

            watch: {
                allPermissions: {
                    handler() {
                        this.checkIfAllSelected();
                    },
                    deep: true
                }
            },

            methods: {

                getAllPermissions() {
                    return axios.get('/api/get-permissions')
                        .then(response => {
                            // Ensure selected field exists
                            let all = response.data.data;
                            Object.values(all).forEach(group => {
                                group.forEach(p => {
                                    this.$set(p, 'selected', false); // ensure reactivity
                                });
                            });
                            this.allPermissions = all;
                            this.checkIfAllSelected();
                        })
                        .catch(error => {
                            console.error("Error fetching permissions:", error);
                        });
                },

                toggleAllPermissions() {
                    let checked = this.allSelected;
                    Object.values(this.allPermissions).forEach(group => {
                        group.forEach(permission => {
                            permission.selected = checked;
                        });
                    });
                },

                checkIfAllSelected() {
                    let allChecked = true;
                    Object.values(this.allPermissions).forEach(group => {
                        group.forEach(permission => {
                            if (!permission.selected) {
                                allChecked = false;
                            }
                        });
                    });
                    app.allSelected = allChecked;
                },

                getSelectedPermission() {
                    let encryptedRoleId = @json(request()->query('role_id'));
                    console.log(encryptedRoleId);
                    if (encryptedRoleId) {
                        let decodedRoleId = atob(decodeURIComponent(encryptedRoleId));
                        console.log("Decoded Role ID:", decodedRoleId);
                        axios.get('/api/get-role-permissions', {
                                params: {
                                    role_id: decodedRoleId
                                }
                            }).then(response => {
                                console.log(response.data.data);
                                let assignedPermissions = response.data.data;
                                console.log("Assigned Permissions:", assignedPermissions);
                                Object.values(this.allPermissions).forEach(group => {
                                    group.forEach(permission => {
                                        permission.selected = assignedPermissions.some(ap => ap
                                            .name === permission.name);
                                    });
                                });
                                this.checkIfAllSelected();
                            })
                            .catch(error => {
                                console.error("Error fetching permissions:", error);
                            });
                    }
                },

                getSelectedPermissionInfo() {
                    let encryptedRoleId = @json(request()->query('role_id'));
                    console.log(encryptedRoleId);
                    if (encryptedRoleId) {
                        let decodedRoleId = atob(decodeURIComponent(encryptedRoleId));
                        console.log("Decoded Role ID:", decodedRoleId);
                        axios.get('/api/get-selected-role-info', {
                                params: {
                                    role_id: decodedRoleId
                                }
                            }).then(response => {
                                app.roleName = response.data.result.name

                            })
                            .catch(error => {
                                console.error("Error fetching permissions:", error);
                            });
                    }
                },

                assignPermissionToRole() {
                    let encryptedRoleId = @json(request()->query('role_id'));
                    console.log(encryptedRoleId);
                    if (encryptedRoleId) {
                        let decodedRoleId = atob(decodeURIComponent(encryptedRoleId));
                        console.log("Decoded Role ID:", decodedRoleId);
                        let selectedPermissions = [];
                        Object.values(this.allPermissions).forEach(group => {
                            group.forEach(permission => {
                                if (permission.selected) {
                                    selectedPermissions.push(permission.id);
                                }
                            });
                        });
                        axios.post('/api/assign-permission-to-role', {
                                "role_id": decodedRoleId,
                                "permission_id": selectedPermissions
                            })
                            .then(response => {
                                app.showAPIResponse(response);

                            })
                            .catch(error => {
                                console.log(error);
                            });
                    }
                },

                showAPIResponse(response) {
                    if (response.data.code == 0) {
                        this.getSelectedPermission();
                        window.location.href = "{{ route('manage.roles.index') }}";
                        Vue.$toast.success(response.data.message, 'Success Alert');
                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                    }
                },
                
                getLoggedUserInfo() {
                    const userJson = window.localStorage.getItem('user');
                    if (userJson) {
                        const user = JSON.parse(userJson);
                        console.log(user);

                        $('#user_name').text(user.result.name || 'Unknown');
                        if (user.result.user_type == "superadmin") {
                            $('#user_type').text('Super Admin');
                        } else if (user.result.user_type == "sales_head") {
                            $('#user_type').text('Sales Head');
                        } else {
                            $('#user_type').text('Employee');
                        }
                        let userImage = user.result.user_image;
                        if (userImage) {
                            $('#user_image').attr('src', this.base_url + '/storage/' + userImage);
                            $('#user_image_header').attr('src', this.base_url + '/storage/' + userImage);
                        } else {
                            $('#user_image').attr('src', 'https://cdn-icons-png.freepik.com/512/9703/9703596.png');
                            $('#user_image_header').attr('src',
                                'https://cdn-icons-png.freepik.com/512/9703/9703596.png');
                        }
                    }
                }
            },

            mounted() {
                this.getAllPermissions()
                    .then(() => {
                        this.getSelectedPermission();
                    });
                this.getSelectedPermissionInfo();
                this.getLoggedUserInfo();
            }
        });
    </script>
@endsection
