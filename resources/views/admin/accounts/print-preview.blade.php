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
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 0 25px rgba(0, 0, 0, 0.05);
    }

    .export-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #ddd;
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
    }

    .export-header img { height: 40px; }

    .balance-positive {
        color: white !important;
        text-shadow: 0 0 5px black;
    }

    .balance-negative {
        color: yellow !important;
        font-weight: 700;
        text-shadow: 0 0 5px black;
    }

    .export-footer {
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
    <div class="layout-wrapper">
        <div id="export-area" class="export-box">
            <div class="export-header">
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
                                        @php
                                            $currencies = \App\Models\Currency::where('is_active', 1)->get();
                                            $displayCurrencies = [];
                                            foreach ($currencies as $currency) {
                                                $displayCurrencies[$currency->code] = $currency;
                                            }

                                            foreach ($summariesByCurrency as $currency => $summary) {
                                                if (isset($displayCurrencies[$currency])) {
                                                    unset($displayCurrencies[$currency]);
                                                }
                                            }

                                            foreach ($displayCurrencies as $currency => $displayCurrency) {
                                                $summariesByCurrency[$currency] = [
                                                    'credit' => 0,
                                                    'debit' => 0,
                                                ];
                                            }
                                        @endphp

                                        @foreach ($summariesByCurrency as $currency => $summary)
                                        @php
                                            $balance = isset($summary['credit']) && isset($summary['debit']) ? $summary['credit'] - $summary['debit'] : 0;
                                            $balanceClass = $balance >= 0 ? 'balance-positive' : 'balance-negative';
                                            $currencyFlag = strtolower(substr($currency, 0, 2));
                                        @endphp
                                        <div class="card border-0 text-white shadow-sm"
                                            style="background: linear-gradient(135deg, #187ad6, #0f58e1);">
                                            <div class="card-body p-2">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <img src="/assets/flags/{{ $currencyFlag ?? 'default' }}.svg" width="30" height="20" class="border" style="object-fit: cover;" onerror="this.src='/assets/flags/default.svg'">
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
                        <div class="rounded-3 p-3 shadow-sm" style="background: linear-gradient(135deg, #f25959, #ef5550); border: 1px solid #e4e4e4;">
                            <div class="mb-2" style="font-size: 0.85rem; font-weight: 500; color: white; text-shadow: 0 0 2px orange;">
                                <span style="font-size: 1rem;">&#x1F4CB;</span>
                                <span style="font-weight: normal;">{{ $setting->note_en }}</span>
                            </div>
                            <div style="font-size: 0.85rem; font-family: 'Droid Arabic Kufi', sans-serif; direction: rtl; color: white; text-shadow: 0 0 2px black;">
                                {{ $setting->note_fa }}
                            </div>
                            <div style="font-size: 0.85rem; font-family: 'Droid Arabic Kufi', sans-serif; direction: rtl; color: white; text-shadow: 0 0 2px black;">
                                {{ $setting->note_ps }}
                            </div>
                        </div>
                        <div class="small text-muted mt-3" style="font-size: 0.80rem; color: black;">
                            {{ $setting->company_name }} &middot; {{ $setting->email }} &middot; {{ $setting->contact }}
                        </div>
                    </div>
        </div>
    </div>
    <!-- Core JS -->
    <script src="{{ asset('vendor/jquery360/jquery-3.6.0.min.js') }}"></script>

    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/node-waves/node-waves.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
</body>

</html>
