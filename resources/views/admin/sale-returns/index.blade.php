{{-- resources/views/admin/sale-returns/index.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Sale Returns')

@section('css')
    <link href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-index.css') }}">
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4 erp-index-ui">

        {{-- PAGE HEADER --}}
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-arrow-return-left me-2" style="color: #f59e0b;"></i>
                        Sale <span class="accent">Returns</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-box-arrow-up-right me-1"></i>
                        Manage customer returns and restocking
                    </p>
                </div>

                <a href="{{ route('admin.sale-returns.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>
                    New Return
                </a>
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
                <div class="stat-label">Total Returns</div>
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
                    <div class="stat-value">{{ $stats['approved'] ?? 0 }}</div>
                </div>
                <div class="stat-label">{{ __('ui.approved') }}</div>
            </div>

            <div class="stat-card green-accent">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <div class="stat-value">{{ $stats['processed'] ?? 0 }}</div>
                </div>
                <div class="stat-label">{{ __('ui.processed') }}</div>
            </div>
        </div>

        {{-- FILTER BAR --}}
        <div class="filter-bar">
            <div class="filter-group">
                <span class="filter-label"><i class="bi bi-funnel me-1"></i> {{ __('ui.status_colon') }}</span>
                <div class="status-filter-group">
                    <a href="{{ route('admin.sale-returns.index', ['status' => 'all']) }}"
                        class="status-filter-btn {{ request('status', 'all') == 'all' ? 'active-all' : '' }}">
                        <i class="bi bi-circle-fill"></i> {{ __('ui.all') }}
                    </a>
                    <a href="{{ route('admin.sale-returns.index', ['status' => 'draft']) }}"
                        class="status-filter-btn {{ request('status') == 'draft' ? 'active-draft' : '' }}">
                        <i class="bi bi-circle-fill"></i> {{ __('ui.draft') }}
                    </a>
                    <a href="{{ route('admin.sale-returns.index', ['status' => 'approved']) }}"
                        class="status-filter-btn {{ request('status') == 'approved' ? 'active-approved' : '' }}">
                        <i class="bi bi-circle-fill"></i> {{ __('ui.approved') }}
                    </a>
                    <a href="{{ route('admin.sale-returns.index', ['status' => 'rejected']) }}"
                        class="status-filter-btn {{ request('status') == 'rejected' ? 'active-rejected' : '' }}">
                        <i class="bi bi-circle-fill"></i> {{ __('ui.rejected') }}
                    </a>
                    <a href="{{ route('admin.sale-returns.index', ['status' => 'processed']) }}"
                        class="status-filter-btn {{ request('status') == 'processed' ? 'active-processed' : '' }}">
                        <i class="bi bi-circle-fill"></i> {{ __('ui.processed') }}
                    </a>
                </div>
            </div>

            {{-- Search --}}
            <form action="{{ route('admin.sale-returns.index') }}" method="GET"
                class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                @if (request('status') && request('status') != 'all')
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif

                <div class="input-group" style="min-width: 200px; width: auto;">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="{{ __('ui.search_returns') }}"
                        value="{{ request('search') }}" style="border-left: none; box-shadow: none;">
                    @if (request('search'))
                        <a href="{{ route('admin.sale-returns.index', request()->except('search')) }}"
                            class="btn btn-outline-secondary border-start-0" style="border-left: none;">
                            <i class="bi bi-x-circle-fill"></i>
                        </a>
                    @endif
                </div>

                <button type="submit" class="btn-filter btn-primary">
                    <i class="bi bi-search me-1"></i> {{ __('ui.search') }}
                </button>

                @if (request()->filled('search') || (request()->filled('status') && request('status') !== 'all'))
                    <a href="{{ route('admin.sale-returns.index') }}" class="btn-filter btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('ui.reset') }}
                    </a>
                @endif
            </form>
        </div>

        {{-- MAIN TABLE --}}
        <div class="table-card">
            <div class="card-header-custom">
                <h5>
                    <i class="bi bi-table"></i> Sale Returns List
                    <span class="header-badge">
                        <i class="bi bi-database me-1"></i> {{ $returns->total() }} returns
                    </span>
                </h5>
            </div>

            <div class="table-responsive-custom">
                <table class="table-ledger">
                    <thead>
                        <tr>
                            <th style="min-width: 100px;">Return #</th>
                            <th style="min-width: 130px;">Original Sale</th>
                            <th style="min-width: 150px;">{{ __('ui.customer') }}</th>
                            <th class="text-center" style="min-width: 100px;">{{ __('ui.status') }}</th>
                            <th class="text-center" style="min-width: 80px;">{{ __('ui.items') }}</th>
                            <th class="text-end" style="min-width: 130px;">{{ __('ui.total') }}</th>
                            <th class="text-end" style="min-width: 120px;">Refund</th>
                            <th class="text-center" style="min-width: 110px;">{{ __('ui.date') }}</th>
                            <th class="text-end" style="width: 1%;">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $return)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.sale-returns.show', $return->id) }}" class="po-link">
                                        {{ $return->return_no }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('admin.sales.show', $return->sale_id) }}" class="po-link"
                                        style="font-size: 0.85rem;">
                                        {{ $return->sale->sale_no ?? 'N/A' }}
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold" style="color: var(--gray-700);">
                                        {{ $return->customer->name ?? '-' }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusConfig = [
                                            'draft' => ['class' => 'draft', 'icon' => 'bi-pencil-square'],
                                            'approved' => ['class' => 'confirmed', 'icon' => 'bi-check2-circle'],
                                            'rejected' => ['class' => 'rejected', 'icon' => 'bi-x-circle'],
                                            'processed' => ['class' => 'delivered', 'icon' => 'bi-arrow-repeat'],
                                        ];
                                        $config = $statusConfig[$return->status] ?? [
                                            'class' => 'draft',
                                            'icon' => 'bi-question-circle',
                                        ];
                                    @endphp
                                    <span class="status-badge {{ $config['class'] }}">
                                        <i class="bi {{ $config['icon'] }}"></i>
                                        {{ ucfirst($return->status ?? 'Unknown') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span style="font-weight: 600; color: var(--gray-700);">
                                        {{ $return->items->count() }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span style="font-weight: 700; color: var(--gray-900);">
                                        {{ $return->currency->symbol ?? '$' }}{{ number_format($return->grand_total ?? 0, 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span style="font-weight: 700; color: #f59e0b;">
                                        {{ $return->currency->symbol ?? '$' }}{{ number_format($return->refund_amount ?? 0, 2) }}
                                    </span>
                                </td>
                                <td class="text-center" style="color: var(--gray-600); font-size: 0.85rem;">
                                    {{ $return->return_date ? date('Y-m-d', strtotime($return->return_date)) : '-' }}
                                </td>
                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a href="{{ route('admin.sale-returns.show', $return->id) }}" class="action-btn"
                                            title="{{ __('ui.view_details') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if (in_array($return->status, ['draft', 'rejected']))
                                            <form action="{{ route('admin.sale-returns.destroy', $return->id) }}"
                                                method="POST" style="display: inline-block;"
                                                onsubmit="return confirm('Are you sure you want to delete return #{{ $return->return_no }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn text-danger" title="{{ __('ui.delete') }}"
                                                    style="border: none; background: transparent; cursor: pointer; padding: 0; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="bi bi-arrow-return-left"></i>
                                        <p>No sale returns found</p>
                                        <p class="sub-text">Create your first return by clicking "New Return"</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($returns->hasPages())
                <div class="pagination-wrap">
                    <span class="info-text">
                        Showing {{ $returns->firstItem() }} – {{ $returns->lastItem() }}
                        of {{ $returns->total() }} entries
                    </span>
                    {{ $returns->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

    </div>
@endsection
