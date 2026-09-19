{{-- resources/views/admin/sales/index.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.sales_orders'))

@section('css')
    <link href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}"
          rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">

        {{-- PAGE HEADER --}}
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-cart-plus me-2"></i>
                        {{ __('ui.sales') }} <span class="accent">{{ __('ui.orders') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-box-arrow-up-right me-1"></i>
                        Manage customer sales and track profitability
                    </p>
                </div>

                @can('create sales')
                <a href="{{ route('admin.sales.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>
                    New Sale Order
                </a>
                @endcan
            </div>
        </div>

        {{-- STATS CARDS --}}
        <div class="stats-grid">
            <div class="stat-card purple-accent">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                </div>
                <div class="stat-label">{{ __('ui.total_orders') }}</div>
            </div>

            <div class="stat-card yellow-accent">
                <div class="stat-top">
                    <div class="stat-icon yellow">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div class="stat-value">{{ $stats['draft'] ?? 0 }}</div>
                </div>
                <div class="stat-label">{{ __('ui.draft') }}</div>
            </div>

            <div class="stat-card blue-accent">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div class="stat-value">{{ $stats['confirmed'] ?? 0 }}</div>
                </div>
                <div class="stat-label">{{ __('ui.confirmed') }}</div>
            </div>

            <div class="stat-card green-accent">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div class="stat-value">{{ ($stats['shipped'] ?? 0) + ($stats['delivered'] ?? 0) }}</div>
                </div>
                <div class="stat-label">Shipped/Delivered</div>
            </div>
        </div>

        {{-- FILTER BAR --}}
        <div class="filter-bar">
            <div class="filter-group">
                <span class="filter-label"><i class="bi bi-funnel me-1"></i> {{ __('ui.status_colon') }}</span>
                <div class="status-filter-group">
                    <a href="{{ route('admin.sales.index', ['status' => 'all']) }}"
                       class="status-filter-btn {{ request('status', 'all') == 'all' ? 'active-all' : '' }}">
                        <i class="bi bi-circle-fill"></i> {{ __('ui.all') }}
                    </a>
                    <a href="{{ route('admin.sales.index', ['status' => 'draft']) }}"
                       class="status-filter-btn {{ request('status') == 'draft' ? 'active-draft' : '' }}">
                        <i class="bi bi-circle-fill"></i> {{ __('ui.draft') }}
                    </a>
                    <a href="{{ route('admin.sales.index', ['status' => 'confirmed']) }}"
                       class="status-filter-btn {{ request('status') == 'confirmed' ? 'active-confirmed' : '' }}">
                        <i class="bi bi-circle-fill"></i> {{ __('ui.confirmed') }}
                    </a>
                    <a href="{{ route('admin.sales.index', ['status' => 'shipped']) }}"
                       class="status-filter-btn {{ request('status') == 'shipped' ? 'active-shipped' : '' }}">
                        <i class="bi bi-circle-fill"></i> Shipped
                    </a>
                    <a href="{{ route('admin.sales.index', ['status' => 'delivered']) }}"
                       class="status-filter-btn {{ request('status') == 'delivered' ? 'active-delivered' : '' }}">
                        <i class="bi bi-circle-fill"></i> Delivered
                    </a>
                </div>
            </div>

            {{-- Search --}}
            <form action="{{ route('admin.sales.index') }}" method="GET"
                  class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                @if (request('status') && request('status') != 'all')
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif

                <div class="input-group" style="min-width: 200px; width: auto;">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="{{ __('ui.search_orders') }}"
                           value="{{ request('search') }}" style="border-left: none; box-shadow: none;">
                    @if (request('search'))
                        <a href="{{ route('admin.sales.index', request()->except('search')) }}"
                           class="btn btn-outline-secondary border-start-0" style="border-left: none;">
                            <i class="bi bi-x-circle-fill"></i>
                        </a>
                    @endif
                </div>

                <button type="submit" class="btn-filter btn-primary">
                    <i class="bi bi-search me-1"></i> {{ __('ui.search') }}
                </button>

                @if (request()->anyFilled(['search', 'status']) && request('status') != 'all')
                    <a href="{{ route('admin.sales.index') }}" class="btn-filter btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('ui.reset') }}
                    </a>
                @endif
            </form>
        </div>

        {{-- MAIN TABLE --}}
        <div class="table-card">
            <div class="card-header-custom">
                <h5>
                    <i class="bi bi-table"></i> Sales Orders List
                    <span class="header-badge">
                        <i class="bi bi-database me-1"></i> {{ $sales->total() }} orders
                    </span>
                </h5>
            </div>

            <div class="table-responsive-custom">
                <table class="table-ledger">
                    <thead>
                    <tr>
                        <th style="min-width: 80px;">SO #</th>
                        <th style="min-width: 150px;">{{ __('ui.customer') }}</th>
                        <th class="text-center" style="min-width: 80px;">{{ __('ui.currency') }}</th>
                        <th class="text-center" style="min-width: 110px;">{{ __('ui.status') }}</th>
                        <th class="text-center" style="min-width: 80px;">{{ __('ui.items') }}</th>
                        <th class="text-center" style="min-width: 120px;">{{ __('ui.sale_date') }}</th>
                        <th class="text-end" style="min-width: 130px;">{{ __('ui.total') }}</th>
                        <th class="text-end" style="min-width: 120px;">USD Total</th>
                        <th class="text-end" style="min-width: 120px;">{{ __('ui.profit') }}</th>
                        <th class="text-end" style="width: 1%;">{{ __('ui.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($sales as $order)
                        @php
                            $totalRevenueUsd = (float) ($order->usd_grand_total ?? 0);
                            $totalCost = $order->realized_cost_usd ?? $order->items->sum('total_cost_usd');
                            $totalProfit = $totalRevenueUsd - $totalCost;
                            $profitMargin = $totalRevenueUsd > 0 ? ($totalProfit / $totalRevenueUsd) * 100 : 0;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.sales.show', $order->id) }}" class="po-link">
                                    {{ $order->sale_no }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold" style="color: var(--gray-700);">
                                    {{ $order->customer->name ?? '-' }}
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
                                        'confirmed' => ['class' => 'confirmed', 'icon' => 'bi-check2-circle'],
                                        'shipped' => ['class' => 'shipped', 'icon' => 'bi-truck'],
                                        'delivered' => ['class' => 'delivered', 'icon' => 'bi-check2-all'],
                                    ];
                                    $config = $statusConfig[$order->status] ?? [
                                        'class' => 'draft',
                                        'icon' => 'bi-question-circle',
                                    ];
                                @endphp
                                <span class="status-badge {{ $config['class'] }}">
                                        <i class="bi {{ $config['icon'] }}"></i>
                                        {{ ucfirst($order->status ?? 'Unknown') }}
                                    </span>
                            </td>
                            <td class="text-center">
                                    <span style="font-weight: 600; color: var(--gray-700);">
                                        {{ $order->items->count() }}
                                    </span>
                            </td>
                            <td class="text-center" style="color: var(--gray-600); font-size: 0.85rem;">
                                {{ $order->sale_date ? date('Y-m-d', strtotime($order->sale_date)) : '-' }}
                            </td>
                            <td class="text-end">
                                    <span class="amount-value" style="font-weight: 700; color: var(--gray-900);">
                                        {{ $order->currency->symbol ?? '$' }}{{ number_format($order->grand_total ?? 0, 2) }}
                                    </span>
                            </td>
                            <td class="text-end">
                                    <span style="font-weight: 700; color: var(--gray-900);">
                                        ${{ number_format($order->usd_grand_total ?? 0, 2) }}
                                    </span>
                            </td>
                            <td class="text-end">
                                    <span
                                        style="font-weight: 700; color: {{ $totalProfit >= 0 ? 'var(--success)' : 'var(--danger)' }};">
                                        ${{ number_format($totalProfit, 2) }}
                                        <span style="font-size: 0.65rem; color: var(--gray-400);">
                                            ({{ number_format($profitMargin, 1) }}%)
                                        </span>
                                    </span>
                            </td>
                            <td class="text-end">
                                <div class="action-buttons">
                                    <a href="{{ route('admin.sales.show', $order->id) }}" class="action-btn"
                                       title="{{ __('ui.view_details') }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('delete sales')
                                    <form action="{{ route('admin.sales.destroy', $order->id) }}" method="POST"
                                          style="display: inline-block;"
                                          onsubmit="return confirm('Are you sure you want to delete sale #{{ $order->sale_no }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn text-danger" title="{{ __('ui.delete') }}"
                                                style="border: none; background: transparent; cursor: pointer; padding: 0; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <i class="bi bi-inboxes"></i>
                                    <p>No sales orders found</p>
                                    <p class="sub-text">Create your first sale order by clicking "New Sale Order"</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($sales->hasPages())
                <div class="pagination-wrap">
                    <span class="info-text">
                        Showing {{ $sales->firstItem() }} – {{ $sales->lastItem() }}
                        of {{ $sales->total() }} entries
                    </span>
                    {{ $sales->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

    </div>
@endsection
