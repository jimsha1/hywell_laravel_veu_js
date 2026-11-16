<!DOCTYPE html>
<html lang="en">
@include('admin.layout.heads')

<body>

    <!-- Main Wrapper -->
    <div class="main-wrapper">

        <div class="preloader">
            <span class="loader"></span>
        </div>


        @include('admin.components.header')
        @include('admin.components.sidebar')


        @yield('main-content')

        <!-- /Main Wrapper -->
        @include('admin.layout.scripts')

        @yield('script')
    </div>

</body>

</html>
