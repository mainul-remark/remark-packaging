<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{asset('backend/assets/images/favicon.png')}}">
    <title> Admin || @yield('title')</title>
    <link href="{{asset('backend/dist/css/style.min.css')}}" rel="stylesheet">
    <link href="{{asset('backend/toastr/dist/build/toastr.min.css')}}" rel="stylesheet">
    @stack('styles')
    @include('admin.includes.theme-color-css')
</head>

<body data-theme="light">
<div id="main-wrapper">
    @include('admin.includes.header')
    @include('admin.includes.sidebar')
    <div class="page-wrapper">
        @yield('content')
    </div>
</div>

<script src="{{asset('backend/jquery/jquery-3.7.1.js')}}"></script>
<script src="{{asset('backend/assets/libs/popper.js/dist/umd/popper.min.js')}}"></script>
<script src="{{asset('backend/assets/libs/bootstrap/dist/js/bootstrap.min.js')}}"></script>
<script src="{{asset('backend/dist/js/app.min.js')}}"></script>
<script src="{{asset('backend/dist/js/app.init.js')}}"></script>
<script src="{{asset('backend/dist/js/app-style-switcher.js')}}"></script>
<script src="{{asset('backend/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js')}}"></script>
<script src="{{asset('backend/assets/extra-libs/sparkline/sparkline.js')}}"></script>
<script src="{{asset('backend/dist/js/waves.js')}}"></script>
<script src="{{asset('backend/dist/js/sidebarmenu.js')}}"></script>
<script src="{{asset('backend/dist/js/custom.min.js')}}"></script>

<script src="{{asset('backend/toastr/dist/build/toastr.min.js')}}"></script>
<script src="{{asset('backend/toastr/toastr-init.js')}}"></script>

@stack('scripts')

@include('admin.includes.theme-color-js')


<script>
    @if(Session::has('message'))
    var type = "{{ Session::get('alert-type', 'info') }}";

    switch(type){
        case 'info':
            toastr.info("{{ Session::get('message') }}");
            break;
        case 'warning':
            toastr.warning("{{ Session::get('message') }}");
            break;
        case 'success':
            toastr.success("{{ Session::get('message') }}");
            break;
        case 'error':
            toastr.error("{{ Session::get('message') }}");
            break;
    }
    @endif
</script>


<script>
    (function () {
        const theme = localStorage.getItem('admin-theme') || 'light';
        document.documentElement.setAttribute('data-theme', theme);
    })();
</script>

</body>

</html>
