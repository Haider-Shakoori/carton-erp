{{-- resources/views/admin/stock/stock-in.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Stock IN - ' . $product->name)

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}">
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
    <style>
        /* ... (previous styles remain the same) ... */

        /* New style for unit cost display */
        .unit-cost-detail {
            font-size: 0.7rem;
            color: var(--gray-500);
            display: block;
            font-weight: 400;
        }

        .unit-cost-detail .expense-part {
            color: var(--primary);
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">

        {{-- ============================================================
        PAGE HEADER - Same as before
        ============================================================ --}}
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-arrow-down-circle me-2" style="color: #10b981;"></i>
                        Stock <span class="accent">IN</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-box-seam me-1"></i>
                        {{ $product->name }} · Purchase Orders that received this product
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
        PRODUCT INFO CARD - Same as before
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
        STATS CARDS - Updated with Cost with Expenses
        ============================================================ --}}
        <div class="stats-grid">
            <div class="stat-card green-accent">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-box"></i>
                    </div>
                    <div class="stat-value">
                        {{ number_format($totalPurchased, 2) }}
                    </div>
                </div>
                <div class="stat-label">Total Purchased ({{ $product->unit ?? 'unit' }})</div>
            </div>

            <div class="stat-card purple-accent">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="stat-value">
                        <span class="currency">$</span>{{ number_format($totalCost, 2) }}
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.total_cost_usd') }}</div>
            </div>

            <div class="stat-card blue-accent">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <i class="bi bi-calculator"></i>
                    </div>
                    <div class="stat-value">
                        <span class="currency">$</span>{{ number_format($totalCostWithExpenses ?? $totalCost, 2) }}
                    </div>
                </div>
                <div class="stat-label">Total Cost + Expenses</div>
            </div>

            <div class="stat-card yellow-accent">
                <div class="stat-top">
                    <div class="stat-icon yellow">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div class="stat-value">
                        {{ $purchaseItems->unique('purchase_id')->count() }}
                    </div>
                </div>
                <div class="stat-label">Total POs</div>
            </div>
        </div>

        {{-- ============================================================
        FILTER BAR - Same as before
        ============================================================ --}}
        <div class="filter-bar">
            <form action="{{ route('admin.stock.stock-in', $product->id) }}" method="GET"
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
                    <a href="{{ route('admin.stock.stock-in', $product->id) }}" class="btn-filter btn-filter-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('ui.reset') }}
                    </a>
                @endif
            </form>
        </div>

        {{-- ============================================================
        PURCHASE ORDERS TABLE - Updated with Unit Cost
        ============================================================ --}}
        <div class="table-card">
            <div class="card-header-custom">
                <h5>
                    <i class="bi bi-receipt"></i> {{ __('ui.purchase_orders') }}
                    <span class="header-badge">
                        <i class="bi bi-database me-1"></i> {{ $purchaseItems->count() }} batches
                    </span>
                </h5>
                <div class="d-flex gap-2">
                    <span class="header-badge" style="background: var(--success-bg); color: var(--success);">
                        <i class="bi bi-check-circle me-1"></i> In Stock
                    </span>
                    <span class="header-badge" style="background: var(--danger-bg); color: var(--danger);">
                        <i class="bi bi-exclamation-circle me-1"></i> Depleted
                    </span>
                </div>
            </div>

            <div class="table-responsive-custom">
                <table class="table-ledger">
                    <thead>
                        <tr>
                            <th style="min-width: 100px;">{{ __('ui.po_number') }}</th>
                            <th style="min-width: 140px;">{{ __('ui.supplier') }}</th>
                            <th style="min-width: 100px;">{{ __('ui.batch_number') }}</th>
                            <th class="text-center" style="min-width: 80px;">{{ __('ui.purchased') }}</th>
                            <th class="text-center" style="min-width: 80px;">{{ __('ui.sold') }}</th>
                            <th class="text-center" style="min-width: 80px;">{{ __('ui.wasted') }}</th>
                            <th class="text-center" style="min-width: 100px;">{{ __('ui.available') }}</th>
                            <th class="text-end" style="min-width: 130px;">Unit Cost</th>
                            <th class="text-end" style="min-width: 140px;">Unit Cost + Exp</th>
                            <th class="text-end" style="min-width: 120px;">{{ __('ui.total_cost') }}</th>
                            <th class="text-center" style="min-width: 110px;">{{ __('ui.date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseItems as $item)
                            @php
                                // These values are now calculated in the controller
                                $costPerUnit = $item->usd_unit_price ?? 0;
                                $expensePerUnit = $item->expense_per_unit ?? 0;
                                $costPerUnitWithExpenses = $item->usd_unit_price_with_expenses ?? $costPerUnit;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.purchase-orders.show', $item->purchase_id) }}"
                                        class="po-link">
                                        {{ $item->purchase->purchase_no ?? 'N/A' }}
                                    </a>
                                </td>
                                <td>
                                    <div style="font-weight: 500; color: var(--gray-700);">
                                        {{ $item->purchase->supplier->name ?? 'N/A' }}
                                    </div>
                                </td>
                                <td>
                                    <span class="batch-no-badge">{{ $item->batch_no ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <div class="num-cell text-center">
                                        <i class="bi bi-arrow-down-circle num-icon"></i>
                                        {{ number_format($item->qty, 2) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="num-cell green text-center">
                                        <i class="bi bi-arrow-up-circle num-icon"></i>
                                        {{ number_format($item->qty_sold, 2) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="num-cell red text-center">
                                        <i class="bi bi-trash num-icon"></i>
                                        {{ number_format($item->qty_wasted, 2) }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if ($item->qty_available > 0)
                                        <span class="stock-badge in-stock">
                                            <i class="bi bi-circle-fill"></i>
                                            {{ number_format($item->qty_available, 2) }}
                                        </span>
                                    @else
                                        <span class="stock-badge out-of-stock">
                                            <i class="bi bi-circle-fill"></i>
                                            Depleted
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="num-cell text-end" style="justify-content: flex-end;">
                                        <span class="currency-sm">$</span>
                                        {{ number_format($costPerUnit, 1) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="num-cell text-end" style="justify-content: flex-end;">
                                        <span class="currency-sm">$</span>
                                        {{ number_format($costPerUnitWithExpenses, 1) }}
                                        @if ($expensePerUnit > 0)
                                            <span class="unit-cost-detail">
                                                +${{ number_format($expensePerUnit, 1) }} exp
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="num-cell text-end" style="justify-content: flex-end; font-weight: 600;">
                                        <span class="currency-sm">$</span>
                                        {{ number_format($item->usd_total, 2) }}
                                        @if (isset($item->total_cost_with_expenses) && $item->total_cost_with_expenses > $item->usd_total)
                                            <span class="unit-cost-detail">
                                                +${{ number_format($item->total_cost_with_expenses - $item->usd_total, 2) }}
                                                exp
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center" style="color: var(--gray-500); font-size: 0.8rem;">
                                    {{ $item->created_at->format('M d, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11">
                                    <div class="empty-state">
                                        <i class="bi bi-inboxes"></i>
                                        <p>No purchase orders found for this product</p>
                                        <p class="sub-text">Try adjusting your date filters or search criteria</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ─── Pagination ─── --}}
            @if (method_exists($purchaseItems, 'hasPages') && $purchaseItems->hasPages())
                <div class="pagination-wrap">
                    <span class="info-text">
                        Showing {{ $purchaseItems->firstItem() }} – {{ $purchaseItems->lastItem() }}
                        of {{ $purchaseItems->total() }} entries
                    </span>
                    {{ $purchaseItems->links('pagination::bootstrap-5') }}
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
