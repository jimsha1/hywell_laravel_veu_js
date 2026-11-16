@extends('public.layout.base')

@section('public-main-content')
    <div id="app">
        <div class="account-content">
            <div class="d-flex flex-wrap w-100 vh-100 overflow-hidden account-bg-01">
                <div class="d-flex flex-column justify-content-center flex-wrap vh-100 overflow-auto p-4 w-50 bg-backdrop">
                    <div class="mx-auto mw-450">
                        <div class="text-start mb-3 mt-4">
                            <img style="position: relative; left: -20px;" src="{{ asset('assets/logo/logo.png') }}"
                                width="30%" alt="Logo">
                        </div>
                        <div class="mb-4">
                            <h4 class="mb-2 fs-20">Customer Login In</h4>
                        </div>
                        <div class="mb-3">
                            <label class="col-form-label">Phone Number</label>
                            <div class="position-relative">
                                <span class="input-icon-addon">
                                    <i class="ti ti-phone"></i>
                                </span>
                                <input style="min-height: 48px;" type="tel" name="number" placeholder="+966 5XXXXXXXX"
                                    class="form-control" required v-model="phoneNumber">

                            </div>
                        </div>
                        <div class="mb-3">
                            <button v-on:click="login()" type="submit" class="btn btn-primary w-100">Login In</button>
                        </div>


                    </div>
                    <div class="text-center mt-auto">
                        <p class="fw-medium text-gray">Qatrat Sadeem</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('public-script')
    <script>
        var app = new Vue({
            el: '#app',
            data: {
                phoneNumber: ''
            },
            methods: {
                login() {
                    if (app.phoneNumber) {
                        let publicCustomerInfo = {
                            phoneNumber: app.phoneNumber
                        };
                        window.localStorage.setItem('publicCustomerInfo', JSON.stringify(publicCustomerInfo));
                        window.location.href = "{{ route('public.home') }}";
                    } else {
                        showErrorToast("Please enter a valid phone number.");
                    }
                }
            },
        });
    </script>
@endsection
