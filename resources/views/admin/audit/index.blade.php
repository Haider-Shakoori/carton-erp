{{-- resources/views/admin/audit/index.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.audit_logs'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        /* ─── Stats Cards ─── */
        .stats-grid-modern {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card-modern {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
        }
        .stat-card-modern:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .stat-card-modern .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        .stat-card-modern .stat-icon.primary { background: var(--primary-bg); color: var(--primary); }
        .stat-card-modern .stat-icon.success { background: var(--success-bg); color: var(--success); }
        .stat-card-modern .stat-icon.warning { background: var(--warning-bg); color: var(--warning); }
        .stat-card-modern .stat-icon.info { background: var(--info-bg); color: var(--info); }

        .stat-card-modern .stat-info .stat-number {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gray-800);
            line-height: 1.2;
        }
        .stat-card-modern .stat-info .stat-label {
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.04em;
        }

        /* ─── Filter Section ─── */
        .filter-section-modern {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        .filter-section-modern .filter-label {
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.25rem;
        }
        .filter-section-modern .form-control,
        .filter-section-modern .form-select {
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-200);
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            transition: var(--transition);
            background: white;
        }
        .filter-section-modern .form-control:focus,
        .filter-section-modern .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
            outline: none;
        }

        /* ─── Event Badges ─── */
        .badge-event-modern {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .badge-event-modern .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .badge-event-modern.created { background: #d1fae5; color: #065f46; }
        .badge-event-modern.created .dot { background: #065f46; }
        .badge-event-modern.updated { background: #dbeafe; color: #1e40af; }
        .badge-event-modern.updated .dot { background: #1e40af; }
        .badge-event-modern.deleted { background: #fee2e2; color: #991b1b; }
        .badge-event-modern.deleted .dot { background: #991b1b; }
        .badge-event-modern.restored { background: #fef3c7; color: #92400e; }
        .badge-event-modern.restored .dot { background: #92400e; }
        .badge-event-modern.login { background: #e0e7ff; color: #3730a3; }
        .badge-event-modern.login .dot { background: #3730a3; }
        .badge-event-modern.logout { background: #f3f4f6; color: #6b7280; }
        .badge-event-modern.logout .dot { background: #6b7280; }

        /* ─── Table Enhancements ─── */
        .table-audit {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 4px;
            font-size: 0.85rem;
        }
        .table-audit thead th {
            padding: 0.6rem 1rem;
            background: var(--gray-50);
            color: var(--gray-500);
            font-weight: 700;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border: none;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        .table-audit thead th:first-child {
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
        }
        .table-audit thead th:last-child {
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
        }
        .table-audit tbody tr {
            background: white;
            transition: var(--transition);
            border-radius: var(--radius-sm);
        }
        .table-audit tbody tr:hover {
            box-shadow: var(--shadow-sm);
            background: var(--gray-50);
        }
        .table-audit tbody td {
            padding: 0.6rem 1rem;
            border: none;
            vertical-align: middle;
        }
        .table-audit tbody td:first-child {
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
        }
        .table-audit tbody td:last-child {
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
        }

        /* ─── User Avatar ─── */
        .user-avatar-small {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--primary-bg);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.6rem;
            flex-shrink: 0;
        }
        .user-avatar-small img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .user-info .user-name {
            font-weight: 600;
            color: var(--gray-800);
        }
        .user-info .user-role {
            font-size: 0.65rem;
            color: var(--gray-400);
        }

        /* ─── Description ─── */
        .log-description {
            color: var(--gray-700);
            font-size: 0.85rem;
        }
        .log-description .highlight {
            color: var(--primary);
            font-weight: 500;
        }

        /* ─── Subject Tag ─── */
        .subject-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.15rem 0.6rem;
            border-radius: 12px;
            font-size: 0.6rem;
            font-weight: 500;
            background: var(--gray-100);
            color: var(--gray-600);
        }
        .subject-tag i {
            font-size: 0.5rem;
        }

        /* ─── Timestamp ─── */
        .log-timestamp {
            font-size: 0.75rem;
            color: var(--gray-400);
            white-space: nowrap;
        }
        .log-timestamp .date {
            font-weight: 500;
        }
        .log-timestamp .time {
            color: var(--gray-400);
        }

        /* ─── Action Buttons ─── */
        .action-btn-group {
            display: flex;
            gap: 0.25rem;
            justify-content: flex-end;
        }
        .action-btn-group .btn-icon {
            width: 30px;
            height: 30px;
            border: none;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-400);
            transition: var(--transition);
            background: transparent;
            cursor: pointer;
        }
        .action-btn-group .btn-icon:hover {
            background: var(--gray-100);
            color: var(--gray-700);
        }
        .action-btn-group .btn-icon.view:hover {
            background: var(--primary-bg);
            color: var(--primary);
        }

        /* ─── Empty State ─── */
        .empty-state-modern {
            text-align: center;
            padding: 3rem 1rem;
        }
        .empty-state-modern .empty-icon {
            font-size: 3rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }
        .empty-state-modern .empty-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--gray-700);
        }
        .empty-state-modern .empty-desc {
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        /* ─── Pagination Fix ─── */
        .pagination-modern {
            display: flex;
            gap: 0.25rem;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .pagination-modern .page-item {
            list-style: none;
        }
        .pagination-modern .page-link {
            padding: 0.35rem 0.85rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--gray-200);
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--gray-500);
            background: white;
            transition: var(--transition);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .pagination-modern .page-link i {
            font-size: 0.7rem;
        }
        .pagination-modern .page-link:hover {
            background: var(--primary-bg);
            color: var(--primary);
            border-color: var(--primary-light);
        }
        .pagination-modern .active .page-link {
            background: var(--primary-gradient);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
        }
        .pagination-modern .disabled .page-link {
            color: var(--gray-300);
            cursor: not-allowed;
            background: var(--gray-50);
        }
        .pagination-modern .disabled .page-link:hover {
            background: var(--gray-50);
            color: var(--gray-300);
            border-color: var(--gray-200);
        }

        .pagination-info {
            font-size: 0.8rem;
            color: var(--gray-400);
        }

        /* ─── Tooltip ─── */
        .log-tooltip {
            position: relative;
            cursor: help;
        }
        .log-tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background: var(--gray-800);
            color: white;
            text-align: left;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-sm);
            position: absolute;
            z-index: 10;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.7rem;
            font-weight: 400;
            white-space: normal;
        }
        .log-tooltip .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: var(--gray-800) transparent transparent transparent;
        }
        .log-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        /* ─── Search Wrapper ─── */
        .search-wrapper {
            position: relative;
        }
        .search-wrapper .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 0.8rem;
        }
        .search-wrapper .form-control {
            padding-left: 30px;
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-200);
            font-size: 0.8rem;
            padding: 0.3rem 0.75rem 0.3rem 30px;
            width: 200px;
            background: var(--gray-50);
            transition: var(--transition);
        }
        .search-wrapper .form-control:focus {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
        }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .filter-section-modern .row {
                gap: 0.5rem;
            }
            .stats-grid-modern {
                grid-template-columns: repeat(2, 1fr);
            }
            .table-audit {
                font-size: 0.75rem;
            }
            .table-audit thead th,
            .table-audit tbody td {
                padding: 0.4rem 0.5rem;
            }
            .pagination-modern .page-link {
                padding: 0.25rem 0.6rem;
                font-size: 0.7rem;
            }
            .pagination-modern .page-link i {
                font-size: 0.6rem;
            }
            .search-wrapper .form-control {
                width: 140px;
            }
            .d-flex.justify-content-between.align-items-center {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start !important;
            }
            .pagination-modern {
                justify-content: flex-start;
            }
        }
        @media (max-width: 480px) {
            .stats-grid-modern {
                grid-template-columns: 1fr;
            }
            .stat-card-modern {
                padding: 0.75rem;
            }
            .stat-card-modern .stat-number {
                font-size: 1.2rem;
            }
            .search-wrapper .form-control {
                width: 100%;
            }
        }
        /* ─── Log Details Modal ─── */
        .log-details {
            padding: 0.25rem;
        }
        .log-details .detail-label {
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.15rem;
        }
        .log-details .detail-value {
            font-size: 0.95rem;
            color: var(--gray-800);
        }
        .log-details .detail-value .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .log-details .detail-value .user-name {
            font-weight: 600;
        }
        .log-details .detail-value .user-role {
            font-size: 0.7rem;
            color: var(--gray-400);
        }
        .log-details .properties-container {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 0.75rem 1rem;
            max-height: 200px;
            overflow-y: auto;
        }
        .log-details .properties-container .property-item {
            display: flex;
            gap: 0.5rem;
            padding: 0.25rem 0;
            border-bottom: 1px solid var(--gray-100);
            font-size: 0.8rem;
        }
        .log-details .properties-container .property-item:last-child {
            border-bottom: none;
        }
        .log-details .properties-container .property-key {
            font-weight: 600;
            color: var(--gray-600);
            min-width: 100px;
        }
        .log-details .properties-container .property-value {
            color: var(--gray-700);
            word-break: break-word;
        }
        .log-details .properties-container .property-value code {
            background: var(--gray-100);
            padding: 0.1rem 0.3rem;
            border-radius: 3px;
            font-size: 0.75rem;
            color: var(--gray-700);
        }

        /* ─── Badge Event Modern ─── */
        .badge-event-modern {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .badge-event-modern .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .badge-event-modern.created { background: #d1fae5; color: #065f46; }
        .badge-event-modern.created .dot { background: #065f46; }
        .badge-event-modern.updated { background: #dbeafe; color: #1e40af; }
        .badge-event-modern.updated .dot { background: #1e40af; }
        .badge-event-modern.deleted { background: #fee2e2; color: #991b1b; }
        .badge-event-modern.deleted .dot { background: #991b1b; }
        .badge-event-modern.restored { background: #fef3c7; color: #92400e; }
        .badge-event-modern.restored .dot { background: #92400e; }
        .badge-event-modern.login { background: #e0e7ff; color: #3730a3; }
        .badge-event-modern.login .dot { background: #3730a3; }
        .badge-event-modern.logout { background: #f3f4f6; color: #6b7280; }
        .badge-event-modern.logout .dot { background: #6b7280; }

        /* ─── User Avatar Small ─── */
        .user-avatar-small {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-bg);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.7rem;
            flex-shrink: 0;
        }
        .user-avatar-small img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        /* ─── Subject Tag ─── */
        .subject-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.15rem 0.6rem;
            border-radius: 12px;
            font-size: 0.6rem;
            font-weight: 500;
            background: var(--gray-100);
            color: var(--gray-600);
        }
        .subject-tag i {
            font-size: 0.5rem;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <!-- ─── Page Header ─── -->
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-clock-history me-2"></i> Audit <span class="accent">Logs</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-list-check me-1"></i> Track all system activities and changes
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-outline-secondary" onclick="window.location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i> {{ __('ui.refresh') }}
                    </button>
                    <a href="{{ route('admin.audit.export') }}" class="btn btn-outline-success">
                        <i class="bi bi-download me-1"></i> Export CSV
                    </a>
                    <button class="btn btn-outline-danger" onclick="clearLogs()">
                        <i class="bi bi-trash me-1"></i> Clear Old Logs
                    </button>
                </div>
            </div>
        </div>

        <!-- ─── Stats ─── -->
        <div class="stats-grid-modern">
            <div class="stat-card-modern">
                <div class="stat-icon primary">
                    <i class="bi bi-database"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">{{ $stats['total'] }}</div>
                    <div class="stat-label">Total Logs</div>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon success">
                    <i class="bi bi-calendar-today"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">{{ $stats['today'] }}</div>
                    <div class="stat-label">{{ __('ui.today') }}</div>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon warning">
                    <i class="bi bi-calendar-week"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">{{ $stats['this_week'] }}</div>
                    <div class="stat-label">{{ __('ui.this_week') }}</div>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon info">
                    <i class="bi bi-calendar-month"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">{{ $stats['this_month'] }}</div>
                    <div class="stat-label">{{ __('ui.this_month') }}</div>
                </div>
            </div>
        </div>

        <!-- ─── Filters ─── -->
        <div class="filter-section-modern">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <div class="filter-label">User</div>
                    <select name="user_id" class="form-select select2">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="filter-label">Event Type</div>
                    <select name="event" class="form-select">
                        <option value="">All Events</option>
                        @foreach($events as $event)
                            <option value="{{ $event }}" {{ request('event') == $event ? 'selected' : '' }}>
                                {{ ucfirst($event) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="filter-label">{{ __('ui.start_date') }}</div>
                    <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <div class="filter-label">{{ __('ui.end_date') }}</div>
                    <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2">
                    <div class="filter-label">{{ __('ui.search') }}</div>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ __('ui.search_logs') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> {{ __('ui.apply_filters') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- ─── Logs Table ─── -->
        <div class="table-card">
            <div class="card-header-custom">
            <span class="fw-semibold">
                <i class="bi bi-list-ul me-1"></i> Activity Logs
            </span>
                <div class="d-flex align-items-center gap-3">
                <span class="header-badge">
                    <i class="bi bi-database me-1"></i> Total: {{ $logs->total() }}
                </span>
                    <div class="search-wrapper">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="form-control form-control-sm" id="logSearch" placeholder="{{ __('ui.search_table') }}">
                    </div>
                </div>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table-audit" id="logsTable">
                        <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="min-width: 140px;">User</th>
                            <th style="min-width: 100px;">Event</th>
                            <th>{{ __('ui.description') }}</th>
                            <th style="min-width: 100px;">Subject</th>
                            <th style="min-width: 160px;">Date/Time</th>
                            <th style="width: 60px;" class="text-end">{{ __('ui.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="num-cell">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="user-info">
                                        @if($log->causer && $log->causer->profile_photo)
                                            <img src="{{ Storage::url($log->causer->profile_photo) }}" class="user-avatar-small">
                                        @else
                                            <div class="user-avatar-small">
                                                {{ $log->causer ? strtoupper(substr($log->causer->name, 0, 2)) : 'SY' }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="user-name">{{ $log->causer?->name ?? 'System' }}</div>
                                            <div class="user-role">{{ $log->causer?->email ?? 'System' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                <span class="badge-event-modern {{ $log->event }}">
                                    <span class="dot"></span>
                                    {{ ucfirst($log->event) }}
                                </span>
                                </td>
                                <td>
                                    <div class="log-description log-tooltip">
                                        {{ Str::limit($log->description, 60) }}
                                        @if(strlen($log->description) > 60)
                                            <span class="tooltip-text">{{ $log->description }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                <span class="subject-tag">
                                    <i class="bi bi-tag"></i>
                                    {{ class_basename($log->subject_type ?? 'N/A') }}
                                </span>
                                </td>
                                <td>
                                    <div class="log-timestamp">
                                        <div class="date">{{ $log->created_at->format('M d, Y') }}</div>
                                        <div class="time">{{ $log->created_at->format('h:i A') }}</div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="action-btn-group">
                                        <button class="btn-icon view" onclick="viewLog({{ $log->id }})" title="{{ __('ui.view_details') }}">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state-modern">
                                        <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                                        <div class="empty-title">No Audit Logs Found</div>
                                        <div class="empty-desc">No activities have been logged in the system yet.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="pagination-info">
                        {{ __('ui.showing') }} <strong>{{ $logs->firstItem() ?? 0 }}</strong> to <strong>{{ $logs->lastItem() ?? 0 }}</strong> {{ __('ui.of') }} <strong>{{ $logs->total() }}</strong> {{ __('ui.entries') }}
                    </div>
                    <div>
                        @if ($logs->hasPages())
                            <ul class="pagination-modern">
                                {{-- Previous Page Link --}}
                                @if ($logs->onFirstPage())
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="bi bi-chevron-left"></i></span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $logs->previousPageUrl() }}" rel="prev">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                @endif

                                {{-- Pagination Elements --}}
                                @foreach ($logs->links()->elements as $element)
                                    @if (is_string($element))
                                        <li class="page-item disabled">
                                            <span class="page-link">{{ $element }}</span>
                                        </li>
                                    @endif

                                    @if (is_array($element))
                                        @foreach ($element as $page => $url)
                                            @if ($page == $logs->currentPage())
                                                <li class="page-item active" aria-current="page">
                                                    <span class="page-link">{{ $page }}</span>
                                                </li>
                                            @else
                                                <li class="page-item">
                                                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                                </li>
                                            @endif
                                        @endforeach
                                    @endif
                                @endforeach

                                {{-- Next Page Link --}}
                                @if ($logs->hasMorePages())
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $logs->nextPageUrl() }}" rel="next">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                @else
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="bi bi-chevron-right"></i></span>
                                    </li>
                                @endif
                            </ul>
                        @endif
                    </div>
                </div>

                <!-- ─── View Log Modal ─── -->
                <div class="modal fade" id="viewLogModal" tabindex="-1" data-bs-backdrop="static">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bi bi-info-circle me-2" style="color: white;"></i>
                                    <span style="color: white;">Audit Log Details</span>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4" id="logDetailsContent">
                                <div class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">{{ __('ui.loading') }}</span>
                                    </div>
                                    <p class="mt-2 text-muted">{{ __('ui.loading_log_details') }}</p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.close') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // ─── Select2 ───
            $('.select2').select2({
                width: '100%',
                placeholder: 'Select...',
                allowClear: true
            });

            // ─── DataTable ───
            const table = $('#logsTable').DataTable({
                pageLength: 15,
                lengthChange: false,
                ordering: true,
                searching: true,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [6] }
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Search...',
                },
                dom: 't'
            });

            // ─── Custom Search ───
            $('#logSearch').on('keyup', function() {
                table.search($(this).val()).draw();
            });

            // ─── Clear Logs ───
            window.clearLogs = function() {
                Swal.fire({
                    title: 'Clear Old Logs?',
                    html: `
                <p>This will delete audit logs older than:</p>
                <select class="form-control" id="daysSelect">
                    <option value="30">30 days</option>
                    <option value="60">60 days</option>
                    <option value="90" selected>90 days</option>
                    <option value="180">180 days</option>
                    <option value="365">365 days</option>
                </select>
                <p class="text-muted small mt-2">This action cannot be undone.</p>
            `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, delete!',
                    cancelButtonText: 'Cancel',
                    preConfirm: () => {
                        const days = document.getElementById('daysSelect').value;
                        return { days: days };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait while logs are being deleted.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: '{{ route("admin.audit.clear") }}',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                days: result.value.days
                            },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Success!', res.message, 'success').then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', res.message || 'Failed to clear logs.', 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to clear logs. Please try again.', 'error');
                            }
                        });
                    }
                });
            };

            // ─── View Log ───
            // ─── View Log Details ───
            window.viewLog = function(id) {
                // Show modal with loading
                $('#viewLogModal').modal('show');
                $('#logDetailsContent').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">{{ __('ui.loading') }}</span>
            </div>
            <p class="mt-2 text-muted">{{ __('ui.loading_log_details') }}</p>
        </div>
    `);

                // Fetch log details
                $.ajax({
                    url: '/admin/audit/' + id,
                    method: 'GET',
                    success: function(res) {
                        if (res.success) {
                            renderLogDetails(res.data);
                        } else {
                            $('#logDetailsContent').html(`
                    <div class="text-center py-5 text-danger">
                        <i class="bi bi-exclamation-circle fs-1 d-block mb-3"></i>
                        <h5>Error Loading Log</h5>
                        <p>${res.message || 'Failed to load log details.'}</p>
                    </div>
                `);
                        }
                    },
                    error: function() {
                        $('#logDetailsContent').html(`
                <div class="text-center py-5 text-danger">
                    <i class="bi bi-exclamation-circle fs-1 d-block mb-3"></i>
                    <h5>Error</h5>
                    <p>Failed to load log details. Please try again.</p>
                </div>
            `);
                    }
                });
            };

// ─── Render Log Details ───
            function renderLogDetails(log) {
                const propertiesHtml = log.properties ? Object.entries(log.properties).map(([key, value]) => {
                    if (typeof value === 'object') {
                        value = JSON.stringify(value, null, 2);
                    }
                    return `
            <div class="property-item">
                <span class="property-key">${key}:</span>
                <span class="property-value"><code>${value}</code></span>
            </div>
        `;
                }).join('') : '<span class="text-muted">No additional data</span>';

                const html = `
        <div class="log-details">
            <!-- Header Info -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="detail-label">Log ID</div>
                    <div class="detail-value fw-bold">#${log.id}</div>
                </div>
                <div class="col-md-6">
                    <div class="detail-label">Event</div>
                    <div class="detail-value">
                        <span class="badge-event-modern ${log.event}">
                            <span class="dot"></span>
                            ${ucfirst(log.event)}
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-label">User</div>
                    <div class="detail-value">
                        <div class="user-info">
                            ${log.user_avatar ? `<img src="${log.user_avatar}" class="user-avatar-small">` :
                    `<div class="user-avatar-small">${log.user_initials || 'SY'}</div>`}
                            <div>
                                <div class="user-name">${log.user_name || 'System'}</div>
                                <div class="user-role">${log.user_email || ''}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-label">Date & Time</div>
                    <div class="detail-value">
                        <div>${log.created_at_formatted || log.created_at}</div>
                        <div class="text-muted small">${log.created_at_diff || ''}</div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="mb-3">
                <div class="detail-label">{{ __('ui.description') }}</div>
                <div class="detail-value p-2 bg-light rounded">${log.description}</div>
            </div>

            <!-- Subject Info -->
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="detail-label">Subject Type</div>
                    <div class="detail-value">
                        <span class="subject-tag">
                            <i class="bi bi-tag"></i>
                            ${log.subject_type || 'N/A'}
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-label">Subject ID</div>
                    <div class="detail-value">${log.subject_id || 'N/A'}</div>
                </div>
            </div>

            <!-- Log Name -->
            <div class="mb-3">
                <div class="detail-label">Log Name</div>
                <div class="detail-value">${log.log_name || 'N/A'}</div>
            </div>

            <!-- Properties -->
            <div>
                <div class="detail-label">Properties</div>
                <div class="properties-container">
                    ${propertiesHtml}
                </div>
            </div>
        </div>
    `;

                $('#logDetailsContent').html(html);
            }

// ─── Helper: Uppercase first letter ───
            function ucfirst(str) {
                if (!str) return '';
                return str.charAt(0).toUpperCase() + str.slice(1);
            }
        });
    </script>
@endsection
