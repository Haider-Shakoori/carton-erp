<!doctype html>

<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="layout-navbar-fixed layout-menu-fixed layout-compact {{ $isRtl ? 'layout-rtl' : '' }}" data-skin="default" data-assets-path="{{ asset('assets') }}" data-template="vertical-menu-template-starter" data-bs-theme="light">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>@yield('title')</title>
    <link rel="shortcut icon" href="{{ asset('images/' . $setting->logo) }}" alt="{{ $setting->company_name }}" type="image/x-icon">
    <meta name="robots" content="noindex" />
    <meta name="description" content="RAHE-ARYA SARAFI is a powerful and modern currency exchange management system designed for efficient tracking of transactions, balances, and remittances." />
    <meta name="keywords" content="sarafi system, currency exchange software, money exchange management, remittance system, forex software, hawala, sarafi management Afghanistan" />
    <meta name="author" content="Fida Technologies" />
    <link rel="canonical" href="https://fida.af" />
    <meta property="og:title" content="@yield('title')" />
    <meta property="og:description" content="Manage all your currency exchange and remittance operations with RAHE-ARYA SARAFI – a secure, fast, and user-friendly Sarafi system." />
    <meta property="og:image" content="https://fida.af/images/rahe-arya-sarafi.jpg" />
    <meta property="og:url" content="https://fida.af" />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="@yield('title')" />
    <meta name="twitter:description" content="Track currency exchange, customer accounts, and financial reports with RAHE-ARYA SARAFI by Fida Technologies." />
    <meta name="twitter:image" content="https://fida.af/images/rahe-arya-sarafi.jpg" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/node-waves/node-waves.css') }}" />
    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('vendor/remixicon/remixicon.css') }}" />
    <link href="{{ asset('vendor/bootstrap-icons/1.10.5/bootstrap-icons.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/pickr/pickr-themes.css') }}" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
    <!-- SweetAlert2 JS -->
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <style>
        .select2-container--open {
            z-index: 999999 !important;
            /* above offcanvas */
        }

    </style>
    <!-- Page CSS -->
    @if ($isRtl)
    <style>
        body {
            direction: rtl;
            text-align: right;
        }

        .menu-inner {
            text-align: right;
        }

        .navbar-nav-right {
            margin-left: auto;
            margin-right: 0;
        }

        .dropdown-menu {
            left: auto;
            right: 0;
            text-align: right;
        }

        .form-control {
            text-align: right;
        }

        .me-2 {
            margin-left: .5rem !important;
            margin-right: 0 !important;
        }

        .layout-rtl .menu-inner {
            text-align: right;
        }

        .layout-rtl .navbar-nav-right {
            margin-left: auto;
            margin-right: 0;
        }

    </style>
    @endif

    @if (app()->getLocale() === 'fa' || app()->getLocale() === 'ps')
    <link href="{{ asset('vendor/fonts/droid-arabic-kufi/droidarabickufi.css') }}" rel="stylesheet">
    <style>
        body {
            background: #1e1e2f;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
        }

        .layout-wrapper {
            width: 100%;
            max-width: 800px;
        }

        html,
        * {
            font-family: 'Droid Arabic Kufi', sans-serif !important;
        }

        .menu .menu-inner .menu-item>a,
        .menu .menu-inner .menu-header {
            white-space: nowrap;
            font-family: 'Droid Arabic Kufi', sans-serif !important;
            font-weight: bolder !important;
            font-size: 17px !important;
        }

    </style>
    @endif

    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/7.3.1/css/all.min.css') }}">
    <style>
        html {
            font-size: 15px;
            background: #f0f2f5;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .export-box {
            background: #fff;
            max-width: 750px;
            margin: 2rem auto;
            padding: 2rem 2.5rem;
            border-radius: 12px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.05);
        }

        .export-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #ddd;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
        }

        .export-header img {
            height: 50px;
        }

        .export-header h4 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1c2c52;
        }

        .section-card {
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0d6efd;
            margin-bottom: 1rem;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 0.5rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f2f2f2;
        }

        .info-label {
            font-weight: 600;
            color: #333;
        }

        .info-value {
            color: #555;
            text-align: right;
        }

        .balance-positive {
            color: #00ff8c !important;
        }

        .balance-negative {
            color: #ff6b6b !important;
        }

        .export-footer {
            border-top: 1px solid #ddd;
            margin-top: 2rem;
            padding-top: 1rem;
            text-align: center;
            font-size: 0.85rem;
            color: #666;
        }

        .export-actions {
            max-width: 750px;
            margin: 1rem auto;
            text-align: right;
        }

    </style>
    <!-- Helpers -->
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>

    <script src="{{ asset('assets/js/config.js') }}"></script>
