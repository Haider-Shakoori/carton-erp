{{-- resources/views/admin/sales/deleted.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Deleted Sales Orders')

@section('css')
    <link href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>
        .deleted-badge {
            background: #fecaca;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .restore-btn {
            color: #059669;
        }

        .restore-btn:hover {
            background: #d1fae5;
            color: #065f46;
        }

        .force-delete-btn {
            color: #dc2626;
        }

        .force-delete-btn:hover {
            background: #fecaca;
            color: #991b1b;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">

        {{-- PAGE HEADER --}}
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-trash3 me-2" style="color: #dc2626;"></i>
                        Deleted <span class="accent">{{ __('ui.sales_orders') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-clock-history me-1"></i>
                        View and manage soft-deleted sales orders
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Sales
                    </a>
                </div>
            </div>
        </div>

        {{-- STATS CARDS --}}
        <div class="stats-grid">
            <div class="stat-card red-accent">
                <div class="stat-top">
                    <div class="stat-icon red">
                        <i class="bi bi-trash3"></i>
                    </div>
                    <div class="stat-value">{{ $stats['total_deleted'] ?? 0 }}</div>
                </div>
                <div class="stat-label">Total Deleted</div>
            </div>
            <div class="stat-card yellow-accent">
                <div class="stat-top">
                    <div class="stat-icon yellow">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div class="stat-value">{{ $stats['deleted_draft'] ?? 0 }}</div>
                </div>
                <div class="stat-label">Draft Deleted</div>
            </div>
            <div class="stat-card purple-accent">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div class="stat-value">{{ $stats['deleted_confirmed'] ?? 0 }}</div>
                </div>
                <div class="stat-label">Confirmed Deleted</div>
            </div>
            <div class="stat-card blue-accent">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div class="stat-value">{{ ($stats['deleted_shipped'] ?? 0) + ($stats['deleted_delivered'] ?? 0) }}
                    </div>
                </div>
                <div class="stat-label">Shipped/Delivered Deleted</div>
            </div>
        </div>

        {{-- FILTER BAR --}}
        <div class="filter-bar">
            <div class="filter-group">
                <span class="filter-label"><i class="bi bi-funnel me-1"></i> {{ __('ui.filter') }}</span>
            </div>

            {{-- Search --}}
            <form action="{{ route('admin.sales.deleted') }}" method="GET"
                class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                <div class="input-group" style="min-width: 200px; width: auto;">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="{{ __('ui.search_deleted') }}"
                        value="{{ request('search') }}" style="border-left: none; box-shadow: none;">
                    @if (request('search'))
                        <a href="{{ route('admin.sales.deleted', request()->except('search')) }}"
                            class="btn btn-outline-secondary border-start-0" style="border-left: none;">
                            <i class="bi bi-x-circle-fill"></i>
                        </a>
                    @endif
                </div>

                <button type="submit" class="btn-filter btn-primary">
                    <i class="bi bi-search me-1"></i> {{ __('ui.search') }}
                </button>

                @if (request('search'))
                    <a href="{{ route('admin.sales.deleted') }}" class="btn-filter btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('ui.reset') }}
                    </a>
                @endif
            </form>
        </div>

        {{-- MAIN TABLE --}}
        <div class="table-card">
            <div class="card-header-custom">
                <h5>
                    <i class="bi bi-trash3"></i> Deleted Sales Orders
                    <span class="header-badge">
                        <i class="bi bi-database me-1"></i> {{ $deletedSales->total() }} deleted
                    </span>
                </h5>
            </div>

            <div class="table-responsive-custom">
                <table class="table-ledger">
                    <thead>
                        <tr>
                            <th style="min-width: 100px;">{{ __('ui.sale_number') }}</th>
                            <th style="min-width: 150px;">{{ __('ui.customer') }}</th>
                            <th class="text-center" style="min-width: 100px;">{{ __('ui.status') }}</th>
                            <th class="text-center" style="min-width: 80px;">{{ __('ui.items') }}</th>
                            <th class="text-end" style="min-width: 130px;">{{ __('ui.total') }}</th>
                            <th class="text-center" style="min-width: 130px;">Deleted At</th>
                            <th class="text-center" style="min-width: 140px;">Deleted By</th>
                            <th class="text-end" style="width: 1%;">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deletedSales as $order)
                            <tr>
                                <td>
                                    <span class="text-muted text-decoration-line-through">
                                        {{ $order->sale_no }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold" style="color: var(--gray-700);">
                                        {{ $order->customer->name ?? '-' }}
                                    </div>
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
                                        {{ $order->items()->withTrashed()->count() }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span style="font-weight: 700; color: var(--gray-900);">
                                        {{ $order->currency->symbol ?? '$' }}{{ number_format($order->grand_total ?? 0, 2) }}
                                    </span>
                                </td>
                                <td class="text-center" style="color: var(--gray-600); font-size: 0.85rem;">
                                    {{ $order->deleted_at ? $order->deleted_at->format('Y-m-d H:i') : '-' }}
                                </td>
                                <td class="text-center">
                                    <span style="font-size: 0.85rem; color: var(--gray-600);">
                                        {{ $order->deletedBy->name ?? 'System' }}
                                    </span>
                                    @if ($order->deletion_reason)
                                        <div style="font-size: 0.65rem; color: var(--gray-400);">
                                            {{ Str::limit($order->deletion_reason, 30) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="action-buttons">
                                        {{-- Restore Button --}}
                                        <form action="{{ route('admin.sales.restore', $order->id) }}" method="POST"
                                            style="display: inline-block;"
                                            onsubmit="return confirm('Restore this sale? This will restore all stock and transactions.');">
                                            @csrf
                                            <button type="submit" class="action-btn restore-btn" title="{{ __('ui.restore') }}">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </form>

                                        {{-- Force Delete Button --}}
                                        <form action="{{ route('admin.sales.force-delete', $order->id) }}" method="POST"
                                            style="display: inline-block;"
                                            onsubmit="return confirm('⚠️ PERMANENTLY DELETE this sale? This action cannot be undone!');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn force-delete-btn"
                                                title="{{ __('ui.permanently_delete') }}">
                                                <i class="bi bi-x-octagon"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="bi bi-trash3"></i>
                                        <p>No deleted sales orders found</p>
                                        <p class="sub-text">Deleted orders will appear here for 30 days before automatic
                                            cleanup</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($deletedSales->hasPages())
                <div class="pagination-wrap">
                    <span class="info-text">
                        Showing {{ $deletedSales->firstItem() }} – {{ $deletedSales->lastItem() }}
                        of {{ $deletedSales->total() }} entries
                    </span>
                    {{ $deletedSales->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

    </div>
@endsection
