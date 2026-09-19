{{-- resources/views/admin/customers/show.blade.php --}}
@extends('layouts.admin.base')

@section('title', ($customer->name ?? 'Customer') . ' - Customer Profile')

@section('css')
    <style>
        :root {
            --profile-primary: #1a56db;
            --profile-primary-dark: #1e3a5f;
            --profile-danger: #991b1b;
            --profile-gray-50: #f8fafc;
            --profile-gray-100: #f1f5f9;
            --profile-gray-200: #e2e8f0;
            --profile-gray-300: #cbd5e1;
            --profile-gray-400: #94a3b8;
            --profile-gray-500: #64748b;
            --profile-gray-600: #475569;
            --profile-gray-700: #334155;
            --profile-gray-800: #1e293b;
            --profile-gray-900: #0f172a;
            --profile-radius: 12px;
            --profile-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            --profile-shadow-hover: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            background: white;
            border-radius: var(--profile-radius);
            padding: 1.5rem 2rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--profile-gray-200);
            box-shadow: var(--profile-shadow);
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--profile-primary), #3b82f6);
        }

        .profile-header .profile-top {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .profile-header .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            color: white;
            background: linear-gradient(135deg, var(--profile-primary), #3b82f6);
            flex-shrink: 0;
        }

        .profile-header .profile-info {
            flex: 1;
        }

        .profile-header .profile-info .name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--profile-gray-900);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .profile-header .profile-info .name .badge-code {
            font-size: 0.7rem;
            font-weight: 600;
            font-family: monospace;
            background: var(--profile-gray-100);
            color: var(--profile-primary);
            padding: 0.2rem 0.75rem;
            border-radius: 20px;
            border: 1px solid var(--profile-gray-200);
        }

        .profile-header .profile-info .name .status-badge {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.2rem 0.75rem;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .profile-header .profile-info .name .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }

        .profile-header .profile-info .name .status-badge.inactive {
            background: #fecaca;
            color: #991b1b;
        }

        .profile-header .profile-info .details {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 0.5rem;
        }

        .profile-header .profile-info .details .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            color: var(--profile-gray-600);
        }

        .profile-header .profile-info .details .detail-item i {
            color: var(--profile-gray-400);
            font-size: 0.9rem;
            width: 18px;
        }

        .profile-header .profile-info .details .detail-item .label {
            font-weight: 500;
            color: var(--profile-gray-500);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .profile-header .profile-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .profile-header .profile-actions .btn-action {
            padding: 0.4rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8125rem;
            border: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .profile-header .profile-actions .btn-action.btn-primary {
            background: var(--profile-primary);
            color: white;
        }

        .profile-header .profile-actions .btn-action.btn-primary:hover {
            background: var(--profile-primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(26, 86, 219, 0.3);
        }

        .profile-header .profile-actions .btn-action.btn-outline {
            background: white;
            color: var(--profile-gray-600);
            border: 1.5px solid var(--profile-gray-200);
        }

        .profile-header .profile-actions .btn-action.btn-outline:hover {
            background: var(--profile-gray-50);
            border-color: var(--profile-gray-300);
        }

        .profile-header .profile-actions .btn-action.btn-whatsapp {
            background: #25D366;
            color: white;
        }

        .profile-header .profile-actions .btn-action.btn-whatsapp:hover {
            background: #128C7E;
            transform: translateY(-2px);
        }

        .profile-header .profile-actions .btn-action.btn-danger {
            background: #dc3545;
            color: white;
        }

        .profile-header .profile-actions .btn-action.btn-danger:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }

        .profile-header .profile-actions .btn-action.btn-success {
            background: #059669;
            color: white;
        }

        .profile-header .profile-actions .btn-action.btn-success:hover {
            background: #047857;
            transform: translateY(-2px);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--profile-radius);
            padding: 1rem 1.25rem;
            border: 1px solid var(--profile-gray-200);
            box-shadow: var(--profile-shadow);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            box-shadow: var(--profile-shadow-hover);
            transform: translateY(-2px);
        }

        .stat-card .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--profile-gray-900);
        }

        .stat-card .stat-value .currency-sm {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--profile-gray-400);
        }

        .stat-card .stat-label {
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--profile-gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 0.1rem;
        }

        .stat-card .stat-icon {
            font-size: 1.1rem;
            margin-bottom: 0.2rem;
            display: block;
        }

        .stat-card .stat-icon.blue {
            color: var(--profile-primary);
        }

        .stat-card .stat-icon.green {
            color: #10b981;
        }

        .stat-card .stat-icon.red {
            color: #ef4444;
        }

        .stat-card .stat-icon.orange {
            color: #f59e0b;
        }

        .stat-card .stat-icon.purple {
            color: #8b5cf6;
        }

        /* Balance Cards */
        .balance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .balance-card {
            background: white;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid var(--profile-gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .balance-card:hover {
            border-color: var(--profile-primary);
            box-shadow: var(--profile-shadow-hover);
        }

        .balance-card .balance-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .balance-card .balance-info .currency-symbol {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
            background: var(--profile-gray-100);
            color: var(--profile-gray-700);
        }

        .balance-card .balance-info .currency-symbol.positive {
            background: #dbeafe;
            color: var(--profile-primary);
        }

        .balance-card .balance-info .currency-symbol.negative {
            background: #fecaca;
            color: #991b1b;
        }

        .balance-card .balance-info .currency-symbol.zero {
            background: var(--profile-gray-100);
            color: var(--profile-gray-500);
        }

        .balance-card .balance-info .currency-name {
            font-weight: 600;
            color: var(--profile-gray-700);
            font-size: 0.875rem;
        }

        .balance-card .balance-info .currency-code {
            font-size: 0.65rem;
            color: var(--profile-gray-400);
        }

        .balance-card .balance-amount {
            font-weight: 700;
            font-size: 1rem;
        }

        .balance-card .balance-amount.positive {
            color: var(--profile-primary);
        }

        .balance-card .balance-amount.negative {
            color: #991b1b;
        }

        .balance-card .balance-amount.zero {
            color: var(--profile-gray-400);
        }

        .balance-card .balance-amount .status-label {
            font-size: 0.5rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 1px 6px;
            border-radius: 3px;
            margin-left: 4px;
        }

        .balance-card .balance-amount .status-label.positive {
            background: #dbeafe;
            color: var(--profile-primary);
        }

        .balance-card .balance-amount .status-label.negative {
            background: #fecaca;
            color: #991b1b;
        }

        .balance-card .balance-amount .status-label.zero {
            background: var(--profile-gray-100);
            color: var(--profile-gray-500);
        }

        /* Tabs */
        .profile-tabs {
            margin-top: 1.5rem;
        }

        .profile-tabs .nav-tabs {
            border-bottom: 2px solid var(--profile-gray-200);
            gap: 0.5rem;
        }

        .profile-tabs .nav-tabs .nav-link {
            border: none;
            padding: 0.6rem 1.25rem;
            font-weight: 600;
            color: var(--profile-gray-500);
            border-radius: 8px 8px 0 0;
            transition: all 0.3s ease;
        }

        .profile-tabs .nav-tabs .nav-link:hover {
            color: var(--profile-gray-700);
            background: var(--profile-gray-50);
        }

        .profile-tabs .nav-tabs .nav-link.active {
            color: var(--profile-primary);
            background: transparent;
            border-bottom: 3px solid var(--profile-primary);
        }

        .profile-tabs .nav-tabs .nav-link .badge-count {
            background: var(--profile-gray-100);
            color: var(--profile-gray-600);
            padding: 0.05rem 0.5rem;
            border-radius: 12px;
            font-size: 0.6rem;
            font-weight: 700;
            margin-left: 0.3rem;
        }

        .profile-tabs .nav-tabs .nav-link.active .badge-count {
            background: #dbeafe;
            color: var(--profile-primary);
        }

        .profile-tabs .tab-content {
            padding-top: 1.5rem;
        }

        /* Filter Bar */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            margin-bottom: 1.25rem;
            padding: 0.75rem 1rem;
            background: var(--profile-gray-50);
            border-radius: 8px;
            border: 1px solid var(--profile-gray-200);
        }

        .filter-bar .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            border: 1.5px solid var(--profile-gray-200);
            border-radius: 8px;
            padding: 0.1rem 0.1rem 0.1rem 0.75rem;
            transition: all 0.3s ease;
            flex: 1 1 160px;
        }

        .filter-bar .filter-group:focus-within {
            border-color: var(--profile-primary);
            box-shadow: 0 0 0 4px rgba(26, 86, 219, 0.08);
        }

        .filter-bar .filter-group .filter-icon {
            color: var(--profile-gray-400);
            font-size: 0.85rem;
        }

        .filter-bar .filter-group input,
        .filter-bar .filter-group select {
            border: none;
            padding: 0.4rem 0.5rem;
            font-size: 0.8125rem;
            background: transparent;
            width: 100%;
            outline: none;
            color: var(--profile-gray-700);
        }

        .filter-bar .filter-group input::placeholder {
            color: var(--profile-gray-400);
        }

        .filter-bar .btn-apply {
            background: var(--profile-primary);
            color: white;
            border: none;
            padding: 0.4rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8125rem;
            transition: all 0.3s ease;
        }

        .filter-bar .btn-apply:hover {
            background: var(--profile-primary-dark);
            transform: translateY(-1px);
        }

        .filter-bar .btn-reset {
            background: white;
            color: var(--profile-gray-600);
            border: 1.5px solid var(--profile-gray-200);
            padding: 0.4rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8125rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .filter-bar .btn-reset:hover {
            background: var(--profile-gray-50);
            border-color: var(--profile-gray-300);
        }

        /* Table Styles */
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }

        .table-custom thead th {
            padding: 0.5rem 0.75rem;
            background: var(--profile-gray-50);
            color: var(--profile-gray-600);
            font-weight: 700;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 2px solid var(--profile-gray-200);
            white-space: nowrap;
        }

        .table-custom tbody td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--profile-gray-100);
            vertical-align: middle;
        }

        .table-custom tbody tr:hover {
            background: rgba(26, 86, 219, 0.02);
        }

        .table-custom .text-primary {
            color: var(--profile-primary) !important;
        }

        .table-custom .text-danger {
            color: #991b1b !important;
        }

        .table-custom .badge {
            font-size: 0.65rem;
            font-weight: 600;
            padding: 0.25rem 0.6rem;
        }

        .pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid var(--profile-gray-200);
            margin-top: 1rem;
        }

        .pagination-wrap .info-text {
            font-size: 0.75rem;
            color: var(--profile-gray-400);
        }

        @media (max-width: 768px) {
            .profile-header .profile-top {
                flex-direction: column;
                text-align: center;
            }

            .profile-header .profile-info .details {
                justify-content: center;
            }

            .profile-header .profile-actions {
                justify-content: center;
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .balance-grid {
                grid-template-columns: 1fr;
            }

            .filter-bar {
                flex-direction: column;
            }

            .filter-bar .filter-group {
                width: 100%;
            }

            .filter-bar .btn-apply,
            .filter-bar .btn-reset {
                width: 100%;
                text-align: center;
                justify-content: center;
            }

            .pagination-wrap {
                flex-direction: column;
                align-items: center;
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
    <div class="container-fluid px-3 px-md-4">

        {{-- Back Button --}}
        <div class="mb-3">
            <a href="{{ route('admin.customers.index') }}" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-1"></i> Back to Customers
            </a>
        </div>

        {{-- Profile Header --}}
        <div class="profile-header">
            <div class="profile-top">
                <div class="profile-avatar">
                    {{ $customer->initials ?? substr($customer->name, 0, 2) }}
                </div>

                <div class="profile-info">
                    <div class="name">
                        {{ $customer->name }}
                        <span class="badge-code">{{ $customer->code }}</span>
                        <span class="status-badge {{ $customer->is_active ? 'active' : 'inactive' }}">
                            {{ $customer->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="details">
                        @if ($customer->company)
                            <div class="detail-item">
                                <i class="bi bi-building"></i>
                                <span class="label">{{ __('ui.company_colon') }}</span>
                                {{ $customer->company }}
                            </div>
                        @endif
                        @if ($customer->contact)
                            <div class="detail-item">
                                <i class="bi bi-phone"></i>
                                <span class="label">{{ __('ui.contact_colon') }}</span>
                                {{ $customer->contact }}
                            </div>
                        @endif
                        @if ($customer->whatsapp)
                            <div class="detail-item">
                                <i class="bi bi-whatsapp" style="color: #25D366;"></i>
                                <span class="label">WhatsApp:</span>
                                {{ $customer->whatsapp }}
                            </div>
                        @endif
                        @if ($customer->email)
                            <div class="detail-item">
                                <i class="bi bi-envelope"></i>
                                <span class="label">{{ __('ui.email_colon') }}</span>
                                {{ $customer->email }}
                            </div>
                        @endif
                        @if ($customer->address)
                            <div class="detail-item">
                                <i class="bi bi-geo-alt"></i>
                                <span class="label">{{ __('ui.address_colon') }}</span>
                                {{ Str::limit($customer->address, 50) }}
                            </div>
                        @endif
                        <div class="detail-item">
                            <i class="bi bi-calendar3"></i>
                            <span class="label">Since:</span>
                            {{ $customer->created_at->format('M d, Y') }}
                        </div>
                    </div>
                </div>

                <div class="profile-actions">
                    <button class="btn-action btn-outline" data-bs-toggle="modal" data-bs-target="#printStatementModal">
                        <i class="bi bi-printer"></i> Print
                    </button>

                    @if ($customer->whatsapp)
                        <button class="btn-action btn-whatsapp" onclick="sendWhatsApp({{ $customer->id }})">
                            <i class="bi bi-whatsapp"></i> {{ __('ui.whatsapp') }}
                        </button>
                    @endif
                    @can('update customers')
                        <button class="btn-action btn-primary" data-bs-toggle="modal" data-bs-target="#editCustomerModal">
                            <i class="bi bi-pencil"></i> {{ __('ui.edit') }}
                        </button>
                    @endcan
                </div>
            </div>

            @if ($customer->notes)
                <div class="mt-2 pt-2 border-top" style="color: var(--profile-gray-500); font-size: 0.8125rem;">
                    <i class="bi bi-file-text me-1"></i> {{ $customer->notes }}
                </div>
            @endif
        </div>

        {{-- Stats Cards --}}
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon blue"><i class="bi bi-arrow-down-circle"></i></span>
                <div class="stat-value">
                    <span class="currency-sm">$</span>
                    {{ number_format($transactionSummary['total_credit'] ?? 0, 2) }}
                </div>
                <div class="stat-label">{{ __('ui.total_credit') }}</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon red"><i class="bi bi-arrow-up-circle"></i></span>
                <div class="stat-value">
                    <span class="currency-sm">$</span>
                    {{ number_format($transactionSummary['total_debit'] ?? 0, 2) }}
                </div>
                <div class="stat-label">{{ __('ui.total_debit') }}</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon purple"><i class="bi bi-list-ul"></i></span>
                <div class="stat-value">{{ number_format($transactionSummary['total_transactions'] ?? 0) }}</div>
                <div class="stat-label">{{ __('ui.total_transactions') }}</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon green"><i class="bi bi-cart"></i></span>
                <div class="stat-value">{{ number_format($salesSummary['total_sales'] ?? 0) }}</div>
                <div class="stat-label">{{ __('ui.total_sales') }}</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon orange"><i class="bi bi-cash-stack"></i></span>
                <div class="stat-value">
                    <span class="currency-sm">$</span>
                    {{ number_format($salesSummary['total_amount'] ?? 0, 2) }}
                </div>
                <div class="stat-label">Sales Total</div>
            </div>
        </div>

        {{-- Balance by Currency --}}
        @if ($balances->isNotEmpty())
            <div class="balance-grid">
                @foreach ($balances as $balance)
                    @php
                        $isPositive = $balance->balance > 0;
                        $isNegative = $balance->balance < 0;
                        $isZero = $balance->balance == 0;
                        $statusClass = $isPositive ? 'positive' : ($isNegative ? 'negative' : 'zero');
                        $amountClass = $isPositive ? 'positive' : ($isNegative ? 'negative' : 'zero');
                        $label = $isPositive ? 'Cr' : ($isNegative ? 'Dr' : '0');
                    @endphp
                    <div class="balance-card">
                        <div class="balance-info">
                            <div class="currency-symbol {{ $statusClass }}">
                                {{ $balance->currency_symbol ?? '$' }}
                            </div>
                            <div>
                                <div class="currency-name">{{ $balance->currency_code }}</div>
                                <div class="currency-code">{{ __('ui.balance') }}</div>
                            </div>
                        </div>
                        <div class="balance-amount {{ $amountClass }}">
                            {{ number_format(abs($balance->balance), 2) }}
                            <span class="status-label {{ $statusClass }}">{{ $label }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Tabs --}}
        <div class="profile-tabs">
            <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="transactions-tab" data-bs-toggle="tab"
                        data-bs-target="#transactions" type="button" role="tab">
                        <i class="bi bi-list-ul me-1"></i> {{ __('ui.transactions') }}
                        <span class="badge-count">{{ $transactions->total() }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button"
                        role="tab">
                        <i class="bi bi-cart me-1"></i> {{ __('ui.sales') }}
                        <span class="badge-count">{{ $sales->total() }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="returns-tab" data-bs-toggle="tab" data-bs-target="#returns"
                        type="button" role="tab">
                        <i class="bi bi-arrow-return-left me-1"></i> Returns
                        <span class="badge-count">{{ $customer->saleReturns->count() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                {{-- Transactions Tab --}}
                <div class="tab-pane fade show active" id="transactions" role="tabpanel">
                    @include('admin.customers.partials.transactions-table', [
                        'transactions' => $transactions,
                        'customer' => $customer,
                        'filters' => $filters,
                        'currencies' => $currencies,
                    ])
                </div>

                {{-- Sales Tab --}}
                <div class="tab-pane fade" id="sales" role="tabpanel">
                    @include('admin.customers.partials.sales-table', [
                        'sales' => $sales,
                        'customer' => $customer,
                        'filters' => $filters,
                    ])
                </div>

                {{-- Returns Tab --}}
                <div class="tab-pane fade" id="returns" role="tabpanel">
                    @include('admin.customers.partials.returns-table', [
                        'returns' => $customer->saleReturns,
                    ])
                </div>
            </div>
        </div>

    </div>

    {{-- Modals --}}
    @include('admin.customers.partials.print-modal', [
        'customer' => $customer,
        'currencies' => $currencies,
    ])
    @include('admin.customers.partials.edit-modal', ['customer' => $customer])

@endsection

@section('js')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {

            // ─── Send WhatsApp ───
            window.sendWhatsApp = function(customerId) {
                Swal.fire({
                    title: 'Send WhatsApp Message?',
                    text: 'Send a message to this customer via WhatsApp?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, send it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.get('/admin/customers/' + customerId + '/send-whatsapp', function(response) {
                            Swal.fire('Success!', response.message ||
                                'Message sent successfully.',
                                'success');
                        }).fail(function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message ||
                                'Failed to send message.', 'error');
                        });
                    }
                });
            };

            // ─── Edit Customer Form ───
            $('#editCustomerForm').on('submit', function(e) {
                e.preventDefault();
                var id = $('#edit_customer_id').val();
                var $btn = $('#updateCustomerBtn');
                var formData = new FormData(this);

                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span> Updating...');

                $.ajax({
                    url: '/admin/customers/' + id,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                            $btn.prop('disabled', false).html(
                                '<i class="bi bi-check-lg"></i> Update Customer');
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
                            '<i class="bi bi-check-lg"></i> Update Customer');
                    }
                });
            });

        });
    </script>
@endsection