</head>

<body>
<div class="row">
    <div class="col-md-4"></div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
    <div class="card-body p-2">
        <div class="export-box">
            <div class="export-header">
                {{-- <img src="{{ public_path('images/' . $setting->logo) }}" alt="{{ $setting->company_name }}"> --}}
                <img src="{{ asset('images/' . $setting->logo) }}" alt="{{ $setting->company_name }}">
                <div>
                    <h6 class="mb-0" style="font-size: 1rem;">{{ __('ui.account_summary') }}</h6>
                    <small style="color: #6c757d;">{{ now()->format('Y-m-d H:i:s') }}</small>
                </div>
            </div>

            <div class="row gy-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-2">
                            <h6 class="text-primary fw-semibold mb-2" style="font-size: 0.95rem;">
                                <i class="fas fa-user-circle me-1"></i> {{ __('ui.client_information') }}
                            </h6>
                            <div class="row mb-2">
                                <div class="col-6">
                                    <div class="fw-semibold text-dark" style="font-size: 0.9rem;">{{ $account->code }}</div>
                                    <div class="text-muted small">{{ __('ui.code') }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="fw-semibold text-dark" style="font-size: 0.9rem;">{{ $account->name }}</div>
                                    <div class="text-muted small">{{ __('ui.name') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-2">
                            <h6 class="text-primary fw-semibold mb-2" style="font-size: 0.95rem;">
                                <i class="fas fa-balance-scale me-1"></i> {{ __('ui.balances') }}
                            </h6>
                            <div class="d-flex flex-column gap-2">
                                @foreach ($summariesByCurrency as $currency => $summary)
                                @php
                                    $balance = $summary['credit'] - $summary['debit'];
                                    $balanceClass = $balance >= 0 ? 'balance-positive' : 'balance-negative';
                                    $currencyFlag = strtolower(substr($currency, 0, 2));
                                @endphp
                                <div class="card border-0 text-white shadow-sm"
                                    style="background: linear-gradient(135deg, #187ad6, #0cc5dd);">
                                    <div class="card-body p-2">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <img src="{{ asset('assets/flags/' . $currencyFlag . '.svg') }}" width="30" height="20" class="border" style="object-fit: cover;">
                                            <div class="fw-semibold text-uppercase" style="font-size: 0.85rem;">{{ $currency }}</div>
                                            <div class="ms-auto fw-bold {{ $balanceClass }}">{{ number_format($balance, 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="export-footer mt-4 px-2">
                <div class="rounded-3 shadow-sm p-3"
                    style="background: linear-gradient(135deg, #f25959, #ef5550); border: 1px solid #e4e4e4;">
                    <div class="mb-2" style="font-size: 0.85rem; font-weight: 500; color: white; text-shadow: 0 0 2px black;">
                        <span style="font-size: 1rem;">&#x1F4CB;</span>
                        <span style="font-weight: normal;">Please check and confirm the balance.</span>
                    </div>
                    <div style="font-size: 0.85rem; font-family: 'Droid Arabic Kufi', sans-serif; direction: rtl; color: white; text-shadow: 0 0 2px black;">
                        لطفاً بیلانس ارسال شده را بررسی و از صحت آن اطمینان حاصل نمایید.
                    </div>
                    <div style="font-size: 0.85rem; font-family: 'Droid Arabic Kufi', sans-serif; direction: rtl; color: white; text-shadow: 0 0 2px black;">
                        مهرباني وکړئ د لېږدول شوي بیلانس د صحت په اړه اطمینان حاصل کړئ.
                    </div>
                </div>
                <div class="mt-3 small text-muted" style="font-size: 0.80rem; color: black;">
                    {{ $setting->company_name }} &middot; {{ $setting->email }} &middot; {{ $setting->contact }}
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
</div>
</body>
</html>
