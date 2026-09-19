{{-- resources/views/admin/stock/stock-out.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Stock OUT - ' . $product->name)

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}">
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
    <style>
        .stat-card.yellow-accent::before {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .stat-card .stat-icon.yellow {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #d97706;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);
        }

        .stat-card.purple-accent::before {
            background: linear-gradient(90deg, #8b5cf6, #a78bfa);
        }

        .stat-card .stat-icon.purple {
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            color: #7c3aed;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.15);
        }

        .stat-card.blue-accent::before {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
        }

        .stat-card .stat-icon.blue {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #2563eb;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }

        .stat-card.green-accent::before {
            background: linear-gradient(90deg, #10b981, #34d399);
        }

        .stat-card .stat-icon.green {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #059669;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        }

        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            padding: 0.75rem 1rem;
            background: white;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .filter-bar .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-bar .filter-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--gray-400);
        }

        .filter-bar .form-control,
        .filter-bar .form-select {
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-200);
            padding: 0.35rem 0.75rem;
            font-size: 0.8125rem;
            transition: all 0.3s ease;
            min-width: 140px;
        }

        .filter-bar .form-control:focus,
        .filter-bar .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
        }

        .filter-bar .btn-filter {
            padding: 0.35rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
        }

        .filter-bar .btn-filter-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .filter-bar .btn-filter-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .filter-bar .btn-filter-secondary {
            background: var(--gray-100);
            color: var(--gray-600);
        }

        .filter-bar .btn-filter-secondary:hover {
            background: var(--gray-200);
        }

        .date-input-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .date-input-group .date-separator {
            color: var(--gray-400);
            font-weight: 600;
        }

        .sale-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .sale-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .profit-positive {
            color: var(--success);
            font-weight: 700;
        }

        .profit-negative {
            color: var(--danger);
            font-weight: 700;
        }

        .profit-badge {
            font-size: 0.6875rem;
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-left: 0.25rem;
        }

        .profit-badge.positive {
            background: #d1fae5;
            color: #065f46;
        }

        .profit-badge.negative {
            background: #fecaca;
            color: #991b1b;
        }

        .num-cell {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
        }

        .num-cell .num-icon {
            font-size: 0.7rem;
            opacity: 0.6;
        }

        .num-cell.green .num-icon {
            color: var(--success);
        }

        .num-cell.red .num-icon {
            color: var(--danger);
        }

        .num-cell.text-end {
            justify-content: flex-end;
        }

        .currency-sm {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray-400);
        }

        .empty-state {
            text-align: center;
            padding: 2.5rem 1rem;
        }

        .empty-state i {
            font-size: 2.5rem;
            color: var(--gray-300);
            margin-bottom: 0.75rem;
            display: block;
        }

        .empty-state p {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.9375rem;
            margin: 0;
        }

        .empty-state .sub-text {
            color: var(--gray-400);
            font-size: 0.8125rem;
            margin-top: 0.25rem;
        }

        .customer-name {
            font-weight: 500;
            color: var(--gray-700);
        }

        .po-ref {
            font-size: 0.75rem;
            color: var(--gray-400);
        }

        .batch-no-badge {
            font-weight: 600;
            color: var(--text-primary);
            background: var(--gray-100);
            padding: 0.1rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">

        {{-- ============================================================
        PAGE HEADER
        ============================================================ --}}
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-arrow-up-circle me-2" style="color: #f59e0b;"></i>
                        Stock <span class="accent">OUT</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-box-seam me-1"></i>
                        {{ $product->name }} · Sales that used this product
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.stock.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        {{-- ============================================================
        PRODUCT INFO CARD
        ============================================================ --}}
        <div class="table-card" style="margin-bottom: 30px;">
            <div class="card-header-custom">
                <h5>
                    <i class="bi bi-info-circle"></i> Product Information
                </h5>
                <div class="d-flex gap-2">
                    <span class="header-badge" style="background: var(--primary-bg); color: var(--primary);">
                        <i class="bi bi-tag me-1"></i> {{ $product->category->name ?? 'Uncategorized' }}
                    </span>
                    <span class="header-badge" style="background: var(--success-bg); color: var(--success);">
                        <i class="bi bi-box me-1"></i> {{ $product->unit ?? 'unit' }}
                    </span>
                </div>
            </div>
            <div style="padding: 20px;">
                <div class="row align-items-center">
                    <div class="col-auto">
                        @if ($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                                style="width: 80px; height: 80px; object-fit: cover; border-radius: 12px; border: 2px solid var(--border-color);">
                        @else
                            <div
                                style="width: 80px; height: 80px; border-radius: 12px; background: var(--bg-secondary); display: flex; align-items: center; justify-content: center; font-size: 32px; color: var(--text-muted); border: 2px solid var(--border-color);">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        @endif
                    </div>
                    <div class="col">
                        <h3 style="font-weight: 700; color: var(--text-primary); margin: 0;">{{ $product->name }}</h3>
                        <div style="display: flex; flex-wrap: wrap; gap: 16px; margin-top: 4px;">
                            <span
                                style="display: flex; align-items: center; gap: 4px; color: var(--text-secondary); font-size: 13px;">
                                <i class="bi bi-hash" style="color: var(--primary);"></i> SKU: {{ $product->sku ?? 'N/A' }}
                            </span>
                            <span
                                style="display: flex; align-items: center; gap: 4px; color: var(--text-secondary); font-size: 13px;">
                                <i class="bi bi-box" style="color: var(--primary);"></i> Unit: {{ $product->unit ?? 'N/A' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
        STATS CARDS
        ============================================================ --}}
        <div class="stats-grid">
            <div class="stat-card yellow-accent">
                <div class="stat-top">
                    <div class="stat-icon yellow">
                        <i class="bi bi-box"></i>
                    </div>
                    <div class="stat-value">
                        {{ number_format($totalSold, 2) }}
                    </div>
                </div>
                <div class="stat-label">Total Sold ({{ $product->unit ?? 'unit' }})</div>
            </div>

            <div class="stat-card purple-accent">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="stat-value">
                        <span class="currency">$</span>{{ number_format($totalRevenue, 2) }}
                    </div>
                </div>
                <div class="stat-label">Total Revenue (USD)</div>
            </div>

            <div class="stat-card blue-accent">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <i class="bi bi-cart-plus"></i>
                    </div>
                    <div class="stat-value">
                        {{ $saleItems->count() }}
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.total_sales') }}</div>
            </div>

            <div class="stat-card green-accent">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-value">
                        {{ $saleItems->unique('sale.customer_id')->count() }}
                    </div>
                </div>
                <div class="stat-label">Unique Customers</div>
            </div>
        </div>

        {{-- ============================================================
        FILTER BAR
        ============================================================ --}}
        <div class="filter-bar">
            <form action="{{ route('admin.stock.stock-out', $product->id) }}" method="GET"
                class="d-flex flex-wrap align-items-center gap-3 w-100">
                <div class="filter-group">
                    <span class="filter-label"><i class="bi bi-funnel me-1"></i> {{ __('ui.filter') }}</span>
                </div>

                <div class="date-input-group">
                    <input type="text" name="from_date" class="form-control datepicker" placeholder="{{ __('ui.from_date') }}"
                        value="{{ request('from_date') }}" style="min-width: 130px;">
                    <span class="date-separator">→</span>
                    <input type="text" name="to_date" class="form-control datepicker" placeholder="{{ __('ui.to_date') }}"
                        value="{{ request('to_date') }}" style="min-width: 130px;">
                </div>

                <button type="submit" class="btn-filter btn-filter-primary">
                    <i class="bi bi-search me-1"></i> Apply
                </button>

                @if (request('from_date') || request('to_date'))
                    <a href="{{ route('admin.stock.stock-out', $product->id) }}" class="btn-filter btn-filter-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('ui.reset') }}
                    </a>
                @endif
            </form>
        </div>

        {{-- ============================================================
        SALES TABLE
        ============================================================ --}}
        <div class="table-card">
            <div class="card-header-custom">
                <h5>
                    <i class="bi bi-cart-plus"></i> {{ __('ui.sales') }}
                    <span class="header-badge">
                        <i class="bi bi-database me-1"></i> {{ $saleItems->count() }} sales
                    </span>
                </h5>
                <div class="d-flex gap-2">
                    <span class="header-badge" style="background: var(--success-bg); color: var(--success);">
                        <i class="bi bi-check-circle me-1"></i> {{ __('ui.profit') }}
                    </span>
                    <span class="header-badge" style="background: var(--danger-bg); color: var(--danger);">
                        <i class="bi bi-exclamation-circle me-1"></i> Loss
                    </span>
                </div>
            </div>

            <div class="table-responsive-custom">
                <table class="table-ledger">
                    <thead>
                        <tr>
                            <th style="min-width: 100px;">{{ __('ui.sale_number') }}</th>
                            <th style="min-width: 140px;">{{ __('ui.customer') }}</th>
                            <th style="min-width: 100px;">From PO</th>
                            <th style="min-width: 80px;">Batch</th>
                            <th class="text-center" style="min-width: 80px;">Qty Sold</th>
                            <th class="text-end" style="min-width: 110px;">{{ __('ui.unit_price') }}</th>
                            <th class="text-end" style="min-width: 120px;">{{ __('ui.total') }}</th>
                            <th class="text-end" style="min-width: 110px;">{{ __('ui.cost_per_unit_short') }}</th>
                            <th class="text-end" style="min-width: 120px;">{{ __('ui.profit') }}</th>
                            <th class="text-center" style="min-width: 110px;">{{ __('ui.date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($saleItems as $item)
                        @php
                            $isSale = $item->type === 'sale';
                            $isProduction = $item->type === 'production';
                            $currencySymbol = $item->currency_symbol ?? '$';
                            $totalCost = ($item->cost_per_unit ?? 0) * ($item->qty ?? 0);
                        @endphp
                        <tr>
                            <td>
                                @if($isSale && $item->sale_id)
                                    <a href="{{ route('admin.sales.show', $item->sale_id) }}" class="sale-link">
                                        {{ $item->reference }}
                                    </a>
                                @elseif($isProduction)
                                    <div>
                        <span class="fw-semibold">
                            <i class="bi bi-industry text-info me-1"></i>
                            {{ $item->reference }}
                        </span>
                                        @if($item->finished_product && $item->finished_product != 'N/A')
                                            <div class="small text-muted">
                                                <i class="bi bi-box me-1"></i>
                                                Produced: {{ $item->finished_product }}
                                                ({{ number_format($item->quantity_produced, 2) }} units)
                                            </div>
                                        @endif
                                        @if($item->status && $item->status != 'unknown')
                                            <span class="badge bg-{{ $item->status === 'completed' ? 'success' : ($item->status === 'in_progress' ? 'warning' : 'secondary') }} text-white" style="font-size: 9px;">
                                {{ ucfirst($item->status) }}
                            </span>
                                        @endif
                                    </div>
                                @else
                                    {{ $item->reference }}
                                @endif
                            </td>
                            <td>
                                <div class="customer-name">
                                    {{ $item->customer }}
                                    @if($isProduction)
                                        <span class="badge bg-info text-white ms-1" style="font-size: 9px;">
                            <i class="bi bi-boxes"></i> {{ __('ui.raw_material') }}
                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="po-ref">{{ $item->purchase_no ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="batch-no-badge">{{ $item->batch_no ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <div class="num-cell text-center">
                                    <i class="bi bi-arrow-up-circle num-icon" style="color: var(--warning);"></i>
                                    {{ number_format($item->qty, 2) }}
                                    @if($isProduction)
                                        <span class="text-muted" style="font-size: 9px;">used</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="num-cell text-end" style="justify-content: flex-end;">
                                    @if($isSale && $item->unit_price !== null)
                                        {{ $currencySymbol }}{{ number_format($item->unit_price, 2) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="num-cell text-end" style="justify-content: flex-end; font-weight: 600;">
                                    @if($isSale && $item->total !== null)
                                        {{ $currencySymbol }}{{ number_format($item->total, 2) }}
                                    @elseif($isProduction)
                                        <span style="color: #3730a3; font-weight: 700;">
                            ${{ number_format($totalCost, 2) }}
                        </span>
                                        <span class="text-muted" style="font-size: 9px;">(cost)</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="num-cell text-end" style="justify-content: flex-end;">
                                    <span class="currency-sm">$</span>
                                    <span @if($isProduction) style="color: #3730a3; font-weight: 600;" @endif>
                        {{ number_format($item->cost_per_unit ?? 0, 4) }}
                    </span>
                                    @if($isProduction)
                                        <span class="badge bg-primary text-white ms-1" style="font-size: 9px;">COST</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($isSale && $item->profit !== null)
                                    <div class="num-cell text-end" style="justify-content: flex-end; gap: 0.5rem;">
                        <span class="{{ $item->profit >= 0 ? 'profit-positive' : 'profit-negative' }}">
                            ${{ number_format($item->profit, 2) }}
                        </span>
                                        <span class="profit-badge {{ $item->profit_margin >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format($item->profit_margin, 1) }}%
                        </span>
                                    </div>
                                @elseif($isProduction)
                                    <div class="text-center">
                        <span class="badge bg-secondary" style="font-size: 10px;">
                            <i class="bi bi-arrow-right me-1"></i> To Production
                        </span>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center justify-content-center gap-2">
                    <span class="badge bg-{{ $item->color }} text-white" style="font-size: 10px;">
                        <i class="bi {{ $item->icon }} me-1"></i>
                        {{ $item->source }}
                    </span>
                                    <span style="color: var(--gray-500); font-size: 0.75rem;">
                        {{ $item->date->format('M d, Y') }}
                    </span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <i class="bi bi-inboxes"></i>
                                    <p>No stock out records found for this product</p>
                                    <p class="sub-text">
                                        This product hasn't been sold directly or used in production yet.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ─── Pagination ─── --}}
            @if (method_exists($saleItems, 'hasPages') && $saleItems->hasPages())
                <div class="pagination-wrap">
                    <span class="info-text">
                        Showing {{ $saleItems->firstItem() }} – {{ $saleItems->lastItem() }}
                        of {{ $saleItems->total() }} entries
                    </span>
                    {{ $saleItems->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

    </div>
@endsection

@section('scripts')
    <script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr('.datepicker', {
                dateFormat: 'Y-m-d',
                allowInput: true,
                maxDate: 'today'
            });
        });
    </script>
@endsection
