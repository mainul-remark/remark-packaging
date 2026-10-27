<header class="topbar">
    <nav class="navbar top-navbar navbar-expand-md navbar-dark">
        <div class="navbar-header">
            <!-- This is for the sidebar toggle which is visible on mobile only -->
            <a class="nav-toggler waves-effect waves-light d-block d-md-none" href="javascript:void(0)">
                <i class="ti-menu ti-close"></i>
            </a>
            <!-- ============================================================== -->
            <!-- Logo -->
            <!-- ============================================================== -->
            <div class="navbar-brand border-bottom logo-border">
                <a href="{{ route('dashboard') }}" class="logo">
                    <!-- Logo icon -->
                    <b class="">
                        <img src="{{asset('backend/assets/images/9.png')}}" alt="homepage" class="dark-logo px-3" style="max-height:35px;width:auto" />
                        <img src="{{asset('backend/assets/images/9.png')}}" alt="homepage" class="light-logo px-3" style="max-height:35px;width:auto"/>
                    </b>
                    {{-- <b class="logo-icon">
                         <!-- Dark Logo icon -->
                         <img src="{{asset('/')}}admin/assets/images/logo-icon.png" alt="homepage" class="dark-logo" />
                         <!-- Light Logo icon -->
                         <img src="{{asset('/')}}admin/assets/images/22.jpg" alt="homepage" class="light-logo" />
                     </b>--}}
                    <!--End Logo icon -->
                    <!-- Logo text -->
                    {{--                    <span class="logo-text badge badge-orange text-uppercase text-white">--}}
                    {{--                       Admin Panel--}}
                    {{--  <!-- dark Logo text -->
                          <img src="{{asset('/')}}admin/assets/images/logo-text.png" alt="homepage" class="dark-logo" />
                  <!-- Light Logo text -->
                          <img src="{{asset('/')}}admin/assets/images/logo-light-text.png" class="light-logo" alt="homepage" />   --}}
                    {{--                     </span>--}}
                </a>
                <a class="sidebartoggler d-none d-md-block" href="javascript:void(0)" data-sidebartype="mini-sidebar">
                    <i class="mdi mdi-toggle-switch mdi-toggle-switch-off font-20"></i>
                </a>
            </div>
            <a class="topbartoggler d-block d-md-none waves-effect waves-light" href="javascript:void(0)" data-toggle="collapse" data-target="#navbarSupportedContent"
               aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <i class="ti-more"></i>
            </a>
        </div>
        <!-- ============================================================== -->
        <!-- End Logo -->
        <!-- ============================================================== -->
        <div class="navbar-collapse collapse" id="navbarSupportedContent">
            <!-- ============================================================== -->
            <!-- toggle and nav items -->
            <!-- ============================================================== -->
            <ul class="navbar-nav float-left mr-auto">
                <li class="nav-item search-box">
                    <a class="nav-link waves-effect waves-dark" href="javascript:void(0)">
                        {{--<div class="d-flex align-items-center">
                            <i class="mdi mdi-magnify font-20 mr-1"></i>
                            <div class="ml-1 d-none d-sm-block">
                                <span>Search</span>
                            </div>
                        </div>--}}
                    </a>

                </li>
            </ul>
            <!-- ============================================================== -->
            <!-- Right side toggle and nav items -->
            <!-- ============================================================== -->
            <ul class="navbar-nav float-right">

                <li class="nav-item">
                    <a href="javascript:void(0)"
                       class="nav-link waves-effect waves-dark js-theme-toggle"
                       id="themeToggleBtn"
                       title="Toggle Theme">
                        <i class="fa fa-sun font-22 text-warning" id="themeToggleIcon"></i>

                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle waves-effect waves-dark pro-pic" href="javascript:void(0)" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img src="{{ Auth::user()->profile_photo_url }}" alt="user" class="rounded-circle" width="40">

                        <span class="m-l-5 font-medium d-none d-sm-inline-block">
                             @php
                                 $user = Auth::user();
                                 $roles = $user?->roles?->pluck('name')->map(fn ($role) => str($role)->replace('-', ' ')->title())->join(', ');
                             @endphp

                            @if(!empty($user))
                                ({{ $user->name ?? '' }}{{ $roles ? ' - ' . $roles : '' }})
                            @endif

                            <i class="mdi mdi-chevron-down"></i>
                        </span>
                    </a>

                    <div class="dropdown-menu dropdown-menu-right user-dd animated flipInY">
                        <span class="with-arrow">
                            <span class="bg-secondary"></span>
                        </span>
                        <div class="d-flex no-block align-items-center p-15 bg-secondary text-white m-b-10">
                            <div class="m-l-10">
                                <h4 class="m-b-0 px-2">
                                    @if(!empty(Auth::user()->email))
                                        {{Auth::user()->email??''}}
                                    @endif
                                </h4>
                            </div>
                        </div>
                        <div class="profile-dis scrollable">
                            <a class="dropdown-item" href="{{url('/admin/my-profile')}}">
                                <i class="ti-user m-r-5 m-l-5"></i> My Profile
                            </a>
                            <a class="dropdown-item" href="{{url('/admin/password-change')}}">
                                <i class="ti-lock m-r-5 m-l-5"></i> Change Password
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fa fa-power-off m-r-5 m-l-5"></i> Logout
                            </a>
                            <form id="logout-form" action="{{ url('/admin/logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </div>
                </li>

            </ul>
        </div>
    </nav>
</header>

