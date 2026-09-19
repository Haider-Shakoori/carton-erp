{{-- resources/views/admin/sarafs/index.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Sarafs')

@section('css')
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}"
        rel="stylesheet">
    <style>
        :root {
            --saraf-primary: #8b5cf6;
            --saraf-primary-dark: #7c3aed;
            --saraf-success: #10b981;
            --saraf-danger: #ef4444;
            --saraf-warning: #f59e0b;
            --saraf-gray-50: #f8fafc;
            --saraf-gray-100: #f1f5f9;
            --saraf-gray-200: #e2e8f0;
            --saraf-gray-300: #cbd5e1;
            --saraf-gray-400: #94a3b8;
            --saraf-gray-500: #64748b;
            --saraf-gray-600: #475569;
            --saraf-gray-700: #334155;
            --saraf-gray-800: #1e293b;
            --saraf-gray-900: #0f172a;
            --saraf-radius: 16px;
            --saraf-radius-sm: 10px;
            --saraf-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 8px 32px rgba(0, 0, 0, 0.04);
            --saraf-shadow-hover: 0 1px 3px rgba(0, 0, 0, 0.02), 0 12px 48px rgba(0, 0, 0, 0.08);
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
            color: var(--saraf-gray-900);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-header h1 .accent {
            background: linear-gradient(135deg, var(--saraf-primary), #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-header .subtitle {
            color: var(--saraf-gray-500);
            font-size: 0.875rem;
            margin: 0.25rem 0 0 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            background: white;
            border-radius: var(--saraf-radius);
            padding: 1.25rem 1.5rem;
            box-shadow: var(--saraf-shadow);
            border: 1px solid var(--saraf-gray-200);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            box-shadow: var(--saraf-shadow-hover);
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
            background: linear-gradient(90deg, var(--saraf-primary), #a78bfa);
        }

        .stat-card.green::before {
            background: linear-gradient(90deg, #10b981, #34d399);
        }

        .stat-card.red::before {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }

        .stat-card.blue::before {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
        }

        .stat-card .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-card .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--saraf-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .stat-card .stat-icon.purple {
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            color: #7c3aed;
        }

        .stat-card .stat-icon.green {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #059669;
        }

        .stat-card .stat-icon.red {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
            color: #dc2626;
        }

        .stat-card .stat-icon.blue {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #2563eb;
        }

        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--saraf-gray-900);
        }

        .stat-card .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--saraf-gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 0.25rem;
        }

        .stat-card .stat-value .currency-sm {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--saraf-gray-400);
        }

        .main-card {
            background: white;
            border-radius: var(--saraf-radius);
            box-shadow: var(--saraf-shadow);
            border: 1px solid var(--saraf-gray-200);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .main-card:hover {
            box-shadow: var(--saraf-shadow-hover);
        }

        .main-card .card-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #fafafa, #ffffff);
            border-bottom: 1px solid var(--saraf-gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .main-card .card-header h5 {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--saraf-gray-800);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .main-card .card-header .badge-count {
            background: var(--saraf-gray-100);
            color: var(--saraf-gray-600);
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
            border: 1.5px solid var(--saraf-gray-200);
            border-radius: var(--saraf-radius-sm);
            padding: 0.15rem 0.15rem 0.15rem 0.75rem;
            transition: all 0.3s ease;
            flex: 1 1 180px;
        }

        .filter-bar .filter-group:focus-within {
            border-color: var(--saraf-primary);
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.08);
        }

        .filter-bar .filter-group .filter-icon {
            color: var(--saraf-gray-400);
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
            color: var(--saraf-gray-700);
        }

        .filter-bar .filter-group input::placeholder {
            color: var(--saraf-gray-400);
        }

        .filter-bar .btn-primary-custom {
            background: linear-gradient(135deg, var(--saraf-primary), var(--saraf-primary-dark));
            color: white;
            border: none;
            padding: 0.45rem 1.25rem;
            border-radius: var(--saraf-radius-sm);
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
            box-shadow: 0 4px 16px rgba(139, 92, 246, 0.3);
            color: white;
        }

        .filter-bar .btn-outline-custom {
            background: white;
            color: var(--saraf-gray-600);
            border: 1.5px solid var(--saraf-gray-200);
            padding: 0.45rem 1.25rem;
            border-radius: var(--saraf-radius-sm);
            font-weight: 600;
            font-size: 0.8125rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .filter-bar .btn-outline-custom:hover {
            background: var(--saraf-gray-50);
            border-color: var(--saraf-gray-300);
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
            color: var(--saraf-gray-600);
            font-weight: 700;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 2px solid var(--saraf-gray-200);
            white-space: nowrap;
            text-align: left;
        }

        .table-custom tbody td {
            padding: 0.625rem 0.75rem;
            border-bottom: 1px solid var(--saraf-gray-100);
            vertical-align: middle;
        }

        .table-custom tbody tr {
            transition: background 0.3s ease;
        }

        .table-custom tbody tr:hover {
            background: rgba(139, 92, 246, 0.02);
        }

        .saraf-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .saraf-cell .avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--saraf-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
            color: white;
            flex-shrink: 0;
            background: linear-gradient(135deg, var(--saraf-primary), #7c3aed);
        }

        .saraf-cell .info .name {
            font-weight: 600;
            color: var(--saraf-gray-800);
            font-size: 0.875rem;
        }

        .saraf-cell .info .meta {
            font-size: 0.6875rem;
            color: var(--saraf-gray-400);
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .saraf-cell .info .meta .badge-code {
            background: var(--saraf-gray-100);
            padding: 0.05rem 0.4rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--saraf-primary);
        }

        .balance-value {
            font-weight: 700;
            font-size: 0.875rem;
        }

        .balance-value.positive {
            color: var(--saraf-success);
        }

        .balance-value.negative {
            color: var(--saraf-danger);
        }

        .balance-value .currency-sm {
            font-size: 0.6rem;
            font-weight: 500;
            color: var(--saraf-gray-400);
        }

        .contact-details {
            font-size: 0.75rem;
            color: var(--saraf-gray-500);
        }

        .contact-details .contact-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-bottom: 0.1rem;
        }

        .contact-details .contact-item i {
            font-size: 0.65rem;
            color: var(--saraf-gray-400);
            width: 14px;
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
            border-radius: var(--saraf-radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            color: var(--saraf-gray-400);
            cursor: pointer;
        }

        .action-buttons .btn-action:hover {
            background: var(--saraf-gray-100);
        }

        .action-buttons .btn-action.view:hover {
            color: var(--saraf-primary);
        }

        .action-buttons .btn-action.edit:hover {
            color: var(--saraf-warning);
        }

        .action-buttons .btn-action.delete:hover {
            color: var(--saraf-danger);
            background: #fecaca;
        }

        .action-buttons .btn-action.whatsapp:hover {
            color: #25D366;
            background: #d1fae5;
        }

        .pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid var(--saraf-gray-200);
            margin-top: 1rem;
        }

        .pagination-wrap .info-text {
            font-size: 0.75rem;
            color: var(--saraf-gray-400);
        }

        .pagination-wrap .pagination {
            margin: 0;
        }

        .pagination-wrap .pagination .page-link {
            padding: 0.35rem 0.75rem;
            font-size: 0.8125rem;
            border-radius: var(--saraf-radius-sm);
            border: 1px solid var(--saraf-gray-200);
            color: var(--saraf-gray-600);
            transition: all 0.3s ease;
        }

        .pagination-wrap .pagination .page-link:hover {
            background: var(--saraf-gray-50);
            border-color: var(--saraf-primary);
            color: var(--saraf-primary);
        }

        .pagination-wrap .pagination .active .page-link {
            background: linear-gradient(135deg, var(--saraf-primary), var(--saraf-primary-dark));
            border-color: var(--saraf-primary);
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
            color: var(--saraf-gray-300);
            margin-bottom: 0.75rem;
            display: block;
        }

        .empty-state .title {
            font-weight: 600;
            color: var(--saraf-gray-700);
            font-size: 0.9375rem;
        }

        .empty-state .subtitle {
            color: var(--saraf-gray-400);
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
            border-radius: var(--saraf-radius-sm);
            box-shadow: var(--saraf-shadow);
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

    <div class="container-fluid px-3 px-md-4">

        {{-- ─── PAGE HEADER ─── --}}
        <div class="page-header">
            <div>
                <h1>
                    <i class="bi bi-currency-exchange" style="color: var(--saraf-primary);"></i>
                    <span class="accent">Sarafs</span>
                </h1>
                <p class="subtitle">
                    <i class="bi bi-bank me-1"></i>
                    Manage sarafs, track balances, and monitor transactions
                </p>
            </div>
        </div>

        {{-- ─── STATS CARDS ─── --}}
        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-bank"></i>
                    </div>
                    <div class="stat-value" id="total_sarafs">{{ number_format($stats['total_sarafs'] ?? 0) }}</div>
                </div>
                <div class="stat-label">Total Sarafs</div>
            </div>

            <div class="stat-card green">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-arrow-up-circle"></i>
                    </div>
                    <div class="stat-value" id="total_credit">
                        <span class="currency-sm">$</span>{{ number_format($stats['total_credit'] ?? 0, 2) }}
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.total_credit') }}</div>
            </div>

            <div class="stat-card red">
                <div class="stat-top">
                    <div class="stat-icon red">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                    <div class="stat-value" id="total_debit">
                        <span class="currency-sm">$</span>{{ number_format($stats['total_debit'] ?? 0, 2) }}
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.total_debit') }}</div>
            </div>

            <div class="stat-card blue">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="stat-value" id="balance_value"
                        style="color: {{ ($stats['balance'] ?? 0) >= 0 ? 'var(--saraf-success)' : 'var(--saraf-danger)' }};">
                        <span class="currency-sm">$</span>{{ number_format(abs($stats['balance'] ?? 0), 2) }}
                    </div>
                </div>
                <div class="stat-label">{{ ($stats['balance'] ?? 0) >= 0 ? 'Credit Balance' : 'Debit Balance' }}</div>
            </div>
        </div>

        {{-- ─── MAIN CARD ─── --}}
        <div class="main-card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-list-ul" style="color: var(--saraf-primary);"></i>
                    Saraf Directory
                    <span class="badge-count">{{ $sarafs->total() }} sarafs</span>
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
                        <input type="text" id="searchInput" placeholder="{{ __('ui.search_sarafs') }}"
                            value="{{ request('search') }}">
                    </div>

                    <div class="filter-group" style="flex: 0 1 160px;">
                        <i class="bi bi-currency-dollar filter-icon"></i>
                        <select id="currencyFilter">
                            <option value="">{{ __('ui.all_currencies') }}</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}"
                                    {{ request('currency_id') == $currency->id ? 'selected' : '' }}>
                                    {{ $currency->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button class="btn-primary-custom" id="applyFilters">
                        <i class="bi bi-search"></i> Apply
                    </button>

                    @if (request('search') || request('currency_id'))
                        <a href="{{ route('admin.sarafs.index') }}" class="btn-outline-custom">
                            <i class="bi bi-arrow-counterclockwise"></i> {{ __('ui.reset') }}
                        </a>
                    @endif

                    @can('create sarafs')
                        <button class="btn-primary-custom" style="margin-left: auto;" data-bs-toggle="modal"
                            data-bs-target="#createSarafModal">
                            <i class="bi bi-plus-circle"></i> New Saraf
                        </button>
                    @endcan
                </div>

                {{-- ─── TABLE ─── --}}
                <div class="table-responsive-custom">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('ui.saraf') }}</th>
                                <th style="width: 120px;">{{ __('ui.code') }}</th>
                                <th style="width: 140px;">{{ __('ui.type') }}</th>
                                <th>{{ __('ui.contact') }}</th>
                                <th style="width: 140px;" class="text-end">{{ __('ui.balance') }}</th>
                                <th style="width: 140px;" class="text-end">{{ __('ui.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sarafs as $saraf)
                                @php
                                    $balance = $saraf->balance ?? 0;
                                    $currencySymbol = '$';
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration + ($sarafs->currentPage() - 1) * $sarafs->perPage() }}</td>
                                    <td>
                                        <div class="saraf-cell">
                                            <div class="avatar">{{ substr($saraf->name, 0, 2) }}</div>
                                            <div class="info">
                                                <div class="name">{{ $saraf->name }}</div>
                                                <div class="meta">
                                                    @if ($saraf->company)
                                                        <span><i class="bi bi-building"></i> {{ $saraf->company }}</span>
                                                    @endif
                                                    @if ($saraf->email)
                                                        <span><i class="bi bi-envelope"></i> {{ $saraf->email }}</span>
                                                    @endif
                                                    <span class="badge-code">{{ $saraf->code }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            style="font-family: monospace; font-weight: 600; color: var(--saraf-primary);">
                                            {{ $saraf->code }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge {{ $saraf->is_active ? 'active' : 'inactive' }}">
                                            <span class="dot"></span>
                                            {{ $saraf->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="contact-details">
                                            @if ($saraf->contact)
                                                <div class="contact-item">
                                                    <i class="bi bi-phone"></i>
                                                    <span>{{ $saraf->contact }}</span>
                                                </div>
                                            @endif
                                            @if ($saraf->whatsapp)
                                                <div class="contact-item">
                                                    <i class="bi bi-whatsapp" style="color: #25D366;"></i>
                                                    <span>{{ $saraf->whatsapp }}</span>
                                                </div>
                                            @endif
                                            @if (!$saraf->contact && !$saraf->whatsapp)
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="balance-value {{ $balance >= 0 ? 'positive' : 'negative' }}">
                                            <span class="currency-sm">{{ $currencySymbol }}</span>
                                            {{ number_format(abs($balance), 2) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="action-buttons">
                                            <a href="{{ route('admin.sarafs.show', $saraf->id) }}"
                                                class="btn-action view" title="{{ __('ui.view_details') }}">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @can('update sarafs')
                                                <button class="btn-action edit btn-edit-saraf" data-id="{{ $saraf->id }}"
                                                    title="{{ __('ui.edit') }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('delete sarafs')
                                                <button class="btn-action delete btn-delete-saraf"
                                                    data-id="{{ $saraf->id }}" data-name="{{ $saraf->name }}"
                                                    title="{{ __('ui.delete') }}">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            @endcan
                                            @if ($saraf->whatsapp)
                                                <button class="btn-action whatsapp btn-send-whatsapp"
                                                    data-url="{{ route('admin.sarafs.send-whatsapp', $saraf->id) }}"
                                                    title="{{ __('ui.send_whatsapp') }}">
                                                    <i class="bi bi-whatsapp"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="bi bi-inboxes icon"></i>
                                            <div class="title">No sarafs found</div>
                                            <div class="subtitle">Create your first saraf by clicking "New Saraf"</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- ─── PAGINATION ─── --}}
                @if ($sarafs->hasPages())
                    <div class="pagination-wrap">
                        <span class="info-text">
                            Showing {{ $sarafs->firstItem() }} – {{ $sarafs->lastItem() }}
                            of {{ $sarafs->total() }} sarafs
                        </span>
                        {{ $sarafs->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ─── CREATE SARAF MODAL ─── --}}
    <div class="modal fade" id="createSarafModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border: none;">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i> New Saraf
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="createSarafForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.saraf_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" placeholder="Enter saraf name"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.saraf_code') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"
                                        style="background: var(--saraf-gray-50); font-weight: 600; color: var(--saraf-primary);">SAR-</span>
                                    <input type="text" class="form-control" name="code" id="create_saraf_code"
                                        placeholder="0001" required>
                                    <button class="btn btn-outline-secondary" type="button" id="generateSarafCodeBtn"
                                        title="{{ __('ui.generate_code') }}">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </div>
                                <small id="create_code_feedback" class="text-muted">Code will be auto-generated with SAR-
                                    prefix</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.contact') }}</label>
                                <input type="text" class="form-control" name="contact" placeholder="+93 700 000 000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.whatsapp') }}</label>
                                <input type="text" class="form-control" name="whatsapp"
                                    placeholder="+93 700 000 000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.email') }}</label>
                                <input type="email" class="form-control" name="email"
                                    placeholder="saraf@example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.company') }}</label>
                                <input type="text" class="form-control" name="company" placeholder="Company name">
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('ui.address') }}</label>
                                <textarea class="form-control" name="address" rows="2" placeholder="{{ __('ui.enter_address') }}"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('ui.notes') }}</label>
                                <textarea class="form-control" name="notes" rows="2" placeholder="{{ __('ui.additional_notes') }}"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"
                        style="background: var(--saraf-gray-50); border-top: 1px solid var(--saraf-gray-200);">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> {{ __('ui.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitSarafBtn">
                            <i class="bi bi-check-lg"></i> Create Saraf
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── EDIT SARAF MODAL ─── --}}
    <div class="modal fade" id="editSarafModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"
                    style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none;">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i> Edit Saraf
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="editSarafForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" id="edit_saraf_id" name="saraf_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.saraf_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.saraf_code') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"
                                        style="background: var(--saraf-gray-50); font-weight: 600; color: var(--saraf-primary);">SAR-</span>
                                    <input type="text" class="form-control" id="edit_code" name="code" required>
                                </div>
                                <small id="edit_code_feedback" class="text-muted">Full code will be SAR-XXXX</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.contact') }}</label>
                                <input type="text" class="form-control" id="edit_contact" name="contact">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.whatsapp') }}</label>
                                <input type="text" class="form-control" id="edit_whatsapp" name="whatsapp">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.email') }}</label>
                                <input type="email" class="form-control" id="edit_email" name="email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.company') }}</label>
                                <input type="text" class="form-control" id="edit_company" name="company">
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('ui.address') }}</label>
                                <textarea class="form-control" id="edit_address" name="address" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('ui.notes') }}</label>
                                <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"
                        style="background: var(--saraf-gray-50); border-top: 1px solid var(--saraf-gray-200);">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> {{ __('ui.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-warning" id="updateSarafBtn">
                            <i class="bi bi-check-lg"></i> Update Saraf
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

            // ─── Generate Saraf Code ───
            function generateSarafCode() {
                var prefix = 'SAR-';
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
                if (!checkCode.startsWith('SAR-')) {
                    checkCode = 'SAR-' + checkCode;
                }

                $.ajax({
                    url: '{{ route('admin.sarafs.check-code') }}',
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
            $('#generateSarafCodeBtn').on('click', function() {
                var code = generateSarafCode();
                var suffix = code.replace('SAR-', '');
                $('#create_saraf_code').val(suffix);
                $('#create_code_feedback').text('Generated: ' + code).removeClass('text-muted text-danger')
                    .addClass('text-success');
                checkCodeAvailability(code, '#create_code_feedback', '#submitSarafBtn');
            });

            // ─── Create Code Input Change ───
            $('#create_saraf_code').on('input', function() {
                var val = $(this).val().trim();
                var fullCode = 'SAR-' + val;
                if (val) {
                    checkCodeAvailability(fullCode, '#create_code_feedback', '#submitSarafBtn');
                } else {
                    $('#create_code_feedback').text('Enter a code suffix').removeClass(
                            'text-success text-danger')
                        .addClass('text-muted');
                    $('#submitSarafBtn').prop('disabled', false);
                }
            });

            // ─── Edit Code Input Change ───
            $('#edit_code').on('input', function() {
                var val = $(this).val().trim();
                var fullCode = 'SAR-' + val;
                if (val) {
                    checkCodeAvailability(fullCode, '#edit_code_feedback', '#updateSarafBtn');
                } else {
                    $('#edit_code_feedback').text('Enter a code suffix').removeClass(
                            'text-success text-danger')
                        .addClass('text-muted');
                    $('#updateSarafBtn').prop('disabled', false);
                }
            });

            // ─── Auto-generate code on modal open ───
            $('#createSarafModal').on('shown.bs.modal', function() {
                if (!$('#create_saraf_code').val()) {
                    setTimeout(function() {
                        $('#generateSarafCodeBtn').click();
                    }, 300);
                }
            });

            // ─── Apply Filters ───
            $('#applyFilters').on('click', function() {
                var search = $('#searchInput').val();
                var currency = $('#currencyFilter').val();
                var url = '{{ route('admin.sarafs.index') }}';
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

            // ─── Create Saraf ───
            $('#createSarafForm').on('submit', function(e) {
                e.preventDefault();

                var $btn = $('#submitSarafBtn');
                var codeSuffix = $('#create_saraf_code').val().trim();

                if (!codeSuffix) {
                    Swal.fire('Error', 'Please enter or generate a saraf code.', 'error');
                    return;
                }

                var fullCode = 'SAR-' + codeSuffix;

                var isCodeAvailable = $('#create_code_feedback').hasClass('text-success');
                if (!isCodeAvailable) {
                    Swal.fire('Error', 'Please use a valid and available saraf code.', 'error');
                    return;
                }

                var formData = {
                    name: $('input[name="name"]').val(),
                    code: fullCode,
                    contact: $('input[name="contact"]').val(),
                    email: $('input[name="email"]').val(),
                    whatsapp: $('input[name="whatsapp"]').val(),
                    company: $('input[name="company"]').val(),
                    address: $('textarea[name="address"]').val(),
                    notes: $('textarea[name="notes"]').val(),
                    _token: '{{ csrf_token() }}'
                };

                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span> Creating...');

                $.ajax({
                    url: '{{ route('admin.sarafs.store') }}',
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
                                '<i class="bi bi-check-lg"></i> Create Saraf');
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
                            '<i class="bi bi-check-lg"></i> Create Saraf');
                    }
                });
            });

            // ─── Edit Saraf ───
            $(document).on('click', '.btn-edit-saraf', function() {
                var id = $(this).data('id');
                $.get('/admin/sarafs/' + id + '/edit', function(data) {
                    $('#edit_saraf_id').val(data.id);
                    $('#edit_name').val(data.name);
                    var code = data.code || '';
                    if (code.startsWith('SAR-')) {
                        code = code.substring(4);
                    }
                    $('#edit_code').val(code);
                    $('#edit_contact').val(data.contact || '');
                    $('#edit_whatsapp').val(data.whatsapp || '');
                    $('#edit_email').val(data.email || '');
                    $('#edit_company').val(data.company || '');
                    $('#edit_address').val(data.address || '');
                    $('#edit_notes').val(data.notes || '');
                    $('#edit_code_feedback').text('Editing: SAR-' + code).removeClass(
                            'text-muted text-danger')
                        .addClass('text-success');
                    $('#editSarafModal').modal('show');
                });
            });

            // ─── Update Saraf ───
            $('#editSarafForm').on('submit', function(e) {
                e.preventDefault();

                var id = $('#edit_saraf_id').val();
                var $btn = $('#updateSarafBtn');
                var codeSuffix = $('#edit_code').val().trim();

                if (!codeSuffix) {
                    Swal.fire('Error', 'Please enter a valid saraf code.', 'error');
                    return;
                }

                var fullCode = 'SAR-' + codeSuffix;

                var formData = {
                    name: $('#edit_name').val(),
                    code: fullCode,
                    contact: $('#edit_contact').val(),
                    email: $('#edit_email').val(),
                    whatsapp: $('#edit_whatsapp').val(),
                    company: $('#edit_company').val(),
                    address: $('#edit_address').val(),
                    notes: $('#edit_notes').val(),
                    _token: '{{ csrf_token() }}',
                    _method: 'PUT'
                };

                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span> Updating...');

                $.ajax({
                    url: '/admin/sarafs/' + id,
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
                                '<i class="bi bi-check-lg"></i> Update Saraf');
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
                            '<i class="bi bi-check-lg"></i> Update Saraf');
                    }
                });
            });

            // ─── Delete Saraf ───
            $(document).on('click', '.btn-delete-saraf', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');

                Swal.fire({
                    title: 'Delete Saraf?',
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
                            url: '/admin/sarafs/' + id,
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
                                Swal.fire('Error', 'Failed to delete saraf.', 'error');
                            }
                        });
                    }
                });
            });

            // ─── Preview Saraf ───
            $(document).on('click', '.btn-preview-saraf', function() {
                var id = $(this).data('id');
                $('#accountPreviewContent').html(
                    '<div class="d-flex justify-content-center align-items-center" style="height: 200px;"><div class="spinner-border text-primary" role="status"></div></div>'
                );
                $('#accountPreviewModal').modal('show');
                $.get('/admin/sarafs/' + id + '/print', function(data) {
                    $('#accountPreviewContent').html(data);
                });
            });

            // ─── Send WhatsApp ───
            $(document).on('click', '.btn-send-whatsapp', function() {
                var url = $(this).data('url');
                Swal.fire({
                    title: 'Send WhatsApp?',
                    text: 'Send a message to this saraf via WhatsApp?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, send it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.get(url, function(response) {
                            Swal.fire('Success!', response.message ||
                                'Message sent successfully.', 'success');
                        }).fail(function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.error ||
                                'Failed to send message.', 'error');
                        });
                    }
                });
            });

            // ─── Reset modals on close ───
            $('#createSarafModal').on('hidden.bs.modal', function() {
                $('#createSarafForm')[0].reset();
                $('#create_code_feedback').text('Code will be auto-generated with SAR- prefix').removeClass(
                    'text-success text-danger').addClass('text-muted');
                $('#submitSarafBtn').prop('disabled', false).html(
                    '<i class="bi bi-check-lg"></i> Create Saraf');
            });

            $('#editSarafModal').on('hidden.bs.modal', function() {
                $('#editSarafForm')[0].reset();
                $('#edit_code_feedback').text('Full code will be SAR-XXXX').removeClass(
                    'text-success text-danger').addClass('text-muted');
                $('#updateSarafBtn').prop('disabled', false).html(
                    '<i class="bi bi-check-lg"></i> Update Saraf');
            });

        });
    </script>
@endsection
