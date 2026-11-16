<!-- jQuery -->
<script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>

<!-- Bootstrap Core JS -->
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

<!-- Feather Icon JS -->
<script src="{{ asset('assets/js/feather.min.js') }}"></script>

<!-- Slimscroll JS -->
<script src="{{ asset('assets/js/jquery.slimscroll.js') }}"></script>
<script src="{{ asset('assets/js/jquery.slimscroll.min.js') }}"></script>

<!-- Daterangepicker JS -->
<script src="{{ asset('assets/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>

<!-- Apexchart JS -->
<script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>

<!-- Datatable JS -->
<script src="{{ asset('assets/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/js/dataTables.bootstrap5.min.js') }}"></script>

<!-- Datetimepicker JS -->
<script src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}"></script>

<!-- Color Picker JS -->
<script src="{{ asset('assets/plugins/@simonwep/pickr/pickr.es5.min.js') }}"></script>

<!-- Sweet Alert -->
<script src="{{ asset('assets/plugins/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>

<!-- Select2 JS -->
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>



<!-- Summernote JS -->
<script src="{{ asset('assets/plugins/summernote/summernote-lite.min.js') }}"></script>

<!-- Custom Json Js -->
<!--<script src="{{ asset('assets/js/jsonscript.js') }}"></script>-->

<!--- Custom Js -->
<script src="{{ asset('assets/js/theme-colorpicker.js') }}"></script>
<script src="{{ asset('assets/js/script.js') }}"></script>

<script src="{{ asset('assets/vuejs/dist/vue.js') }}"></script>
<script src="{{ asset('assets/vuejs/axios/axios.min.js') }}"></script>
<script src="{{ asset('assets/vuejs/vee_validate/veeValidate.js') }}"></script>


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

{{-- validation --}}
<script src="{{ asset('assets/vuejs/vee_validate/veeValidate.js') }}"></script>
<script>
    Vue.use(VeeValidate)
</script>

<script src="{{ asset('assets/vuejs/infinite_scroll/vue-infinite-scroll.js') }}"></script>
<script src="{{ asset('assets/vuejs/dist/vue-js-modal.js?11') }}"></script>

<!-- Sticky Sidebar JS -->
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/ResizeSensor.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/theia-sticky-sidebar.js') }}"></script>

<!-- Bootstrap Tagsinput JS -->
<script src="{{ asset('assets/plugins/bootstrap-tagsinput/bootstrap-tagsinput.js') }}"></script>
<script src="{{ asset('assets/common_helper/array_list_helper.js') }}"></script>
<script src="{{ asset('assets/common_helper/custom_validator.js') }}"></script>

{{-- multiselect --}}
<link href="{{ asset('assets/vuejs/multi_select/vue-multiselect.min.css') }}" rel="stylesheet">
<script src="{{ asset('assets/vuejs/multi_select/vue-multiselect.min.js') }}"></script>

<!-- Swiper JS -->
{{-- <script src="{{ asset('assets/plugins/swiper/swiper.min.js')}}"></script>
<script src="{{ asset('assets/plugins/swiper/swiper.js')}}"></script> --}}


<script>
    Vue.component("vue-multiselect", window.VueMultiselect.default);
</script>
