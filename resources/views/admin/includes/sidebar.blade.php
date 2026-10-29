@php
    $packagingMenuOpen = request()->routeIs('package-station', 'carton-cross-check', 'cartons.*');
@endphp

<aside class="left-sidebar sidebar-reference" data-sidebarbg="skin6">
    <div class="scroll-sidebar">
        <nav class="sidebar-nav">
            <ul id="sidebarnav">

                    {{-- Dashboard --}}
                    @can('dashboard.view')
                    <li class="sidebar-item">
                        <a class="sidebar-link waves-effect waves-dark" href="{{ route('dashboard') }}">
                            <i class="mdi mdi-view-dashboard"></i>
                            <span class="hide-menu">DASHBOARD</span>
                        </a>
                    </li>
                    @endcan

                    {{-- User Management --}}
                    @canany(['roles.view', 'users.view'])
                    <li class="sidebar-item">
                        <a class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false">
                            <i class="mdi mdi-account-multiple-plus"></i>
                            <span class="hide-menu">User Management</span>
                        </a>

                        <ul aria-expanded="false" class="collapse first-level">
                            @can('roles.view')
                            <li class="sidebar-item">
                                <a href="{{ url('admin/roles') }}" class="sidebar-link">
                                    <i class="mdi mdi-adjust"></i>
                                    <span class="hide-menu">Roles</span>
                                </a>
                            </li>
                            @endcan
                            @can('users.view')
                            <li class="sidebar-item">
                                <a href="{{ url('admin/users') }}" class="sidebar-link">
                                    <i class="mdi mdi-adjust"></i>
                                    <span class="hide-menu">Users</span>
                                </a>
                            </li>
                            @endcan

                        </ul>
                    </li>
                    @endcanany

                @can('orders.view')
                <li class="sidebar-item">
                    <a class="sidebar-link waves-effect waves-dark" href="{{ route('orders.index') }}">
                        <i class="mdi mdi-view-dashboard"></i>
                        <span class="hide-menu">Orders</span>
                    </a>
                </li>
                @endcan

                {{-- Product Management --}}
                @canany(['product-categories.view', 'brands.view', 'products.view'])
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false">
                        <i class="mdi mdi-account-multiple-plus"></i>
                        <span class="hide-menu">Product Management</span>
                    </a>

                    <ul aria-expanded="false" class="collapse first-level">
                        @can('product-categories.view')
                        <li class="sidebar-item">
                            <a href="{{ route('product-categories.index') }}" class="sidebar-link">
                                <i class="mdi mdi-adjust"></i>
                                <span class="hide-menu">Category</span>
                            </a>
                        </li>
                        @endcan
                        @can('brands.view')
                        <li class="sidebar-item">
                            <a href="{{ route('brands.index') }}" class="sidebar-link">
                                <i class="mdi mdi-adjust"></i>
                                <span class="hide-menu">Brand</span>
                            </a>
                        </li>
                        @endcan
                        @can('products.view')
                        <li class="sidebar-item">
                            <a href="{{ route('products.index') }}" class="sidebar-link">
                                <i class="mdi mdi-adjust"></i>
                                <span class="hide-menu">Products</span>
                            </a>
                        </li>
                        @endcan

                    </ul>
                </li>
                @endcanany

                    {{-- Packaging Management --}}
                    @canany(['package-station.view', 'carton-cross-check.view', 'cartons.view'])
                    <li class="sidebar-item {{ $packagingMenuOpen ? 'selected' : '' }}">
                        <a class="sidebar-link has-arrow waves-effect waves-dark {{ $packagingMenuOpen ? 'active' : '' }}" href="javascript:void(0)" aria-expanded="{{ $packagingMenuOpen ? 'true' : 'false' }}">
                            <i class="mdi mdi-account-multiple-plus"></i>
                            <span class="hide-menu">Packaging</span>
                        </a>

                        <ul aria-expanded="{{ $packagingMenuOpen ? 'true' : 'false' }}" class="collapse first-level {{ $packagingMenuOpen ? 'in' : '' }}">
                            @can('package-station.view')
                            <li class="sidebar-item {{ request()->routeIs('package-station') ? 'active' : '' }}">
                                <a href="{{ route('package-station') }}" class="sidebar-link {{ request()->routeIs('package-station') ? 'active' : '' }}">
                                    <i class="mdi mdi-adjust"></i>
                                    <span class="hide-menu">Package Carton</span>
                                </a>
                            </li>
                            @endcan
                            @can('carton-cross-check.view')
                            <li class="sidebar-item {{ request()->routeIs('carton-cross-check') ? 'active' : '' }}">
                                <a href="{{ route('carton-cross-check') }}" class="sidebar-link {{ request()->routeIs('carton-cross-check') ? 'active' : '' }}">
                                    <i class="mdi mdi-adjust"></i>
                                    <span class="hide-menu">Cross Check Carton</span>
                                </a>
                            </li>
                            @endcan
                            @can('cartons.view')
                            <li class="sidebar-item {{ request()->routeIs('cartons.*') ? 'active' : '' }}">
                                <a href="{{ route('cartons.index') }}" class="sidebar-link {{ request()->routeIs('cartons.*') ? 'active' : '' }}">
                                    <i class="mdi mdi-adjust"></i>
                                    <span class="hide-menu">Cartons</span>
                                </a>
                            </li>
                            @endcan

                        </ul>
                    </li>
                    @endcanany

                    {{-- Settings --}}
                    @can('activity-log.view')
                    <li class="sidebar-item">
                        <a class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false">
                            <i class="mdi mdi-settings"></i>
                            <span class="hide-menu">Setting</span>
                        </a>

                        <ul aria-expanded="false" class="collapse first-level">
                            @can('activity-log.view')
                                <li class="sidebar-item">
                                    <a href="{{ url('admin/activity-log') }}" class="sidebar-link">
                                        <i class="mdi mdi-history"></i>
                                        <span class="hide-menu">Activity Log</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan


                {{-- Logout --}}
                <li class="sidebar-item">
                    <a class="sidebar-link waves-effect waves-dark sidebar-link" href="{{ url('admin/logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="mdi mdi-logout m-r-5 m-l-5"></i> Logout
                    </a>

                    <form id="logout-form" action="{{ url('admin/logout') }}" method="POST" style="display:none;">
                        @csrf
                    </form>
                </li>
            </ul>
        </nav>
    </div>
</aside>
