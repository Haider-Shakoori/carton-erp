{{-- resources/views/admin/expenses/show.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Expense Details - ' . $expense->name)

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/datatables/buttons/buttons.bootstrap5.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('vendor/fontawesome/7.3.1/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}" />
    <style>
        :root {
            --expense-primary: #ef4444;
            --expense-primary-dark: #dc2626;
            --expense-success: #10b981;
            --expense-danger: #ef4444;
            --expense-warning: #f59e0b;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --radius: 16px;
            --radius-sm: 10px;
            --shadow: 0 1px 3px rgba(0,0,0,0.02), 0 8px 32px rgba(0,0,0,0.04);
            --shadow-hover: 0 1px 3px rgba(0,0,0,0.02), 0 12px 48px rgba(0,0,0,0.08);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--gray-900);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .page-header h1 .accent {
            background: linear-gradient(135deg, var(--expense-primary), #dc2626);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .page-header .subtitle {
            color: var(--gray-500);
            font-size: 0.875rem;
            margin: 0.25rem 0 0 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .page-header .header-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .page-header .header-actions .btn {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            transition: all 0.3s ease;
        }
        .page-header .header-actions .btn:hover {
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            .stats-grid { grid-template-columns: 1fr; }
        }

        .stat-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.25rem 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        .stat-card.purple::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
        .stat-card.green::before { background: linear-gradient(90deg, #10b981, #34d399); }
        .stat-card.red::before { background: linear-gradient(90deg, var(--expense-primary), #f87171); }
        .stat-card.blue::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        
        .stat-card .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-card .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .stat-card .stat-icon.purple {
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            color: #7c3aed;
        }
        .stat-card .stat-icon.green {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #059669;
        }
        .stat-card .stat-icon.red {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
            color: #dc2626;
        }
        .stat-card .stat-icon.blue {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #2563eb;
        }
        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gray-900);
        }
        .stat-card .stat-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 0.25rem;
        }
        .stat-card .stat-value .currency-sm {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-400);
        }

        .info-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            margin-bottom: 1.5rem;
        }
        .info-card:hover {
            box-shadow: var(--shadow-hover);
        }
        .info-card .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .info-card .info-item .label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-400);
        }
        .info-card .info-item .value {
            font-size: 0.95rem;
            color: var(--gray-800);
            font-weight: 600;
        }

        .currency-summary-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .currency-summary-card {
            background: white;
            border-radius: var(--radius-sm);
            padding: 1rem;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            text-align: center;
        }
        .currency-summary-card:hover {
            box-shadow: var(--shadow-hover);
            border-color: var(--expense-primary);
            transform: translateY(-2px);
        }
        .currency-summary-card .currency-symbol {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-800);
        }
        .currency-summary-card .currency-code {
            font-size: 0.75rem;
            color: var(--gray-400);
        }
        .currency-summary-card .currency-balance {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0.25rem 0;
        }
        .currency-summary-card .currency-balance.positive {
            color: var(--expense-success);
        }
        .currency-summary-card .currency-balance.negative {
            color: var(--expense-danger);
        }
        .currency-summary-card .currency-stats {
            font-size: 0.7rem;
            color: var(--gray-400);
        }

        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            padding: 0.75rem 1rem;
            background: white;
            border-radius: var(--radius-sm);
            border: 1px solid var(--gray-200);
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
        }
        .filter-bar .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            border-color: var(--expense-primary);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.08);
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
            background: linear-gradient(135deg, var(--expense-primary), var(--expense-primary-dark));
            color: white;
        }
        .filter-bar .btn-filter-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
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

        .table-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }
        .table-card:hover {
            box-shadow: var(--shadow-hover);
        }
        .table-card .card-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #fafafa, #ffffff);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .table-card .card-header h5 {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .table-card .card-body {
            padding: 1.5rem;
        }

        .badge-credit {
            background: #d1fae5;
            color: #065f46;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .badge-debit {
            background: #fecaca;
            color: #991b1b;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }

        .table-hover tbody tr:hover {
            background: rgba(239, 68, 68, 0.02);
        }

        .dataTables_filter {
            display: none !important;
        }
        .dataTables_length {
            display: none !important;
        }
        .dataTables_info {
            font-size: 0.75rem;
            color: var(--gray-400);
            padding: 0.5rem 0;
        }
        .dataTables_paginate {
            font-size: 0.8rem;
        }
        .dataTables_paginate .paginate_button {
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            border: 1px solid var(--gray-200);
            margin: 0 0.1rem;
            background: white;
            color: var(--gray-600);
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .dataTables_paginate .paginate_button:hover {
            background: var(--gray-100);
            border-color: var(--expense-primary);
            color: var(--expense-primary);
        }
        .dataTables_paginate .paginate_button.current {
            background: var(--expense-primary);
            border-color: var(--expense-primary);
            color: white;
        }
        .dataTables_paginate .paginate_button.disabled {
            opacity: 0.4;
            pointer-events: none;
        }

        .dt-buttons .btn {
            font-size: 0.7rem;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            border: 1px solid var(--gray-200);
            background: white;
            color: var(--gray-600);
            transition: all 0.3s ease;
        }
        .dt-buttons .btn:hover {
            background: var(--gray-50);
            border-color: var(--gray-300);
        }

        .toast-container {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 1055;
        }
        .toast {
            border: none;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow);
            padding: 0.75rem 1rem;
        }
        .toast .toast-body {
            font-weight: 500;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-bar .filter-group {
                flex-wrap: wrap;
            }
            .filter-bar .form-control,
            .filter-bar .form-select {
                min-width: 100%;
            }
            .info-card .info-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .currency-summary-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    {{-- Toast Notifications --}}
    <div class="toast-container">
        @if (session('success'))
            <div class="toast align-items-center text-bg-success show border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
                    <button type="button" class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="toast align-items-center text-bg-danger show border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}</div>
                    <button type="button" class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif
    </div>

    <div class="container-fluid px-3 px-md-4">

        {{-- ─── PAGE HEADER ─── --}}
        <div class="page-header">
            <div>
                <h1>
                    <i class="bi bi-receipt" style="color: var(--expense-primary);"></i>
                    {{ __('ui.expense') }} <span class="accent">{{ __('ui.details') }}</span>
                </h1>
                <p class="subtitle">
                    <i class="bi bi-tag me-1"></i>
                    {{ $expense->name }} · {{ $expense->code }}
                </p>
            </div>
            <div class="header-actions">
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <button class="btn btn-danger" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>

        {{-- ─── STATS CARDS ─── --}}
        @php
            $totalCredit = 0;
            $totalDebit = 0;
            $totalBalance = 0;
            foreach ($summariesByCurrency as $summary) {
                $totalCredit += $summary->credit;
                $totalDebit += $summary->debit;
                $totalBalance += $summary->balance;
            }
            $transactionCount = $expense->transactions->count();
        @endphp

        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="stat-value">{{ $transactionCount }}</div>
                </div>
                <div class="stat-label">{{ __('ui.total_transactions') }}</div>
            </div>

            <div class="stat-card green">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-arrow-up-circle"></i>
                    </div>
                    <div class="stat-value">
                        <span class="currency-sm">$</span>{{ number_format($totalCredit, 2) }}
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.total_credit') }}</div>
            </div>

            <div class="stat-card red">
                <div class="stat-top">
                    <div class="stat-icon red">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                    <div class="stat-value">
                        <span class="currency-sm">$</span>{{ number_format($totalDebit, 2) }}
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.total_debit') }}</div>
            </div>

            <div class="stat-card blue">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="stat-value" style="color: {{ $totalBalance >= 0 ? 'var(--expense-success)' : 'var(--expense-danger)' }};">
                        <span class="currency-sm">$</span>{{ number_format(abs($totalBalance), 2) }}
                    </div>
                </div>
                <div class="stat-label">{{ $totalBalance >= 0 ? 'Net Credit' : 'Net Debit' }}</div>
            </div>
        </div>

        {{-- ─── INFO CARD ─── --}}
        <div class="info-card">
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">{{ __('ui.expense_code') }}</div>
                    <div class="value">{{ $expense->code }}</div>
                </div>
                <div class="info-item">
                    <div class="label">Account Name</div>
                    <div class="value">{{ $expense->name }}</div>
                </div>
                <div class="info-item">
                    <div class="label">{{ __('ui.status') }}</div>
                    <div class="value">
                        <span class="badge {{ $expense->is_active ? 'bg-success' : 'bg-danger' }}">
                            {{ $expense->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="label">{{ __('ui.created_at') }}</div>
                    <div class="value">{{ $expense->created_at->format('M d, Y H:i') }}</div>
                </div>
            </div>
        </div>

        {{-- ─── CURRENCY SUMMARY ─── --}}
        @if ($summariesByCurrency->count() > 0)
        <div class="currency-summary-section">
            @foreach ($summariesByCurrency as $summary)
            @php
                $balance = $summary->balance;
                $balanceClass = $balance >= 0 ? 'positive' : 'negative';
            @endphp
            <div class="currency-summary-card">
                <div class="currency-symbol">
                    {{ $summary->symbol }}
                    <span class="currency-code">{{ $summary->currency }}</span>
                </div>
                <div class="currency-balance {{ $balanceClass }}">
                    {{ number_format(abs($balance), 2) }}
                </div>
                <div class="currency-stats">
                    <span class="text-success">CR: {{ number_format($summary->credit, 2) }}</span>
                    <span class="text-danger ms-2">DR: {{ number_format($summary->debit, 2) }}</span>
                </div>
            </div> @endforeach
        </div>
        @endif

        {{-- ─── FILTER BAR ─── --}}
        <div class="filter-bar">
    <div class="filter-group">
        <span class="filter-label"><i class="bi bi-funnel me-1"></i> Filters:</span>
    </div>

    <div class="date-input-group">
        <input autocomplete="off" type="text" id="from_date" class="form-control datepicker" placeholder="{{ __('ui.from_date') }}"
            style="min-width: 130px;" value="{{ request('from_date') }}">
        <span class="date-separator">→</span>
        <input autocomplete="off" type="text" id="to_date" class="form-control datepicker" placeholder="{{ __('ui.to_date') }}"
            style="min-width: 130px;" value="{{ request('to_date') }}">
    </div>

    <div class="filter-group">
        <select id="transaction_type_filter" class="form-select" style="min-width: 120px;">
            <option value="">{{ __('ui.all_types') }}</option>
            <option value="credit">{{ __('ui.credit') }}</option>
            <option value="debit">Debit</option>
        </select>
    </div>

    <button class="btn-filter btn-filter-primary" id="applyFilters">
        <i class="bi bi-search me-1"></i> Apply
    </button>

    <button class="btn-filter btn-filter-secondary" id="resetFilters">
        <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('ui.reset') }}
    </button>

    <div class="ms-auto">
        <div class="btn-group" role="group">
            <button class="btn btn-sm btn-outline-danger" id="exportPdf">
                <i class="bi bi-file-pdf"></i> PDF
            </button>
            <button class="btn btn-sm btn-outline-success" id="exportExcel">
                <i class="bi bi-file-excel"></i> Excel
            </button>
            <button class="btn btn-sm btn-outline-secondary" id="exportCsv">
                <i class="bi bi-file-text"></i> CSV
            </button>
        </div>
    </div>
    </div>

    {{-- ─── TRANSACTIONS TABLE ─── --}}
    <div class="table-card">
        <div class="card-header">
            <h5>
                <i class="bi bi-list-ul" style="color: var(--expense-primary);"></i>
                Transaction History
                <span class="badge bg-secondary ms-2" style="font-size: 0.65rem; font-weight: 600;">
                    {{ $transactionCount }} transactions
                </span>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm" id="transactions-table" style="width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>{{ __('ui.date') }}</th>
                            <th>{{ __('ui.amount') }}</th>
                            <th>{{ __('ui.currency') }}</th>
                            <th>{{ __('ui.type') }}</th>
                            <th>Reference</th>
                            <th>{{ __('ui.description') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expense->transactions->sortByDesc('created_at') as $transaction)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td style="font-size: 0.8rem; color: var(--gray-500);">
                                    {{ $transaction->created_at->format('M d, Y H:i') }}
                                </td>
                                <td>
                                    <strong>{{ number_format($transaction->amount, 2) }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $transaction->currency->code ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="badge {{ $transaction->transaction_type == 'credit' ? 'badge-credit' : 'badge-debit' }}">
                                        {{ ucfirst($transaction->transaction_type) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($transaction->table_name && $transaction->table_row_id)
                                        <span class="badge bg-info text-white" style="font-size: 0.65rem;">
                                            {{ ucfirst($transaction->table_name) }} #{{ $transaction->table_row_id }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $transaction->description ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="bi bi-inboxes"
                                        style="font-size: 2rem; color: var(--gray-300); display: block; margin-bottom: 0.5rem;"></i>
                                    <span class="text-muted">No transactions found for this expense account</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/moment/moment-2.29.4.min.js') }}"></script>
    <script src="{{ asset('vendor/daterangepicker/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/buttons/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/buttons/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/buttons/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/buttons/buttons.print.min.js') }}"></script>
    <script src="{{ asset('vendor/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('vendor/pdfmake/pdfmake-0.2.7.min.js') }}"></script>
    <script src="{{ asset('vendor/pdfmake/vfs_fonts-0.2.7.js') }}"></script>
    <script>
        $(document).ready(function() {

            // ─── Datepicker ───
            $('.datepicker').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: false,
                locale: {
                    format: 'YYYY-MM-DD'
                }
            });

            $('.datepicker').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD'));
            });

            $('.datepicker').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

            // ─── DataTable with Server-side or Client-side ───
            var table = $('#transactions-table').DataTable({
                dom: 'Bfrtip',
                buttons: [{
                        extend: 'copy',
                        className: 'btn btn-sm btn-outline-secondary',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6]
                        }
                    },
                    {
                        extend: 'csv',
                        className: 'btn btn-sm btn-outline-secondary',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6]
                        }
                    },
                    {
                        extend: 'excel',
                        className: 'btn btn-sm btn-outline-secondary',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6]
                        }
                    },
                    {
                        extend: 'pdf',
                        className: 'btn btn-sm btn-outline-secondary',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6]
                        },
                        customize: function(doc) {
                            doc.defaultStyle.fontSize = 10;
                            doc.styles.tableHeader.fontSize = 11;
                            doc.styles.tableHeader.fillColor = '#ef4444';
                            doc.styles.tableHeader.color = 'white';
                        }
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-sm btn-outline-secondary',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5, 6]
                        }
                    }
                ],
                language: {
                    info: "Showing _START_ to _END_ of _TOTAL_ transactions",
                    infoEmpty: "Showing 0 to 0 of 0 transactions",
                    infoFiltered: "(filtered from _MAX_ total transactions)",
                    emptyTable: "No transactions found",
                    zeroRecords: "No matching transactions found"
                },
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                order: [
                    [1, 'desc']
                ],
                columnDefs: [{
                    orderable: false,
                    targets: [0, 4, 5]
                }]
            });

            // ─── Apply Filters ───
            $('#applyFilters').on('click', function() {
                var fromDate = $('#from_date').val();
                var toDate = $('#to_date').val();
                var type = $('#transaction_type_filter').val();

                // Custom filtering function for date range
                $.fn.dataTable.ext.search.push(
                    function(settings, data, dataIndex) {
                        var date = data[1]; // Date column
                        var from = $('#from_date').val();
                        var to = $('#to_date').val();

                        if (!from && !to) return true;

                        // Parse date - assuming format MMM DD, YYYY HH:mm
                        var dateParts = date.replace(',', '').split(' ');
                        if (dateParts.length >= 4) {
                            var month = moment(dateParts[0] + ' ' + dateParts[1] + ' ' + dateParts[2],
                                'MMM D YYYY');
                            if (from && moment(from, 'YYYY-MM-DD').isAfter(month)) return false;
                            if (to && moment(to, 'YYYY-MM-DD').isBefore(month)) return false;
                        }
                        return true;
                    }
                );

                // Filter by type
                if (type) {
                    table.column(4).search(type, true, false);
                } else {
                    table.column(4).search('', true, false);
                }

                table.draw();
            });

            // ─── Reset Filters ───
            $('#resetFilters').on('click', function() {
                $('#from_date').val('');
                $('#to_date').val('');
                $('#transaction_type_filter').val('');

                // Remove custom date filter
                $.fn.dataTable.ext.search.pop();
                table.search('').columns().search('').draw();
            });

            // ─── Export Buttons ───
            $('#exportPdf').on('click', function() {
                table.button('.buttons-pdf').trigger();
            });

            $('#exportExcel').on('click', function() {
                table.button('.buttons-excel').trigger();
            });

            $('#exportCsv').on('click', function() {
                table.button('.buttons-csv').trigger();
            });

            // ─── Show Toasts ───
            const toastElements = document.querySelectorAll('.toast');
            toastElements.forEach(toast => new bootstrap.Toast(toast).show());

        });
    </script>
@endsection
