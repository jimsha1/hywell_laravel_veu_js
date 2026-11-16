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
                                    <h4 class="page-title">Product</h4>
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

                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row align-items-end row-gap-3">
                                    <div class="col-sm-3">
                                        <label class="mb-1">Product Type</label>
                                        <select v-model="filter_product_type" class="form-select">
                                            <option value="">All Types</option>
                                            <option value="{{ config('constants.cartoon') }}">Cartoon</option>
                                            <option value="{{ config('constants.pack') }}">Pack</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-12 d-flex gap-2 justify-content-end">
                                        <button class="btn btn-primary" v-on:click="applyFilter()">
                                            <i class="ti ti-search"></i> Search
                                        </button>
                                        <button class="btn btn-secondary" v-on:click="resetFilter()">
                                            <i class="ti ti-reload"></i> Reset
                                        </button>
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
                                            <input type="text" class="form-control" placeholder="Search Product"
                                                v-model="search" @input="getProductList">
                                        </div>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="text-sm-end">
                                            <a href="javascript:void(0);" class="btn btn-primary "
                                                v-on:click="showEditProductModal(0)"><i
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
                                                <th>Category</th>
                                                <th>Product Name</th>
                                                <th>Product Type</th>
                                                <th>Unit Price</th>
                                                <th>Selling Price</th>
                                                <th>Outsider Price</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!productList || !productList.length">
                                                <tr>
                                                    <td colspan="7">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in productList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>
                                                    <td>@{{ data.product_code }}</td>
                                                    <td>@{{ data.category.category_name }}</td>
                                                    <td>@{{ data.product_name }}</td>
                                                    <td>@{{ data.product_type }}</td>
                                                    <td>@{{ data.unit_price }}</td>
                                                    <td>@{{ data.selling_price }}</td>
                                                    <td>@{{data.outside_selling_price}}</td>
                                                    <td>
                                                        <span
                                                            :class="data.status == 1 ? 'badge bg-success' :
                                                                'badge bg-danger'">
                                                            <p v-if="data.status==1">Active</p>
                                                            <p v-else>Inactive</p>
                                                        </span>
                                                    </td>
                                           <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <a class=" bg-transparent" style="cursor:pointer;"
                                                    v-on:click="showEditProductModal(data)">
                                                    <i class="ti ti-edit text-blue"></i>
                                                </a>

                                                <a class=" text-bg-transparent" style="cursor:pointer;"
                                                    v-on:click="deleteItem(data,index)">
                                                    <i class="ti ti-trash text-red"></i>
                                                </a>
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

        <div class="modal fade" id="add_product" role="dialog">
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
                                        <label class="form-label">Product Category<span
                                                class="text-danger">*</span></label>
                                        <select v-model="category_id" class="form-select"
                                            v-validate.initial="category_id" data-rules="required">
                                            <option value="">Choose</option>
                                            <option v-for="item in categoryList" :value="item.id">
                                                @{{ item.category_name }}
                                            </option>
                                        </select>
                                        <span v-if="formErrors['category_id']"
                                            class="error text-danger">@{{ formErrors['category_id'] }}</span>
                                        <p class="text-danger" v-if="errors.has('category_id')">@{{ errors.first('category_id') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label"> Product Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="product_name"
                                            v-model="product_name" v-validate.initial="product_name"
                                            data-rules="required" id="product_name" placeholder="Product Name">
                                        <span v-if="formErrors['product_name']"
                                            class="error text-danger">@{{ formErrors['product_name'] }}</span>
                                        <p class="text-danger" v-if="errors.has('product_name')">@{{ errors.first('product_name') }}
                                        </p>
                                    </div>

                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Product Name(ar)</label>
                                        <input type="text" class="form-control" name="ar_product_name"
                                            v-model="ar_product_name" placeholder="Product Name(ar)"
                                            @input="ar_product_name = ar_product_name.replace(/[^\u0600-\u06FF\s]/g, '')"

                                            >
                                    </div>

                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Product Type</label>
                                        <select v-model="product_type" class="form-select" name="product_type">
                                            <option value="">Choose</option>
                                            <option value="{{ config('constants.cartoon') }}">Cartoon
                                            </option>
                                            <option value="{{ config('constants.pack') }}">Pack
                                            </option>
                                        </select>
                                    </div>

                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Product Uom</label>
                                        <select v-model="product_uom" class="form-select" name="product_uom">
                                            <option value="">Choose</option>
                                            <option value="{{ config('constants.PCS') }}">{{ config('constants.PCS') }}
                                            </option>
                                            <option value="{{ config('constants.PACK') }}">{{ config('constants.PACK') }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Unit Price<span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="unit_price"
                                            v-model="unit_price" placeholder="Unit Price" step="any"
                                            name="unit_price" v-validate.initial="unit_price" data-rules="'required|min:0.01'">

                                        <span v-if="formErrors['unit_price']"
                                            class="error text-danger">@{{ formErrors['unit_price'] }}</span>
                                        <p class="text-danger" v-if="errors.has('unit_price')">@{{ errors.first('unit_price') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Selling Price<span
                                                class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="selling_price"
                                            v-model="selling_price" placeholder="Selling Price" step="any"
                                            v-validate.initial="selling_price" data-rules="'required|min:0.01'">
                                        <span v-if="formErrors['selling_price']"
                                            class="error text-danger">@{{ formErrors['selling_price'] }}</span>
                                        <p class="text-danger" v-if="errors.has('selling_price')">@{{ errors.first('selling_price') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Outside Selling Price<span
                                                class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="outside_selling_price"
                                            v-model="outside_selling_price" placeholder="Selling Price for outside salesman" step="any"
                                            v-validate.initial="outside_selling_price" data-rules="'required|min:0.01'">
                                        <span v-if="formErrors['outside_selling_price']"
                                            class="error text-danger">@{{ formErrors['outside_selling_price'] }}</span>
                                        <p class="text-danger" v-if="errors.has('outside_selling_price')">@{{ errors.first('outside_selling_price') }}
                                        </p>
                                    </div>

                                </div>
                            </div>
                            {{-- <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6 mb-6">
                                        <label class="form-label">Tax(%)</label>
                                        <select v-model="tax_id" class="form-select">
                                            <option value="">Choose</option>
                                            <option v-for="item in taxList" :value="item.id">
                                                @{{ item.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Salesman Commission</label>
                                        <input type="number" step="any" class="form-control"
                                            name="salesman_commission" v-model="salesman_commission"
                                            placeholder="Enter Salesman Commission">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Excetive Commission</label>
                                        <input type="number" step="any" class="form-control"
                                            name="executive_commission" v-model="executive_commission"
                                            placeholder="Enter Excetive Commission">
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Remarks</label>
                                        <textarea class="form-control" rows="1" v-model="remarks" placeholder="Remarks"></textarea>
                                    </div>
                                </div>
                            </div> --}}

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

                supplierList: [],
                taxList: [],
                productList: [],
                categoryList: [],
                product_name: '',
                ar_product_name: '',
                product_uom: 'PCS',
                product_type: 'cartoon',
                product_code: '',
                unit_price: '',
                selling_price: '',
                outside_selling_price:'',
                tax_id: '',
                remarks: '',
                status: '',
                user_id: '',
                id: '',
                category_id: '',
                modelBtn: '',
                modelHeading: '',
                filter_product_type: '',
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
                delivery_vehicle_commission: 0,
                executive_commission: 0,
                salesman_commission: 0,
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
                        this.createProduct();
                    }
                },

                createProduct() {
                    const formData = new FormData();

                    formData.append("category_id", app.category_id);
                    formData.append("product_name", app.product_name);
                    formData.append("ar_product_name", app.ar_product_name);
                    formData.append("product_uom", app.product_uom);
                    formData.append("product_type", app.product_type);
                    formData.append("unit_price", app.unit_price);
                    formData.append("selling_price", app.selling_price);
                    formData.append("outside_selling_price",app.outside_selling_price);
                    formData.append("tax_id", app.tax_id);
                    formData.append("remarks", app.remarks);
                    formData.append("status", app.status);
                    formData.append("id", app.id);
                    formData.append("user_id", this.user_id);
                    formData.append("delivery_vehicle_commission", app.delivery_vehicle_commission);
                    formData.append("executive_commission", app.executive_commission);
                    formData.append("salesman_commission", app.salesman_commission);

                    app.submit_button_flag = true;

                    if (app.id > 0) {
                        formData.append("_method", "put");
                        formData.append("id", app.id);

                        axios.post("/api/product/update", formData)
                            .then(response => {
                                app.showAPIResponse(response);
                            })
                            .catch(error => {
                                app.submit_button_flag = false;
                                console.error("Error updating product:", error);
                            });

                    } else {
                        axios.post("/api/product/store", formData)
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

                        this.getProductList();
                        app.resetForm();
                        $('#add_product').modal('hide');
                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        app.submit_button_flag = false;
                    }
                    app.submit_button_flag = false;
                },

                showEditProductModal(data) {
                    if (data.id > 0) {
                        app.product_name = data.product_name;
                        app.ar_product_name = data.ar_product_name;
                        app.product_uom = data.product_uom;
                        app.product_type = data.product_type;
                        app.unit_price = data.unit_price;
                        app.selling_price = data.selling_price;
                        app.outside_selling_price=data.outside_selling_price;
                        app.category_id = data.category_id;
                        app.tax_id = data.tax_id || '';
                        app.remarks = data.remarks;
                        app.status = data.status;
                        app.id = data.id;
                        app.delivery_vehicle_commission = data.delivery_vehicle_commission;
                        app.executive_commission = data.executive_commission;
                        app.salesman_commission = data.salesman_commission;

                        app.formSubmitted = false;
                        app.formErrors = {};

                        app.modelHeading = "Edit Product";
                        app.modelBtn = "Update";

                        $('#add_product').modal('show');

                    } else {
                        app.resetForm();
                        app.modelHeading = "Add Product";
                        app.modelBtn = "Create";

                        $('#add_product').modal('show');
                    }

                },

                deleteItem(data) {
                    Swal.fire({
                        title: "Are you sure you want to remove " + data.product_code + "?",
                        text: "You won't be able to revert this!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, delete it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.delete("/api/delete/product", {
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

                getProductList: function(page = 1) {
                    const url = "/api/product/list";

                    axios.get(url, {
                        params: {
                            page: page,
                            page_length: this.page_length,
                            search: this.search,
                            product_type: this.filter_product_type,
                        }
                    }).then(response => {
                        app.productList = response.data.data.products;
                        app.pagination = response.data.data.pagination;
                        app.loading = false;
                    });
                },

                getProductCategoryList() {
                    axios.get('/api/category/list', {
                            params: {
                                platform: 'api'
                            }
                        })
                        .then(response => {
                            app.categoryList = response.data.data;
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },

                getTaxList() {
                    axios.get('/api/tax/list', {
                            params: {
                                platform: 'api'
                            }
                        })
                        .then(response => {
                            app.taxList = response.data.data;
                        })
                        .catch(error => {
                            console.log(error);
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
                    app.product_name = '';
                    app.ar_product_name = '';
                    app.product_uom = 'PCS';
                    app.product_type = 'cartoon';
                    app.unit_price = '';
                    app.selling_price = '';
                    app.outside_selling_price='';
                    app.category_id = '';
                    app.tax_id = '';
                    app.remarks = '',
                        app.status = 1;
                    app.id = '';

                    app.formSubmitted = false;
                    app.formErrors = {};
                    app.delivery_vehicle_commission = 0;
                    app.executive_commission = 0;
                    app.salesman_commission = 0;

                    if (this.errors && this.errors.clear) {
                        this.errors.clear();
                    }
                },
                applyFilter() {
                    this.getProductList(1);
                },

                resetFilter() {
                    this.filter_product_type = '';
                    this.getProductList(1);
                },
            },

            mounted() {
                this.getLoggedUserInfo();
                this.getProductList();
                this.getProductCategoryList();
                this.getTaxList();
                $('#add_product').on('hidden.bs.modal', () => {
                    this.resetForm();
                });

            },
        });
    </script>
@endsection
