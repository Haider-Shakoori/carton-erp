<!doctype html>

<html lang="en" class="layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" data-skin="default" data-assets-path="../../assets/"
    data-template="vertical-menu-template-starter" data-bs-theme="light">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
        <meta name="robots" content="noindex" />
        <title>@yield('title')</title>

        <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/1.10.3/bootstrap-icons.css') }}" />

        <link rel="stylesheet" href="../../assets/vendor/libs/node-waves/node-waves.css" />

        <link rel="stylesheet" href="../../assets/vendor/libs/pickr/pickr-themes.css" />

        <link rel="stylesheet" href="../../assets/vendor/css/core.css" />
        <link rel="stylesheet" href="../../assets/css/demo.css" />

        <!-- Vendors CSS -->

        <link rel="stylesheet" href="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

        <!-- endbuild -->

        <!-- Page CSS -->

        <!-- Helpers -->
        <script src="../../assets/vendor/js/helpers.js"></script>
        <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

        <!--? Config: Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file. -->

        <script src="../../assets/js/config.js"></script>
    </head>

    <body>
        <!-- Layout wrapper -->
        <div class="layout-wrapper layout-content-navbar">
            <div class="layout-container">
                <!-- Menu -->
                <aside id="layout-menu" class="layout-menu menu-vertical menu bg-white">
                    <div class="app-brand demo">
                        <a href="index.html" class="app-brand-link gap-xl-0 gap-2">
                            <span class="app-brand-logo demo me-1">
                                <span class="text-primary">
                                    <img src="{{ asset('images/' . $setting->logo) }}" alt="{{ $setting->company_name }}" height="50">
                                </span>
                            </span>
                            <span class="app-brand-text demo menu-text fw-semibold ms-2">RAHE <br>ARYA</span>
                        </a>

                        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                            <i class="menu-toggle-icon d-xl-inline-block align-middle"></i>
                        </a>
                    </div>

                    <div class="menu-inner-shadow"></div>

                    <ul class="menu-inner py-3" style="margin-top: 15px;">
                        <!-- Page -->
                        <li class="menu-item {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
                            <a href="{{ route('staff.dashboard') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-house-door"></i>
                                <div data-i18n="Dashboard">Dashboard</div>
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('staff.stock.index') ? 'active' : '' }}">
                            <a href="{{ route('staff.stock.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-box-seam"></i>
                                <div data-i18n="Stock Entry">Stock Entry</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/containers" class="menu-link">
                                <i class="menu-icon icon-base bi bi-truck"></i>
                                <div data-i18n="Containers">Containers</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/transactions" class="menu-link">
                                <i class="menu-icon icon-base bi bi-file-text"></i>
                                <div data-i18n="Transactions">Transactions</div>
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('staff.accounts.index') ? 'active' : '' }}">
                            <a href="{{ route('staff.accounts.index') }}" class="menu-link">
                                <i class="menu-icon icon-base bi bi-person-circle"></i>
                                <div data-i18n="Accounts">Accounts</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/warehouses" class="menu-link">
                                <i class="menu-icon icon-base bi bi-building"></i>
                                <div data-i18n="Warehouses">Warehouses</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/expenses" class="menu-link">
                                <i class="menu-icon icon-base bi bi-wallet2"></i>
                                <div data-i18n="Expenses">Expenses</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/reports" class="menu-link">
                                <i class="menu-icon icon-base bi bi-file-bar-graph"></i>
                                <div data-i18n="Reports">Reports</div>
                            </a>
                        </li>

                        @if (Auth::guard('staff')->user()?->can('view users'))
                            <li class="menu-item {{ request()->routeIs('staff.users.index') ? 'active' : '' }}">
                                <a href="{{ route('staff.users.index') }}" class="menu-link">
                                    <i class="menu-icon icon-base bi bi-people"></i>
                                    <div data-i18n="User Management">Manage Users</div>
                                </a>
                            </li>
                        @endif

                    </ul>
                </aside>

                <div class="menu-mobile-toggler d-xl-none rounded-1">
                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary rounded-1 p-2">
                        <i class="ri ri-menu-line icon-base"></i>
                        <i class="ri ri-arrow-right-s-line icon-base"></i>
                    </a>
                </div>
                <!-- / Menu -->

                <!-- Layout container -->
                <div class="layout-page">
                    <!-- Navbar -->

                    <nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
                        <div class="layout-menu-toggle navbar-nav align-items-xl-center me-xl-0 d-xl-none me-4">
                            <a class="nav-item nav-link me-xl-6 px-0" href="javascript:void(0)">
                                <i class="icon-base ri ri-menu-line icon-md"></i>
                            </a>
                        </div>

                        <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
                            <div class="navbar-nav align-items-center">

                            </div>

                            <ul class="navbar-nav align-items-center ms-md-auto flex-row">
                                <!-- User -->
                                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                    <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <div class="avatar avatar-online">
                                            <img src="../../assets/img/avatars/1.png" alt="alt" class="rounded-circle" />
                                        </div>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="#">
                                                <div class="d-flex">
                                                    <div class="me-3 flex-shrink-0">
                                                        <div class="avatar avatar-online">
                                                            <img src="../../assets/img/avatars/1.png" alt="alt" class="w-px-40 rounded-circle h-auto" />
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0">John Doe</h6>
                                                        <small class="text-body-secondary">Admin</small>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                        <li>
                                            <div class="dropdown-divider my-1"></div>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#">
                                                <i class="icon-base ri ri-user-line icon-md me-3"></i>
                                                <span>My Profile</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#">
                                                <i class="icon-base ri ri-settings-4-line icon-md me-3"></i>
                                                <span>Settings</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#">
                                                <span class="d-flex align-items-center align-middle">
                                                    <i class="icon-base ri ri-bank-card-line icon-md me-3 flex-shrink-0"></i>
                                                    <span class="flex-grow-1 ms-1 align-middle">Billing Plan</span>
                                                    <span class="badge rounded-pill bg-danger flex-shrink-0">4</span>
                                                </span>
                                            </a>
                                        </li>
                                        <li>
                                            <div class="dropdown-divider my-1"></div>
                                        </li>
                                        <li>
                                            <div class="d-grid px-4 pb-1 pt-2">
                                                <a class="btn btn-danger d-flex" href="javascript:void(0);">
                                                    <small class="align-middle">Logout</small>
                                                    <i class="ri ri-logout-box-r-line ri-xs ms-2"></i>
                                                </a>
                                            </div>
                                        </li>
                                    </ul>
                                </li>
                                <!--/ User -->
                            </ul>
                        </div>
                    </nav>

                    <!-- / Navbar -->

                    <!-- Content wrapper -->
                    <div class="content-wrapper">
                        <!-- Content -->
                        <div class="container-xxl flex-grow-1 container-p-y">
                            @yield('content')
                        </div>
                        <!-- / Content -->
                    </div>
                    <!-- Content wrapper -->
                </div>
                <!-- / Layout page -->
            </div>

        </div>
        <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
        <script src="../../assets/vendor/js/bootstrap.js"></script>
        <script src="../../assets/vendor/js/menu.js"></script>
        <script src="../../assets/js/main.js"></script>
        @yield('js')
    </body>

</html>
