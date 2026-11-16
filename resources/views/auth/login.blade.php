<!DOCTYPE html>
<html lang="en">

<head>

    <!-- Meta Tags -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Streamline your business with our advanced CRM template. Easily integrate and customize to manage sales, support, and customer interactions efficiently. Perfect for any business size">
    <meta name="keywords"
        content="Advanced CRM template, customer relationship management, business CRM, sales optimization, customer support software, CRM integration, customizable CRM, business tools, enterprise CRM solutions">
    <meta name="author" content="Dreams Technologies">
    <meta name="robots" content="index, follow">

    <!-- Title -->
    <title>Login | Oxion </title>

    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/logo/app_logo.png') }}">

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('assets/logo/app_logo.png') }}" type="image/x-icon">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/logo/app_logo.png') }}">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">

    <!-- Tabler Icon CSS -->
    <link rel="stylesheet" href="assets/plugins/tabler-icons/tabler-icons.css">

    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome/css/all.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <script src="{{ asset('assets/vuejs/dist/vue.js') }}"></script>
    <script src="{{ asset('assets/vuejs/axios/axios.min.js') }}"></script>
    <script src="{{ asset('assets/vuejs/vee_validate/veeValidate.js') }}"></script>
    <script>
        Vue.use(VeeValidate);
    </script>

    <link href="{{ asset('assets/vuejs/toast/vue_toast.css') }}" rel="stylesheet">
    <script src="{{ asset('assets/vuejs/toast/vue_toast.js') }}"></script>

    <!-- Init the plugin -->
    <script>
        Vue.use(VueToast, {
            position: "top-right",
            duration: 5000,
            dismissible: true
        })
    </script>

</head>

<body class="account-page">

    <!-- Main Wrapper -->
    <div class="main-wrapper" id="app">

        <div class="account-content">
            <div class="d-flex flex-wrap w-100 vh-100 overflow-hidden account-bg-01">
                <div
                    class="d-flex align-items-center justify-content-center flex-wrap vh-100 overflow-auto p-4 w-50 bg-backdrop">
                    <form @submit.prevent="validateBeforeSubmit" v-if="!formSubmitted" method="post" class="flex-fill">
                        <div class="mx-auto mw-450">
                            <div class="text-center mb-4">
                                <img src="{{ asset('assets/logo/app_logo.png') }}" style="max-width: 22vh;"
                                    alt="Logo">
                            </div>
                            <div class="mb-4">
                                <h4 class="mb-2 fs-20">Sign In</h4>
                                <p>Access the LMS panel using your email and passcode.</p>
                            </div>
                            <div class="mb-3">
                                <label class="col-form-label">Email Address</label>
                                <div class="position-relative">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-mail"></i>
                                    </span>
                                    <input type="text" value="" name="email" v-model="email"
                                        class="form-control" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="col-form-label">Password</label>
                                <div class="pass-group">
                                    <input type="password" name="password" v-model="password"
                                        class="pass-input form-control" required>
                                    <span class="ti toggle-password ti-eye-off"></span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="form-check form-check-md d-flex align-items-center">
                                    <input class="form-check-input" type="checkbox" value="" id="checkebox-md"
                                        checked="">
                                    <label class="form-check-label" for="checkebox-md">
                                        Remember Me
                                    </label>
                                </div>
                                <div class="text-end">
                                    <a href="#" class="text-primary fw-medium link-hover">Forgot
                                        Password?</a>
                                </div>
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary w-100">Sign In</button>
                            </div>

                            <div class="text-center">
                                <p class="fw-medium text-gray">Copyright &copy; <?php echo date('Y'); ?> - LMS</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
    <!-- /Main Wrapper -->

    <!-- jQuery -->
    <script src="assets/js/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap Core JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <!-- Feather Icon JS -->
    <script src="assets/js/feather.min.js"></script>

    <!-- Slimscroll JS -->
    <script src="assets/js/jquery.slimscroll.min.js"></script>

    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>

</body>

<script>
    var app = new Vue({
        el: '#app',

        data: {
            base_url: "{{ url('/') }}",

            formSubmitted: false,
            email: "",
            password: "",

            offset: 4,
            formErrors: {},
        },

        computed: {

        },

        //

        methods: {

            validateBeforeSubmit(e) {

                this.$validator.validateAll();
                if (!this.errors.any()) {
                    this.loginApi()
                }
            },

            loginApi: function(page = 1) {

                // api login
                axios.post('/user-authentication', {
                        "email": app.email,
                        "password": app.password,
                    })
                    .then(function(response) {

                        if (response.data.code == 0) {
                            console.log(response.data);

                            Vue.$toast.success(response.data.message, 'Success Alert');
                            window.localStorage.setItem('user', JSON.stringify(response.data));

                            const userType = response.data.data.user_type;
                            if (userType === 'superadmin') {
                                window.location.replace(app.base_url + "/admin/dashboard");
                            }

                            app.formSubmitted = true;
                            app.formErrors = {};
                        } else {
                            Vue.$toast.error(response.data.message, 'Danger Alert');
                        }

                    })
            },
        },
        mounted() {

        }
    });
</script>

</html>
