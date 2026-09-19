{{-- resources/views/admin/profit-distributions/index.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Profit Distributions')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <style>
        /* ─── Variables ─── */
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5, #7c3aed);
            --success-gradient: linear-gradient(135deg, #059669, #10b981);
            --warning-gradient: linear-gradient(135deg, #d97706, #f59e0b);
            --danger-gradient: linear-gradient(135deg, #dc2626, #ef4444);
            --info-gradient: linear-gradient(135deg, #2563eb, #3b82f6);
            --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            --card-shadow-hover: 0 8px 30px rgba(0, 0, 0, 0.08);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius-lg: 16px;
            --border-radius-md: 12px;
            --border-radius-sm: 8px;
        }

        /* ─── Page Header ─── */
        .page-header-modern {
            animation: fade-up 0.5s ease forwards;
            opacity: 0;
            padding-bottom: 1.5rem;
        }
        .page-header-modern h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .page-header-modern h1 .accent {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .page-header-modern .subtitle {
            color: #64748b;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* ─── Stats Cards ─── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card-modern {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius-md);
            border: 1px solid #f1f5f9;
            transition: var(--transition-smooth);
            animation: fade-up 0.5s ease forwards;
            opacity: 0;
            position: relative;
            overflow: hidden;
        }
        .stat-card-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }
        .stat-card-modern:nth-child(1) { animation-delay: 0.05s; }
        .stat-card-modern:nth-child(1)::before { background: var(--primary-gradient); }
        .stat-card-modern:nth-child(2) { animation-delay: 0.1s; }
        .stat-card-modern:nth-child(2)::before { background: var(--success-gradient); }
        .stat-card-modern:nth-child(3) { animation-delay: 0.15s; }
        .stat-card-modern:nth-child(3)::before { background: var(--warning-gradient); }
        .stat-card-modern:nth-child(4) { animation-delay: 0.2s; }
        .stat-card-modern:nth-child(4)::before { background: var(--info-gradient); }

        .stat-card-modern:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-shadow-hover);
            border-color: #e2e8f0;
        }
        .stat-card-modern .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        .stat-card-modern .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: var(--transition-smooth);
        }
        .stat-card-modern:hover .stat-icon {
            transform: scale(1.05) rotate(-3deg);
        }
        .stat-card-modern .stat-icon.purple {
            background: #eef2ff;
            color: #4f46e5;
        }
        .stat-card-modern .stat-icon.green {
            background: #d1fae5;
            color: #059669;
        }
        .stat-card-modern .stat-icon.yellow {
            background: #fef3c7;
            color: #d97706;
        }
        .stat-card-modern .stat-icon.blue {
            background: #dbeafe;
            color: #2563eb;
        }
        .stat-card-modern .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }
        .stat-card-modern .stat-label {
            font-size: 0.7rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        .stat-card-modern .stat-trend {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.15rem 0.5rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
        }
        .stat-card-modern .stat-trend.up {
            background: #d1fae5;
            color: #065f46;
        }
        .stat-card-modern .stat-trend.down {
            background: #fecaca;
            color: #991b1b;
        }

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

        /* ─── Status Badges ─── */
        .badge-status {
            padding: 0.3rem 0.9rem;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            letter-spacing: 0.3px;
            transition: var(--transition-smooth);
        }
        .badge-status i {
            font-size: 0.6rem;
        }
        .badge-status.distributed {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .badge-status.pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .badge-status.pending .pulse-dot {
            animation: pulse-dot 1.5s ease-in-out infinite;
        }
        .badge-status.draft {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }
        .badge-status.approved {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }
        .badge-status.cancelled {
            background: #fecaca;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.7); }
        }

        /* ─── Filter Card ─── */
        .filter-card {
            background: white;
            border-radius: var(--border-radius-md);
            border: 1px solid #f1f5f9;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            animation: fade-up 0.5s ease 0.25s forwards;
            opacity: 0;
            transition: var(--transition-smooth);
        }
        .filter-card:hover {
            box-shadow: var(--card-shadow-hover);
        }
        .filter-card .form-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.25rem;
        }
        .filter-card .form-control,
        .filter-card .form-select {
            border-color: #e5e7eb;
            border-radius: var(--border-radius-sm);
            font-size: 0.85rem;
            padding: 0.45rem 0.75rem;
            transition: var(--transition-smooth);
        }
        .filter-card .form-control:focus,
        .filter-card .form-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
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
        .card-header-custom .title {
            font-weight: 700;
            font-size: 0.85rem;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .card-header-custom .title i {
            color: #4f46e5;
        }
        .card-header-custom .header-badge {
            background: #eef2ff;
            color: #4f46e5;
            padding: 0.2rem 0.7rem;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        /* ─── Table ─── */
        .table-ledger {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
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
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }
        .table-ledger .num-cell.text-success {
            color: #059669;
        }
        .table-ledger .num-cell.text-danger {
            color: #dc2626;
        }

        /* ─── Progress Bar ─── */
        .progress-bar-custom {
            height: 6px;
            border-radius: 4px;
            background: #f1f5f9;
            overflow: hidden;
            flex: 1;
            min-width: 60px;
        }
        .progress-bar-custom .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: var(--primary-gradient);
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .progress-bar-custom .progress-fill.complete {
            background: var(--success-gradient);
        }
        .progress-bar-custom .progress-fill.warning {
            background: var(--warning-gradient);
        }
        .progress-bar-custom .progress-fill.danger {
            background: var(--danger-gradient);
        }
        .progress-progress-text {
            font-size: 0.7rem;
            font-weight: 700;
            color: #0f172a;
            min-width: 36px;
            text-align: right;
        }

        /* ─── Action Buttons ─── */
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            justify-content: flex-end;
        }
        .action-btn {
            width: 34px;
            height: 34px;
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
            font-size: 0.95rem;
        }
        .action-btn:hover {
            background: #f1f5f9;
            color: #4f46e5;
            transform: translateY(-2px);
        }
        .action-btn.text-danger:hover {
            background: #fecaca;
            color: #dc2626;
        }
        .action-btn.text-primary:hover {
            background: #dbeafe;
            color: #2563eb;
        }
        .action-btn.text-success:hover {
            background: #d1fae5;
            color: #059669;
        }

        /* ─── Pagination ─── */
        .pagination-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
        }
        .pagination-wrapper .pagination-info {
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 500;
        }
        .pagination-wrapper .pagination-info i {
            margin-right: 0.3rem;
        }
        .pagination-wrapper .pagination {
            margin-bottom: 0;
        }
        .pagination-wrapper .pagination .page-link {
            border: none;
            color: #64748b;
            font-size: 0.8rem;
            padding: 0.4rem 0.75rem;
            border-radius: var(--border-radius-sm);
            transition: var(--transition-smooth);
            font-weight: 600;
        }
        .pagination-wrapper .pagination .page-link:hover {
            background: #f1f5f9;
            color: #4f46e5;
        }
        .pagination-wrapper .pagination .page-item.active .page-link {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
        }
        .pagination-wrapper .pagination .page-item.disabled .page-link {
            color: #cbd5e1;
            cursor: not-allowed;
        }

        /* ─── Distribution Number Link ─── */
        .distribution-link {
            font-weight: 700;
            color: #4f46e5;
            text-decoration: none;
            transition: var(--transition-smooth);
        }
        .distribution-link:hover {
            color: #4338ca;
            text-decoration: underline;
        }

        /* ─── Period Badge ─── */
        .period-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            background: #f1f5f9;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .period-badge i {
            font-size: 0.6rem;
            color: #94a3b8;
        }

        /* ─── Empty State ─── */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        .empty-state .icon {
            font-size: 3rem;
            color: #e5e7eb;
            display: block;
            margin-bottom: 0.75rem;
        }
        .empty-state .title {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
        }
        .empty-state .subtitle {
            color: #94a3b8;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        /* ─── Responsive ─── */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }
            .stat-card-modern {
                padding: 1rem;
            }
            .stat-card-modern .stat-value {
                font-size: 1.3rem;
            }
            .stat-card-modern .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            .table-ledger thead th,
            .table-ledger tbody td {
                padding: 0.5rem 0.6rem;
                font-size: 0.75rem;
            }
            .card-header-custom {
                padding: 0.75rem 1rem;
            }
            .pagination-wrapper {
                flex-direction: column;
                align-items: center;
            }
            .action-btn {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }
        }
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            .filter-card {
                padding: 0.75rem 1rem;
            }
            .filter-card .row {
                gap: 0.5rem;
            }
            .table-responsive {
                font-size: 0.7rem;
            }
            .badge-status {
                font-size: 0.6rem;
                padding: 0.15rem 0.6rem;
            }
            .period-badge {
                font-size: 0.65rem;
                padding: 0.1rem 0.4rem;
            }
        }

        /* ─── DataTable Overrides ─── */
        .dataTables_wrapper .dataTables_filter {
            display: none;
        }
        .dataTables_wrapper .dataTables_info {
            font-size: 0.8rem;
            color: #94a3b8;
            padding-top: 0.5rem;
        }
        .dataTables_wrapper .dataTables_paginate {
            display: none;
        }

        /* ─── Print Styles ─── */
        @media print {
            .filter-card,
            .action-buttons,
            .btn-primary,
            .dataTables_filter,
            .dataTables_paginate,
            .no-print {
                display: none !important;
            }
            .table-card {
                box-shadow: none !important;
                border: 1px solid #e5e7eb;
            }
            .stat-card-modern {
                border: 1px solid #e5e7eb;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">

        {{-- ─── PAGE HEADER ─── --}}
        <div class="page-header-modern">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-graph-up-arrow me-2" style="color: #4f46e5;"></i>
                        {{ __('ui.profit') }} <span class="accent">{{ __('ui.distributions') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-currency-dollar me-1"></i>
                        Track and manage profit distributions to shareholders
                        <span class="badge bg-primary ms-1" style="font-size: 0.6rem; font-weight: 600;">
                            {{ $distributions->total() }} total
                        </span>
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap no-print">
                    <a href="{{ route('admin.profit-distributions.create') }}" class="btn btn-primary" style="border-radius: var(--border-radius-sm); padding: 0.6rem 1.5rem; font-weight: 700; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3); transition: var(--transition-smooth);">
                        <i class="bi bi-plus-circle me-1"></i> {{ __('ui.new_distribution') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- ─── STATS CARDS ─── --}}
        <div class="stats-grid">
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <span class="stat-trend up">
                        <i class="bi bi-arrow-up-short"></i> {{ __('ui.active') }}
                    </span>
                </div>
                <div class="stat-label">Total Distributions</div>
                <div class="stat-value">{{ $distributions->total() }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <span class="stat-trend up">
                        <i class="bi bi-check"></i> {{ __('ui.completed') }}
                    </span>
                </div>
                <div class="stat-label">{{ __('ui.distributed') }}</div>
                <div class="stat-value">{{ $distributions->where('status', 'distributed')->count() }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon yellow">
                        <i class="bi bi-clock"></i>
                    </div>
                    <span class="stat-trend">
                        <i class="bi bi-hourglass-split"></i> Waiting
                    </span>
                </div>
                <div class="stat-label">{{ __('ui.pending') }}</div>
                <div class="stat-value">{{ $distributions->where('status', 'pending')->count() }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <span class="stat-trend up">
                        <i class="bi bi-cash"></i> {{ __('ui.total') }}
                    </span>
                </div>
                <div class="stat-label">Total Distributed Amount</div>
                <div class="stat-value">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($distributions->sum('distributed_amount'), 0) }}</div>
            </div>
        </div>

        {{-- ─── FILTER CARD ─── --}}
        <div class="filter-card">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label"><i class="bi bi-funnel me-1"></i> {{ __('ui.status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">{{ __('ui.all_status') }}</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>📄 Draft</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>✅ Approved</option>
                        <option value="distributed" {{ request('status') == 'distributed' ? 'selected' : '' }}>🎯 Distributed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>❌ Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label"><i class="bi bi-calendar3 me-1"></i> {{ __('ui.start_date') }}</label>
                    <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label"><i class="bi bi-calendar3 me-1"></i> {{ __('ui.end_date') }}</label>
                    <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100" style="font-weight: 600; border-radius: var(--border-radius-sm);">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        @if(request()->anyFilled(['status', 'start_date', 'end_date']))
                            <a href="{{ route('admin.profit-distributions.index') }}" class="btn btn-outline-secondary" style="font-weight: 600; border-radius: var(--border-radius-sm);">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- ─── DISTRIBUTION TABLE ─── --}}
        <div class="table-card">
            <div class="card-header-custom">
                <span class="title">
                    <i class="bi bi-list-ul"></i> Distribution List
                </span>
                <span class="header-badge">
                    <i class="bi bi-database me-1"></i> Total: {{ $distributions->total() }}
                </span>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table-ledger" id="distributionsTable">
                        <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="min-width: 140px;">{{ __('ui.distribution_number') }}</th>
                            <th style="min-width: 140px;">{{ __('ui.period') }}</th>
                            <th class="text-end" style="min-width: 120px;">{{ __('ui.total_profit') }}</th>
                            <th class="text-end" style="min-width: 120px;">{{ __('ui.distributed') }}</th>
                            <th style="min-width: 160px;">{{ __('ui.progress') }}</th>
                            <th style="min-width: 120px;">{{ __('ui.status') }}</th>
                            <th class="text-end" style="min-width: 100px;">{{ __('ui.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($distributions as $distribution)
                            @php
                                $profitClass = $distribution->total_profit >= 0 ? 'text-success' : 'text-danger';
                                $profitSign = $distribution->total_profit >= 0 ? '' : '-';
                                $progress = $distribution->progress_percentage ?? 0;
                                $progressClass = $progress >= 100 ? 'complete' : ($progress >= 50 ? 'warning' : '');
                                $statusClass = $distribution->status ?? 'draft';
                            @endphp
                            <tr>
                                <td class="num-cell" style="color: #94a3b8; font-size: 0.75rem;">{{ $loop->iteration }}</td>
                                <td>
                                    <a href="{{ route('admin.profit-distributions.show', $distribution) }}" class="distribution-link">
                                        {{ $distribution->distribution_number }}
                                    </a>
                                    @if($distribution->created_at)
                                        <br>
                                        <small style="font-size: 0.6rem; color: #94a3b8;">
                                            <i class="bi bi-clock me-1"></i>
                                            {{ $distribution->created_at->format('d M Y, H:i') }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="period-badge">
                                        <i class="bi bi-calendar-range"></i>
                                        {{ $distribution->period_label ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="num-cell {{ $profitClass }}">
                                    {{ $profitSign }}{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format(abs($distribution->total_profit), 2) }}
                                    @if($distribution->total_profit != 0)
                                        <small style="font-size: 0.6rem; color: #94a3b8; display: block;">
                                            {{ $distribution->total_profit >= 0 ? 'Profit' : 'Loss' }}
                                        </small>
                                    @endif
                                </td>
                                <td class="num-cell" style="font-weight: 700; color: #0f172a;">
                                    {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($distribution->distributed_amount, 2) }}
                                    @if($distribution->distributed_amount > 0)
                                        <small style="font-size: 0.6rem; color: #94a3b8; display: block;">
                                            {{ round(($distribution->distributed_amount / max($distribution->total_profit, 1)) * 100, 0) }}% of profit
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="progress-progress-text">{{ round($progress, 0) }}%</span>
                                        <div class="progress-bar-custom">
                                            <div class="progress-fill {{ $progressClass }}"
                                                 style="width: {{ $progress }}%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-status {{ $statusClass }}">
                                        @if($statusClass === 'pending')
                                            <span class="pulse-dot" style="width: 6px; height: 6px; border-radius: 50%; background: #d97706; display: inline-block;"></span>
                                        @else
                                            <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>
                                        @endif
                                        {{ $distribution->status_label ?? ucfirst($statusClass) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('admin.profit-distributions.show', $distribution) }}" class="action-btn text-primary" title="{{ __('ui.view_details') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($distribution->status == 'draft' || $distribution->status == 'pending')
                                            <button class="action-btn text-danger delete-distribution"
                                                    data-id="{{ $distribution->id }}"
                                                    data-number="{{ $distribution->distribution_number }}"
                                                    title="{{ __('ui.delete_distribution') }}">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        @endif
                                        @if($distribution->status == 'draft')
                                            <button class="action-btn text-success edit-distribution"
                                                    data-id="{{ $distribution->id }}"
                                                    title="Edit Distribution"
                                                    onclick="window.location.href='{{ route('admin.profit-distributions.edit', $distribution) }}'">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="bi bi-inboxes icon"></i>
                                        <div class="title">No Profit Distributions Found</div>
                                        <div class="subtitle">Create your first profit distribution to get started.</div>
                                        <a href="{{ route('admin.profit-distributions.create') }}" class="btn btn-primary mt-3" style="border-radius: var(--border-radius-sm); font-weight: 600;">
                                            <i class="bi bi-plus-circle me-1"></i> Create Distribution
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- ─── PAGINATION ─── --}}
                @if($distributions->hasPages())
                    <div class="pagination-wrapper">
                        <div class="pagination-info">
                            <i class="bi bi-info-circle"></i>
                            Showing {{ $distributions->firstItem() ?? 0 }} to {{ $distributions->lastItem() ?? 0 }} of {{ $distributions->total() }} entries
                        </div>
                        {{ $distributions->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                @elseif($distributions->total() > 0)
                    <div class="pagination-wrapper">
                        <div class="pagination-info">
                            <i class="bi bi-check-circle"></i>
                            Showing all {{ $distributions->total() }} entries
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ─── DATATABLE INITIALIZATION ───
            if (typeof $.fn.DataTable !== 'undefined') {
                const table = $('#distributionsTable').DataTable({
                    pageLength: 15,
                    lengthChange: false,
                    ordering: true,
                    searching: false,
                    order: [[0, 'desc']],
                    columnDefs: [
                        { orderable: false, targets: [7] }
                    ],
                    language: {
                        search: '',
                        searchPlaceholder: 'Search...',
                        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                        infoEmpty: 'No entries found',
                        infoFiltered: '(filtered from _MAX_ total entries)',
                    },
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                        '<"row"<"col-sm-12"tr>>' +
                        '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    drawCallback: function() {
                        // Animate progress bars on draw
                        animateProgressBars();
                    }
                });

                // ─── CUSTOM SEARCH ───
                // Add custom search input if needed
                const searchInput = document.querySelector('.dataTables_filter input');
                if (searchInput) {
                    searchInput.classList.add('form-control', 'form-control-sm');
                    searchInput.setAttribute('placeholder', 'Search distributions...');
                }
            }

            // ─── ANIMATE PROGRESS BARS ───
            function animateProgressBars() {
                document.querySelectorAll('.progress-bar-custom .progress-fill').forEach(function(el) {
                    const width = el.style.width;
                    el.style.width = '0%';
                    setTimeout(function() {
                        el.style.width = width;
                    }, 100);
                });
            }

            // Initial animation
            setTimeout(animateProgressBars, 300);

            // ─── DELETE DISTRIBUTION ───
            document.querySelectorAll('.delete-distribution').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.dataset.id;
                    const number = this.dataset.number;

                    Swal.fire({
                        title: 'Delete Distribution?',
                        html: `
                            <div style="text-align: left; margin-top: 0.5rem;">
                                <p style="color: #6b7280; font-size: 0.95rem;">
                                    Are you sure you want to delete distribution
                                    <strong style="color: #dc2626;">"${number}"</strong>?
                                </p>
                                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 0.75rem 1rem; margin-top: 0.75rem;">
                                    <p style="color: #991b1b; font-size: 0.85rem; margin: 0;">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        This will <strong>reverse all transactions</strong> associated with this distribution.
                                    </p>
                                    <p style="color: #7f1d1d; font-size: 0.75rem; margin: 0.25rem 0 0 0;">
                                        This action cannot be undone.
                                    </p>
                                </div>
                            </div>
                        `,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc2626',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: '<i class="bi bi-trash3 me-1"></i> Yes, delete!',
                        cancelButtonText: '<i class="bi bi-x-lg me-1"></i> Cancel',
                        reverseButtons: true,
                        customClass: {
                            confirmButton: 'btn btn-danger',
                            cancelButton: 'btn btn-outline-secondary'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const submitBtn = Swal.getConfirmButton();
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Deleting...';

                            fetch(`/admin/profit-distributions/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Deleted!',
                                            text: data.message || 'Distribution deleted successfully.',
                                            timer: 2000,
                                            showConfirmButton: false
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: data.message || 'Failed to delete distribution.'
                                        });
                                    }
                                })
                                .catch(() => {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Failed to delete distribution. Please try again.'
                                    });
                                });
                        }
                    });
                });
            });

            // ─── AUTO-REFRESH FOR PENDING DISTRIBUTIONS ───
            const hasPending = document.querySelector('.badge-status.pending');
            if (hasPending) {
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
                            .catch(() => {});
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
                                    .catch(() => {});
                            }
                        }, 30000);
                    }
                });
            }

            // ─── KEYBOARD SHORTCUTS ───
            document.addEventListener('keydown', function(e) {
                // Ctrl + N = New Distribution
                if (e.ctrlKey && e.key === 'n') {
                    e.preventDefault();
                    window.location.href = '{{ route("admin.profit-distributions.create") }}';
                }
                // Escape = Clear filters
                if (e.key === 'Escape') {
                    const filterForm = document.querySelector('.filter-card form');
                    if (filterForm && document.activeElement?.tagName === 'INPUT') {
                        document.activeElement.value = '';
                    }
                }
            });
        });
    </script>
@endsection
