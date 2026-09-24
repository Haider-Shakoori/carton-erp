{{-- resources/views/admin/production-orders/index.blade.php --}}

@extends('layouts.admin.base')

@section('title', __('ui.production_orders'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <style>
        /* ─── Variables ─── */
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5, #7c3aed);
            --success-gradient: linear-gradient(135deg, #059669, #10b981);
            --warning-gradient: linear-gradient(135deg, #d97706, #f59e0b);
            --danger-gradient: linear-gradient(135deg, #dc2626, #ef4444);
            --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            --card-shadow-hover: 0 8px 30px rgba(0, 0, 0, 0.08);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius-lg: 16px;
            --border-radius-md: 12px;
            --border-radius-sm: 8px;
        }

        /* ─── Status Badges ─── */
        .status-badge {
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            letter-spacing: 0.3px;
            transition: var(--transition-smooth);
        }
        .status-badge i {
            font-size: 0.5rem;
        }
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .status-badge.in_progress {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }
        .status-badge.in_progress .pulse-dot {
            animation: pulse-dot 1.5s ease-in-out infinite;
        }
        .status-badge.completed {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .status-badge.cancelled {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.7); }
        }

        /* ─── Stats Cards ─── */
        .stat-card {
            background: white;
            padding: 1.25rem 1.5rem;
            border-radius: var(--border-radius-md);
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            transition: var(--transition-smooth);
            animation: fade-up 0.5s ease forwards;
            opacity: 0;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }
        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(1)::before { background: #4f46e5; }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(2)::before { background: #d97706; }
        .stat-card:nth-child(3) { animation-delay: 0.15s; }
        .stat-card:nth-child(3)::before { background: #2563eb; }
        .stat-card:nth-child(4) { animation-delay: 0.2s; }
        .stat-card:nth-child(4)::before { background: #059669; }

        @keyframes fade-up {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-shadow-hover);
            border-color: #e2e8f0;
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
            transition: var(--transition-smooth);
        }
        .stat-card:hover .stat-icon {
            transform: scale(1.05) rotate(-3deg);
        }
        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }
        .stat-label {
            font-size: 0.7rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        .stat-change {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.15rem 0.5rem;
            border-radius: 50px;
            display: inline-block;
            margin-left: 0.5rem;
        }
        .stat-change.up {
            background: #d1fae5;
            color: #065f46;
        }
        .stat-change.down {
            background: #fecaca;
            color: #991b1b;
        }

        /* ─── Filter Bar ─── */
        .filter-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius-md);
            border: 1px solid #f1f5f9;
            margin-bottom: 1.5rem;
            animation: fade-up 0.5s ease 0.25s forwards;
            opacity: 0;
        }
        .filter-bar .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .filter-bar .filter-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            white-space: nowrap;
        }
        .filter-bar .form-select,
        .filter-bar .form-control {
            border-color: #e5e7eb;
            font-size: 0.8rem;
            padding: 0.4rem 0.75rem;
            border-radius: var(--border-radius-sm);
            transition: var(--transition-smooth);
        }
        .filter-bar .form-select:focus,
        .filter-bar .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .filter-bar .input-group-text {
            background: white;
            border-color: #e5e7eb;
            color: #94a3b8;
            font-size: 0.8rem;
        }

        /* ─── Table Card ─── */
        .table-card {
            background: white;
            border-radius: var(--border-radius-lg);
            border: 1px solid #f1f5f9;
            overflow: hidden;
            animation: fade-up 0.5s ease 0.3s forwards;
            opacity: 0;
            box-shadow: var(--card-shadow);
            transition: var(--transition-smooth);
        }
        .table-card:hover {
            box-shadow: var(--card-shadow-hover);
        }

        .card-header-custom {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #fafafa, #ffffff);
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .card-header-custom h6 {
            font-size: 0.8rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .card-header-custom h6 i {
            color: #4f46e5;
        }
        .card-header-custom .header-badge {
            background: #eef2ff;
            color: #4f46e5;
            padding: 0.15rem 0.6rem;
            border-radius: 50px;
            font-size: 0.65rem;
            font-weight: 700;
        }

        /* ─── Table ─── */
        .table-ledger {
            font-size: 0.8rem;
            margin-bottom: 0;
        }
        .table-ledger thead th {
            padding: 0.75rem 1rem;
            background: #f8fafc;
            color: #64748b;
            font-weight: 700;
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 2px solid #f1f5f9;
            white-space: nowrap;
        }
        .table-ledger tbody td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f8fafc;
            transition: var(--transition-smooth);
        }
        .table-ledger tbody tr {
            transition: var(--transition-smooth);
        }
        .table-ledger tbody tr:hover {
            background: #f8fafc;
        }
        .table-ledger tbody tr:last-child td {
            border-bottom: none;
        }

        .table-ledger .num-cell {
            font-weight: 700;
            color: #94a3b8;
            font-size: 0.7rem;
        }

        /* ─── Product Link ─── */
        .product-link {
            color: #0f172a;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition-smooth);
        }
        .product-link:hover {
            color: #4f46e5;
            text-decoration: underline;
        }

        /* ─── BOM Badge ─── */
        .badge-cat {
            background: #f1f5f9;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #475569;
            display: inline-block;
        }
        .badge-cat small {
            font-weight: 400;
            font-size: 0.6rem;
            color: #94a3b8;
        }

        /* ─── Progress Bar ─── */
        .progress-bar-custom {
            height: 6px;
            border-radius: 4px;
            background: #f1f5f9;
            overflow: hidden;
            min-width: 80px;
            flex: 1;
        }
        .progress-bar-custom .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .progress-bar-custom .progress-fill.success {
            background: var(--success-gradient);
        }
        .progress-bar-custom .progress-fill.warning {
            background: var(--warning-gradient);
        }
        .progress-bar-custom .progress-fill.info {
            background: var(--primary-gradient);
        }
        .progress-bar-custom .progress-fill.danger {
            background: var(--danger-gradient);
        }

        /* ─── Action Buttons ─── */
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            justify-content: flex-end;
        }
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: var(--border-radius-sm);
            border: none;
            background: transparent;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: var(--transition-smooth);
            cursor: pointer;
            font-size: 0.9rem;
            position: relative;
        }
        .action-btn:hover {
            background: #f1f5f9;
            color: #4f46e5;
            transform: translateY(-2px);
        }
        .action-btn.text-success:hover {
            background: #d1fae5;
            color: #059669;
        }
        .action-btn.text-danger:hover {
            background: #fecaca;
            color: #dc2626;
        }
        .action-btn.text-primary:hover {
            background: #dbeafe;
            color: #2563eb;
        }
        .action-btn form {
            display: inline;
        }

        /* ─── Empty State ─── */
        .empty-state {
            padding: 3rem 1rem;
        }
        .empty-state i {
            font-size: 3rem;
            color: #e5e7eb;
            display: block;
            margin-bottom: 0.75rem;
        }
        .empty-state p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        /* ─── Pagination ─── */
        .pagination-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .pagination-custom .pagination {
            margin-bottom: 0;
        }
        .pagination-custom .pagination .page-link {
            border: none;
            color: #64748b;
            font-size: 0.8rem;
            padding: 0.4rem 0.75rem;
            border-radius: var(--border-radius-sm);
            transition: var(--transition-smooth);
        }
        .pagination-custom .pagination .page-link:hover {
            background: #f1f5f9;
            color: #4f46e5;
        }
        .pagination-custom .pagination .page-item.active .page-link {
            background: var(--primary-gradient);
            color: white;
        }
        .pagination-custom .pagination .page-item.disabled .page-link {
            color: #cbd5e1;
        }

        /* ─── Responsive ─── */
        @media (max-width: 992px) {
            .filter-bar form {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-bar .filter-group {
                flex-wrap: wrap;
            }
            .filter-bar .input-group {
                min-width: 100% !important;
            }
            .action-buttons {
                gap: 0.1rem;
            }
        }
        @media (max-width: 768px) {
            .stat-card {
                padding: 1rem;
            }
            .stat-value {
                font-size: 1.3rem;
            }
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            .table-ledger thead th,
            .table-ledger tbody td {
                padding: 0.5rem 0.6rem;
                font-size: 0.7rem;
            }
            .card-header-custom {
                padding: 0.75rem 1rem;
            }
            .card-header-custom h6 {
                font-size: 0.7rem;
            }
            .pagination-custom {
                flex-direction: column;
                align-items: center;
            }
        }
        @media (max-width: 576px) {
            .stat-card {
                flex-direction: column;
                text-align: center;
                padding: 1rem 0.75rem;
            }
            .table-responsive {
                font-size: 0.7rem;
            }
            .action-btn {
                width: 28px;
                height: 28px;
                font-size: 0.75rem;
            }
            .status-badge {
                font-size: 0.6rem;
                padding: 3px 10px;
            }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/admin-index.css') }}">
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4 erp-index-ui">

        {{-- ─── PAGE HEADER ─── --}}
        <div class="page-header" style="animation: fade-up 0.5s ease forwards; opacity: 0;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1 style="font-size: 1.75rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em;">
                        <i class="bi bi-gear me-2" style="color: #4f46e5;"></i>
                        {{ __('ui.production') }} <span class="accent" style="color: #4f46e5;">{{ __('ui.orders') }}</span>
                    </h1>
                    <p class="subtitle" style="color: #64748b; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-boxes me-1"></i>
                        Manage and track production orders
                        <span class="badge bg-primary ms-1" style="font-weight: 600; font-size: 0.6rem;">
                            {{ $orders->total() }} total
                        </span>
                    </p>
                </div>
                <div>
                    <a href="{{ route('production-orders.create') }}" class="btn btn-primary" style="border-radius: var(--border-radius-sm); padding: 0.6rem 1.5rem; font-weight: 700; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3); transition: var(--transition-smooth);">
                        <i class="bi bi-plus-circle me-1"></i> New Production Order
                    </a>
                </div>
            </div>
        </div>

        {{-- ─── STATISTICS CARDS ─── --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #eef2ff; color: #4f46e5;">
                        <i class="bi bi-list-ul"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                        <div class="stat-label">{{ __('ui.total_orders') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fffbeb; color: #d97706;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['pending'] ?? 0 }}</div>
                        <div class="stat-label">{{ __('ui.pending') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #dbeafe; color: #2563eb;">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['in_progress'] ?? 0 }}</div>
                        <div class="stat-label">{{ __('ui.in_progress') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #d1fae5; color: #059669;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['completed'] ?? 0 }}</div>
                        <div class="stat-label">{{ __('ui.completed') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── FILTER BAR ─── --}}
        <div class="filter-bar">
            <form action="{{ route('production-orders.index') }}" method="GET" class="d-flex flex-wrap align-items-center gap-3">
                <div class="filter-group">
                    <span class="filter-label"><i class="bi bi-funnel me-1"></i> {{ __('ui.status_colon') }}</span>
                    <select name="status" class="form-select form-select-sm" style="width: auto; min-width: 140px;">
                        <option value="all" {{ request('status', 'all') == 'all' ? 'selected' : '' }}>{{ __('ui.all_status') }}</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ Pending</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>🔄 In Progress</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>✅ Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>❌ Cancelled</option>
                    </select>
                </div>

                <div class="input-group" style="min-width: 220px; width: auto;">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="{{ __('ui.search_orders') }}" value="{{ request('search') }}" style="font-size: 0.8rem;">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm" style="font-weight: 600; padding: 0.4rem 1.25rem;">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    @if(request()->filled('search') || (request()->filled('status') && request('status') !== 'all'))
                        <a href="{{ route('production-orders.index') }}" class="btn btn-outline-secondary btn-sm" style="font-weight: 600;">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('ui.reset') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- ─── TABLE ─── --}}
        <div class="table-card">
            <div class="card-header-custom">
                <h6>
                    <i class="bi bi-table me-2"></i> {{ __('ui.production_orders') }}
                    <span class="header-badge ms-2">{{ $orders->total() }} orders</span>
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 0.65rem;">
                        <i class="bi bi-arrow-up-short me-1"></i> Latest first
                    </span>
                </div>
            </div>
            <div class="p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-ledger mb-0">
                        <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Order #</th>
                            <th>{{ __('ui.product') }}</th>
                            <th>{{ __('ui.bom') }}</th>
                            <th class="text-center" style="min-width: 100px;">{{ __('ui.quantity') }}</th>
                            <th class="text-center" style="min-width: 150px;">{{ __('ui.progress') }}</th>
                            <th class="text-center">{{ __('ui.status') }}</th>
                            <th class="text-end">{{ __('ui.total_cost') }}</th>
                            <th class="text-center">{{ __('ui.start_date') }}</th>
                            <th class="text-end" style="min-width: 120px;">{{ __('ui.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($orders as $order)
                            @php
                                $progress = $order->progress_percentage;
                                $progressColor = $progress >= 100 ? 'success' : ($progress >= 50 ? 'warning' : 'info');
                                $statusClass = $order->status;
                            @endphp
                            <tr>
                                <td class="num-cell">{{ $loop->iteration }}</td>
                                <td>
                                    <a href="{{ route('production-orders.show', $order) }}" class="product-link" style="font-weight: 700; color: #4f46e5;">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('production-orders.show', $order) }}" class="product-link">
                                        {{ $order->product->name ?? 'N/A' }}
                                    </a>
                                    @if($order->product)
                                        <br>
                                        <small class="text-muted" style="font-size: 0.6rem;">
                                            SKU: {{ $order->product->sku ?? 'N/A' }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge-cat">
                                        {{ $order->bom->name ?? 'N/A' }}
                                        @if($order->bom)
                                            <small>v{{ $order->bom->version ?? '1.0' }}</small>
                                        @endif
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold" style="color: #0f172a;">
                                        {{ number_format($order->quantity_ordered) }}
                                    </span>
                                    <br>
                                    <small class="text-muted" style="font-size: 0.65rem;">
                                        <i class="bi bi-check-circle-fill me-1" style="color: #059669; font-size: 0.5rem;"></i>
                                        {{ number_format($order->quantity_produced) }} produced
                                    </small>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center gap-2" style="min-width: 130px;">
                                        <div class="progress-bar-custom">
                                            <div class="progress-fill {{ $progressColor }}" style="width: {{ $progress }}%;"></div>
                                        </div>
                                        <span class="small fw-bold" style="color: #0f172a; font-size: 0.75rem; min-width: 36px;">
                                            {{ round($progress) }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="status-badge {{ $statusClass }}">
                                        @if($statusClass === 'in_progress')
                                            <span class="pulse-dot" style="width: 6px; height: 6px; border-radius: 50%; background: #2563eb; display: inline-block;"></span>
                                        @else
                                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                        @endif
                                        {{ $order->status_label }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold" style="color: #0f172a;">
                                    ${{ number_format($order->total_cost, 2) }}
                                </td>
                                <td class="text-center">
                                    <small style="color: #64748b; font-size: 0.75rem;">
                                        @if($order->start_date)
                                            <i class="bi bi-calendar3 me-1" style="font-size: 0.6rem;"></i>
                                            {{ $order->start_date->format('d M Y') }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </small>
                                </td>
                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a href="{{ route('production-orders.show', $order) }}" class="action-btn text-primary" title="{{ __('ui.view_details') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($order->status === 'pending')
                                            <form action="{{ route('production-orders.start', $order) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="action-btn text-success" title="{{ __('ui.start_production') }}" onclick="return confirm('⚠️ Start production?\n\nThis will consume materials from inventory.\n\nContinue?')">
                                                    <i class="bi bi-play-fill"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($order->status === 'in_progress')
                                            <a href="{{ route('production-orders.show', $order) }}#completeProductionModal"
                                               class="action-btn text-success"
                                               title="Complete production with actual quantities">
                                                <i class="bi bi-check2"></i>
                                            </a>
                                        @endif
                                        @if(in_array($order->status, ['pending', 'in_progress']))
                                            <form action="{{ route('production-orders.cancel', $order) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="action-btn text-danger" title="{{ __('ui.cancel_order') }}" onclick="return confirm('❌ Cancel production?\n\nThis will restore materials to inventory.\n\nContinue?')">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="empty-state text-center py-4">
                                        <i class="bi bi-inboxes"></i>
                                        <p class="mt-2 text-muted">No production orders found</p>
                                        <a href="{{ route('production-orders.create') }}" class="btn btn-primary btn-sm" style="border-radius: var(--border-radius-sm); font-weight: 600;">
                                            <i class="bi bi-plus-circle me-1"></i> Create First Order
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ─── PAGINATION ─── --}}
            @if($orders->hasPages())
                <div class="p-3 border-top" style="border-color: #f1f5f9;">
                    <div class="pagination-custom">
                        <div class="text-muted" style="font-size: 0.75rem; font-weight: 500;">
                            <i class="bi bi-info-circle me-1"></i>
                            Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }} entries
                        </div>
                        {{ $orders->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ─── AUTO-REFRESH for pending/in-progress orders ───
            let hasActiveOrders = document.querySelector('.status-badge.in_progress, .status-badge.pending');
            if (hasActiveOrders) {
                let refreshInterval = setInterval(function() {
                    if (!document.hidden) {
                        fetch(window.location.href + '?check_status=1', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.has_changes) {
                                    location.reload();
                                }
                            })
                            .catch(error => {
                                console.log('Status check failed:', error);
                            });
                    }
                }, 30000);

                document.addEventListener('visibilitychange', function() {
                    if (document.hidden) {
                        clearInterval(refreshInterval);
                    } else {
                        refreshInterval = setInterval(function() {
                            if (!document.hidden) {
                                fetch(window.location.href + '?check_status=1', {
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.has_changes) {
                                            location.reload();
                                        }
                                    })
                                    .catch(error => {
                                        console.log('Status check failed:', error);
                                    });
                            }
                        }, 30000);
                    }
                });
            }

            // ─── ANIMATE PROGRESS BARS ON SCROLL ───
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const fill = entry.target.querySelector('.progress-fill');
                        if (fill) {
                            const width = fill.style.width;
                            fill.style.width = '0%';
                            setTimeout(() => {
                                fill.style.width = width;
                            }, 100);
                        }
                    }
                });
            }, { threshold: 0.5 });

            document.querySelectorAll('.progress-bar-custom').forEach(bar => {
                observer.observe(bar);
            });
        });
    </script>
@endsection
