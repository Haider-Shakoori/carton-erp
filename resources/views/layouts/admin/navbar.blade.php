<nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-xl-0 d-xl-none me-4">
        <a class="nav-item nav-link me-xl-6 px-0" href="javascript:void(0)">
            <i class="icon-base ri ri-menu-line icon-md"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

        <ul class="navbar-nav align-items-center ms-auto flex-row">

            <!-- Language -->
            <li class="nav-item dropdown">
                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow"
                    href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                    <img src="{{ asset('assets/img/flags/' . app()->getLocale() . '.svg') }}"
                        alt="{{ app()->getLocale() }}" class="w-px-20 h-auto" />
                    <span class="d-none d-sm-inline ms-2 fw-medium"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end animate__animated animate__fadeIn">
                    <li>
                        <a class="dropdown-item d-flex align-items-center {{ app()->getLocale() === 'en' ? 'active' : '' }}"
                            href="{{ route('lang.switch', 'en') }}">
                            <img src="{{ asset('assets/img/flags/en.svg') }}" alt="EN" class="w-px-20 me-2" />
                            <span>English</span>
                            @if (app()->getLocale() === 'en')
                                <i class="bi bi-check2 ms-auto text-success"></i>
                            @endif
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center {{ app()->getLocale() === 'fa' ? 'active' : '' }}"
                            href="{{ route('lang.switch', 'fa') }}">
                            <img src="{{ asset('assets/img/flags/fa.svg') }}" alt="FA" class="w-px-20 me-2" />
                            <span>فارسی</span>
                            @if (app()->getLocale() === 'fa')
                                <i class="bi bi-check2 ms-auto text-success"></i>
                            @endif
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center {{ app()->getLocale() === 'ps' ? 'active' : '' }}"
                            href="{{ route('lang.switch', 'ps') }}">
                            <img src="{{ asset('assets/img/flags/ps.svg') }}" alt="PS" class="w-px-20 me-2" />
                            <span>پښتو</span>
                            @if (app()->getLocale() === 'ps')
                                <i class="bi bi-check2 ms-auto text-success"></i>
                            @endif
                        </a>
                    </li>
                </ul>
            </li>


            <!--/ Language -->

            <!-- Quick links  -->
            <li class="nav-item dropdown-shortcuts navbar-dropdown dropdown">
                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow"
                    href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                    aria-expanded="false">
                    <i class='ri-star-smile-line ri-22px'></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end py-0">
                    <div class="dropdown-menu-header border-bottom py-50">
                        <div class="dropdown-header d-flex align-items-center py-2">
                            <h6 class="mb-0 me-auto">{{ __('ui.shortcuts') }}</h6>
                            <a href="javascript:void(0)"
                                class="btn btn-text-secondary rounded-pill btn-icon dropdown-shortcuts-add"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('ui.add_shortcuts') }}"><i
                                    class="ri-layout-grid-line ri-24px text-heading"></i></a>
                        </div>
                    </div>
                    <div class="dropdown-shortcuts-list scrollable-container">
                        <div class="row row-bordered g-0 overflow-visible">
                            @can('view customers')
                                <div class="dropdown-shortcuts-item col">
                                    <span class="dropdown-shortcuts-icon rounded-circle mb-2">
                                        <i class="bi bi-people-fill text-primary"></i>
                                    </span>
                                    <a href="{{ route('admin.accounts.index') }}"
                                        class="stretched-link">{{ __('ui.customers') }}</a>
                                </div>
                            @endcan

                            @can('view saraf')
                                <div class="dropdown-shortcuts-item col">
                                    <span class="dropdown-shortcuts-icon rounded-circle mb-2">
                                        <i class="bi bi-coin text-success"></i>
                                    </span>
                                    <a href="{{ route('admin.suppliers.index') }}"
                                        class="stretched-link">{{ __('ui.saraf') }}</a>
                                </div>
                            @endcan
                        </div>

                        <div class="row row-bordered g-0 overflow-visible">
                            @can('view transactions')
                                <div class="dropdown-shortcuts-item col">
                                    <span class="dropdown-shortcuts-icon rounded-circle mb-2">
                                        <i class="bi bi-cash-stack text-warning"></i>
                                    </span>
                                    <a href="{{ route('admin.transactions.index') }}"
                                        class="stretched-link">{{ __('ui.transactions') }}</a>
                                </div>
                            @endcan

                            @can('view journal')
                                <div class="dropdown-shortcuts-item col">
                                    <span class="dropdown-shortcuts-icon rounded-circle mb-2">
                                        <i class="bi bi-book text-info"></i>
                                    </span>
                                    <a href="{{ route('admin.journal.index') }}"
                                        class="stretched-link">{{ __('ui.journal') }}</a>
                                </div>
                            @endcan
                        </div>

                        <div class="row row-bordered g-0 overflow-visible">
                            @can('view exchange sales')
                                <div class="dropdown-shortcuts-item col">
                                    <span class="dropdown-shortcuts-icon rounded-circle mb-2">
                                        <i class="bi bi-currency-dollar text-danger"></i>
                                    </span>
                                    <a href="{{ route('admin.exchange-purchase.index') }}"
                                        class="stretched-link">{{ __('ui.exchange_purchase') }}</a>
                                </div>

                                <div class="dropdown-shortcuts-item col">
                                    <span class="dropdown-shortcuts-icon rounded-circle mb-2">
                                        <i class="bi bi-currency-exchange text-primary"></i>
                                    </span>
                                    <a href="{{ route('admin.exchange.index') }}"
                                        class="stretched-link">{{ __('ui.money_exchange') }}</a>
                                </div>
                            @endcan
                        </div>

                        <div class="row row-bordered g-0 overflow-visible">
                            @can('view remittances')
                                <div class="dropdown-shortcuts-item col">
                                    <span class="dropdown-shortcuts-icon rounded-circle mb-2">
                                        <i class="bi bi-send text-success"></i>
                                    </span>
                                    <a href="{{ route('admin.remittances.index') }}"
                                        class="stretched-link">{{ __('ui.remittances') }}</a>
                                </div>
                            @endcan

                        </div>
                    </div>

                </div>
            </li>
            <!-- Quick links -->

            <!-- Notification -->
            <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-xl-1 me-4">
                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow"
                    href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                    aria-expanded="false">
                    <i class="ri-notification-2-line ri-22px"></i>
                    <span
                        class="position-absolute start-50 translate-middle-y badge badge-dot bg-danger top-0 mt-2 border"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end py-0">
                    <li class="dropdown-menu-header border-bottom">
                        <div class="dropdown-header d-flex align-items-center py-3">
                            <h6 class="mb-0 me-auto">{{ __('ui.notifications') }}</h6>
                            <div class="d-flex align-items-center">
                                <span class="badge rounded-pill bg-label-primary me-2">8 New</span>
                                <a href="javascript:void(0)"
                                    class="btn btn-text-secondary rounded-pill btn-icon dropdown-notifications-all"
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('ui.mark_all_as_read') }}"><i
                                        class="ri-mail-open-line ri-20px text-body"></i></a>
                            </div>
                        </div>
                    </li>
                    <li class="dropdown-notifications-list scrollable-container">
                        <ul class="list-group list-group-flush">
                            {{-- <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-40 rounded-circle h-auto">
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">Congratulation Lettie 🎉</h6>
                                        <small class="d-block text-body mb-1">Won the monthly best seller gold badge</small>
                                        <small class="text-muted">1h ago</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li> --}}
                            {{-- <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <span class="avatar-initial rounded-circle bg-label-danger">CF</span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">Charles Franklin</h6>
                                        <small class="d-block text-body mb-1">Accepted your connection</small>
                                        <small class="text-muted">12hr ago</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item list-group-item-action dropdown-notifications-item marked-as-read">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <img src="{{ asset('assets/img/avatars/2.png') }}" alt class="w-px-40 rounded-circle h-auto">
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">New Message ✉️</h6>
                                        <small class="d-block text-body mb-1">You have new message from Natalie</small>
                                        <small class="text-muted">1h ago</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <span class="avatar-initial rounded-circle bg-label-success"><i class="ri-shopping-cart-2-line"></i></span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">Whoo! You have new order 🛒 </h6>
                                        <small class="d-block text-body mb-1">ACME Inc. made new order $1,154</small>
                                        <small class="text-muted">1 day ago</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item list-group-item-action dropdown-notifications-item marked-as-read">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <img src="{{ asset('assets/img/avatars/9.png') }}" alt class="w-px-40 rounded-circle h-auto">
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">Application has been approved 🚀 </h6>
                                        <small class="d-block text-body mb-1">Your ABC project application has been approved.</small>
                                        <small class="text-muted">2 days ago</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item list-group-item-action dropdown-notifications-item marked-as-read">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <span class="avatar-initial rounded-circle bg-label-success"><i class="ri-pie-chart-2-line"></i></span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">Monthly report is generated</h6>
                                        <small class="d-block text-body mb-1">July monthly financial report is generated </small>
                                        <small class="text-muted">3 days ago</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item list-group-item-action dropdown-notifications-item marked-as-read">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <img src="{{ asset('assets/img/avatars/5.png') }}" alt class="w-px-40 rounded-circle h-auto">
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">Send connection request</h6>
                                        <small class="d-block text-body mb-1">Peter sent you connection request</small>
                                        <small class="text-muted">4 days ago</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <img src="{{ asset('assets/img/avatars/6.png') }}" alt class="w-px-40 rounded-circle h-auto">
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">New message from Jane</h6>
                                        <small class="d-block text-body mb-1">Your have new message from Jane</small>
                                        <small class="text-muted">5 days ago</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item list-group-item-action dropdown-notifications-item marked-as-read">
                                <div class="d-flex">
                                    <div class="me-3 flex-shrink-0">
                                        <div class="avatar">
                                            <span class="avatar-initial rounded-circle bg-label-warning"><i class="ri-error-warning-line"></i></span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="small mb-1">CPU is running high</h6>
                                        <small class="d-block text-body mb-1">CPU Utilization Percent is currently at 88.63%,</small>
                                        <small class="text-muted">5 days ago</small>
                                    </div>
                                    <div class="dropdown-notifications-actions flex-shrink-0">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="ri-close-line"></span></a>
                                    </div>
                                </div>
                            </li> --}}
                        </ul>
                    </li>
                    <li class="border-top">
                        <div class="d-grid p-4">
                            <a class="btn btn-primary btn-sm d-flex" href="javascript:void(0);">
                                <small class="align-middle">View all notifications</small>
                            </a>
                        </div>
                    </li>
                </ul>
            </li>
            <!--/ Notification -->

            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);"
                    data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="{{ asset('assets/img/avatars/1.png') }}" alt class="w-px-40 rounded-circle h-auto">
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end mt-3 py-2">
                    <li>
                        <a class="dropdown-item pb-3"
                            href="{{ Route::has('profile.show') ? route('profile.show') : url('pages/profile-user') }}">
                            <div class="d-flex align-items-center">
                                <div class="me-2 flex-shrink-0">
                                    <div class="avatar avatar-online">
                                        <img src="{{ asset('assets/img/avatars/1.png') }}" alt
                                            class="w-px-40 rounded-circle h-auto">
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="small mb-0">
                                        @if (Auth::check())
                                            {{ Auth::user()->name }}
                                        @else
                                            Admin
                                        @endif
                                    </h6>
                                    <small class="text-muted">{{ Auth::user()->account_type }}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item"
                            href="{{ Route::has('profile.show') ? route('profile.show') : url('pages/profile-user') }}">
                            <i class="ri-user-3-line ri-22px me-2"></i>
                            <span class="align-middle">{{ __('ui.my_profile') }}</span>
                        </a>
                    </li>


                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    @if (Auth::check())
                        <li>
                            <div class="d-grid px-4 pb-1 pt-2">
                                <a class="btn btn-danger d-flex" href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <small class="align-middle">{{ __('ui.logout') }}</small>
                                    <i class="ri-logout-box-r-line ri-16px ms-2"></i>
                                </a>
                            </div>
                        </li>
                        <form method="POST" id="logout-form" action="{{ route('logout') }}">
                            @csrf
                        </form>
                    @else
                        <li>
                            <div class="d-grid px-4 pb-1 pt-2">
                                <a class="btn btn-danger d-flex"
                                    href="{{ Route::has('login') ? route('login') : url('auth/login-basic') }}">
                                    <small class="align-middle">{{ __('ui.login') }}</small>
                                    <i class="ri-logout-box-r-line ri-16px ms-2"></i>
                                </a>
                            </div>
                        </li>
                    @endif
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>
