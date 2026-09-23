@extends('layouts.admin.base')

@section('title', __('ui.purchase_orders'))

@section('css')
    <link href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>
        #createPurchaseModal:target {
            display: block;
            opacity: 1;
            background: rgba(15, 23, 42, 0.5);
        }

        #createPurchaseModal:target .modal-dialog {
            transform: none;
        }
    </style>

    <link rel="stylesheet" href="{{ asset('css/admin-index.css') }}">
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4 erp-index-ui">

        {{-- ============================================================
        PAGE HEADER
        ============================================================ --}}
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-cart-check me-2"></i>
                        {{ __('ui.purchase') }} <span class="accent">{{ __('ui.orders') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-truck me-1"></i>
                        {{ __('ui.purchase_orders_subtitle') }}
                    </p>
                </div>

                <a href="#createPurchaseModal" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPurchaseModal">
                    <i class="bi bi-plus-lg me-2"></i>
                    {{ __('ui.new_purchase_order') }}
                </a>
            </div>
        </div>

        {{-- ============================================================
        STATS CARDS
        ============================================================ --}}
        <div class="stats-grid">
            <div class="stat-card purple-accent">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="stat-value">
                        {{ $stats['total'] ?? 0 }}
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.total_orders') }}</div>
            </div>

            <div class="stat-card yellow-accent">
                <div class="stat-top">
                    <div class="stat-icon yellow">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div class="stat-value">
                        {{ $stats['draft'] ?? 0 }}
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.draft_orders') }}</div>
            </div>

            <div class="stat-card blue-accent">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <i class="bi bi-send"></i>
                    </div>
                    <div class="stat-value">
                        {{ $stats['shipping'] ?? 0 }}
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.shipping') }}</div>
            </div>

            <div class="stat-card green-accent">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div class="stat-value">
                        {{ $stats['arrived'] ?? 0 }}
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.arrived') }}</div>
            </div>
        </div>

        {{-- ============================================================
        FILTER BAR
        ============================================================ --}}
        <div class="filter-bar">
            {{-- Status Filter Buttons --}}
            <div class="filter-group">
                <span class="filter-label"><i class="bi bi-funnel me-1"></i> {{ __('ui.status') }}:</span>
                <div class="status-filter-group">
                    <a href="{{ route('admin.purchase-orders.index', ['status' => 'all']) }}"
                        class="status-filter-btn {{ request('status', 'all') == 'all' ? 'active-all' : '' }}">
                        <i class="bi bi-circle-fill"></i> {{ __('ui.all') }}
                    </a>
                    <a href="{{ route('admin.purchase-orders.index', ['status' => 'draft']) }}"
                        class="status-filter-btn {{ request('status') == 'draft' ? 'active-draft' : '' }}">
                        <i class="bi bi-circle-fill"></i> {{ __('ui.draft') }}
                    </a>
                    <a href="{{ route('admin.purchase-orders.index', ['status' => 'shipping']) }}"
                        class="status-filter-btn {{ request('status') == 'shipping' ? 'active-shipping' : '' }}">
                        <i class="bi bi-circle-fill"></i> {{ __('ui.shipping') }}
                    </a>
                    <a href="{{ route('admin.purchase-orders.index', ['status' => 'arrived']) }}"
                        class="status-filter-btn {{ request('status') == 'arrived' ? 'active-arrived' : '' }}">
                        <i class="bi bi-circle-fill"></i> {{ __('ui.arrived') }}
                    </a>
                </div>
            </div>

            {{-- Search & Filter Form --}}
            <form action="{{ route('admin.purchase-orders.index') }}" method="GET"
                class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                @if (request('status') && request('status') != 'all')
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif

                <div class="input-group" style="min-width: 200px; width: auto;">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="{{ __('ui.search_orders') }}"
                        value="{{ request('search') }}" style="border-left: none; box-shadow: none; min-width: 180px;">
                    @if (request('search'))
                        <a href="{{ route('admin.purchase-orders.index', request()->except('search')) }}"
                            class="btn btn-outline-secondary border-start-0" style="border-left: none;">
                            <i class="bi bi-x-circle-fill"></i>
                        </a>
                    @endif
                </div>

                <button type="submit" class="btn-filter btn-primary" style="white-space: nowrap;">
                    <i class="bi bi-search me-1"></i> {{ __('ui.search') }}
                </button>

                @if (request()->filled('search') || (request()->filled('status') && request('status') !== 'all'))
                    <a href="{{ route('admin.purchase-orders.index') }}" class="btn-filter btn-outline-secondary"
                        style="white-space: nowrap;">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('ui.reset') }}
                    </a>
                @endif
            </form>
        </div>

        {{-- ============================================================
        MAIN TABLE
        ============================================================ --}}
        <div class="table-card">
            <div class="card-header-custom">
                <h5>
                    <i class="bi bi-table"></i> {{ __('ui.purchase_orders_list') }}
                    <span class="header-badge">
                        <i class="bi bi-database me-1"></i> {{ $purchaseOrders->total() }} {{ __('ui.orders') }}
                    </span>
                </h5>
                <div class="d-flex gap-2">
                    <span class="header-badge" style="background: var(--gray-100); color: var(--gray-600);">
                        <i class="bi bi-circle-fill me-1" style="color: var(--gray-400);"></i> {{ __('ui.draft') }}
                    </span>
                    <span class="header-badge" style="background: var(--warning-bg); color: #92400e;">
                        <i class="bi bi-circle-fill me-1" style="color: var(--warning);"></i> {{ __('ui.shipping') }}
                    </span>
                    <span class="header-badge" style="background: var(--success-bg); color: #065f46;">
                        <i class="bi bi-circle-fill me-1" style="color: var(--success);"></i> {{ __('ui.arrived') }}
                    </span>
                </div>
            </div>

            <div class="table-responsive-custom">
                <table class="table-ledger">
                    <thead>
                        <tr>
                            <th style="min-width: 80px;">{{ __('ui.po_number') }}</th>
                            <th style="min-width: 150px;">{{ __('ui.supplier') }}</th>
                            <th class="text-center" style="min-width: 80px;">{{ __('ui.currency') }}</th>
                            <th class="text-center" style="min-width: 110px;">{{ __('ui.status') }}</th>
                            <th class="text-center" style="min-width: 80px;">{{ __('ui.items') }}</th>
                            <th class="text-center" style="min-width: 120px;">{{ __('ui.purchase_date') }}</th>
                            <th class="text-center" style="min-width: 120px;">{{ __('ui.arrival_date') }}</th>
                            <th class="text-end" style="min-width: 130px;">{{ __('ui.total') }} · Order Currency</th>
                            <th class="text-end" style="min-width: 120px;">{{ __('ui.expense_usd') }}</th>
                            <th class="text-end" style="min-width: 120px;">{{ __('ui.usd_amount') }}</th>
                            <th class="text-end" style="width: 1%;">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseOrders as $order)
                            @php
                                // Calculate per-order stats
                                $itemCount = $order->items->count();
                                $totalQty = $order->items->sum('qty');
                                $totalExpenseUsd = $order->expenses->sum('usd_amount');
                                $currencySymbol = $order->currency->symbol ?? '$';
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.purchase-orders.show', $order->id) }}" class="po-link">
                                        {{ $order->purchase_no }}
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold" style="color: var(--gray-700);">
                                        {{ $order->supplier->name ?? '-' }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="currency-code">
                                        {{ $order->currency->code ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusConfig = [
                                            'draft' => ['class' => 'draft', 'icon' => 'bi-pencil-square'],
                                            'shipping' => ['class' => 'shipping', 'icon' => 'bi-send'],
                                            'arrived' => ['class' => 'arrived', 'icon' => 'bi-check2-circle'],
                                        ];
                                        $config = $statusConfig[$order->status] ?? [
                                            'class' => 'draft',
                                            'icon' => 'bi-question-circle',
                                        ];
                                    @endphp
                                    <span class="status-badge {{ $config['class'] }}">
                                        <i class="bi {{ $config['icon'] }}"></i>
                                        {{ __('ui.' . ($order->status ?? 'unknown')) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span style="font-weight: 600; color: var(--gray-700);">
                                        {{ $itemCount }}
                                        <span style="font-size: 0.65rem; color: var(--gray-400);">
                                            ({{ number_format($totalQty) }})
                                        </span>
                                    </span>
                                </td>
                                <td class="text-center" style="color: var(--gray-600); font-size: 0.85rem;">
                                    {{ $order->purchase_date ? date('Y-m-d', strtotime($order->purchase_date)) : '-' }}
                                </td>
                                <td class="text-center" style="color: var(--gray-600); font-size: 0.85rem;">
                                    {{ $order->arrival_date ? date('Y-m-d', strtotime($order->arrival_date)) : '-' }}
                                </td>
                                <td class="text-end">
                                    <span class="amount-value" style="font-weight: 700; color: var(--gray-900);">
                                        {{ $currencySymbol }}{{ number_format($order->grand_total ?? 0, 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="amount-usd" style="color: #dc2626; font-weight: 600;">
                                        ${{ number_format($totalExpenseUsd, 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="amount-usd" style="font-weight: 700; color: var(--gray-900);">
                                        ${{ number_format($order->usd_grand_total ?? 0, 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a href="{{ route('admin.purchase-orders.show', $order->id) }}"
                                            class="action-btn" title="{{ __('ui.view_details') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.purchase-orders.edit', $order->id) }}"
                                            class="action-btn" title="{{ __('ui.edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.purchase-orders.destroy', $order->id) }}"
                                            method="POST" style="display: inline-block;"
                                            onsubmit="return confirm(@js(__('ui.confirm_delete_purchase')));">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn text-danger" title="{{ __('ui.delete') }}"
                                                style="border: none; background: transparent; cursor: pointer; padding: 0; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11">
                                    <div class="empty-state">
                                        <i class="bi bi-inboxes"></i>
                                        <p>{{ __('ui.no_purchase_orders_found') }}</p>
                                        <p class="sub-text">{{ __('ui.adjust_search_filters') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ─── Pagination ─── --}}
            @if ($purchaseOrders->hasPages())
                <div class="pagination-wrap">
                    <span class="info-text">
                        {{ __('ui.showing') }} {{ $purchaseOrders->firstItem() }} – {{ $purchaseOrders->lastItem() }}
                        {{ __('ui.of') }} {{ $purchaseOrders->total() }} {{ __('ui.entries') }}
                    </span>
                    {{ $purchaseOrders->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

    </div>

    {{-- ============================================================
    CREATE PURCHASE MODAL
    ============================================================ --}}
    <div class="modal fade" id="createPurchaseModal" tabindex="-1" data-open-on-load="{{ $errors->any() ? 'true' : 'false' }}">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.purchase-orders.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle"></i> {{ __('ui.create_purchase_order') }}
                        </h5>
                        <a href="#" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></a>
                    </div>

                    <div class="modal-body">
                        @if ($errors->any())
                            <div class="alert alert-danger mb-4" style="border-radius: 12px;">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.purchase_number') }}</label>
                            <input type="text" name="purchase_no" class="form-control" value="{{ $nextPurchaseNo }}"
                                readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.supplier') }}</label>
                            <select name="supplier_id" class="form-select" required>
                                <option value="">{{ __('ui.select_supplier') }}</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.purchase_currency') }}</label>
                            <select name="currency_id" class="form-select" required>
                                <option value="">{{ __('ui.select_currency') }}</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}"
                                        {{ old('currency_id') == $currency->id ? 'selected' : '' }}>
                                        {{ $currency->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <a href="#" class="btn btn-light" data-bs-dismiss="modal">{{ __('ui.cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('ui.create_purchase') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

