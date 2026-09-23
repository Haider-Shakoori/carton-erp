{{-- resources/views/admin/expenses/index.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.expenses'))

@section('css')
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}"
        rel="stylesheet">
    <style>
        :root {
            --expense-primary: #ef4444;
            --expense-primary-dark: #dc2626;
            --expense-success: #1e40af;
            --expense-danger: #991b1b;
            --expense-warning: #f59e0b;
            --expense-gray-50: #f8fafc;
            --expense-gray-100: #f1f5f9;
            --expense-gray-200: #e2e8f0;
            --expense-gray-300: #cbd5e1;
            --expense-gray-400: #94a3b8;
            --expense-gray-500: #64748b;
            --expense-gray-600: #475569;
            --expense-gray-700: #334155;
            --expense-gray-800: #1e293b;
            --expense-gray-900: #0f172a;
            --expense-radius: 16px;
            --expense-radius-sm: 10px;
            --expense-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 8px 32px rgba(0, 0, 0, 0.04);
            --expense-shadow-hover: 0 1px 3px rgba(0, 0, 0, 0.02), 0 12px 48px rgba(0, 0, 0, 0.08);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--expense-gray-900);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-header h1 .accent {
            background: linear-gradient(135deg, var(--expense-primary), #dc2626);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-header .subtitle {
            color: var(--expense-gray-500);
            font-size: 0.875rem;
            margin: 0.25rem 0 0 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            background: white;
            border-radius: var(--expense-radius);
            padding: 1.25rem 1.5rem;
            box-shadow: var(--expense-shadow);
            border: 1px solid var(--expense-gray-200);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            box-shadow: var(--expense-shadow-hover);
            transform: translateY(-2px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.purple::before {
            background: linear-gradient(90deg, #8b5cf6, #a78bfa);
        }

        .stat-card.green::before {
            background: linear-gradient(90deg, #1e40af, #3b82f6);
        }

        .stat-card.red::before {
            background: linear-gradient(90deg, #991b1b, #dc2626);
        }

        .stat-card.blue::before {
            background: linear-gradient(90deg, #1e3a5f, #2563eb);
        }

        .stat-card.orange::before {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .stat-card.pink::before {
            background: linear-gradient(90deg, #ec4899, #f472b6);
        }

        .stat-card .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-card .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--expense-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 800;
        }

        .stat-card .stat-icon.purple {
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            color: #7c3aed;
        }

        .stat-card .stat-icon.green {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
        }

        .stat-card .stat-icon.red {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
            color: #991b1b;
        }

        .stat-card .stat-icon.blue {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e3a5f;
        }

        .stat-card .stat-icon.orange {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
        }

        .stat-card .stat-icon.pink {
            background: linear-gradient(135deg, #fce7f3, #fbcfe8);
            color: #db2777;
        }

        .stat-card .stat-icon .currency-symbol-icon {
            font-size: 1.1rem;
            font-weight: 800;
        }

        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--expense-gray-900);
        }

        .stat-card .stat-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--expense-gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 0.25rem;
        }

        .stat-card .stat-value .currency-sm {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--expense-gray-400);
            margin-right: 1px;
        }

        .stat-card .stat-value .balance-status {
            font-size: 0.55rem;
            font-weight: 700;
            margin-left: 4px;
            text-transform: uppercase;
            padding: 1px 6px;
            border-radius: 3px;
        }

        .stat-card .stat-value .balance-status.positive {
            color: #1e40af;
            background: #dbeafe;
        }

        .stat-card .stat-value .balance-status.negative {
            color: #991b1b;
            background: #fecaca;
        }

        .stat-card .stat-value .balance-status.zero {
            color: var(--expense-gray-500);
            background: var(--expense-gray-100);
        }

        .main-card {
            background: white;
            border-radius: var(--expense-radius);
            box-shadow: var(--expense-shadow);
            border: 1px solid var(--expense-gray-200);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .main-card:hover {
            box-shadow: var(--expense-shadow-hover);
        }

        .main-card .card-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #fafafa, #ffffff);
            border-bottom: 1px solid var(--expense-gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .main-card .card-header h5 {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--expense-gray-800);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .main-card .card-header .badge-count {
            background: var(--expense-gray-100);
            color: var(--expense-gray-600);
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .main-card .card-body {
            padding: 1.5rem;
        }

        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .filter-bar .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            border: 1.5px solid var(--expense-gray-200);
            border-radius: var(--expense-radius-sm);
            padding: 0.15rem 0.15rem 0.15rem 0.75rem;
            transition: all 0.3s ease;
            flex: 1 1 180px;
        }

        .filter-bar .filter-group:focus-within {
            border-color: var(--expense-primary);
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.08);
        }

        .filter-bar .filter-group .filter-icon {
            color: var(--expense-gray-400);
            font-size: 0.9rem;
        }

        .filter-bar .filter-group input,
        .filter-bar .filter-group select {
            border: none;
            padding: 0.45rem 0.5rem;
            font-size: 0.8125rem;
            background: transparent;
            width: 100%;
            outline: none;
            color: var(--expense-gray-700);
        }

        .filter-bar .filter-group input::placeholder {
            color: var(--expense-gray-400);
        }

        .filter-bar .btn-primary-custom {
            background: linear-gradient(135deg, var(--expense-primary), var(--expense-primary-dark));
            color: white;
            border: none;
            padding: 0.45rem 1.25rem;
            border-radius: var(--expense-radius-sm);
            font-weight: 600;
            font-size: 0.8125rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .filter-bar .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.3);
            color: white;
        }

        .filter-bar .btn-outline-custom {
            background: white;
            color: var(--expense-gray-600);
            border: 1.5px solid var(--expense-gray-200);
            padding: 0.45rem 1.25rem;
            border-radius: var(--expense-radius-sm);
            font-weight: 600;
            font-size: 0.8125rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .filter-bar .btn-outline-custom:hover {
            background: var(--expense-gray-50);
            border-color: var(--expense-gray-300);
        }

        .table-responsive-custom {
            overflow-x: auto;
        }

        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }

        .table-custom thead th {
            padding: 0.625rem 0.75rem;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            color: var(--expense-gray-600);
            font-weight: 700;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 2px solid var(--expense-gray-200);
            white-space: nowrap;
            text-align: left;
        }

        .table-custom tbody td {
            padding: 0.625rem 0.75rem;
            border-bottom: 1px solid var(--expense-gray-100);
            vertical-align: middle;
        }

        .table-custom tbody tr {
            transition: background 0.3s ease;
        }

        .table-custom tbody tr:hover {
            background: rgba(239, 68, 68, 0.02);
        }

        .expense-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .expense-cell .avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--expense-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
            color: white;
            flex-shrink: 0;
            background: linear-gradient(135deg, var(--expense-primary), #dc2626);
        }

        .expense-cell .info .name {
            font-weight: 600;
            color: var(--expense-gray-800);
            font-size: 0.875rem;
        }

        .expense-cell .info .meta .badge-code {
            background: var(--expense-gray-100);
            padding: 0.05rem 0.4rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--expense-primary);
        }

        /* Balance Badge Styles */
        .balance-badge {
            display: inline-block;
            padding: 0.15rem 0.6rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-right: 0.5rem;
            margin-bottom: 0.2rem;
            white-space: nowrap;
        }

        .balance-badge.positive {
            color: #1e40af;
            background: #dbeafe;
        }

        .balance-badge.negative {
            color: #991b1b;
            background: #fecaca;
        }

        .balance-badge.zero {
            color: var(--expense-gray-500);
            background: var(--expense-gray-100);
        }

        .balance-badge .currency-code {
            font-size: 0.55rem;
            opacity: 0.7;
            font-weight: 400;
        }

        .balance-badge .balance-symbol {
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 0.3rem;
            justify-content: flex-end;
        }

        .action-buttons .btn-action {
            width: 32px;
            height: 32px;
            border: none;
            background: transparent;
            border-radius: var(--expense-radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            color: var(--expense-gray-400);
            cursor: pointer;
        }

        .action-buttons .btn-action:hover {
            background: var(--expense-gray-100);
        }

        .action-buttons .btn-action.view:hover {
            color: var(--expense-primary);
        }

        .action-buttons .btn-action.edit:hover {
            color: var(--expense-warning);
        }

        .action-buttons .btn-action.delete:hover {
            color: var(--expense-danger);
            background: #fecaca;
        }

        .pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid var(--expense-gray-200);
            margin-top: 1rem;
        }

        .pagination-wrap .info-text {
            font-size: 0.75rem;
            color: var(--expense-gray-400);
        }

        .pagination-wrap .pagination {
            margin: 0;
        }

        .pagination-wrap .pagination .page-link {
            padding: 0.35rem 0.75rem;
            font-size: 0.8125rem;
            border-radius: var(--expense-radius-sm);
            border: 1px solid var(--expense-gray-200);
            color: var(--expense-gray-600);
            transition: all 0.3s ease;
        }

        .pagination-wrap .pagination .page-link:hover {
            background: var(--expense-gray-50);
            border-color: var(--expense-primary);
            color: var(--expense-primary);
        }

        .pagination-wrap .pagination .active .page-link {
            background: linear-gradient(135deg, var(--expense-primary), var(--expense-primary-dark));
            border-color: var(--expense-primary);
            color: white;
        }

        .pagination-wrap .pagination .disabled .page-link {
            opacity: 0.4;
            pointer-events: none;
        }

        .empty-state {
            text-align: center;
            padding: 2.5rem 1rem;
        }

        .empty-state .icon {
            font-size: 2.5rem;
            color: var(--expense-gray-300);
            margin-bottom: 0.75rem;
            display: block;
        }

        .empty-state .title {
            font-weight: 600;
            color: var(--expense-gray-700);
            font-size: 0.9375rem;
        }

        .empty-state .subtitle {
            color: var(--expense-gray-400);
            font-size: 0.8125rem;
            margin-top: 0.25rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }

        .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.inactive {
            background: #fecaca;
            color: #991b1b;
        }

        .status-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-badge.active .dot {
            background: #10b981;
        }

        .status-badge.inactive .dot {
            background: #ef4444;
        }

        .toast-container {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 1055;
        }

        .toast {
            border: none;
            border-radius: var(--expense-radius-sm);
            box-shadow: var(--expense-shadow);
            padding: 0.75rem 1rem;
        }

        .toast .toast-body {
            font-weight: 500;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
            }

            .filter-bar {
                flex-direction: column;
            }

            .filter-bar .filter-group {
                width: 100%;
                flex: 1 1 auto;
            }

            .filter-bar .btn-primary-custom,
            .filter-bar .btn-outline-custom {
                width: 100%;
                justify-content: center;
            }

            .pagination-wrap {
                flex-direction: column;
                align-items: center;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/admin-index.css') }}">
@endsection

@section('content')
    {{-- Toast Notifications --}}
    <div class="toast-container">
        @if (session('success'))
            <div class="toast align-items-center text-bg-success show border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
                    <button type="button" class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="toast align-items-center text-bg-danger show border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}</div>
                    <button type="button" class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif
    </div>

    <div class="container-fluid px-3 px-md-4 erp-index-ui">

        {{-- ─── PAGE HEADER ─── --}}
        <div class="page-header">
            <div>
                <h1>
                    <i class="bi bi-receipt" style="color: var(--expense-primary);"></i>
                    <span class="accent">{{ __('ui.expense_accounts') }}</span>
                </h1>
                <p class="subtitle">
                    <i class="bi bi-wallet2 me-1"></i>
                    Manage expense categories and track spending
                </p>
            </div>
        </div>

        {{-- ─── STATS CARDS ─── --}}
        @php
            $currenciesData = $stats['currencies'] ?? [];
        @endphp

        <div class="stats-grid">
            {{-- Total Expenses Card --}}
            <div class="stat-card purple">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="stat-value">{{ number_format($stats['total_expenses'] ?? 0) }}</div>
                </div>
                <div class="stat-label">Total Expense Accounts</div>
            </div>

            {{-- Currency-specific Stats Cards --}}
            @forelse($currenciesData as $currencyStat)
                @php
                    $balance = $currencyStat['balance'] ?? 0;
                    $isPositive = $balance > 0;
                    $isNegative = $balance < 0;
                    $isZero = $balance == 0;
                    $cardClass = $isPositive ? 'green' : ($isNegative ? 'red' : 'blue');
                    $iconClass = $isPositive ? 'green' : ($isNegative ? 'red' : 'blue');
                    $statusClass = $isPositive ? 'positive' : ($isNegative ? 'negative' : 'zero');
                    $statusText = $isPositive ? 'Cr' : ($isNegative ? 'Dr' : 'Zero');
                    $currencySymbol = $currencyStat['currency_symbol'] ?? '$';
                    $currencyCode = $currencyStat['currency_code'] ?? 'USD';
                @endphp
                <div class="stat-card {{ $cardClass }}">
                    <div class="stat-top">
                        <div class="stat-icon {{ $iconClass }}">
                            <span class="currency-symbol-icon">{{ $currencySymbol }}</span>
                        </div>
                        <div class="stat-value">
                            <span class="currency-sm">{{ $currencySymbol }}</span>
                            {{ number_format(abs($balance), 2) }}
                            <span class="balance-status {{ $statusClass }}">
                                {{ $statusText }}
                            </span>
                        </div>
                    </div>
                    <div class="stat-label">
                        {{ $currencyCode }} Balance
                    </div>
                </div>
            @empty
                <div class="stat-card green">
                    <div class="stat-top">
                        <div class="stat-icon green">
                            <span class="currency-symbol-icon">$</span>
                        </div>
                        <div class="stat-value">
                            <span class="currency-sm">$</span>0.00
                            <span class="balance-status zero">{{ __('ui.zero') }}</span>
                        </div>
                    </div>
                    <div class="stat-label">No currency balances</div>
                </div>
            @endforelse
        </div>

        {{-- ─── MAIN CARD ─── --}}
        <div class="main-card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-list-ul" style="color: var(--expense-primary);"></i>
                    Expense Directory
                    <span class="badge-count">{{ $expenses->total() }} expense accounts</span>
                </h5>
                <div class="d-flex gap-2">
                    <span class="badge-count" style="background: #d1fae5; color: #065f46;">
                        <i class="bi bi-circle-fill" style="font-size: 0.4rem;"></i> {{ __('ui.active') }}
                    </span>
                    <span class="badge-count" style="background: #fecaca; color: #991b1b;">
                        <i class="bi bi-circle-fill" style="font-size: 0.4rem;"></i> {{ __('ui.inactive') }}
                    </span>
                </div>
            </div>

            <div class="card-body">
                {{-- ─── FILTER BAR ─── --}}
                <div class="filter-bar">
                    <div class="filter-group">
                        <i class="bi bi-search filter-icon"></i>
                        <input type="text" id="searchInput" placeholder="{{ __('ui.search_expense_accounts') }}"
                            value="{{ request('search') }}">
                    </div>

                    <div class="filter-group" style="flex: 0 1 160px;">
                        <i class="bi bi-currency-dollar filter-icon"></i>
                        <select id="currencyFilter">
                            <option value="">{{ __('ui.all_currencies') }}</option>
                            @if($currencies && $currencies->count() > 0)
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->id }}" {{ request('currency_id') == $currency->id ? 'selected' : '' }}>
                                        {{ $currency->code }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <button class="btn-primary-custom" id="applyFilters">
                        <i class="bi bi-search"></i> Apply
                    </button>

                    @if (request('search') || request('currency_id'))
                        <a href="{{ route('admin.expenses.index') }}" class="btn-outline-custom">
                            <i class="bi bi-arrow-counterclockwise"></i> {{ __('ui.reset') }}
                        </a>
                    @endif

                    @can('create expenses')
                        <button class="btn-primary-custom" style="margin-left: auto;" data-bs-toggle="modal"
                            data-bs-target="#createExpenseModal">
                            <i class="bi bi-plus-circle"></i> New Expense
                        </button>
                    @endcan
                </div>

                {{-- ─── TABLE ─── --}}
                <div class="table-responsive-custom">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('ui.expense') }}</th>
                                <th style="width: 120px;">{{ __('ui.code') }}</th>
                                <th style="width: 140px;">{{ __('ui.type') }}</th>
                                <th style="min-width: 200px;">{{ __('ui.balances') }}</th>
                                <th style="width: 140px;" class="text-end">{{ __('ui.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenses as $expense)
                                <tr>
                                    <td>{{ $loop->iteration + ($expenses->currentPage() - 1) * $expenses->perPage() }}</td>
                                    <td>
                                        <div class="expense-cell">
                                            <div class="avatar">{{ substr($expense->name, 0, 2) }}</div>
                                            <div class="info">
                                                <div class="name">{{ $expense->name }}</div>
                                                <div class="meta">
                                                    <span class="badge-code">{{ $expense->code }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-family: monospace; font-weight: 600; color: var(--expense-primary);">
                                            {{ $expense->code }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge {{ $expense->is_active ? 'active' : 'inactive' }}">
                                            <span class="dot"></span>
                                            {{ $expense->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $balances = $expense->balances ?? collect();
                                        @endphp
                                        @if($balances->count() > 0)
                                            @foreach($balances as $balance)
                                                @php
                                                    $balanceAmount = (float) ($balance['balance'] ?? 0);
                                                    $isPositive = $balanceAmount > 0;
                                                    $isNegative = $balanceAmount < 0;
                                                    $isZero = $balanceAmount == 0;
                                                    $balanceClass = $isPositive ? 'positive' : ($isNegative ? 'negative' : 'zero');
                                                    $symbol = $balance['currency_symbol'] ?? '$';
                                                    $code = $balance['currency_code'] ?? 'USD';
                                                @endphp
                                                <span class="balance-badge {{ $balanceClass }}">
                                                    <span class="balance-symbol">{{ $symbol }}</span>
                                                    {{ number_format(abs($balanceAmount), 2) }}
                                                    <span class="currency-code">{{ $code }}</span>
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="text-muted" style="font-size: 0.75rem;">{{ __('ui.no_transactions') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="action-buttons">
                                            <a href="{{ route('admin.expenses.show', $expense->id) }}"
                                                class="btn-action view" title="{{ __('ui.view_details') }}">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @can('update expenses')
                                                <button class="btn-action edit btn-edit-expense"
                                                    data-id="{{ $expense->id }}" title="{{ __('ui.edit') }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('delete expenses')
                                                <button class="btn-action delete btn-delete-expense"
                                                    data-id="{{ $expense->id }}" data-name="{{ $expense->name }}"
                                                    title="{{ __('ui.delete') }}">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="bi bi-inboxes icon"></i>
                                            <div class="title">No expense accounts found</div>
                                            <div class="subtitle">Create your first expense account by clicking "New
                                                Expense"</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- ─── PAGINATION ─── --}}
                @if ($expenses->hasPages())
                    <div class="pagination-wrap">
                        <span class="info-text">
                            Showing {{ $expenses->firstItem() }} – {{ $expenses->lastItem() }}
                            of {{ $expenses->total() }} expense accounts
                        </span>
                        {{ $expenses->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ─── CREATE EXPENSE MODAL ─── --}}
    <div class="modal fade" id="createExpenseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border: none;">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i> New Expense Account
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="createExpenseForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.expense_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name"
                                    placeholder="{{ __('ui.enter_expense_name') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.expense_code') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"
                                        style="background: var(--expense-gray-50); font-weight: 600; color: var(--expense-primary);">EXP-</span>
                                    <input type="text" class="form-control" name="code" id="create_expense_code"
                                        placeholder="0001" required>
                                    <button class="btn btn-outline-secondary" type="button" id="generateExpenseCodeBtn"
                                        title="{{ __('ui.generate_code') }}">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </div>
                                <small id="create_code_feedback" class="text-muted">Code will be auto-generated with EXP-
                                    prefix</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"
                        style="background: var(--expense-gray-50); border-top: 1px solid var(--expense-gray-200);">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> {{ __('ui.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-danger" id="submitExpenseBtn">
                            <i class="bi bi-check-lg"></i> Create Expense
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── EDIT EXPENSE MODAL ─── --}}
    <div class="modal fade" id="editExpenseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none;">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i> Edit Expense Account
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="editExpenseForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" id="edit_expense_id" name="expense_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.expense_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.expense_code') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"
                                        style="background: var(--expense-gray-50); font-weight: 600; color: var(--expense-primary);">EXP-</span>
                                    <input type="text" class="form-control" id="edit_code" name="code" required>
                                </div>
                                <small id="edit_code_feedback" class="text-muted">Full code will be EXP-XXXX</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"
                        style="background: var(--expense-gray-50); border-top: 1px solid var(--expense-gray-200);">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> {{ __('ui.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-warning" id="updateExpenseBtn">
                            <i class="bi bi-check-lg"></i> Update Expense
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── PREVIEW MODAL ─── --}}
    <div class="modal fade" id="accountPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-file-text me-2"></i> {{ __('ui.account_summary') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" id="accountPreviewContent" style="background: var(--gray-50);"></div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {

            // ─── Show Toasts ───
            const toastElements = document.querySelectorAll('.toast');
            toastElements.forEach(toast => new bootstrap.Toast(toast).show());

            // ─── Generate Expense Code ───
            function generateExpenseCode() {
                var prefix = 'EXP-';
                var random = String(Math.floor(1000 + Math.random() * 9000));
                return prefix + random;
            }

            // ─── Check Code Availability ───
            function checkCodeAvailability(code, feedbackElement, submitBtn) {
                if (!code) {
                    $(feedbackElement).text('Enter a code').removeClass('text-success text-danger').addClass(
                        'text-muted');
                    return;
                }

                var checkCode = code;
                if (!checkCode.startsWith('EXP-')) {
                    checkCode = 'EXP-' + checkCode;
                }

                $.ajax({
                    url: '{{ route('admin.expenses.check-code') }}',
                    method: 'POST',
                    data: {
                        code: checkCode,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.available) {
                            $(feedbackElement).text('✅ Code available: ' + response.code).removeClass(
                                    'text-danger')
                                .addClass('text-success');
                            if (submitBtn) $(submitBtn).prop('disabled', false);
                        } else {
                            $(feedbackElement).text('❌ Code already exists').removeClass('text-success')
                                .addClass('text-danger');
                            if (submitBtn) $(submitBtn).prop('disabled', true);
                        }
                    },
                    error: function() {
                        $(feedbackElement).text('⚠️ Error checking code').removeClass(
                                'text-success text-danger')
                            .addClass('text-warning');
                    }
                });
            }

            // ─── Generate Code Button ───
            $('#generateExpenseCodeBtn').on('click', function() {
                var code = generateExpenseCode();
                var suffix = code.replace('EXP-', '');
                $('#create_expense_code').val(suffix);
                $('#create_code_feedback').text('Generated: ' + code).removeClass('text-muted text-danger')
                    .addClass('text-success');
                checkCodeAvailability(code, '#create_code_feedback', '#submitExpenseBtn');
            });

            // ─── Create Code Input Change ───
            $('#create_expense_code').on('input', function() {
                var val = $(this).val().trim();
                var fullCode = 'EXP-' + val;
                if (val) {
                    checkCodeAvailability(fullCode, '#create_code_feedback', '#submitExpenseBtn');
                } else {
                    $('#create_code_feedback').text('Enter a code suffix').removeClass(
                            'text-success text-danger')
                        .addClass('text-muted');
                    $('#submitExpenseBtn').prop('disabled', false);
                }
            });

            // ─── Edit Code Input Change ───
            $('#edit_code').on('input', function() {
                var val = $(this).val().trim();
                var fullCode = 'EXP-' + val;
                if (val) {
                    checkCodeAvailability(fullCode, '#edit_code_feedback', '#updateExpenseBtn');
                } else {
                    $('#edit_code_feedback').text('Enter a code suffix').removeClass(
                            'text-success text-danger')
                        .addClass('text-muted');
                    $('#updateExpenseBtn').prop('disabled', false);
                }
            });

            // ─── Auto-generate code on modal open ───
            $('#createExpenseModal').on('shown.bs.modal', function() {
                if (!$('#create_expense_code').val()) {
                    setTimeout(function() {
                        $('#generateExpenseCodeBtn').click();
                    }, 300);
                }
            });

            // ─── Apply Filters ───
            $('#applyFilters').on('click', function() {
                var search = $('#searchInput').val();
                var currency = $('#currencyFilter').val();
                var url = '{{ route('admin.expenses.index') }}';
                var params = [];

                if (search) params.push('search=' + encodeURIComponent(search));
                if (currency) params.push('currency_id=' + encodeURIComponent(currency));

                if (params.length > 0) {
                    window.location.href = url + '?' + params.join('&');
                } else {
                    window.location.href = url;
                }
            });

            // Enter key for search
            $('#searchInput').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#applyFilters').click();
                }
            });

            // ─── Create Expense ───
            $('#createExpenseForm').on('submit', function(e) {
                e.preventDefault();

                var $btn = $('#submitExpenseBtn');
                var codeSuffix = $('#create_expense_code').val().trim();

                if (!codeSuffix) {
                    Swal.fire('Error', 'Please enter or generate an expense code.', 'error');
                    return;
                }

                var fullCode = 'EXP-' + codeSuffix;

                var isCodeAvailable = $('#create_code_feedback').hasClass('text-success');
                if (!isCodeAvailable) {
                    Swal.fire('Error', 'Please use a valid and available expense code.', 'error');
                    return;
                }

                var formData = {
                    name: $('input[name="name"]').val(),
                    code: fullCode,
                    _token: '{{ csrf_token() }}'
                };

                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span> Creating...');

                $.ajax({
                    url: '{{ route('admin.expenses.store') }}',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                            $btn.prop('disabled', false).html(
                                '<i class="bi bi-check-lg"></i> Create Expense');
                        }
                    },
                    error: function(xhr) {
                        var errors = xhr.responseJSON?.errors;
                        if (errors) {
                            var msg = '';
                            $.each(errors, function(key, val) {
                                msg += val[0] + '\n';
                            });
                            Swal.fire('Validation Error', msg, 'error');
                        } else {
                            Swal.fire('Error', xhr.responseJSON?.message ||
                                'Something went wrong', 'error');
                        }
                        $btn.prop('disabled', false).html(
                            '<i class="bi bi-check-lg"></i> Create Expense');
                    }
                });
            });

            // ─── Edit Expense ───
            $(document).on('click', '.btn-edit-expense', function() {
                var id = $(this).data('id');
                $.get('/admin/expenses/' + id + '/edit', function(data) {
                    $('#edit_expense_id').val(data.id);
                    $('#edit_name').val(data.name);
                    var code = data.code || '';
                    if (code.startsWith('EXP-')) {
                        code = code.substring(4);
                    }
                    $('#edit_code').val(code);
                    $('#edit_code_feedback').text('Editing: EXP-' + code).removeClass(
                            'text-muted text-danger')
                        .addClass('text-success');
                    $('#editExpenseModal').modal('show');
                });
            });

            // ─── Update Expense ───
            $('#editExpenseForm').on('submit', function(e) {
                e.preventDefault();

                var id = $('#edit_expense_id').val();
                var $btn = $('#updateExpenseBtn');
                var codeSuffix = $('#edit_code').val().trim();

                if (!codeSuffix) {
                    Swal.fire('Error', 'Please enter a valid expense code.', 'error');
                    return;
                }

                var fullCode = 'EXP-' + codeSuffix;

                var formData = {
                    name: $('#edit_name').val(),
                    code: fullCode,
                    _token: '{{ csrf_token() }}',
                    _method: 'PUT'
                };

                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span> Updating...');

                $.ajax({
                    url: '/admin/expenses/' + id,
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                            $btn.prop('disabled', false).html(
                                '<i class="bi bi-check-lg"></i> Update Expense');
                        }
                    },
                    error: function(xhr) {
                        var errors = xhr.responseJSON?.errors;
                        if (errors) {
                            var msg = '';
                            $.each(errors, function(key, val) {
                                msg += val[0] + '\n';
                            });
                            Swal.fire('Validation Error', msg, 'error');
                        } else {
                            Swal.fire('Error', xhr.responseJSON?.message ||
                                'Something went wrong', 'error');
                        }
                        $btn.prop('disabled', false).html(
                            '<i class="bi bi-check-lg"></i> Update Expense');
                    }
                });
            });

            // ─── Delete Expense ───
            $(document).on('click', '.btn-delete-expense', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');

                Swal.fire({
                    title: 'Delete Expense Account?',
                    text: 'Are you sure you want to delete "' + name +
                        '"? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/admin/expenses/' + id,
                            method: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    }).then(function() {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', response.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to delete expense account.',
                                    'error');
                            }
                        });
                    }
                });
            });

            // ─── Preview Expense ───
            $(document).on('click', '.btn-preview-expense', function() {
                var id = $(this).data('id');
                $('#accountPreviewContent').html(
                    '<div class="d-flex justify-content-center align-items-center" style="height: 200px;"><div class="spinner-border text-primary" role="status"></div></div>'
                );
                $('#accountPreviewModal').modal('show');
                $.get('/admin/expenses/' + id + '/print', function(data) {
                    $('#accountPreviewContent').html(data);
                });
            });

            // ─── Reset modals on close ───
            $('#createExpenseModal').on('hidden.bs.modal', function() {
                $('#createExpenseForm')[0].reset();
                $('#create_code_feedback').text('Code will be auto-generated with EXP- prefix').removeClass(
                    'text-success text-danger').addClass('text-muted');
                $('#submitExpenseBtn').prop('disabled', false).html(
                    '<i class="bi bi-check-lg"></i> Create Expense');
            });

            $('#editExpenseModal').on('hidden.bs.modal', function() {
                $('#editExpenseForm')[0].reset();
                $('#edit_code_feedback').text('Full code will be EXP-XXXX').removeClass(
                    'text-muted text-danger').addClass('text-muted');
                $('#updateExpenseBtn').prop('disabled', false).html(
                    '<i class="bi bi-check-lg"></i> Update Expense');
            });

        });
    </script>
@endsection