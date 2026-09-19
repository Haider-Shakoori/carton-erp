@extends('layouts.admin.base')

@section('title', 'Purchase Order Details')

@section('css')
    <link href="{{ asset('vendor/select2/select2.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}">
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <style>
        /* ============================================================
                   PURCHASE ORDER DETAILS - Page Specific Styles
                ============================================================ */

        /* ─── Yellow Accent for Stats ─── */
        .stat-card.yellow-accent::before {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .stat-card .stat-icon.yellow {
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            color: #f59e0b;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);
        }

        /* ─── Orange Accent for Expenses ─── */
        .stat-card.orange-accent::before {
            background: linear-gradient(90deg, #f97316, #fb923c);
        }

        .stat-card .stat-icon.orange {
            background: linear-gradient(135deg, #fff7ed, #fed7aa);
            color: #f97316;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.15);
        }

        /* ─── Info Card (Order Details) ─── */
        .info-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 8px 32px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 12px 48px rgba(0, 0, 0, 0.06);
        }

        .info-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 0.75rem;
        }

        .info-card-header h5 {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .info-item {
            padding: 0.25rem 0;
        }

        .info-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-500);
            margin-bottom: 0.15rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .info-value {
            font-size: 0.875rem;
            color: var(--gray-800);
            font-weight: 600;
        }

        .info-notes {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--gray-200);
            font-size: 0.8125rem;
        }

        /* ─── Summary Cards (Local & USD) ─── */
        .summary-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 8px 32px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .summary-card:hover {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 12px 48px rgba(0, 0, 0, 0.06);
        }

        .summary-card-header {
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, #fafafa, #ffffff);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .summary-card-header .icon {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .summary-card-header .icon-local {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }

        .summary-card-header .icon-usd {
            background: linear-gradient(135deg, #059669, #10b981);
        }

        .summary-card-header h6 {
            margin: 0;
            font-weight: 700;
            font-size: 0.8125rem;
            color: var(--gray-800);
            flex: 1;
        }

        .summary-card-header .subtitle {
            font-size: 0.65rem;
            color: var(--gray-400);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            flex: 1;
        }

        .summary-table tr {
            border-bottom: 1px solid var(--gray-100);
        }

        .summary-table tr:last-child {
            border-bottom: none;
        }

        .summary-table td {
            padding: 0.5rem 1rem;
            font-size: 0.8125rem;
            background: white;
        }

        .summary-table .label {
            color: var(--gray-600);
            font-weight: 500;
        }

        .summary-table .value {
            text-align: right;
            font-weight: 600;
            color: var(--gray-800);
        }

        .summary-table .value-large {
            font-size: 1rem;
            color: var(--gray-900);
        }

        .summary-table .grand-total-row td {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            font-weight: 700;
            border-top: 2px solid var(--gray-200);
        }

        .summary-table .grand-total-row .value {
            font-size: 1.125rem;
            color: var(--primary);
        }

        /* ─── Status Badges ─── */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.3rem 0.8rem;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .badge-draft {
            background: var(--gray-100);
            color: var(--gray-600);
            border: 1px solid var(--gray-200);
        }

        .badge-shipping {
            background: var(--warning-bg);
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .badge-arrived {
            background: var(--success-bg);
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        /* ─── Section Cards ─── */
        .section-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 8px 32px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--gray-200);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .section-card:hover {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 12px 48px rgba(0, 0, 0, 0.06);
        }

        .section-card .card-header-custom {
            padding: 0.875rem 1.25rem;
            background: linear-gradient(135deg, #fafafa, #ffffff);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .section-card .card-header-custom .d-flex {
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }

        .section-card .card-header-custom h5 {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-card .card-header-custom h5 i {
            font-size: 1.1rem;
        }

        .section-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .icon-items {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }

        .icon-expenses {
            background: linear-gradient(135deg, #f59e0b, #f97316);
        }

        .section-card .card-body {
            padding: 1.25rem;
            background: linear-gradient(135deg, #fdfdfd, #fafafa);
        }

        /* ─── Product Cell ─── */
        .product-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 200px;
        }

        .product-avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            background: var(--gray-100);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-400);
            border: 2px solid var(--gray-200);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        tr:hover .product-avatar {
            border-color: var(--primary-light);
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.15);
        }

        .product-info .product-name {
            font-weight: 600;
            color: var(--gray-800);
            font-size: 0.8125rem;
            line-height: 1.3;
        }

        .product-info .product-meta {
            font-size: 0.6875rem;
            color: var(--gray-500);
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.1rem;
        }

        .product-info .product-meta span {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* ─── Table Styles ─── */
        .table-responsive-custom {
            overflow-x: auto;
        }

        .table-ledger {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }

        .table-ledger thead th {
            padding: 0.625rem 0.75rem;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            color: var(--gray-700);
            font-weight: 700;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 2px solid var(--gray-200);
            white-space: nowrap;
        }

        .table-ledger thead th:first-child {
            padding-left: 1rem;
        }

        .table-ledger thead th:last-child {
            padding-right: 1rem;
        }

        .table-ledger tbody tr {
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        .table-ledger tbody tr:hover {
            background: linear-gradient(90deg, rgba(79, 70, 229, 0.02), rgba(79, 70, 229, 0.05));
        }

        .table-ledger tbody td {
            padding: 0.625rem 0.75rem;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }

        .table-ledger tbody td:first-child {
            padding-left: 1rem;
        }

        .table-ledger tbody td:last-child {
            padding-right: 1rem;
        }

        .table-ledger tbody tr:last-child td {
            border-bottom: none;
        }

        /* ─── Action Buttons ─── */
        .action-btn {
            font-size: 0.6875rem;
            padding: 0.25rem 0.6rem;
            border-radius: var(--radius-xs);
            border: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-btn-edit {
            background: var(--primary-bg);
            color: var(--primary);
        }

        .action-btn-edit:hover {
            background: #dbeafe;
            transform: translateY(-1px);
        }

        .action-btn-delete {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .action-btn-delete:hover {
            background: #fecaca;
            transform: translateY(-1px);
        }

        /* ─── Empty State ─── */
        .empty-state {
            text-align: center;
            padding: 2.5rem 1rem;
        }

        .empty-state-icon {
            font-size: 2.5rem;
            color: var(--gray-300);
            margin-bottom: 0.75rem;
            display: block;
        }

        .empty-state-title {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.9375rem;
        }

        .empty-state-subtitle {
            color: var(--gray-400);
            font-size: 0.8125rem;
            margin-top: 0.25rem;
        }

        /* ─── Form Styles ─── */
        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.35rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .form-control,
        .form-select {
            border-radius: var(--radius-xs);
            border: 1.5px solid var(--gray-200);
            padding: 0.45rem 0.75rem;
            transition: all 0.3s ease;
            font-size: 0.875rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.08);
        }

        .form-control.bg-light {
            background: var(--gray-50);
            color: var(--gray-700);
            cursor: not-allowed;
        }

        /* ─── Buttons ─── */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            color: white;
            padding: 0.45rem 1.25rem;
            border-radius: var(--radius-xs);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            color: white;
            padding: 0.45rem 1.25rem;
            border-radius: var(--radius-xs);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35);
            color: white;
        }

        .btn-outline-secondary {
            border: 1.5px solid var(--gray-200);
            color: var(--gray-600);
            background: transparent;
            padding: 0.45rem 1.25rem;
            border-radius: var(--radius-xs);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            background: var(--gray-100);
            border-color: var(--gray-300);
        }

        .btn-outline-danger {
            border: 1.5px solid var(--danger);
            color: var(--danger);
            background: transparent;
            padding: 0.45rem 1.25rem;
            border-radius: var(--radius-xs);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-danger:hover {
            background: var(--danger);
            color: white;
        }

        .btn-outline-warning {
            border: 1.5px solid var(--warning);
            color: #92400e;
            background: transparent;
            padding: 0.45rem 1.25rem;
            border-radius: var(--radius-xs);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-warning:hover {
            background: var(--warning);
            color: white;
        }

        /* ─── Header Actions ─── */
        .header-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .header-actions .btn {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.4rem 0.875rem;
            border-radius: var(--radius-xs);
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            transition: all 0.3s ease;
        }

        .header-actions .btn:hover {
            transform: translateY(-2px);
        }

        /* ─── Summary Grid ─── */
        .summary-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        /* ─── Select2 Overrides ─── */
        .select2-container--bootstrap-5 .select2-selection {
            border-radius: var(--radius-xs);
            border-color: var(--gray-200);
            min-height: 38px;
            font-size: 0.875rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding: 0.3rem 0.75rem;
            color: var(--gray-800);
        }

        .select2-container--bootstrap-5 .select2-dropdown {
            border-radius: var(--radius-sm);
            border-color: var(--gray-200);
        }

        .select2-product-image {
            width: 32px;
            height: 32px;
            object-fit: cover;
            border-radius: var(--radius-xs);
            margin-right: 0.625rem;
            flex-shrink: 0;
        }

        .select2-results__option--product {
            display: flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
        }

        .select2-product-info .select2-product-name {
            font-weight: 500;
            color: var(--gray-800);
            font-size: 0.8125rem;
        }

        .select2-product-info .select2-product-category {
            font-size: 0.6875rem;
            color: var(--gray-400);
        }

        .select2-agent-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 0;
        }

        .select2-agent-option .agent-code {
            font-weight: 700;
            color: var(--primary);
            background: var(--primary-bg);
            padding: 1px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
        }

        .select2-agent-option .agent-name {
            color: var(--gray-800);
        }

        .select2-agent-option .agent-detail {
            color: var(--gray-400);
            font-size: 0.75rem;
        }

        .select2-agent-selection .agent-code {
            font-weight: 700;
            color: var(--primary);
            margin-right: 6px;
        }

        .select2-agent-selection .agent-name {
            color: var(--gray-800);
        }

        /* ─── Modal ─── */
        .modal-content {
            border: none;
            border-radius: var(--radius);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 1rem 1.5rem;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-title {
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
            padding: 1rem 1.5rem;
        }

        /* ─── Responsive ─── */
        @media (max-width: 1200px) {
            .summary-grid {
                grid-template-columns: 1fr 1fr;
            }

            .summary-grid .info-card {
                grid-column: span 2;
            }
        }

        @media (max-width: 768px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }

            .summary-grid .info-card {
                grid-column: span 1;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .header-actions {
                justify-content: stretch;
            }

            .header-actions .btn {
                flex: 1;
                justify-content: center;
            }

            .info-grid {
                grid-template-columns: 1fr 1fr;
            }

            .section-card .card-body {
                padding: 0.75rem;
            }

            .table-ledger thead th,
            .table-ledger tbody td {
                padding: 0.5rem 0.5rem;
                font-size: 0.7rem;
            }

            .product-cell {
                min-width: 150px;
            }

            .product-avatar {
                width: 32px;
                height: 32px;
            }
        }

        @media (max-width: 480px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .summary-table td {
                padding: 0.4rem 0.5rem;
                font-size: 0.7rem;
            }

            .summary-table .grand-total-row .value {
                font-size: 0.9rem;
            }

            .section-card .card-header-custom {
                flex-direction: column;
                align-items: stretch;
            }

            .section-card .card-header-custom .d-flex {
                justify-content: center;
            }

            .header-actions .btn {
                font-size: 0.65rem;
                padding: 0.3rem 0.6rem;
            }

            .badge-status {
                font-size: 0.6rem;
                padding: 0.2rem 0.6rem;
            }
        }
    </style>
@endsection

@php
    function formatNumber($number, $decimals = 2)
    {
        if ($number === null || $number === '') {
            return '0';
        }

        $formatted = (float) $number;

        // For quantities (when decimals = 0), show as integer
        if ($decimals === 0) {
            return number_format($formatted, 0);
        }

        // For currency amounts, always show 2 decimal places
        return number_format($formatted, 2, '.', ',');
    }

    function formatCurrency($number, $symbol = '$')
    {
        return $symbol . formatNumber($number, 2);
    }
@endphp

@section('content')
    <div class="container-fluid px-3 px-md-4">

        {{-- ============================================================
        PAGE HEADER
        ============================================================ --}}
        <div class="page-header">
            <div>
                <h1>
                    <i class="bi bi-receipt me-2"></i>
                    Purchase Order <span class="accent">#{{ $purchase->purchase_no ?? 'N/A' }}</span>
                </h1>
                <p class="subtitle">
                    <i class="bi bi-truck me-1"></i>
                    Manage and track your purchase order information
                </p>
            </div>

            <div class="header-actions">
                @php
                    $isArrived = ($purchase->status ?? 'draft') === 'arrived';
                    $isDisabled = $isArrived;
                @endphp

                <button class="btn btn-outline-secondary" onclick="window.history.back()">
                    <i class="bi bi-arrow-left"></i> Back
                </button>

                <form action="{{ route('admin.purchase-orders.destroy', $purchase->id) }}" method="POST" class="d-inline"
                    id="deleteOrderForm">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()"
                        {{ $isDisabled ? 'disabled' : '' }}>
                        <i class="bi bi-trash3"></i> {{ __('ui.delete') }}
                    </button>
                </form>

                <a href="{{ route('admin.purchase-orders.edit', $purchase->id) }}" class="btn btn-outline-warning"
                    {{ $isDisabled ? 'disabled' : '' }}>
                    <i class="bi bi-pencil-square"></i> {{ __('ui.edit') }}
                </a>

                @if (($purchase->status ?? 'draft') === 'draft' && !$isArrived)
                    <form action="{{ route('admin.purchase-orders.update-status', $purchase->id) }}" method="POST"
                        class="d-inline" id="statusShippingForm">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="shipping">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#confirmShippingModal">
                            <i class="bi bi-truck"></i> Mark Shipping
                        </button>
                    </form>
                @endif

                @if (($purchase->status ?? 'draft') === 'shipping' && !$isArrived)
                    <form action="{{ route('admin.purchase-orders.update-status', $purchase->id) }}" method="POST"
                        class="d-inline" id="statusArrivedForm">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="arrived">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal"
                            data-bs-target="#confirmArrivedModal">
                            <i class="bi bi-check-circle"></i> Mark Arrived
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @php
            $exchangeRate = $purchase->exchange_rate > 0 ? $purchase->exchange_rate : 1;
            $currencySymbol = $purchase->currency->symbol ?? '$';

            $totalItems = $purchase->items->sum('qty');
            $totalPurchaseAmountLocal = $purchase->items->sum('total');
            $totalPurchaseAmountUSD = $purchase->items->sum('usd_total');
            $totalExpenseAmountUSD = $purchase->expenses->sum('usd_amount');
            $totalExpenseAmountLocal = $purchase->expenses->sum('amount');
            $grandTotalUSD = $totalPurchaseAmountUSD + $totalExpenseAmountUSD;
            $grandTotalLocal = $totalPurchaseAmountLocal + $totalExpenseAmountLocal;
        @endphp

        {{-- ============================================================
        SUMMARY GRID
        ============================================================ --}}
        <div class="summary-grid">
            {{-- Order Info Card --}}
            <div class="info-card">
                <div class="info-card-header">
                    <div>
                        <h5>
                            <i class="bi bi-receipt"></i> Order #{{ $purchase->purchase_no ?? 'N/A' }}
                        </h5>
                        @php
                            $statusClass =
                                [
                                    'draft' => 'badge-draft',
                                    'shipping' => 'badge-shipping',
                                    'arrived' => 'badge-arrived',
                                ][$purchase->status ?? 'draft'] ?? 'badge-draft';
                            $statusIcon =
                                [
                                    'draft' => 'bi-pencil-square',
                                    'shipping' => 'bi-truck',
                                    'arrived' => 'bi-check-circle-fill',
                                ][$purchase->status ?? 'draft'] ?? 'bi-question-circle';
                        @endphp
                        <span class="badge-status {{ $statusClass }} mt-1">
                            <i class="bi {{ $statusIcon }}"></i>
                            {{ ucfirst($purchase->status ?? 'Draft') }}
                        </span>
                    </div>
                    <div style="font-size: 0.6875rem; color: var(--gray-500);">
                        <i class="bi bi-calendar3"></i>
                        {{ $purchase->created_at ? $purchase->created_at->format('Y-m-d H:i') : '-' }}
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label"><i class="bi bi-building"></i> {{ __('ui.supplier') }}</div>
                        <div class="info-value">{{ $purchase->supplier->name ?? '-' }}</div>
                        @if ($purchase->supplier->email ?? false)
                            <div style="font-size: 0.6875rem; color: var(--gray-400); margin-top: 0.15rem;">
                                <i class="bi bi-envelope"></i> {{ $purchase->supplier->email }}
                            </div>
                        @endif
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="bi bi-currency-exchange"></i> {{ __('ui.currency') }}</div>
                        <div class="info-value">{{ $purchase->currency->name ?? '-' }}
                            ({{ $purchase->currency->symbol ?? '-' }})</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="bi bi-calendar-date"></i> {{ __('ui.purchase_date') }}</div>
                        <div class="info-value">
                            {{ $purchase->purchase_date ? date('Y-m-d', strtotime($purchase->purchase_date)) : '-' }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="bi bi-calendar-week"></i> {{ __('ui.expected_arrival') }}</div>
                        <div class="info-value">
                            {{ $purchase->arrival_date ? date('Y-m-d', strtotime($purchase->arrival_date)) : '-' }}
                        </div>
                        @if ($purchase->arrival_date && strtotime($purchase->arrival_date) < time() && ($purchase->status ?? '') !== 'arrived')
                            <div style="font-size: 0.625rem; color: var(--danger); margin-top: 0.15rem;">
                                <i class="bi bi-exclamation-triangle"></i> Overdue
                            </div>
                        @endif
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="bi bi-box"></i> {{ __('ui.total_items') }}</div>
                        <div class="info-value">{{ $purchase->items->count() }} products ({{ formatNumber($totalItems) }}
                            qty)</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="bi bi-arrow-left-right"></i> {{ __('ui.exchange_rate') }}</div>
                        <div class="info-value">1 USD = {{ formatNumber($exchangeRate) }} {{ $currencySymbol }}</div>
                    </div>
                </div>

                @if ($purchase->notes)
                    <div class="info-notes">
                        <div class="info-label"><i class="bi bi-file-text"></i> {{ __('ui.notes') }}</div>
                        <div style="color: var(--gray-700);">{{ $purchase->notes }}</div>
                    </div>
                @endif
            </div>

            {{-- Local Currency Summary --}}
            <div class="summary-card">
                <div class="summary-card-header">
                    <div class="icon icon-local">
                        <i class="bi bi-currency-exchange"></i>
                    </div>
                    <h6>In {{ $currencySymbol }} (Order Currency)</h6>
                    <span class="subtitle">{{ $purchase->currency->name ?? 'Local' }}</span>
                </div>
                <table class="summary-table">
                    <tbody>
                        <tr>
                            <td class="label"><i class="bi bi-cart-check"></i> {{ __('ui.purchase_total') }}</td>
                            <td class="value">{{ $currencySymbol }} {{ formatNumber($totalPurchaseAmountLocal) }}</td>
                        </tr>
                        <tr>
                            <td class="label"><i class="bi bi-receipt"></i> {{ __('ui.expenses_total') }}</td>
                            <td class="value">{{ $currencySymbol }} {{ formatNumber($totalExpenseAmountLocal) }}</td>
                        </tr>
                        <tr class="grand-total-row">
                            <td class="label"><i class="bi bi-trophy"></i> {{ __('ui.grand_total') }}</td>
                            <td class="value value-large">{{ $currencySymbol }} {{ formatNumber($grandTotalLocal) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- USD Summary --}}
            <div class="summary-card">
                <div class="summary-card-header">
                    <div class="icon icon-usd">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <h6>{{ __('ui.in_usd_system') }}</h6>
                    <span class="subtitle">United States Dollar</span>
                </div>
                <table class="summary-table">
                    <tbody>
                        <tr>
                            <td class="label"><i class="bi bi-cart-check"></i> {{ __('ui.purchase_total') }}</td>
                            <td class="value">$ {{ formatNumber($totalPurchaseAmountUSD) }}</td>
                        </tr>
                        <tr>
                            <td class="label"><i class="bi bi-receipt"></i> {{ __('ui.expenses_total') }}</td>
                            <td class="value">$ {{ formatNumber($totalExpenseAmountUSD) }}</td>
                        </tr>
                        <tr class="grand-total-row">
                            <td class="label"><i class="bi bi-trophy"></i> {{ __('ui.grand_total') }}</td>
                            <td class="value value-large">$ {{ formatNumber($grandTotalUSD) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ============================================================
        PURCHASE ITEMS SECTION
        ============================================================ --}}
        <div class="section-card">
            <div class="card-header-custom">
                <div class="d-flex">
                    <div class="section-icon icon-items"><i class="bi bi-box-seam"></i></div>
                    <h5><i class="bi bi-list-ul"></i> {{ __('ui.purchase_items') }}</h5>
                </div>
                <span class="header-badge">
                    <i class="bi bi-box"></i> {{ $purchase->items->count() }} products
                </span>
            </div>

            <div class="card-body">
                @if (!$isArrived)
                    <form id="addItemForm" class="row g-3 align-items-end">
                        @csrf
                        <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">

                        <div class="col-md-3">
                            <label class="form-label"><i class="bi bi-tag"></i> {{ __('ui.product') }}</label>
                            <select name="product_id" id="product_select" class="form-select" required>
                                <option value="">{{ __('ui.select_product_lower') }}</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" data-name="{{ $product->name }}"
                                        data-unit="{{ $product->unit }}"
                                        data-default_kg_per_roll="{{ $product->default_kg_per_roll ?? '' }}"
                                        data-category="{{ $product->category->name ?? 'N/A' }}"
                                        data-image="{{ $product->image ? asset('storage/' . $product->image) : '' }}">
                                        {{ $product->name }} ({{ $product->unit ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label"><i class="bi bi-hash"></i> {{ __('ui.quantity') }}</label>
                            <input type="number" name="qty" id="item_qty" class="form-control" placeholder="0"
                                step="any" value="0">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label"> Unit Price ({{ $currencySymbol }})</label>
                            <input type="number" name="unit_price" id="item_unit_price" class="form-control"
                                placeholder="0" step="any" value="0">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label"><i class="bi bi-arrow-left-right"></i> {{ __('ui.exchange_rate') }}</label>
                            <input type="number" name="rate" id="item_rate" class="form-control" step="any"
                                value="{{ $purchase->exchange_rate ?? 1 }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label"><i class="bi bi-calculator"></i> Line Total
                                ({{ $currencySymbol }})</label>
                            <input type="text" id="item_total_display" class="form-control bg-light" readonly
                                placeholder="0">
                        </div>

                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-lg"></i> Add
                            </button>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-tag"></i> {{ __('ui.batch_no') }}</label>
                            <input type="text" name="batch_no" id="item_batch_no" class="form-control"
                                placeholder="Enter production or batch code...">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-journal-text"></i> {{ __('ui.remarks_optional') }}</label>
                            <input type="text" name="remarks" id="item_remarks" class="form-control"
                                placeholder="Add specific condition, state, or note...">
                        </div>

                        <input type="hidden" name="unit" id="item_unit" value="">

                        <div class="col-md-3" id="item_kg_per_roll_wrapper" style="display:none;">
                            <label class="form-label"><i class="bi bi-weight"></i> {{ __('ui.kg_per_roll') }} <span class="text-danger">*</span></label>
                            <input type="number" name="kg_per_roll" id="item_kg_per_roll" class="form-control"
                                placeholder="e.g. 500" step="any" min="0.0001">
                        </div>

                        <div class="col-md-3" id="item_total_weight_wrapper" style="display:none;">
                            <label class="form-label"><i class="bi bi-speedometer2"></i> {{ __('ui.total_weight_kg') }}</label>
                            <input type="text" id="item_total_weight_display" class="form-control bg-light" readonly placeholder="0 kg">
                        </div>
                    </form>
                    <hr>
                @endif

                <div class="table-responsive-custom">
                    <table class="table-ledger">
                        <thead>
                            <tr>
                                <th style="min-width: 200px;">{{ __('ui.product') }}</th>
                                <th class="text-center">{{ __('ui.qty') }}</th>
                                <th class="text-end">{{ __('ui.unit_price') }}</th>
                                <th class="text-end">{{ __('ui.total') }}</th>
                                <th class="text-center">{{ __('ui.rate') }}</th>
                                <th class="text-end">{{ __('ui.expense') }}</th>
                                <th class="text-end">{{ __('ui.expense_per_item') }}</th>
                                <th class="text-end">{{ __('ui.usd_total') }}</th>
                                <th class="text-end">{{ __('ui.usd_cost_item') }}</th>
                                @if (!$isArrived)
                                    <th class="text-center" style="width: 1%;">{{ __('ui.actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="items_table_body">
                            @forelse($purchase->items as $item)
                                <tr data-item-id="{{ $item->id }}" data-product-id="{{ $item->product_id }}">
                                    <td>
                                        <div class="product-cell">
                                            @if ($item->product && $item->product->image)
                                                <img src="{{ asset('storage/' . $item->product->image) }}"
                                                    alt="{{ $item->product->name }}" class="product-avatar">
                                            @else
                                                <div class="product-avatar">
                                                    <i class="bi bi-box-seam"></i>
                                                </div>
                                            @endif
                                            <div class="product-info">
                                                <div class="product-name">{{ $item->product->name ?? '-' }}</div>
                                                <div class="product-meta">
                                                    @if ($item->remarks)
                                                        <span><i class="bi bi-journal-text"></i>
                                                            {{ $item->remarks }}</span>
                                                    @endif
                                                    @if ($item->batch_no)
                                                        <span><i class="bi bi-tag"></i> Batch:
                                                            {{ $item->batch_no }}</span>
                                                    @endif
                                                    @if ($item->isRollBatch())
                                                        <span><i class="bi bi-box"></i>
                                                            {{ number_format($item->qty, 0) }} roll{{ $item->qty != 1 ? 's' : '' }}
                                                            × {{ number_format($item->kg_per_roll, 2) }} kg = {{ number_format($item->totalKg(), 2) }} kg</span>
                                                        <span><i class="bi bi-truck"></i> Landed
                                                            ${{ number_format($item->landedCostPerKg(), 2) }}/kg</span>
                                                    @elseif(($item->kg_per_roll ?? 0) > 0)
                                                        <span><i class="bi bi-speedometer2"></i> {{ number_format($item->kg_per_roll, 2) }} kg/roll</span>
                                                    @elseif($item->product && strtolower((string) $item->product->unit) === 'roll')
                                                        <span style="color: var(--bs-warning);"><i class="bi bi-exclamation-triangle"></i> {{ __('ui.roll_weight_missing') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold">{{ formatNumber($item->qty) }}</td>
                                    <td class="text-end">
                                        {{ $purchase->currency->symbol }}{{ formatNumber($item->unit_price) }}</td>
                                    <td class="text-end fw-bold">
                                        {{ $purchase->currency->symbol }}{{ formatNumber($item->total) }}</td>
                                    <td class="text-center">{{ formatNumber($item->rate) }}</td>
                                    <td class="text-end">${{ formatNumber($item->expense_per_item) }}</td>
                                    <td class="text-end">${{ formatNumber($item->expense_per_item / $item->qty) }}</td>
                                    <td class="text-end fw-bold">
                                        ${{ formatNumber($item->usd_total + $item->expense_per_item) }}</td>
                                    <td class="text-end fw-bold">
                                        ${{ formatNumber(($item->usd_total + $item->expense_per_item) / $item->qty) }}</td>
                                    @if (!$isArrived)
                                        <td class="text-center">
                                            <button class="action-btn action-btn-edit edit-item"
                                                data-id="{{ $item->id }}"
                                                data-unit="{{ $item->unit ?? '' }}"
                                                data-kg_per_roll="{{ $item->kg_per_roll ?? '' }}"
                                                data-qty="{{ $item->qty }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button class="action-btn action-btn-delete delete-item mt-1"
                                                data-id="{{ $item->id }}">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isArrived ? '10' : '11' }}">
                                        <div class="empty-state">
                                            <i class="bi bi-box-seam empty-state-icon"></i>
                                            <div class="empty-state-title">{{ __('ui.no_items_added') }}</div>
                                            <div class="empty-state-subtitle">Add your first item using the form above
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ============================================================
        EXPENSES SECTION
        ============================================================ --}}
        <div class="section-card">
            <div class="card-header-custom">
                <div class="d-flex">
                    <div class="section-icon icon-expenses"><i class="bi bi-receipt"></i></div>
                    <h5><i class="bi bi-coin"></i> {{ __('ui.expenses') }}</h5>
                </div>
                <span class="header-badge">
                    <i class="bi bi-receipt"></i> {{ $purchase->expenses->count() }} expenses
                </span>
            </div>

            <div class="card-body">
                @if (!$isArrived)
                    <form id="addExpenseForm" class="row g-3 align-items-end">
                        @csrf
                        <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">

                        <div class="col-md-3">
                            <label class="form-label"><i class="bi bi-person"></i> {{ __('ui.agent') }}</label>
                            <select name="agent_id" id="agent_select" class="form-select" required>
                                <option value="">{{ __('ui.select_agent') }}</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id }}" data-code="{{ $agent->code ?? 'N/A' }}"
                                        data-email="{{ $agent->email ?? '' }}">
                                        {{ $agent->name }}
                                        @if ($agent->code)
                                            ({{ $agent->code }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label"><i class="bi bi-currency-exchange"></i> {{ __('ui.currency') }}</label>
                            <select name="currency_id" id="expense_currency" class="form-select" required>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}">{{ $currency->name }} ({{ $currency->symbol }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label"><i class="bi bi-cash"></i> {{ __('ui.amount') }}</label>
                            <input type="number" name="amount" id="expense_amount" class="form-control"
                                placeholder="0" step="any" value="0">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label"><i class="bi bi-arrow-left-right"></i> {{ __('ui.exchange_rate') }}</label>
                            <input type="number" name="rate" id="expense_rate" class="form-control" step="any"
                                placeholder="{{ __('ui.rate') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label"><i class="bi bi-currency-dollar"></i> {{ __('ui.usd_amount') }}</label>
                            <input type="text" id="expense_usd_display" class="form-control bg-light" readonly
                                placeholder="0">
                        </div>

                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-lg"></i> Add
                            </button>
                        </div>

                        <div class="col-12">
                            <label class="form-label"><i class="bi bi-journal-text"></i> {{ __('ui.description_optional') }}</label>
                            <input type="text" name="description" id="expense_description" class="form-control"
                                placeholder="{{ __('ui.expense_description') }}">
                        </div>
                    </form>
                    <hr>
                @endif

                <div class="table-responsive-custom">
                    <table class="table-ledger">
                        <thead>
                            <tr>
                                <th style="min-width: 150px;">{{ __('ui.agent') }}</th>
                                <th style="min-width: 80px;">{{ __('ui.account_code') }}</th>
                                <th style="min-width: 150px;">{{ __('ui.description') }}</th>
                                <th class="text-center" style="min-width: 80px;">{{ __('ui.currency') }}</th>
                                <th class="text-end" style="min-width: 100px;">{{ __('ui.amount') }}</th>
                                <th class="text-center" style="min-width: 80px;">{{ __('ui.exchange_rate') }}</th>
                                <th class="text-end" style="min-width: 100px;">{{ __('ui.usd_amount') }}</th>
                                @if (!$isArrived)
                                    <th class="text-center" style="width: 1%;">{{ __('ui.actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="expenses_table_body">
                            @forelse($purchase->expenses as $expense)
                                <tr data-expense-id="{{ $expense->id }}">
                                    <td>
                                        <div class="product-info">
                                            <div class="product-name">{{ $expense->agent->name ?? '-' }}</div>
                                            <div class="product-meta">
                                                <span><i class="bi bi-person"></i>
                                                    {{ $expense->agent->code ?? '-' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><strong>{{ $expense->agent->code ?? '-' }}</strong></td>
                                    <td>{{ $expense->description ?? '-' }}</td>
                                    <td class="text-center"><span
                                            class="currency-code">{{ $expense->currency->symbol ?? '-' }}</span></td>
                                    <td class="text-end fw-bold">{{ formatNumber($expense->amount) }}</td>
                                    <td class="text-center">{{ formatNumber($expense->rate) }}</td>
                                    <td class="text-end fw-bold">${{ formatNumber($expense->usd_amount) }}</td>
                                    @if (!$isArrived)
                                        <td class="text-center">
                                            <button class="action-btn action-btn-delete delete-expense"
                                                data-id="{{ $expense->id }}">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isArrived ? '7' : '8' }}">
                                        <div class="empty-state">
                                            <i class="bi bi-receipt empty-state-icon"></i>
                                            <div class="empty-state-title">{{ __('ui.no_expenses_added') }}</div>
                                            <div class="empty-state-subtitle">Add expenses like shipping, customs, or agent
                                                fees</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- ============================================================
    EDIT ITEM MODAL
    ============================================================ --}}
    <div class="modal fade" id="editItemModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i> Edit Purchase Item
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editItemForm">
                    <div class="modal-body">
                        <input type="hidden" name="item_id" id="edit_item_id">
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-hash"></i> {{ __('ui.quantity') }}</label>
                            <input type="number" name="qty" id="edit_qty" class="form-control" step="any"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-currency-dollar"></i> Unit Price
                                ({{ $currencySymbol }})</label>
                            <input type="number" name="unit_price" id="edit_unit_price" class="form-control"
                                step="any" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-arrow-left-right"></i> {{ __('ui.exchange_rate') }}</label>
                            <input type="number" name="rate" id="edit_rate" class="form-control" step="any"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-journal-text"></i> {{ __('ui.remarks') }}</label>
                            <input type="text" name="remarks" id="edit_remarks" class="form-control">
                        </div>
                        <input type="hidden" name="unit" id="edit_unit">
                        <input type="hidden" name="kg_per_roll" id="edit_kg_per_roll">
                        <div class="mb-3 d-none" id="edit_roll_info">
                            <div class="alert alert-light border mb-0" style="font-size: 0.85rem;">
                                <i class="bi bi-info-circle text-primary"></i>
                                <strong>{{ __('ui.roll_unit') }}:</strong>
                                <span id="edit_kg_per_roll_display"></span> {{ __('ui.kg_per_roll') }}
                                &middot;
                                <span id="edit_total_weight_display"></span>
                                <br>
                                <small class="text-muted">{{ __('ui.no_roll_weight_set') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> {{ __('ui.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================================
    CONFIRM SHIPPING MODAL
    ============================================================ --}}
    <div class="modal fade" id="confirmShippingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-truck"></i> Change Status to Shipping
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to change this purchase order status to <strong>{{ __('ui.shipping') }}</strong>? This indicates
                    that items are currently in transit from the supplier.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                    <button type="button" class="btn btn-primary"
                        onclick="document.getElementById('statusShippingForm').submit();">
                        <i class="bi bi-check-lg"></i> Confirm & Mark Shipping
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
    CONFIRM ARRIVED MODAL
    ============================================================ --}}
    <div class="modal fade" id="confirmArrivedModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill"></i> Change Status to Arrived
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2 text-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>{{ __('ui.warning_colon') }}</strong> {{ __('ui.marking_order_as') }} <strong>{{ __('ui.arrived') }}</strong> will permanently freeze
                        additions and updates to items!
                    </p>
                    <span class="text-muted" style="font-size: 0.8125rem;">
                        This will log items into active stock balances. Please ensure item counts match real batch details.
                    </span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                    <button type="button" class="btn btn-success"
                        onclick="document.getElementById('statusArrivedForm').submit();">
                        <i class="bi bi-lock-fill"></i> Confirm & Lock Stock
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/jquery360/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>

    <script>
        function formatNumber(value) {
            if (value === null || value === undefined) return '0';
            let num = parseFloat(value);
            if (isNaN(num)) return '0';
            if (num === Math.floor(num)) {
                return num.toString();
            }
            return num.toFixed(2).replace(/\.?0+$/, '');
        }

        function checkDuplicateProduct(productId) {
            let duplicate = false;
            let productName = '';

            $('#items_table_body tr[data-product-id]').each(function() {
                let existingProductId = $(this).data('product-id');
                if (existingProductId == productId) {
                    duplicate = true;
                    productName = $(this).find('.product-name').first().text();
                    return false;
                }
            });

            return {
                duplicate,
                productName
            };
        }

        function formatProductOption(option) {
            if (!option.id) return option.text;

            var imageUrl = $(option.element).data('image');
            var category = $(option.element).data('category');
            var unit = $(option.element).data('unit');

            var imageHtml = imageUrl ?
                '<img src="' + imageUrl + '" class="select2-product-image">' :
                '<div class="select2-product-image d-flex align-items-center justify-content-center bg-light rounded" style="width:32px;height:32px;border-radius:6px;background:#f1f5f9;color:#94a3b8;flex-shrink:0;margin-right:10px;display:flex;align-items:center;justify-content:center;"><i class="bi bi-box-seam"></i></div>';

            return $('<div class="select2-results__option--product">' +
                imageHtml +
                '<div class="select2-product-info">' +
                '<div class="select2-product-name">' + option.text.split(' (')[0] + '</div>' +
                '<div class="select2-product-category">' +
                '<i class="bi bi-tag"></i> ' + category +
                ' | <i class="bi bi-rulers"></i> ' + unit +
                '</div>' +
                '</div>' +
                '</div>');
        }

        function formatProductSelection(option) {
            return option.text.split(' (')[0];
        }

        $(document).ready(function() {
            @if (!$isArrived)
                $('#product_select').select2({
                    placeholder: @json(__('ui.search_product')),
                    theme: 'bootstrap-5',
                    templateResult: formatProductOption,
                    templateSelection: formatProductSelection,
                    escapeMarkup: function(m) {
                        return m;
                    }
                }).on('change', function() {
                    var $opt = $('#product_select option:selected');
                    var unit = $opt.data('unit') || '';
                    var defKg = $opt.data('default_kg_per_roll') || '';
                    $('#item_unit').val(unit);
                    if (unit === 'roll' && defKg && defKg !== '' && parseFloat(defKg) > 0) {
                        $('#item_kg_per_roll').val(defKg);
                        $('#item_kg_per_roll_wrapper').show();
                        updateTotalWeight();
                    } else {
                        $('#item_kg_per_roll').val('');
                        $('#item_kg_per_roll_wrapper').hide();
                        $('#item_total_weight_wrapper').hide();
                        $('#item_total_weight_display').val('');
                    }
                });

                function updateTotalWeight() {
                    var unit = $('#item_unit').val();
                    if (unit !== 'roll') {
                        $('#item_total_weight_wrapper').hide();
                        return;
                    }
                    var qty = parseFloat($('#item_qty').val()) || 0;
                    var kg = parseFloat($('#item_kg_per_roll').val()) || 0;
                    if (qty > 0 && kg > 0) {
                        var total = qty * kg;
                        $('#item_total_weight_display').val(total.toFixed(2) + ' kg');
                        $('#item_total_weight_wrapper').show();
                    } else {
                        $('#item_total_weight_display').val('');
                        $('#item_total_weight_wrapper').hide();
                    }
                }
                $('#item_qty, #item_kg_per_roll').on('input change', updateTotalWeight);

                $('#agent_select').select2({
                    placeholder: @json(__('ui.search_agent')),
                    theme: 'bootstrap-5',
                    templateResult: function(option) {
                        if (!option.id) return option.text;

                        var $option = $(option.element);
                        var code = $option.data('code') || 'N/A';
                        var email = $option.data('email') || '';
                        var name = option.text.split(' (')[0];

                        return $('<div class="select2-agent-option">' +
                            '<span class="agent-code">' + code + '</span>' +
                            '<span class="agent-name">' + name + '</span>' +
                            (email ? '<span class="agent-detail">' + email + '</span>' : '') +
                            '</div>');
                    },
                    templateSelection: function(option) {
                        if (!option.id) return option.text;

                        var $option = $(option.element);
                        var code = $option.data('code') || 'N/A';
                        var name = option.text.split(' (')[0];

                        return $('<span class="select2-agent-selection">' +
                            '<span class="agent-code">' + code + '</span>' +
                            '<span class="agent-name">' + name + '</span>' +
                            '</span>');
                    },
                    escapeMarkup: function(m) {
                        return m;
                    }
                });

                function calculateItemValues() {
                    let qty = parseFloat($('#item_qty').val()) || 0;
                    let price = parseFloat($('#item_unit_price').val()) || 0;
                    let rate = parseFloat($('#item_rate').val()) || 1;

                    let total = qty * price;
                    let usdTotal = rate > 0 ? total / rate : 0;
                    let usdUnitPrice = rate > 0 ? price / rate : 0;

                    $('#item_total_display').val(formatNumber(total));

                    if ($('#usd_preview').length === 0) {
                        $('#addItemForm').append(
                            '<div id="usd_preview" class="col-12 mt-2" style="font-size:0.75rem;color:var(--gray-400);"></div>'
                        );
                    }

                    if (qty > 0 && price > 0) {
                        $('#usd_preview').html(
                            '<i class="bi bi-currency-dollar"></i> {{ __('ui.usd_value_colon') }} <strong>$' + formatNumber(
                                usdTotal) +
                            '</strong> (Unit: $' + formatNumber(usdUnitPrice) + ')'
                        );
                    } else {
                        $('#usd_preview').html('');
                    }
                }

                function calculateExpenseUSD() {
                    let amount = parseFloat($('#expense_amount').val()) || 0;
                    let rate = parseFloat($('#expense_rate').val()) || 1;
                    let usd = rate > 0 ? amount / rate : 0;
                    $('#expense_usd_display').val(formatNumber(usd));
                }

                $('#item_qty, #item_unit_price, #item_rate').on('input', calculateItemValues);
                $('#expense_amount, #expense_rate').on('input', calculateExpenseUSD);

                // Add Item
                $('#addItemForm').off('submit').on('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    let productId = $('[name="product_id"]').val();
                    let productName = $('#product_select option:selected').text().split(' (')[0];
                    let qty = $('#item_qty').val();
                    let price = $('#item_unit_price').val();
                    let rate = $('#item_rate').val();
                    let remarks = $('#item_remarks').val();

                    if (!productId) {
                        alert(@json(__('ui.please_select_product')));
                        return false;
                    }

                    let duplicateCheck = checkDuplicateProduct(productId);
                    if (duplicateCheck.duplicate) {
                        alert(@json(__('ui.duplicate_purchase_product')).replace(':name', duplicateCheck.productName).replace(/\\n/g, '\n'));
                        return false;
                    }

                    if (!qty || parseFloat(qty) <= 0) {
                        alert(@json(__('ui.valid_quantity')));
                        return false;
                    }
                    if (!price || parseFloat(price) <= 0) {
                        alert(@json(__('ui.valid_unit_price')));
                        return false;
                    }
                    if (!rate || parseFloat(rate) <= 0) {
                        alert(@json(__('ui.valid_exchange_rate')));
                        return false;
                    }

                    let postData = {
                        _token: '{{ csrf_token() }}',
                        purchase_id: $('[name="purchase_id"]').val(),
                        product_id: productId,
                        qty: qty,
                        unit_price: price,
                        batch_no: $('#item_batch_no').val(),
                        rate: rate,
                        remarks: remarks,
                        unit: $('#item_unit').val(),
                        kg_per_roll: $('#item_kg_per_roll').val() || ''
                    };

                    $.ajax({
                        url: '{{ route('admin.purchase-items.store') }}',
                        method: 'POST',
                        data: postData,
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert(response.message || @json(__('ui.error_adding_item')));
                            }
                        },
                        error: function(xhr) {
                            let errorMsg = 'Error adding item';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }
                            alert(errorMsg);
                        }
                    });

                    return false;
                });

                // Add Expense
                $('#addExpenseForm').off('submit').on('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    let agentId = $('[name="agent_id"]').val();
                    let currencyId = $('[name="currency_id"]').val();
                    let amount = $('#expense_amount').val();
                    let rate = $('#expense_rate').val();
                    let description = $('#expense_description').val();

                    if (!agentId) {
                        alert(@json(__('ui.please_select_agent')));
                        return false;
                    }
                    if (!currencyId) {
                        alert(@json(__('ui.please_select_currency')));
                        return false;
                    }
                    if (!amount || parseFloat(amount) <= 0) {
                        alert(@json(__('ui.valid_amount')));
                        return false;
                    }
                    if (!rate || parseFloat(rate) <= 0) {
                        alert(@json(__('ui.valid_exchange_rate')));
                        return false;
                    }

                    let postData = {
                        _token: '{{ csrf_token() }}',
                        purchase_id: $('[name="purchase_id"]').val(),
                        agent_id: agentId,
                        currency_id: currencyId,
                        amount: amount,
                        rate: rate,
                        description: description
                    };

                    $.ajax({
                        url: '{{ route('admin.purchase-expenses.store') }}',
                        method: 'POST',
                        data: postData,
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert(response.message || @json(__('ui.error_adding_expense')));
                            }
                        },
                        error: function(xhr) {
                            let errorMsg = 'Error adding expense';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }
                            alert(errorMsg);
                        }
                    });

                    return false;
                });

                // Edit item
                $(document).on('click', '.edit-item', function(e) {
                    e.preventDefault();
                    let id = $(this).data('id');
                    let unit = $(this).data('unit');
                    let kgPerRoll = $(this).data('kg_per_roll');
                    let qty = $(this).data('qty');
                    let url = '{{ route('admin.purchase-items.show', ':id') }}'.replace(':id', id);

                    $.ajax({
                        url: url,
                        method: 'GET',
                        success: function(data) {
                            $('#edit_item_id').val(data.id);
                            $('#edit_qty').val(data.qty);
                            $('#edit_unit_price').val(data.unit_price);
                            $('#edit_rate').val(data.rate);
                            $('#edit_remarks').val(data.remarks || '');
                            $('#edit_unit').val(unit);
                            $('#edit_kg_per_roll').val(kgPerRoll || '');

                            if (unit === 'roll' && kgPerRoll && parseFloat(kgPerRoll) > 0) {
                                var totalW = (parseFloat(qty) * parseFloat(kgPerRoll)).toFixed(2);
                                $('#edit_kg_per_roll_display').text(kgPerRoll);
                                $('#edit_total_weight_display').text(totalW + ' kg');
                                $('#edit_roll_info').removeClass('d-none');
                            } else if (unit === 'roll') {
                                $('#edit_kg_per_roll_display').text('—');
                                $('#edit_total_weight_display').text('{{ __("ui.roll_weight_missing") }}');
                                $('#edit_roll_info').removeClass('d-none');
                            } else {
                                $('#edit_roll_info').addClass('d-none');
                            }

                            $('#editItemModal').modal('show');
                        },
                        error: function(xhr) {
                            alert(@json(__('ui.error_loading_item')) + ' ' + (xhr.responseJSON?.message ||
                                'Unknown error'));
                        }
                    });
                });

                // Update item
                $('#editItemForm').off('submit').on('submit', function(e) {
                    e.preventDefault();
                    let id = $('#edit_item_id').val();
                    let url = '{{ route('admin.purchase-items.update', ':id') }}'.replace(':id', id);
                    let formData = $(this).serialize();

                    $.ajax({
                        url: url,
                        method: 'PUT',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert(response.message || @json(__('ui.error_updating_item')));
                            }
                        },
                        error: function(xhr) {
                            alert(@json(__('ui.error_updating_item')) + ' ' + (xhr.responseJSON?.message ||
                                'Unknown error'));
                        }
                    });
                });

                // Delete item
                $(document).on('click', '.delete-item', function() {
                    if (confirm(@json(__('ui.confirm_delete_item')))) {
                        let id = $(this).data('id');
                        let url = '{{ route('admin.purchase-items.destroy', ':id') }}'.replace(':id', id);

                        $.ajax({
                            url: url,
                            method: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert(response.message || @json(__('ui.error_deleting_item')));
                                }
                            },
                            error: function(xhr) {
                                alert(@json(__('ui.error_deleting_item')) + ' ' + (xhr.responseJSON?.message ||
                                    'Unknown error'));
                            }
                        });
                    }
                });

                // Delete expense
                $(document).on('click', '.delete-expense', function() {
                    if (confirm(@json(__('ui.confirm_delete_expense')))) {
                        let id = $(this).data('id');
                        let url = '{{ route('admin.purchase-expenses.destroy', ':id') }}'.replace(':id',
                            id);

                        $.ajax({
                            url: url,
                            method: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert(response.message || @json(__('ui.error_deleting_expense')));
                                }
                            },
                            error: function(xhr) {
                                alert(@json(__('ui.error_deleting_expense')) + ' ' + (xhr.responseJSON?.message ||
                                    'Unknown error'));
                            }
                        });
                    }
                });

                calculateItemValues();
            @endif
        });

        function confirmDelete() {
            if (confirm(@json(__('ui.confirm_delete_purchase_detail')).replace(/\\n/g, '\n'))) {
                document.getElementById('deleteOrderForm').submit();
            }
        }
    </script>
@endsection
