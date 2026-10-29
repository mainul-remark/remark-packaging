<aside class="left-sidebar">
    <div class="scroll-sidebar">
        <nav class="sidebar-nav">
            <ul id="sidebarnav">

                    {{-- Dashboard --}}
                    <li class="sidebar-item">
                        <a class="sidebar-link waves-effect waves-dark" href="{{ route('dashboard') }}">
                            <i class="mdi mdi-view-dashboard"></i>
                            <span class="hide-menu">DASHBOARD</span>
                        </a>
                    </li>

                    {{-- User Management --}}
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

                <li class="sidebar-item">
                    <a class="sidebar-link waves-effect waves-dark" href="{{ route('orders.index') }}">
                        <i class="mdi mdi-view-dashboard"></i>
                        <span class="hide-menu">Orders</span>
                    </a>
                </li>

                {{-- Packaging Management --}}
                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false">
                        <i class="mdi mdi-account-multiple-plus"></i>
                        <span class="hide-menu">Product Management</span>
                    </a>

                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="{{ route('product-categories.index') }}" class="sidebar-link">
                                <i class="mdi mdi-adjust"></i>
                                <span class="hide-menu">Category</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('brands.index') }}" class="sidebar-link">
                                <i class="mdi mdi-adjust"></i>
                                <span class="hide-menu">Brand</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('products.index') }}" class="sidebar-link">
                                <i class="mdi mdi-adjust"></i>
                                <span class="hide-menu">Products</span>
                            </a>
                        </li>

                    </ul>
                </li>

                    {{-- Packaging Management --}}
                    <li class="sidebar-item">
                        <a class="sidebar-link has-arrow waves-effect waves-dark" href="javascript:void(0)" aria-expanded="false">
                            <i class="mdi mdi-account-multiple-plus"></i>
                            <span class="hide-menu">Packaging</span>
                        </a>

                        <ul aria-expanded="false" class="collapse first-level">
                            <li class="sidebar-item">
                                <a href="{{ route('package-station') }}" class="sidebar-link">
                                    <i class="mdi mdi-adjust"></i>
                                    <span class="hide-menu">Package Carton</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a href="{{ route('carton-cross-check') }}" class="sidebar-link">
                                    <i class="mdi mdi-adjust"></i>
                                    <span class="hide-menu">Cross Check Carton</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a href="{{ route('cartons.index') }}" class="sidebar-link">
                                    <i class="mdi mdi-adjust"></i>
                                    <span class="hide-menu">Cartons</span>
                                </a>
                            </li>

                        </ul>
                    </li>

                    {{-- Settings --}}
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
