{{-- resources/views/admin/production-orders/show.blade.php --}}

@extends('layouts.admin.base')

@section('title', 'Production Order Details')

@section('css')
    <style>
        /* ─── Variables ─── */
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5, #7c3aed);
            --success-gradient: linear-gradient(135deg, #059669, #10b981);
            --danger-gradient: linear-gradient(135deg, #dc2626, #ef4444);
            --warning-gradient: linear-gradient(135deg, #d97706, #f59e0b);
            --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            --card-shadow-hover: 0 8px 30px rgba(0, 0, 0, 0.08);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius-lg: 16px;
            --border-radius-md: 12px;
            --border-radius-sm: 8px;
        }

        /* ─── Base Styles ─── */
        .detail-label {
            font-size: 0.65rem;
            color: #94a3b8;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .detail-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
        }

        /* ─── Status Badges ─── */
        .status-badge {
            padding: 5px 16px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            letter-spacing: 0.3px;
            transition: var(--transition-smooth);
            position: relative;
        }
        .status-badge .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            animation: pulse-dot 1.5s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.7); }
        }
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .status-badge.pending .pulse-dot {
            background: #d97706;
        }
        .status-badge.in_progress {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }
        .status-badge.in_progress .pulse-dot {
            background: #2563eb;
        }
        .status-badge.completed {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .status-badge.completed .pulse-dot {
            background: #059669;
        }
        .status-badge.cancelled {
            background: #f3f4f6;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }
        .status-badge.cancelled .pulse-dot {
            background: #6b7280;
        }

        /* ─── Currency Badge ─── */
        .currency-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.6rem;
            font-weight: 700;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .currency-badge.afn {
            background: #fef3c7;
            color: #92400e;
        }
        .currency-badge.usd {
            background: #d1fae5;
            color: #065f46;
        }

        /* ─── Action Buttons ─── */
        .action-btn-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .action-btn {
            padding: 0.6rem 1.5rem;
            border-radius: var(--border-radius-sm);
            font-weight: 700;
            font-size: 0.8rem;
            border: none;
            transition: var(--transition-smooth);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.3px;
        }
        .action-btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0);
            transition: var(--transition-smooth);
        }
        .action-btn:hover::after {
            background: rgba(255, 255, 255, 0.15);
        }
        .action-btn:active {
            transform: scale(0.96);
        }
        .action-btn-success {
            background: var(--success-gradient);
            color: white;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);
        }
        .action-btn-success:hover {
            box-shadow: 0 6px 24px rgba(5, 150, 105, 0.45);
            transform: translateY(-2px);
        }
        .action-btn-danger {
            background: var(--danger-gradient);
            color: white;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);
        }
        .action-btn-danger:hover {
            box-shadow: 0 6px 24px rgba(220, 38, 38, 0.45);
            transform: translateY(-2px);
        }
        .action-btn-secondary {
            background: #f1f5f9;
            color: #475569;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .action-btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }
        .action-btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
        }
        .action-btn-primary:hover {
            box-shadow: 0 6px 24px rgba(79, 70, 229, 0.45);
            transform: translateY(-2px);
        }

        /* ─── Cards ─── */
        .card-modern {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--card-shadow);
            border: 1px solid #f1f5f9;
            overflow: hidden;
            transition: var(--transition-smooth);
            height: 100%;
            animation: fade-up 0.5s ease forwards;
            opacity: 0;
        }
        .card-modern:hover {
            box-shadow: var(--card-shadow-hover);
            border-color: #e2e8f0;
        }
        .card-modern:nth-child(1) { animation-delay: 0.05s; }
        .card-modern:nth-child(2) { animation-delay: 0.1s; }
        .card-modern:nth-child(3) { animation-delay: 0.15s; }

        @keyframes fade-up {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header-modern {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #fafafa, #ffffff);
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .card-header-modern h6 {
            font-size: 0.8rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .card-header-modern h6 i {
            color: #4f46e5;
            font-size: 1rem;
        }
        .card-body-modern {
            padding: 1.5rem;
        }

        /* ─── Stats Grid ─── */
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
            text-align: center;
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
            border-radius: 0 0 3px 3px;
        }
        .stat-card-modern:nth-child(1) { animation-delay: 0.05s; }
        .stat-card-modern:nth-child(1)::before { background: #4f46e5; }
        .stat-card-modern:nth-child(2) { animation-delay: 0.1s; }
        .stat-card-modern:nth-child(2)::before { background: #059669; }
        .stat-card-modern:nth-child(3) { animation-delay: 0.15s; }
        .stat-card-modern:nth-child(3)::before { background: #d97706; }
        .stat-card-modern:nth-child(4) { animation-delay: 0.2s; }
        .stat-card-modern:nth-child(4)::before { background: #7c3aed; }

        .stat-card-modern:hover {
            border-color: #e2e8f0;
            transform: translateY(-4px);
            box-shadow: var(--card-shadow-hover);
        }
        .stat-card-modern .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 1.2rem;
            transition: var(--transition-smooth);
        }
        .stat-card-modern:hover .stat-icon {
            transform: scale(1.05) rotate(-3deg);
        }
        .stat-card-modern .stat-icon.blue {
            background: #eef2ff;
            color: #4f46e5;
        }
        .stat-card-modern .stat-icon.green {
            background: #ecfdf5;
            color: #059669;
        }
        .stat-card-modern .stat-icon.orange {
            background: #fffbeb;
            color: #d97706;
        }
        .stat-card-modern .stat-icon.purple {
            background: #f5f3ff;
            color: #7c3aed;
        }
        .stat-card-modern .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }
        .stat-card-modern .stat-value.green {
            color: #059669;
        }
        .stat-card-modern .stat-value.blue {
            color: #4f46e5;
        }
        .stat-card-modern .stat-value.orange {
            color: #d97706;
        }
        .stat-card-modern .stat-value.purple {
            color: #7c3aed;
        }
        .stat-card-modern .stat-label {
            font-size: 0.65rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-top: 0.25rem;
            font-weight: 600;
        }
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ─── Progress Ring ─── */
        .progress-ring-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            padding: 0.5rem 0;
        }
        .progress-ring-wrapper {
            position: relative;
            width: 140px;
            height: 140px;
            flex-shrink: 0;
        }
        .progress-ring-wrapper svg {
            transform: rotate(-90deg);
        }
        .progress-ring-wrapper .ring-bg {
            fill: none;
            stroke: #f1f5f9;
            stroke-width: 8;
        }
        .progress-ring-wrapper .ring-fg {
            fill: none;
            stroke: #4f46e5;
            stroke-width: 8;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .progress-ring-wrapper .ring-fg.green {
            stroke: #059669;
        }
        .progress-ring-wrapper .ring-fg.orange {
            stroke: #d97706;
        }
        .progress-ring-wrapper .ring-fg.red {
            stroke: #dc2626;
        }
        .progress-ring-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        .progress-ring-center .percentage {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
        }
        .progress-ring-center .label {
            font-size: 0.6rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            margin-top: 0.15rem;
        }
        .progress-stats {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .progress-stat-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            padding: 0.25rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .progress-stat-item:last-child {
            border-bottom: none;
        }
        .progress-stat-item .label {
            color: #64748b;
        }
        .progress-stat-item .value {
            font-weight: 600;
            color: #0f172a;
        }

        /* ─── Linked Sale Card ─── */
        .linked-sale-card {
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            border: 1px solid #a5b4fc;
            border-radius: var(--border-radius-md);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            animation: fade-up 0.5s ease 0.1s forwards;
            opacity: 0;
            transition: var(--transition-smooth);
        }
        .linked-sale-card:hover {
            box-shadow: var(--card-shadow-hover);
            border-color: #818cf8;
        }
        .linked-sale-card .sale-link {
            font-weight: 700;
            color: #4f46e5;
            text-decoration: none;
            transition: var(--transition-smooth);
        }
        .linked-sale-card .sale-link:hover {
            text-decoration: underline;
            color: #4338ca;
        }

        /* ─── Cost Summary ─── */
        .cost-summary {
            background: #f8fafc;
            padding: 1.25rem;
            border-radius: var(--border-radius-sm);
            border: 1px solid #f1f5f9;
        }
        .cost-summary .cost-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.6rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.85rem;
        }
        .cost-summary .cost-item:last-child {
            border-bottom: none;
        }
        .cost-summary .cost-total {
            font-weight: 700;
            font-size: 1rem;
            color: #4f46e5;
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            margin: 0 -1.25rem;
            padding: 0.6rem 1.25rem;
            border-radius: 0;
        }
        .cost-summary .cost-total span:last-child {
            color: #4f46e5;
        }

        .bg-per-unit {
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-sm);
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .bg-total {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-sm);
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* ─── Material Table ─── */
        .table-modern {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }
        .table-modern thead th {
            padding: 0.75rem 1rem;
            background: #f8fafc;
            color: #64748b;
            font-weight: 700;
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 2px solid #f1f5f9;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        .table-modern tbody td {
            padding: 0.6rem 1rem;
            border-bottom: 1px solid #f8fafc;
            vertical-align: middle;
            transition: var(--transition-smooth);
        }
        .table-modern tbody tr {
            transition: var(--transition-smooth);
        }
        .table-modern tbody tr:hover {
            background: #f8fafc;
        }
        .table-modern tbody tr:last-child td {
            border-bottom: none;
        }
        .table-modern tfoot {
            background: #f8fafc;
            font-weight: 700;
            border-top: 2px solid #f1f5f9;
        }
        .table-modern tfoot td {
            padding: 0.75rem 1rem;
        }

        .badge-available {
            background: #d1fae5;
            color: #065f46;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.65rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .badge-shortage {
            background: #fecaca;
            color: #991b1b;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.65rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        /* ─── Alerts ─── */
        .alert-modern {
            border-radius: var(--border-radius-md);
            border: none;
            padding: 1rem 1.5rem;
            animation: slide-down 0.4s ease forwards;
            opacity: 0;
        }
        @keyframes slide-down {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .alert-modern .btn-close {
            filter: brightness(0.8);
        }

        /* ─── Page Header ─── */
        .page-header-modern {
            padding: 0.5rem 0 1.5rem 0;
            animation: fade-up 0.5s ease forwards;
            opacity: 0;
        }
        .page-header-modern h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .page-header-modern h1 .accent {
            color: #4f46e5;
        }
        .page-header-modern .subtitle {
            color: #64748b;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        /* ─── Responsive ─── */
        @media (max-width: 992px) {
            .linked-sale-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .progress-ring-container {
                flex-direction: column;
                gap: 1rem;
            }
        }
        @media (max-width: 768px) {
            .page-header-modern h1 {
                font-size: 1.4rem;
            }
            .card-body-modern {
                padding: 1rem;
            }
            .cost-summary {
                padding: 1rem;
            }
        }
        @media (max-width: 576px) {
            .action-btn-group .action-btn {
                flex: 1;
                justify-content: center;
                padding: 0.5rem 1rem;
                font-size: 0.7rem;
            }
            .card-header-modern {
                padding: 0.75rem 1rem;
            }
            .card-header-modern h6 {
                font-size: 0.7rem;
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
                        <i class="bi bi-gear-wide me-2" style="color: #4f46e5;"></i>
                        {{ __('ui.production_order') }} <span class="accent">#{{ $productionOrder->order_number }}</span>
                    </h1>
                    <p class="subtitle">
                        <span><i class="bi bi-box me-1"></i> {{ $productionOrder->product->name ?? 'N/A' }}</span>
                        <span class="text-muted">·</span>
                        <span><i class="bi bi-file-text me-1"></i> {{ $productionOrder->bom->name ?? 'N/A' }}</span>
                        <span class="status-badge {{ $productionOrder->status }} ms-2">
                            <span class="pulse-dot"></span>
                            {{ $productionOrder->status_label }}
                        </span>
                        @if(isset($currencyCode))
                            <span class="currency-badge {{ $currencyCode === 'USD' ? 'usd' : 'afn' }}">
                                <i class="bi bi-currency-exchange me-1"></i>
                                {{ $currencyCode }}
                            </span>
                        @endif
                    </p>
                </div>
                <div class="action-btn-group">
                    @if($productionOrder->status === 'pending')
                        <button type="button"
                                class="action-btn action-btn-success"
                                data-bs-toggle="modal"
                                data-bs-target="#startProductionModal">
                            <i class="bi bi-play-fill"></i> {{ __('ui.start_production') }}
                        </button>
                    @endif

                    @if($productionOrder->status === 'in_progress')
                        <button type="button"
                                class="action-btn action-btn-success"
                                data-bs-toggle="modal"
                                data-bs-target="#completeProductionModal">
                            <i class="bi bi-check2"></i> {{ __('ui.complete_production') }}
                        </button>
                    @endif

                    @if(in_array($productionOrder->status, ['pending', 'in_progress']))
                        <form action="{{ route('production-orders.cancel', $productionOrder) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="action-btn action-btn-danger" onclick="return confirm('Cancel production? This will restore materials to inventory.')">
                                <i class="bi bi-x-lg"></i> {{ __('ui.cancel') }}
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('production-orders.index') }}" class="action-btn action-btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        @if($productionOrder->status === 'pending')
            <div class="modal fade" id="startProductionModal" tabindex="-1" aria-labelledby="startProductionModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <form action="{{ route('production-orders.start', $productionOrder) }}" method="POST" id="startProductionForm">
                            @csrf
                            <div class="modal-header border-0 pb-0">
                                <div>
                                    <h5 class="modal-title fw-bold" id="startProductionModalLabel">Start Production</h5>
                                    <div class="text-muted small">Enter the quantity you plan to produce in this run.</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button>
                            </div>
                            <div class="modal-body pt-3">
                                <div class="rounded-3 p-3 mb-3" style="background:#eff6ff;border:1px solid #bfdbfe;">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Customer Ordered</span>
                                        <strong>{{ number_format((float) $productionOrder->quantity_ordered, 2) }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Current Raw Material Supports</span>
                                        <strong>{{ $maxProducibleQuantity !== null ? number_format((float) $maxProducibleQuantity, 2) : 'N/A' }}</strong>
                                    </div>
                                </div>

                                <label for="quantity_planned" class="form-label fw-semibold">
                                    Planned Production Quantity <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                       class="form-control form-control-lg {{ isset($errors) && $errors->has('quantity_planned') ? 'is-invalid' : '' }}"
                                       id="quantity_planned"
                                       name="quantity_planned"
                                       value="{{ old('quantity_planned', $productionOrder->quantity_ordered) }}"
                                       min="0.01"
                                       max="999999999.99"
                                       step="0.01"
                                       required>
                                @if(isset($errors) && $errors->has('quantity_planned'))
                                    <div class="invalid-feedback">{{ $errors->first('quantity_planned') }}</div>
                                @endif

                                <div class="alert alert-info mt-3 mb-0 small">
                                    <i class="bi bi-calculator me-1"></i>
                                    Raw-material quantity and production cost will be calculated from this value.
                                    It may be lower or higher than the customer order, but it cannot exceed what current raw material can support.
                                    The real finished quantity will still be entered when production ends.
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-play-circle me-1"></i> Calculate & Start Production
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if($productionOrder->status === 'in_progress')
            <div class="modal fade" id="completeProductionModal" tabindex="-1" aria-labelledby="completeProductionModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg">
                        <form action="{{ route('production-orders.complete', $productionOrder) }}" method="POST">
                            @csrf
                            <div class="modal-header border-0 pb-0">
                                <div>
                                    <h5 class="modal-title fw-bold" id="completeProductionModalLabel">Complete Production — Actual Results</h5>
                                    <div class="text-muted small">Record the real output and raw material used on the shop floor.</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button>
                            </div>

                            <div class="modal-body pt-3">
                                <div class="rounded-3 p-3 mb-4" style="background:#eff6ff;border:1px solid #bfdbfe;">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="text-muted small">Customer Ordered</div>
                                            <strong>{{ number_format((float) $productionOrder->quantity_ordered, 2) }}</strong>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-muted small">Planned for This Run</div>
                                            <strong>{{ number_format((float) ($productionOrder->quantity_planned ?: $productionOrder->quantity_ordered), 2) }}</strong>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-muted small">Completion Rule</div>
                                            <strong>Manufactured = Good + Rejected</strong>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="fw-bold mb-3">1. Finished Output</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label for="quantity_manufactured" class="form-label fw-semibold">Manufactured Qty <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control form-control-lg @error('quantity_manufactured') is-invalid @enderror"
                                               id="quantity_manufactured" name="quantity_manufactured"
                                               value="{{ old('quantity_manufactured', $productionOrder->quantity_planned ?: $productionOrder->quantity_ordered) }}"
                                               min="0.01" max="999999999.99" step="0.01" required autofocus>
                                        @error('quantity_manufactured')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        <div class="form-text">All cartons physically manufactured, including rejected units.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="quantity_produced" class="form-label fw-semibold">Good / Actual Finished Qty <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control form-control-lg @error('quantity_produced') is-invalid @enderror"
                                               id="quantity_produced" name="quantity_produced"
                                               value="{{ old('quantity_produced', $productionOrder->quantity_planned ?: $productionOrder->quantity_ordered) }}"
                                               min="0.01" max="999999999.99" step="0.01" required>
                                        @error('quantity_produced')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        <div class="form-text">Usable cartons available for delivery/invoicing.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="quantity_rejected" class="form-label fw-semibold">Rejected / Scrap Qty <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control form-control-lg @error('quantity_rejected') is-invalid @enderror"
                                               id="quantity_rejected" name="quantity_rejected"
                                               value="{{ old('quantity_rejected', 0) }}"
                                               min="0" max="999999999.99" step="0.01" required>
                                        @error('quantity_rejected')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        <div class="form-text">Defective or unusable cartons from this run.</div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold mb-0">2. Actual Raw Material Consumption</h6>
                                    <span class="badge bg-light text-dark border">BOM remains the planned baseline</span>
                                </div>
                                <p class="text-muted small mb-3">
                                    Enter the total quantity that actually left inventory. Actual waste is part of that total; it is not deducted a second time.
                                </p>

                                @error('materials')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

                                <div class="table-responsive border rounded-3">
                                    <table class="table align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Material</th>
                                                <th class="text-end">Planned</th>
                                                <th style="min-width:180px;">Actual Used</th>
                                                <th style="min-width:180px;">Actual Waste</th>
                                                <th>Unit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($completionMaterials as $index => $material)
                                                <tr>
                                                    <td class="fw-semibold">
                                                        {{ $material['material_name'] }}
                                                        <input type="hidden" name="materials[{{ $index }}][material_id]" value="{{ $material['material_id'] }}">
                                                        <input type="hidden" name="materials[{{ $index }}][unit]" value="{{ $material['unit'] }}">
                                                    </td>
                                                    <td class="text-end">{{ number_format((float) $material['planned_quantity'], 4) }}</td>
                                                    <td>
                                                        <input type="number" class="form-control @error('materials.'.$index.'.actual_quantity') is-invalid @enderror"
                                                               name="materials[{{ $index }}][actual_quantity]"
                                                               value="{{ old('materials.'.$index.'.actual_quantity', $material['current_actual_quantity']) }}"
                                                               min="0" step="0.000001" required>
                                                        @error('materials.'.$index.'.actual_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control @error('materials.'.$index.'.wastage_quantity') is-invalid @enderror"
                                                               name="materials[{{ $index }}][wastage_quantity]"
                                                               value="{{ old('materials.'.$index.'.wastage_quantity', $material['current_wastage_quantity']) }}"
                                                               min="0" step="0.000001" required>
                                                        @error('materials.'.$index.'.wastage_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    </td>
                                                    <td>{{ $material['unit'] }}</td>
                                                </tr>

                                                @if(!empty($material['reel_options']))
                                                    @php
                                                        $useReelSelection = (bool) old(
                                                            'materials.'.$index.'.use_reel_selection',
                                                            false
                                                        );
                                                    @endphp
                                                    <tr class="bg-light">
                                                        <td colspan="5" class="p-0">
                                                            <div class="p-3 border-top">
                                                                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
                                                                    <div class="form-check form-switch mb-0">
                                                                        <input class="form-check-input reel-selection-toggle"
                                                                               type="checkbox"
                                                                               role="switch"
                                                                               id="useReelSelection{{ $index }}"
                                                                               name="materials[{{ $index }}][use_reel_selection]"
                                                                               value="1"
                                                                               data-target="reelSelectionPanel{{ $index }}"
                                                                               @checked($useReelSelection)>
                                                                        <label class="form-check-label fw-semibold" for="useReelSelection{{ $index }}">
                                                                            Use physical reel declaration
                                                                        </label>
                                                                        <div class="form-text">
                                                                            Optional. Leave this off to keep normal FIFO allocation.
                                                                        </div>
                                                                    </div>

                                                                    <div class="input-group input-group-sm" style="max-width:360px;">
                                                                        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                                                                        <input type="text"
                                                                               class="form-control reel-scan-input"
                                                                               data-target="reelSelectionPanel{{ $index }}"
                                                                               placeholder="Scan / enter reel code">
                                                                    </div>
                                                                </div>

                                                                <div id="reelSelectionPanel{{ $index }}"
                                                                     class="reel-selection-panel mt-3"
                                                                     style="{{ $useReelSelection ? '' : 'display:none;' }}">
                                                                    @error('materials.'.$index.'.reels')
                                                                        <div class="alert alert-danger py-2 mb-3">{{ $message }}</div>
                                                                    @enderror

                                                                    <div class="table-responsive border rounded-3">
                                                                        <table class="table table-sm align-middle mb-0">
                                                                            <thead class="table-light">
                                                                                <tr>
                                                                                    <th>Reel / Source</th>
                                                                                    <th>Status</th>
                                                                                    <th class="text-end">Available for this run</th>
                                                                                    <th style="min-width:160px;">Consumed kg</th>
                                                                                    <th style="min-width:180px;">Final weighed remainder</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach($material['reel_options'] as $reelIndex => $reel)
                                                                                    <tr class="reel-option-row"
                                                                                        data-reel-code="{{ strtoupper($reel['reel_code']) }}"
                                                                                        data-selectable="{{ $reel['selectable'] ? '1' : '0' }}">
                                                                                        <td>
                                                                                            <div class="fw-semibold">{{ $reel['reel_code'] }}</div>
                                                                                            <small class="text-muted">
                                                                                                Batch {{ $reel['batch_no'] ?: '—' }}
                                                                                                @if($reel['purchase_no'])
                                                                                                    · {{ $reel['purchase_no'] }}
                                                                                                @endif
                                                                                            </small>
                                                                                            <input type="hidden"
                                                                                                   name="materials[{{ $index }}][reels][{{ $reelIndex }}][reel_id]"
                                                                                                   value="{{ $reel['id'] }}">
                                                                                        </td>
                                                                                        <td>
                                                                                            @if($reel['selectable'])
                                                                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                                                                    {{ $reel['status'] === 'consumed' ? 'Used in this run' : ucfirst($reel['status']) }}
                                                                                                </span>
                                                                                            @else
                                                                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                                                                    {{ ucfirst($reel['status']) }} · unavailable
                                                                                                </span>
                                                                                            @endif
                                                                                        </td>
                                                                                        <td class="text-end">
                                                                                            <strong>{{ number_format((float) $reel['available_for_run_kg'], 4) }} kg</strong>
                                                                                            @if((float) $reel['current_run_consumed_kg'] > 0)
                                                                                                <div class="small text-muted">
                                                                                                    {{ number_format((float) $reel['current_run_consumed_kg'], 4) }} kg provisionally allocated
                                                                                                </div>
                                                                                            @endif
                                                                                        </td>
                                                                                        <td>
                                                                                            <input type="number"
                                                                                                   class="form-control form-control-sm reel-consumed-input"
                                                                                                   name="materials[{{ $index }}][reels][{{ $reelIndex }}][consumed_kg]"
                                                                                                   value="{{ old('materials.'.$index.'.reels.'.$reelIndex.'.consumed_kg') }}"
                                                                                                   min="0"
                                                                                                   step="0.000001"
                                                                                                   placeholder="Actual kg"
                                                                                                   @disabled(!$reel['selectable'])>
                                                                                        </td>
                                                                                        <td>
                                                                                            <input type="number"
                                                                                                   class="form-control form-control-sm"
                                                                                                   name="materials[{{ $index }}][reels][{{ $reelIndex }}][final_remaining_kg]"
                                                                                                   value="{{ old('materials.'.$index.'.reels.'.$reelIndex.'.final_remaining_kg') }}"
                                                                                                   min="0"
                                                                                                   step="0.000001"
                                                                                                   placeholder="Optional scale weight"
                                                                                                   @disabled(!$reel['selectable'])>
                                                                                        </td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>

                                                                    <div class="row g-2 mt-2 align-items-start">
                                                                        <div class="col-lg-7">
                                                                            <div class="form-text">
                                                                                Enter consumed kg, or leave it blank and enter the final weighed remainder so consumption can be inferred.
                                                                                If both are entered, consumed kg drives inventory and the weighed remainder is recorded separately as remnant variance.
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-lg-5">
                                                                            <input type="text"
                                                                                   class="form-control form-control-sm"
                                                                                   name="materials[{{ $index }}][selection_note]"
                                                                                   value="{{ old('materials.'.$index.'.selection_note') }}"
                                                                                   maxlength="1000"
                                                                                   placeholder="Optional reel / scale note">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @empty
                                                <tr><td colspan="5" class="text-center text-danger py-4">No production materials are available for completion.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="alert alert-warning mt-4 mb-0 small">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Completion is atomic. Materials without a physical reel declaration keep FIFO. An explicit reel declaration replaces only that material's provisional allocation and preserves the landed cost of each selected source batch. Scale remainders are observational and never overwrite stock directly.
                                </div>
                            </div>

                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                                <button type="submit" class="btn btn-success" @disabled(empty($completionMaterials))>
                                    <i class="bi bi-check-circle me-1"></i> Save Actuals & Complete Production
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- ─── FLASH MESSAGES ─── --}}
        @if(session('error'))
            <div class="alert alert-danger alert-modern alert-dismissible fade show mt-3 mb-4" role="alert" id="errorAlert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle-fill me-2 mt-1" style="font-size: 1.25rem; flex-shrink: 0;"></i>
                    <div style="white-space: pre-line; flex: 1;">
                        {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('ui.close') }}"></button>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-modern alert-dismissible fade show mt-3 mb-4" role="alert" id="successAlert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-check-circle-fill me-2 mt-1" style="font-size: 1.25rem; flex-shrink: 0;"></i>
                    <div style="white-space: pre-line; flex: 1;">
                        {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('ui.close') }}"></button>
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-modern alert-dismissible fade show mt-3 mb-4" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle-fill me-2 mt-1" style="font-size: 1.25rem; flex-shrink: 0;"></i>
                    <div style="white-space: pre-line; flex: 1;">
                        {{ session('warning') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('ui.close') }}"></button>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info alert-modern alert-dismissible fade show mt-3 mb-4" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-info-circle-fill me-2 mt-1" style="font-size: 1.25rem; flex-shrink: 0;"></i>
                    <div style="white-space: pre-line; flex: 1;">
                        {{ session('info') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('ui.close') }}"></button>
                </div>
            </div>
        @endif

        {{-- ─── LINKED SALE ALERT ─── --}}
        @if($sale)
            <div class="linked-sale-card">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <i class="bi bi-cart-plus" style="color: #4f46e5; font-size: 1.25rem;"></i>
                    <div>
                        <strong>Linked Sale Order:</strong>
                        <a href="{{ route('admin.sales.show', $sale->id) }}" class="sale-link">
                            #{{ $sale->sale_no }}
                        </a>
                        <span class="badge bg-{{ $sale->is_produced ? 'success' : 'warning' }} ms-2">
                            <i class="bi bi-{{ $sale->is_produced ? 'check-circle-fill' : 'clock' }} me-1"></i>
                            {{ $sale->is_produced ? 'Produced' : 'Pending Production' }}
                        </span>
                        <span class="badge bg-{{ $isUSD ? 'success' : 'warning' }} ms-2">
                            <i class="bi bi-currency-exchange me-1"></i>
                            {{ $currencyCode }}
                        </span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="text-muted" style="font-size: 0.85rem;">
                        <i class="bi bi-person me-1"></i> {{ $sale->customer->name ?? 'N/A' }}
                    </span>
                    <span class="text-muted" style="font-size: 0.85rem;">
                        <i class="bi bi-cash me-1"></i>
                        {{ $currencySymbol }}{{ number_format($sale->grand_total ?? 0, 2) }}
                    </span>
                    <a href="{{ route('admin.sales.show', $sale->id) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye me-1"></i> {{ __('ui.view') }}
                    </a>
                </div>
            </div>
        @endif

        {{-- ─── STATS CARDS ─── --}}
        <div class="stats-grid">
            <div class="stat-card-modern">
                <div class="stat-icon blue">
                    <i class="bi bi-hash"></i>
                </div>
                <div class="stat-value blue">{{ number_format($productionOrder->quantity_ordered) }}</div>
                <div class="stat-label">Ordered Quantity</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon purple">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <div class="stat-value purple">
                    {{ $productionOrder->quantity_planned !== null ? number_format((float) $productionOrder->quantity_planned) : '—' }}
                </div>
                <div class="stat-label">Planned Production</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon green">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-value green">{{ number_format($productionOrder->quantity_produced) }}</div>
                <div class="stat-label">Good / Finished Qty</div>
            </div>
            @if($productionOrder->quantity_manufactured !== null)
                <div class="stat-card-modern">
                    <div class="stat-icon blue"><i class="bi bi-boxes"></i></div>
                    <div class="stat-value blue">{{ number_format((float) $productionOrder->quantity_manufactured) }}</div>
                    <div class="stat-label">Manufactured Qty</div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon orange"><i class="bi bi-trash3"></i></div>
                    <div class="stat-value orange">{{ number_format((float) $productionOrder->quantity_rejected) }}</div>
                    <div class="stat-label">Rejected / Scrap Qty</div>
                </div>
            @endif
            <div class="stat-card-modern">
                <div class="stat-icon orange">
                    <i class="bi bi-percent"></i>
                </div>
                <div class="stat-value orange">{{ round($progress) }}%</div>
                <div class="stat-label">{{ __('ui.progress') }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon purple">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-value purple">{{ $currencySymbol }}{{ number_format($totalCostInCurrency, 2) }}</div>
                <div class="stat-label">Total Cost ({{ $currencyCode }})</div>
            </div>
        </div>

        {{-- ─── MAIN CONTENT GRID ─── --}}
        <div class="row g-4">

            {{-- ─── LEFT COLUMN: Order Information ─── --}}
            <div class="col-lg-4 col-md-6">
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h6>
                            <i class="bi bi-info-circle"></i> Order Information
                        </h6>
                        <span class="text-muted" style="font-size: 0.6rem; font-weight: 600;">
                            <i class="bi bi-clock"></i> {{ $productionOrder->created_at->format('d M Y, H:i') }}
                        </span>
                    </div>
                    <div class="card-body-modern">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="detail-label">Order Number</div>
                                <div class="detail-value">{{ $productionOrder->order_number }}</div>
                            </div>
                            <div class="col-6">
                                <div class="detail-label">{{ __('ui.status') }}</div>
                                <div class="detail-value">
                                    <span class="status-badge {{ $productionOrder->status }}" style="font-size: 0.65rem; padding: 2px 12px;">
                                        <span class="pulse-dot"></span>
                                        {{ $productionOrder->status_label }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="detail-label">{{ __('ui.product') }}</div>
                                <div class="detail-value">{{ $productionOrder->product->name ?? 'N/A' }}</div>
                                <div class="text-muted" style="font-size: 0.7rem; margin-top: 0.15rem;">
                                    Category: {{ $productionOrder->product->category->name ?? 'Uncategorized' }}
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="detail-label">{{ __('ui.bom') }}</div>
                                <div class="detail-value">{{ $productionOrder->bom->name ?? 'N/A' }}</div>
                                <div class="text-muted" style="font-size: 0.7rem; margin-top: 0.15rem;">
                                    Code: {{ $productionOrder->bom->code ?? 'N/A' }} · Version: {{ $productionOrder->bom->version ?? '1.0' }}
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="detail-label">{{ __('ui.start_date') }}</div>
                                <div class="detail-value">
                                    {{ $productionOrder->start_date ? $productionOrder->start_date->format('d M, Y') : '-' }}
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="detail-label">{{ __('ui.completion_date') }}</div>
                                <div class="detail-value">
                                    {{ $productionOrder->completion_date ? $productionOrder->completion_date->format('d M, Y') : '-' }}
                                </div>
                            </div>
                            @if($productionOrder->notes)
                                <div class="col-12">
                                    <div class="detail-label">{{ __('ui.notes') }}</div>
                                    <div class="detail-value" style="font-weight: 400; font-size: 0.85rem; color: #475569;">
                                        {{ $productionOrder->notes }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── MIDDLE COLUMN: Cost Summary ─── --}}
            <div class="col-lg-4 col-md-6">
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h6>
                            <i class="bi bi-calculator"></i> Cost Summary
                            <span class="badge bg-{{ $isUSD ? 'success' : 'warning' }} ms-1">
                                <i class="bi bi-currency-exchange me-1"></i>
                                {{ $currencyCode }}
                            </span>
                        </h6>
                        <span class="badge bg-secondary" style="font-size: 0.6rem; font-weight: 700;">
                            <i class="bi bi-box me-1"></i>
                            {{ number_format($baseQuantity) }} units
                        </span>
                    </div>
                    <div class="card-body-modern">
                        <div class="cost-summary">

                            {{-- ─── PROGRESS RING ─── --}}
                            <div class="progress-ring-container">
                                <div class="progress-ring-wrapper">
                                    <svg width="140" height="140" viewBox="0 0 140 140">
                                        <circle class="ring-bg" cx="70" cy="70" r="60"/>
                                        <circle class="ring-fg {{ $progress >= 75 ? 'green' : ($progress >= 50 ? 'orange' : 'red') }}"
                                                cx="70" cy="70" r="60"
                                                stroke-dasharray="376.99"
                                                stroke-dashoffset="{{ 376.99 - (376.99 * $progress / 100) }}"/>
                                    </svg>
                                    <div class="progress-ring-center">
                                        <div class="percentage">{{ round($progress) }}%</div>
                                        <div class="label">Order Fulfillment</div>
                                    </div>
                                </div>
                                <div class="progress-stats">
                                    <div class="progress-stat-item">
                                        <span class="label">Ordered</span>
                                        <span class="value">{{ number_format($productionOrder->quantity_ordered) }}</span>
                                    </div>
                                    <div class="progress-stat-item">
                                        <span class="label">Good / Finished</span>
                                        <span class="value">{{ number_format($productionOrder->quantity_produced) }}</span>
                                    </div>
                                    <div class="progress-stat-item">
                                        <span class="label">Production Variance</span>
                                        @php
                                            $productionVariance = (float) ($productionOrder->quantity_manufactured ?? $productionOrder->quantity_produced) - (float) ($productionOrder->quantity_planned ?? $productionOrder->quantity_ordered);
                                        @endphp
                                        <span class="value {{ $productionVariance > 0 ? 'text-success' : ($productionVariance < 0 ? 'text-warning' : '') }}">
                                            {{ $productionVariance > 0 ? '+' : '' }}{{ number_format($productionVariance, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <hr style="margin: 1rem 0; border-color: #f1f5f9;">

                            {{-- ─── PER UNIT COSTS ─── --}}
                            <div class="bg-per-unit">
                                <span style="font-size: 0.65rem; text-transform: uppercase; color: #4f46e5; font-weight: 700;">
                                    <i class="bi bi-box me-1"></i> Per Unit Costs
                                </span>
                                @if($productionOrder->bom)
                                    <span style="font-size: 0.6rem; color: #6b7280;">
                                        <i class="bi bi-file-text me-1"></i>
                                        {{ $productionOrder->bom->code ?? 'N/A' }}
                                    </span>
                                @endif
                            </div>

                            <div class="cost-item">
                                <span>
                                    Material Cost / Unit
                                    <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                        ({{ $isUSD ? 'USD' : 'AFN' }})
                                    </small>
                                </span>
                                <span class="fw-semibold">
                                    {{ $isUSD ? '$' : '؋' }}{{ number_format($materialCostPerUnit ?? 0, 4) }}
                                    @if(!$isUSD)
                                        <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                            (${{ number_format($materialCostPerUnitUsd ?? 0, 4) }} USD)
                                        </small>
                                    @endif
                                </span>
                            </div>
                            <div class="cost-item">
                                <span>
                                    {{ ($usesStandardWorkCost ?? false) ? 'Standard Work / Profit (' . number_format($workPercentage ?? 40, 0) . '%) — Not a production cost' : 'Labor Cost / Unit' }}
                                    <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                        ({{ $isUSD ? 'USD' : 'AFN' }})
                                    </small>
                                </span>
                                <span class="fw-semibold">
                                    {{ $isUSD ? '$' : '؋' }}{{ number_format($laborCostPerUnit ?? 0, 4) }}
                                    @if(!$isUSD)
                                        <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                            (${{ number_format($laborCostPerUnitUsd ?? 0, 4) }} USD)
                                        </small>
                                    @endif
                                </span>
                            </div>
                            <div class="cost-item">
                                <span>
                                    Overhead Cost / Unit
                                    <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                        ({{ $isUSD ? 'USD' : 'AFN' }})
                                    </small>
                                </span>
                                <span class="fw-semibold">
                                    {{ $isUSD ? '$' : '؋' }}{{ number_format($overheadCostPerUnit ?? 0, 4) }}
                                    @if(!$isUSD)
                                        <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                            (${{ number_format($overheadCostPerUnitUsd ?? 0, 4) }} USD)
                                        </small>
                                    @endif
                                </span>
                            </div>

                            <div style="border-top: 2px dashed #e5e7eb; margin: 0.75rem 0;"></div>

                            {{-- ─── TOTAL COSTS ─── --}}
                            <div class="bg-total">
                                <span style="font-size: 0.65rem; text-transform: uppercase; color: #065f46; font-weight: 700;">
                                    <i class="bi bi-calculator me-1"></i> {{ ($hasActualConsumption ?? false) ? 'Actual FIFO Costs' : 'Estimated Costs' }}
                                    <span style="font-weight: 400; color: #6b7280; font-size: 0.6rem;">
                                        ({{ number_format($baseQuantity) }} units)
                                    </span>
                                </span>
                            </div>

                            <div class="cost-item">
                                <span>{{ __('ui.total_material_cost') }}</span>
                                <span class="fw-semibold">
                                    {{ $isUSD ? '$' : '؋' }}{{ number_format($totalMaterialCostInCurrency ?? 0, 2) }}
                                    @if(!$isUSD)
                                        <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                            (${{ number_format($totalMaterialCostUsd ?? 0, 2) }} USD)
                                        </small>
                                    @endif
                                </span>
                            </div>
                            <div class="cost-item">
                                <span>{{ ($usesStandardWorkCost ?? false) ? 'Standard Work / Profit (' . number_format($workPercentage ?? 40, 0) . '%) — Not a production cost' : 'Total Labor Cost' }}</span>
                                <span class="fw-semibold">
                                    {{ $isUSD ? '$' : '؋' }}{{ number_format($totalLaborCostInCurrency ?? 0, 2) }}
                                    @if(!$isUSD)
                                        <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                            (${{ number_format($totalLaborCostUsd ?? 0, 2) }} USD)
                                        </small>
                                    @endif
                                </span>
                            </div>
                            <div class="cost-item">
                                <span>Total Overhead Cost</span>
                                <span class="fw-semibold">
                                    {{ $isUSD ? '$' : '؋' }}{{ number_format($totalOverheadCostInCurrency ?? 0, 2) }}
                                    @if(!$isUSD)
                                        <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                            (${{ number_format($totalOverheadCostUsd ?? 0, 2) }} USD)
                                        </small>
                                    @endif
                                </span>
                            </div>
                            @if(($otherDirectCostInCurrency ?? 0) != 0)
                                <div class="cost-item">
                                    <span>Other Direct Cost</span>
                                    <span class="fw-semibold">
                                        {{ $isUSD ? '$' : '؋' }}{{ number_format($otherDirectCostInCurrency ?? 0, 2) }}
                                        @if(!$isUSD)
                                            <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                                (${{ number_format($otherDirectCostUsd ?? 0, 2) }} USD)
                                            </small>
                                        @endif
                                    </span>
                                </div>
                            @endif

                            <div class="cost-item cost-total" style="margin-top: 0.75rem; border-radius: var(--border-radius-sm);">
                                <span>{{ __('ui.total_cost') }}</span>
                                <span>
                                    {{ $isUSD ? '$' : '؋' }}{{ number_format($totalCostInCurrency ?? 0, 2) }}
                                    @if(!$isUSD)
                                        <small class="text-muted" style="font-size: 0.6rem; display: block;">
                                            (${{ number_format($totalCostUsd ?? 0, 2) }} USD)
                                        </small>
                                    @endif
                                </span>
                            </div>

                            @if($quantityProduced > 0)
                                <div class="cost-item" style="color: #059669; border-top: 1px solid #f1f5f9; margin-top: 0.5rem; padding-top: 0.75rem;">
                                    <span>Actual Cost per Unit (Produced)</span>
                                    <span class="fw-semibold" style="color: #059669;">
                                        {{ $isUSD ? '$' : '؋' }}{{ number_format($costPerUnitInCurrency ?? 0, 4) }}
                                    </span>
                                </div>
                            @endif

                            {{-- ─── EXCHANGE RATE ─── --}}
                            @if(!$isUSD && isset($exchangeRate))
                                <div class="cost-item" style="border-top: 2px solid #f1f5f9; margin-top: 0.5rem; padding-top: 0.75rem;">
                                    <span style="font-size: 0.75rem; color: #6b7280;">
                                        <i class="bi bi-arrow-left-right me-1"></i> {{ __('ui.exchange_rate') }}
                                    </span>
                                    <span style="font-size: 0.8rem; color: #6b7280; font-weight: 600;">
                                        1 USD = {{ number_format($exchangeRate, 2) }} {{ $currencyCode }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── RIGHT COLUMN: Material Status ─── --}}
            <div class="col-lg-4 col-md-12">
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h6>
                            <i class="bi bi-box-seam"></i> Material Status
                        </h6>
                        <span class="badge bg-primary ms-2" style="font-weight: 700;">{{ $productionOrder->materials->count() }}</span>
                    </div>
                    <div class="card-body-modern p-0">
                        <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                            <table class="table-modern">
                                <thead>
                                <tr>
                                    <th>{{ __('ui.material') }}</th>
                                    <th class="text-end">Required</th>
                                    <th class="text-end">{{ __('ui.available') }}</th>
                                    <th class="text-center">{{ __('ui.status') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($productionOrder->materials as $material)
                                    @php
                                        $isAvailable = $material->shortage_quantity == 0;
                                        $shortagePercent = $material->required_quantity > 0
                                            ? ($material->shortage_quantity / $material->required_quantity) * 100
                                            : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600; color: #0f172a;">
                                                {{ $material->product->name ?? 'N/A' }}
                                            </div>
                                            <div style="font-size: 0.6rem; color: #94a3b8;">
                                                {{ $material->unit }}
                                            </div>
                                        </td>
                                        <td class="text-end" style="font-weight: 600; color: #0f172a;">
                                            {{ number_format($material->required_quantity, 2) }}
                                        </td>
                                        <td class="text-end">
                                            <span class="{{ $isAvailable ? 'text-success' : 'text-danger' }}" style="font-weight: 600;">
                                                {{ number_format($material->available_quantity, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($isAvailable)
                                                <span class="badge-available">
                                                    <i class="bi bi-check-circle me-1"></i> {{ __('ui.available') }}
                                                </span>
                                            @else
                                                <span class="badge-shortage" title="Shortage: {{ number_format($material->shortage_quantity, 2) }} {{ $material->unit }}">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                    {{ number_format($shortagePercent, 0) }}% short
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="bi bi-inboxes" style="display: block; font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.3;"></i>
                                            No materials defined
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ─── MATERIAL BREAKDOWN (Below the grid) ─── --}}
        @if(!empty($materialDetails) && count($materialDetails) > 0)
            <div class="card-modern mt-4">
                <div class="card-header-modern">
                    <h6>
                        <i class="bi bi-box-seam me-2" style="color: #4f46e5;"></i>
                        {{ ($hasActualConsumption ?? false) ? 'Material Requirement & Actual FIFO Cost' : 'Material Breakdown' }}
                    </h6>
                    <span class="badge bg-primary ms-2" style="font-weight: 700;">{{ count($materialDetails) }}</span>
                </div>
                <div class="card-body-modern p-0">
                    <div class="table-responsive">
                        <table class="table-modern">
                            <thead>
                            <tr>
                                <th>{{ __('ui.material') }}</th>
                                <th class="text-end">{{ __('ui.quantity') }}</th>
                                <th class="text-end">{{ __('ui.unit') }}</th>
                                <th class="text-end">Cost/Unit (USD)</th>
                                <th class="text-end">{{ __('ui.total_cost_usd') }}</th>
                                <th class="text-end">Total Cost ({{ $currencyCode }})</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($materialDetails as $material)
                                <tr>
                                    <td>{{ $material['material_name'] }}</td>
                                    <td class="text-end">{{ number_format($material['required_quantity'], 4) }}</td>
                                    <td class="text-end">{{ $material['unit'] ?? 'unit' }}</td>
                                    <td class="text-end">${{ number_format($material['cost_per_unit_usd'], 4) }}</td>
                                    <td class="text-end">${{ number_format($material['total_cost_usd'], 2) }}</td>
                                    <td class="text-end">{{ $currencySymbol }}{{ number_format($material['total_cost_usd'] * $exchangeRate, 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-bold">
                                    {{ ($hasActualConsumption ?? false) ? 'Actual FIFO Material Cost' : 'Total Material Cost' }}
                                </td>
                                <td class="text-end fw-bold">${{ number_format($totalMaterialCostUsd ?? 0, 2) }}</td>
                                <td class="text-end fw-bold">{{ $currencySymbol }}{{ number_format($totalMaterialCostInCurrency ?? 0, 2) }}</td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($productionVariance) && ($productionVariance['has_actual'] ?? false))
            <div class="card-modern mt-4">
                <div class="card-header-modern">
                    <h6>
                        <i class="bi bi-clipboard-data me-2" style="color:#4f46e5;"></i>
                        Planned vs Actual Production Variance
                    </h6>
                    <span class="badge bg-light text-dark border">Actual FIFO consumption</span>
                </div>
                <div class="card-body-modern">
                    @php($output = $productionVariance['output'] ?? [])
                    <div class="row g-3 mb-4">
                        <div class="col-md-2 col-6"><div class="text-muted small">Planned</div><strong>{{ number_format((float) ($output['planned_quantity'] ?? 0), 2) }}</strong></div>
                        <div class="col-md-2 col-6"><div class="text-muted small">Manufactured</div><strong>{{ number_format((float) ($output['manufactured_quantity'] ?? 0), 2) }}</strong></div>
                        <div class="col-md-2 col-6"><div class="text-muted small">Good</div><strong class="text-success">{{ number_format((float) ($output['good_quantity'] ?? 0), 2) }}</strong></div>
                        <div class="col-md-2 col-6"><div class="text-muted small">Rejected</div><strong class="text-warning">{{ number_format((float) ($output['rejected_quantity'] ?? 0), 2) }}</strong></div>
                        <div class="col-md-2 col-6"><div class="text-muted small">Output Variance</div><strong>{{ number_format((float) ($output['manufactured_variance_quantity'] ?? 0), 2) }}</strong></div>
                        <div class="col-md-2 col-6"><div class="text-muted small">Yield</div><strong>{{ number_format((float) ($output['yield_percentage'] ?? 0), 2) }}%</strong></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Material</th>
                                    <th class="text-end">Planned</th>
                                    <th class="text-end">Actual</th>
                                    <th class="text-end">Variance</th>
                                    <th class="text-end">Actual Waste</th>
                                    <th class="text-end">Cost Variance (USD)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productionVariance['materials'] ?? [] as $row)
                                    <tr>
                                        <td>{{ $row['material_name'] }}</td>
                                        <td class="text-end">{{ number_format((float) $row['planned_quantity'], 4) }} {{ $row['unit'] }}</td>
                                        <td class="text-end">{{ number_format((float) $row['actual_quantity'], 4) }} {{ $row['unit'] }}</td>
                                        <td class="text-end {{ $row['variance_quantity'] > 0 ? 'text-danger' : ($row['variance_quantity'] < 0 ? 'text-success' : '') }}">
                                            {{ $row['variance_quantity'] > 0 ? '+' : '' }}{{ number_format((float) $row['variance_quantity'], 4) }}
                                        </td>
                                        <td class="text-end">{{ number_format((float) $row['actual_wastage_quantity'], 4) }}</td>
                                        <td class="text-end {{ $row['cost_variance_usd'] > 0 ? 'text-danger' : ($row['cost_variance_usd'] < 0 ? 'text-success' : '') }}">
                                            {{ $row['cost_variance_usd'] > 0 ? '+' : '' }}${{ number_format((float) $row['cost_variance_usd'], 4) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Total Material Cost Variance</td>
                                    <td class="text-end fw-bold">${{ number_format((float) data_get($productionVariance, 'summary.material_cost_variance_usd', 0), 4) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ─── CHECK FOR ERRORS AND SHOW ALERT ───
            @if(session('error'))
            var errorMessage = {!! json_encode(session('error')) !!};
            alert('❌ ' + errorMessage);

            var errorAlert = document.getElementById('errorAlert');
            if (errorAlert) {
                setTimeout(function() {
                    errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            }
            @endif

            // ─── CHECK FOR SUCCESS AND SHOW ALERT ───
            @if(session('success'))
            var successMessage = {!! json_encode(session('success')) !!};
            alert('✅ ' + successMessage);

            var successAlert = document.getElementById('successAlert');
            if (successAlert) {
                setTimeout(function() {
                    successAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            }
            @endif

            @if(isset($errors) && $errors->has('quantity_planned'))
            const startModalElement = document.getElementById('startProductionModal');
            if (startModalElement && window.bootstrap) {
                new bootstrap.Modal(startModalElement).show();
            }
            @endif

            @if(isset($errors) && (
                $errors->has('quantity_manufactured')
                || $errors->has('quantity_produced')
                || $errors->has('quantity_rejected')
                || $errors->has('materials')
                || collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'materials.'))
            ))
            const completionModalElement = document.getElementById('completeProductionModal');
            if (completionModalElement && window.bootstrap) {
                new bootstrap.Modal(completionModalElement).show();
            }
            @endif

            // ─── OPTIONAL PHYSICAL REEL DECLARATION ───
            document.querySelectorAll('.reel-selection-toggle').forEach(function(toggle) {
                const panel = document.getElementById(toggle.dataset.target);
                const syncPanel = function() {
                    if (panel) {
                        panel.style.display = toggle.checked ? '' : 'none';
                    }
                };

                toggle.addEventListener('change', syncPanel);
                syncPanel();
            });

            document.querySelectorAll('.reel-scan-input').forEach(function(input) {
                input.addEventListener('keydown', function(event) {
                    if (event.key !== 'Enter') {
                        return;
                    }

                    event.preventDefault();
                    const code = input.value.trim().toUpperCase();
                    if (!code) {
                        return;
                    }

                    const panel = document.getElementById(input.dataset.target);
                    if (!panel) {
                        return;
                    }

                    const rows = Array.from(
                        panel.querySelectorAll('.reel-option-row')
                    );
                    const match = rows.find(function(row) {
                        return row.dataset.reelCode === code;
                    });

                    if (!match) {
                        alert('Reel code not found for this material.');
                        return;
                    }

                    if (match.dataset.selectable !== '1') {
                        alert('This reel is currently blocked or unavailable for production.');
                        return;
                    }

                    const toggle = document.querySelector(
                        '.reel-selection-toggle[data-target="' + input.dataset.target + '"]'
                    );
                    if (toggle && !toggle.checked) {
                        toggle.checked = true;
                        toggle.dispatchEvent(new Event('change'));
                    }

                    match.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    match.classList.add('table-success');

                    const consumedInput = match.querySelector(
                        '.reel-consumed-input'
                    );
                    if (consumedInput) {
                        consumedInput.focus();
                    }

                    window.setTimeout(function() {
                        match.classList.remove('table-success');
                    }, 1800);
                });
            });

            // ─── CONFIRM DIALOG FOR START PRODUCTION ───
            const startForm = document.getElementById('startProductionForm');
            if (startForm) {
                startForm.addEventListener('submit', function(e) {
                    const confirmMessage = '⚠️ Start Production?\n\nThis will calculate and consume raw material using the Planned Production Quantity you entered. The planned quantity can be above or below the customer order but must be supported by current stock.\n\nYou will enter the real finished quantity when production ends. Continue?';

                    if (!confirm(confirmMessage)) {
                        e.preventDefault();
                        return false;
                    }
                });
            }

            // ─── AUTO-REFRESH for pending/in-progress orders ───
            @if($productionOrder->status === 'pending' || $productionOrder->status === 'in_progress')
            let refreshInterval = setInterval(function() {
                if (!document.hidden) {
                    fetch(window.location.href + '?check_status=1', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status_changed) {
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
                                    if (data.status_changed) {
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
            @endif
        });
    </script>
@endsection
