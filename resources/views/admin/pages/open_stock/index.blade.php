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
                                    <h4 class="page-title">Opening Stock</h4>
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
                                                @input="getOpenBalanceList">
                                        </div>
                                    </div>
                                    {{-- <div class="col-sm-8">
                                        <div class="text-sm-end">
                                            <a href="javascript:void(0);" class="btn btn-primary "
                                                v-on:click="showEditOpeningStockModal(0)"><i
                                                    class="ti ti-square-rounded-plus me-2"></i>Create</a>
                                        </div>
                                    </div> --}}
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
                                                <th>Product</th>
                                                <th>Quantity</th>
                                                <th>Purchase Price/unit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-if="!OpeningStockList || !OpeningStockList.length">
                                                <tr>
                                                    <td colspan="5">No data found</td>
                                                </tr>
                                            </template>
                                            <template v-else v-for="(data, index) in OpeningStockList">
                                                <tr>
                                                    <td>@{{ index + 1 }}</td>

                                                    <td>@{{ data.ref_no }}</td>
                                                    <td>@{{ data.product.product_name }}</td>
                                                    <td>@{{ parseInt(data.quantity) }}</td>
                                                    <td>@{{ data.stock_original ? parseFloat(data.stock_original.cost_price).toFixed(2) : '0.00' }}</td>

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

        <div class="modal fade" id="add_opening_stock" role="dialog">
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
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Product<span class="text-danger">*</span></label>
                                        <select v-model="product_id" class="form-select" v-validate.initial="product_id"
                                            data-rules="required" @change="onProductChange">
                                            <option value="">Choose
                                            </option>
                                            <option v-for="item in productList" :value="item.id">
                                                @{{ item.product_name }}
                                            </option>
                                        </select>
                                        <span v-if="formErrors['product_id']"
                                            class="error text-danger">@{{ formErrors['product_id'] }}</span>
                                        <p class="text-danger" v-if="errors.has('product_id')">@{{ errors.first('product_id') }}
                                        </p>
                                    </div>

                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Quantity<span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="qty" v-model="qty"
                                            id="brand" placeholder="Quantity" v-validate.initial="qty"
                                            data-rules="required" @input="calculateAmount(qty,1)">
                                        <span v-if="formErrors['qty']"
                                            class="error text-danger">@{{ formErrors['qty'] }}</span>
                                        <p class="text-danger" v-if="errors.has('qty')">@{{ errors.first('qty') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Unit Price<span class="text-danger">*</span></label>
                                        <input type="number" step="any" class="form-control" name="unit_price"
                                            v-model="unit_price" id="brand" placeholder="Unit Price" :value=0
                                            v-validate.initial="unit_price" data-rules="required" @input="calculateAmount(qty,unit_price)">
                                        <span v-if="formErrors['unit_price']"
                                            class="error text-danger">@{{ formErrors['unit_price'] }}</span>
                                        <p class="text-danger" v-if="errors.has('unit_price')">@{{ errors.first('unit_price') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-6">
                                        <label class="col-form-label">Amount<span class="text-danger">*</span></label>
                                        <input type="number" step="any" class="form-control" name="amount"
                                            v-model="amount" id="brand" placeholder="Amount"
                                            v-validate.initial="amount" data-rules="required" disabled>
                                        <span v-if="formErrors['']"
                                            class="error text-danger">@{{ formErrors['amount'] }}</span>
                                        <p class="text-danger" v-if="errors.has('amount')">@{{ errors.first('amount') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="date" v-model="stock_date"
                                            required>
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

                OpeningStockList: [],
                productList: [],
                product_id: '',
                qty: '',
                amount: '',
                stock_date: '',
                user_id: '',
                id: '',
                unit_price: '',
                modelBtn: '',
                modelHeading: '',

                cp: '',
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

                amount() {
                    return (this.qty || 0) * (this.unit_price || 0);
                },

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
                onProductChange() {

                    const pro = this.productList.find(p => p.id == this.product_id);
                    if (pro) {
                        this.cp = pro.unit_price || 0;

                    }

                },
                calculateAmount(qty,cp) {
                    this.amount = cp * qty;
                },
                validateBeforeSubmit() {
                    this.$validator.validateAll();
                    console.log(this.errors);
                    if (!this.errors.any()) {
                        this.createOpeningStock();
                    }
                },
                showEditOpeningStockModal(data) {
                    if (data.id > 0) {
                        app.product_id = data.product_id;
                        app.qty = data.qty;
                        app.amount = data.amount;
                        app.id = data.id;

                        // Format date for date input (extract date part if datetime)
                        if (data.created_at) {
                            const dateStr = data.created_at;
                            app.stock_date = dateStr.includes(' ') ? dateStr.split(' ')[0] : dateStr;
                        } else {
                            app.stock_date = this.getCurrentDate();
                        }

                        app.formSubmitted = false;
                        app.formErrors = {};

                        app.modelHeading = "Edit Opening Stock";
                        app.modelBtn = "Update";

                        $('#add_opening_stock').modal('show');

                    } else {
                        app.resetForm();
                        app.modelHeading = "Add Opening Stock";
                        app.modelBtn = "Create";

                        $('#add_opening_stock').modal('show');
                    }

                },
                createOpeningStock() {
                    const formData = new FormData();

                    formData.append("qty", app.qty);
                    formData.append("amount", app.amount);
                    formData.append("product_id", app.product_id);
                    formData.append("date", app.stock_date);
                    formData.append("id", app.id);
                    formData.append("user_id", this.user_id);

                    app.submit_button_flag = true;

                    if (app.id > 0) {
                        formData.append("_method", "put");
                        formData.append("id", app.id);

                        axios.post("/api/opening_stock/update", formData)
                            .then(response => {
                                app.showAPIResponse(response);
                            })
                            .catch(error => {
                                app.submit_button_flag = false;
                                console.error("Error updating opening stock:", error);
                            });

                    } else {
                        axios.post("/api/opening_stock/store", formData)
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

                        this.getOpenBalanceList();
                        app.resetForm();
                        $('#add_opening_stock').modal('hide');
                    } else {
                        Vue.$toast.error(response.data.message, 'Error Alert');
                        app.submit_button_flag = false;
                    }
                    app.submit_button_flag = false;
                },
                getCurrentDate() {
                    const today = new Date();
                    const year = today.getFullYear();
                    const month = String(today.getMonth() + 1).padStart(2, '0');
                    const day = String(today.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                },
                resetForm() {
                    app.qty = '';
                    app.amount = '';
                    app.product_id = '';
                    app.stock_date = this.getCurrentDate();
                    app.id = '';

                    app.formSubmitted = false;
                    app.formErrors = {};

                    if (this.errors && this.errors.clear) {
                        this.errors.clear();
                    }

                },
                getOpenBalanceList: function(page = 1) {
                    const url = "/api/opening_stock/list";

                    axios.get(url, {
                        params: {
                            page: page,
                            page_length: this.page_length,
                            search: this.search,
                        }
                    }).then(response => {
                        app.OpeningStockList = response.data.data.open_balance;
                        app.pagination = response.data.data.pagination;
                        app.loading = false;
                    });
                },
                getProductList(platform) {
                    axios.get('/api/product/list', {
                            params: {
                                platform: 'api',
                                productType: 'Product'
                            }
                        })
                        .then(response => {
                            app.productList = response.data.data.products;
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
                this.getProductList();
                this.getOpenBalanceList();
                this.stock_date = this.getCurrentDate();
            },
        });
    </script>
@endsection
