{{-- resources/views/admin/profit-distributions/show.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Profit Distribution Details')

@section('css')
    <style>
        .distribution-header {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .distribution-header .number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }
        .distribution-header .label {
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            font-weight: 600;
        }
        .badge-status {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .badge-status.distributed { background: var(--success-bg); color: var(--success); }
        .badge-status.pending { background: var(--warning-bg); color: var(--warning); }
        .badge-status.draft { background: var(--gray-200); color: var(--gray-600); }
        .badge-status.approved { background: var(--info-bg); color: var(--info); }
        .badge-status.cancelled { background: var(--danger-bg); color: var(--danger); }

        .info-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .info-card .info-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .info-card .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-800);
        }
        .info-card .info-value.large {
            font-size: 1.5rem;
        }

        .profit-positive {
            color: var(--success);
        }
        .profit-negative {
            color: var(--danger);
        }

        .shareholder-item {
            padding: 0.75rem;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            margin-bottom: 0.5rem;
            background: var(--gray-50);
            transition: var(--transition);
        }
        .shareholder-item:hover {
            background: var(--gray-100);
        }
        .shareholder-item .shareholder-name {
            font-weight: 600;
        }
        .shareholder-item .share-percent {
            color: var(--gray-500);
            font-size: 0.8rem;
        }
        .shareholder-item .amount {
            font-weight: 700;
            font-size: 1rem;
        }
        .shareholder-item .amount.profit {
            color: var(--success);
        }
        .shareholder-item .amount.loss {
            color: var(--danger);
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--gray-200);
        }
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -1.5rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: var(--gray-300);
            border: 2px solid white;
        }
        .timeline-item.active::before {
            background: var(--primary);
        }
        .timeline-item.completed::before {
            background: var(--success);
        }
        .timeline-item .timeline-title {
            font-weight: 600;
            font-size: 0.85rem;
        }
        .timeline-item .timeline-date {
            font-size: 0.7rem;
            color: var(--gray-400);
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-graph-up-arrow me-2"></i> {{ __('ui.profit_distribution') }} <span class="accent">{{ __('ui.details') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-hash me-1"></i> {{ $distribution->distribution_number }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.profit-distributions.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                    @if($distribution->status == 'draft' || $distribution->status == 'pending')
                        <button class="btn btn-danger delete-distribution" data-id="{{ $distribution->id }}" data-number="{{ $distribution->distribution_number }}">
                            <i class="bi bi-trash me-1"></i> {{ __('ui.delete') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Distribution Header -->
        <div class="distribution-header">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="label">Distribution Number</div>
                    <div class="number">{{ $distribution->distribution_number }}</div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="label">Total Profit / Loss</div>
                    <div class="number {{ $distribution->total_profit >= 0 ? 'profit-positive' : 'profit-negative' }}">
                        {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format(abs($distribution->total_profit), 2) }}
                    </div>
                    <span class="badge {{ $distribution->total_profit >= 0 ? 'bg-success' : 'bg-danger' }} mt-1">
                    {{ $distribution->total_profit >= 0 ? 'Profit' : 'Loss' }}
                </span>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="label">{{ __('ui.status') }}</div>
                    <div>
                    <span class="badge-status {{ $distribution->status }}">
                        {{ $distribution->status_label }}
                    </span>
                    </div>
                    <div class="mt-1">
                    <span class="text-muted small">
                        <i class="bi bi-calendar me-1"></i>
                        {{ $distribution->period_label }}
                    </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="info-card text-center">
                    <div class="info-label">{{ __('ui.total_profit') }}</div>
                    <div class="info-value large {{ $distribution->total_profit >= 0 ? 'profit-positive' : 'profit-negative' }}">
                        {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($distribution->total_profit, 2) }}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-card text-center">
                    <div class="info-label">Distributed Amount</div>
                    <div class="info-value large text-success">
                        {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($distribution->distributed_amount, 2) }}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-card text-center">
                    <div class="info-label">{{ __('ui.remaining') }}</div>
                    <div class="info-value large text-warning">
                        {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($distribution->remaining_amount, 2) }}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-card text-center">
                    <div class="info-label">{{ __('ui.shareholders') }}</div>
                    <div class="info-value large text-primary">
                        {{ $distribution->items->count() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Shareholder Distribution -->
        <div class="info-card">
            <h6 class="fw-bold mb-3">
                <i class="bi bi-people-fill me-1"></i> Shareholder Distribution
            </h6>

            @if($distribution->items->count() > 0)
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('ui.shareholder') }}</th>
                            <th>{{ __('ui.share_percent') }}</th>
                            <th class="text-end">{{ __('ui.amount') }}</th>
                            <th>{{ __('ui.type') }}</th>
                            <th>{{ __('ui.status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($distribution->items as $item)
                            @php
                                $isProfit = $item->amount >= 0;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">
                                    {{ $item->shareholder->name ?? 'Unknown' }}
                                    <div class="text-muted" style="font-size: 0.7rem;">{{ $item->shareholder->code ?? 'N/A' }}</div>
                                </td>
                                <td>{{ $item->share_percentage }}%</td>
                                <td class="text-end {{ $isProfit ? 'profit-positive' : 'profit-negative' }}">
                                    {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($item->amount, 2) }}
                                </td>
                                <td>
                                <span class="badge {{ $isProfit ? 'bg-success' : 'bg-danger' }}">
                                    {{ $isProfit ? 'Profit' : 'Loss' }}
                                </span>
                                </td>
                                <td>
                                <span class="badge bg-{{ $item->status_badge }}">
                                    {{ $item->status_label }}
                                </span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr class="fw-bold" style="background: var(--gray-50);">
                            <td colspan="3" class="text-end">Total:</td>
                            <td class="text-end">
                                {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($distribution->items->sum('amount'), 2) }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                    <p class="text-muted">No shareholders found in this distribution.</p>
                </div>
            @endif
        </div>

        <!-- Distribution Details -->
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-info-circle me-1"></i> Distribution Information
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.period_start') }}</div>
                            <div class="info-value">{{ $distribution->period_start->format('M d, Y') }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.period_end') }}</div>
                            <div class="info-value">{{ $distribution->period_end->format('M d, Y') }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Distribution Date</div>
                            <div class="info-value">{{ $distribution->distribution_date->format('M d, Y') }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.status') }}</div>
                            <div class="info-value">
                            <span class="badge-status {{ $distribution->status }}">
                                {{ $distribution->status_label }}
                            </span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="info-label">{{ __('ui.notes') }}</div>
                            <div class="info-value" style="font-weight: 400;">
                                {{ $distribution->notes ?? 'No notes provided.' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-clock-history me-1"></i> Timeline
                    </h6>
                    <div class="timeline">
                        <div class="timeline-item completed">
                            <div class="timeline-title">Distribution Created</div>
                            <div class="timeline-date">{{ $distribution->created_at->format('M d, Y h:i A') }}</div>
                            <div class="timeline-detail">By: {{ $distribution->createdBy->name ?? 'System' }}</div>
                        </div>

                        @if($distribution->status != 'draft')
                            <div class="timeline-item completed">
                                <div class="timeline-title">{{ __('ui.approved') }}</div>
                                <div class="timeline-date">{{ $distribution->approved_at ? $distribution->approved_at->format('M d, Y h:i A') : 'N/A' }}</div>
                                <div class="timeline-detail">By: {{ $distribution->approvedBy->name ?? 'System' }}</div>
                            </div>
                        @endif

                        @if($distribution->status == 'distributed')
                            <div class="timeline-item completed">
                                <div class="timeline-title">{{ __('ui.distributed') }}</div>
                                <div class="timeline-date">{{ $distribution->updated_at->format('M d, Y h:i A') }}</div>
                            </div>
                        @endif

                        @if($distribution->status == 'pending' || $distribution->status == 'draft')
                            <div class="timeline-item active">
                                <div class="timeline-title">{{ __('ui.awaiting_processing') }}</div>
                                <div class="timeline-date text-warning">Pending action</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Delete Distribution
            $(document).on('click', '.delete-distribution', function() {
                const id = $(this).data('id');
                const number = $(this).data('number');

                Swal.fire({
                    title: 'Delete Distribution?',
                    text: `Are you sure you want to delete distribution "${number}"? This will reverse all transactions.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, delete!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/profit-distributions/${id}`,
                            method: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Deleted!', res.message, 'success').then(() => {
                                        window.location.href = '{{ route("admin.profit-distributions.index") }}';
                                    });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to delete distribution.', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
