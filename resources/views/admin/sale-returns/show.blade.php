{{-- resources/views/admin/sale-returns/show.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Sale Return #' . $return->return_no)

@section('css')
    <link href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>
        .return-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .return-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--gray-900);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .return-header h1 .accent {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .return-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .return-status-badge.draft {
            background: var(--gray-100);
            color: var(--gray-600);
            border: 1px solid var(--gray-200);
        }

        .return-status-badge.approved {
            background: #e0e7ff;
            color: var(--primary);
            border: 1px solid #a5b4fc;
        }

        .return-status-badge.rejected {
            background: #fecaca;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .return-status-badge.processed {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .sale-info-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }

        .sale-summary-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .return-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }

        .return-table thead th {
            padding: 0.625rem 0.75rem;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            color: var(--gray-600);
            font-weight: 700;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 2px solid var(--gray-200);
            white-space: nowrap;
        }

        .return-table tbody td {
            padding: 0.625rem 0.75rem;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }

        .return-table tbody tr:hover {
            background: rgba(79, 70, 229, 0.02);
        }

        .reason-badge {
            font-size: 0.65rem;
            padding: 0.15rem 0.5rem;
            border-radius: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .reason-badge.damaged {
            background: #fecaca;
            color: #991b1b;
        }

        .reason-badge.defective {
            background: #fed7aa;
            color: #9a3412;
        }

        .reason-badge.wrong_item {
            background: #bfdbfe;
            color: #1e40af;
        }

        .reason-badge.wrong_quantity {
            background: #c7d2fe;
            color: #3730a3;
        }

        .reason-badge.customer_cancelled {
            background: #fde68a;
            color: #92400e;
        }

        .reason-badge.quality_issue {
            background: #fbcfe8;
            color: #831843;
        }

        .reason-badge.other {
            background: var(--gray-200);
            color: var(--gray-600);
        }

        .stats-grid-3 {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 1024px) {
            .stats-grid-3 {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 640px) {
            .stats-grid-3 {
                grid-template-columns: 1fr;
            }
        }

        .sale-section {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            margin-top: 1.5rem;
            overflow: hidden;
        }

        .card-header-custom {
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, #fafafa, #ffffff);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .card-header-custom h5 {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-header-custom h5 i {
            color: var(--primary);
        }

        .header-badge {
            font-size: 0.65rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            background: var(--gray-100);
            color: var(--gray-600);
        }

        .empty-state {
            text-align: center;
            padding: 2.5rem 1rem;
        }

        .empty-state .icon {
            font-size: 2.5rem;
            color: var(--gray-300);
            margin-bottom: 0.75rem;
            display: block;
        }

        .empty-state .title {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.9375rem;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">

        {{-- HEADER --}}
        <div class="return-header">
            <div>
                <h1>
                    <i class="bi bi-arrow-return-left" style="color: #f59e0b;"></i>
                    Return <span class="accent">#{{ $return->return_no }}</span>
                </h1>
                <p class="subtitle">
                    <i class="bi bi-receipt me-1"></i>
                    Original Sale: <a href="{{ route('admin.sales.show', $return->sale_id) }}" class="po-link">
                        #{{ $return->sale->sale_no ?? 'N/A' }}
                    </a>
                    <span class="mx-1">·</span>
                    <i class="bi bi-person"></i>
                    {{ $return->customer->name ?? 'No Customer' }}
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                @php
                    $statusConfig = [
                        'draft' => ['class' => 'draft', 'icon' => 'bi-pencil-square', 'label' => 'Draft'],
                        'approved' => ['class' => 'approved', 'icon' => 'bi-check2-circle', 'label' => 'Approved'],
                        'rejected' => ['class' => 'rejected', 'icon' => 'bi-x-circle', 'label' => 'Rejected'],
                        'processed' => ['class' => 'processed', 'icon' => 'bi-arrow-repeat', 'label' => 'Processed'],
                    ];
                    $config = $statusConfig[$return->status] ?? $statusConfig['draft'];
                @endphp

                <span class="return-status-badge {{ $config['class'] }}">
                    <i class="bi {{ $config['icon'] }}"></i>
                    {{ $config['label'] }}
                </span>

                <a href="{{ route('admin.sale-returns.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>

                @if ($return->status === 'draft')
                    {{-- Approve Button --}}
                    <button class="btn btn-success" onclick="updateStatus('approved')">
                        <i class="bi bi-check2-circle"></i> {{ __('ui.approve') }}
                    </button>

                    {{-- Reject Button --}}
                    <button class="btn btn-danger" onclick="updateStatus('rejected')">
                        <i class="bi bi-x-circle"></i> {{ __('ui.reject') }}
                    </button>

                    {{-- Delete Button --}}
                    <form action="{{ route('admin.sale-returns.destroy', $return->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Delete this return?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                @endif

                @if (isset($canProcess) && $canProcess)
                    <form action="{{ route('admin.sale-returns.process', $return->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Process this return? This will restock inventory and create refund transactions.');">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-arrow-repeat"></i> Process Return
                        </button>
                    </form>
                @endif

                @if ($return->status === 'processed')
                    <span class="return-status-badge processed">
                        <i class="bi bi-check2-all"></i>
                        Processed on {{ $return->processed_at ? date('M d, Y', strtotime($return->processed_at)) : 'N/A' }}
                    </span>
                @endif
            </div>
        </div>

        {{-- INFO CARDS --}}
        <div class="stats-grid-3">
            <div class="sale-info-card">
                <div class="card-header-custom"
                    style="border-bottom: 1px solid var(--gray-200); padding-bottom: 0.75rem; margin-bottom: 0.75rem;">
                    <h5 style="font-size: 0.875rem; font-weight: 700; color: var(--gray-800); margin: 0;">
                        <i class="bi bi-receipt" style="color: var(--primary);"></i> Return Details
                    </h5>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <div
                            style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: var(--gray-400);">
                            Return Date</div>
                        <div style="font-weight: 600;">
                            {{ $return->return_date ? date('M d, Y', strtotime($return->return_date)) : '-' }}</div>
                    </div>
                    <div class="col-6">
                        <div
                            style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: var(--gray-400);">
                            {{ __('ui.currency') }}</div>
                        <div style="font-weight: 600;">{{ $return->currency->code ?? 'USD' }}
                            ({{ $return->currency->symbol ?? '$' }})</div>
                    </div>
                    <div class="col-6">
                        <div
                            style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: var(--gray-400);">
                            {{ __('ui.exchange_rate') }}</div>
                        <div style="font-weight: 600;">1 {{ $return->currency->code ?? 'USD' }} =
                            {{ number_format($return->exchange_rate, 4) }} USD</div>
                    </div>
                    <div class="col-6">
                        <div
                            style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: var(--gray-400);">
                            {{ __('ui.items') }}</div>
                        <div style="font-weight: 600;">{{ $return->items->count() }} items returned</div>
                    </div>
                    @if ($return->reason)
                        <div class="col-12">
                            <div
                                style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: var(--gray-400);">
                                Return Reason</div>
                            <div style="font-weight: 500;">{{ $return->reason }}</div>
                        </div>
                    @endif
                    @if ($return->notes)
                        <div class="col-12">
                            <div
                                style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: var(--gray-400);">
                                {{ __('ui.notes') }}</div>
                            <div style="font-weight: 500;">{{ $return->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="sale-summary-card"
                style="border: 1px solid var(--gray-200); border-radius: var(--radius); overflow: hidden;">
                <div
                    style="padding: 1rem; background: linear-gradient(135deg, #fafafa, #ffffff); border-bottom: 1px solid var(--gray-200);">
                    <h6 style="font-weight: 700; font-size: 0.8125rem; color: var(--gray-800); margin: 0;">
                        <i class="bi bi-calculator" style="color: var(--primary);"></i> Totals
                        ({{ $return->currency->code ?? 'USD' }})
                    </h6>
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid var(--gray-100);">
                        <td style="padding: 0.5rem 1rem; color: var(--gray-500); font-weight: 500;">{{ __('ui.subtotal') }}</td>
                        <td style="padding: 0.5rem 1rem; text-align: right; font-weight: 600;">
                            {{ $return->currency->symbol ?? '$' }}{{ number_format($return->subtotal, 2) }}</td>
                    </tr>
                    @if ($return->discount_total > 0)
                        <tr style="border-bottom: 1px solid var(--gray-100);">
                            <td style="padding: 0.5rem 1rem; color: var(--gray-500); font-weight: 500;">{{ __('ui.discount') }}</td>
                            <td style="padding: 0.5rem 1rem; text-align: right; font-weight: 600; color: var(--danger);">
                                -{{ $return->currency->symbol ?? '$' }}{{ number_format($return->discount_total, 2) }}
                            </td>
                        </tr>
                    @endif
                    <tr style="border-bottom: 1px solid var(--gray-100);">
                        <td style="padding: 0.5rem 1rem; color: var(--gray-500); font-weight: 500;">{{ __('ui.restocking_fee') }}</td>
                        <td style="padding: 0.5rem 1rem; text-align: right; font-weight: 600; color: var(--warning);">
                            {{ $return->currency->symbol ?? '$' }}{{ number_format($return->restocking_fee, 2) }}</td>
                    </tr>
                    <tr style="border-top: 2px solid var(--gray-200); background: #fef3c7;">
                        <td style="padding: 0.75rem 1rem; font-weight: 700; color: var(--gray-800);">{{ __('ui.refund_amount') }}</td>
                        <td
                            style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; font-size: 1.1rem; color: #f59e0b;">
                            {{ $return->currency->symbol ?? '$' }}{{ number_format($return->refund_amount, 2) }}</td>
                    </tr>
                </table>
            </div>

            <div class="sale-summary-card"
                style="border: 1px solid var(--gray-200); border-radius: var(--radius); overflow: hidden;">
                <div
                    style="padding: 1rem; background: linear-gradient(135deg, #fafafa, #ffffff); border-bottom: 1px solid var(--gray-200);">
                    <h6 style="font-weight: 700; font-size: 0.8125rem; color: var(--gray-800); margin: 0;">
                        <i class="bi bi-currency-dollar" style="color: #059669;"></i> USD Totals
                    </h6>
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid var(--gray-100);">
                        <td style="padding: 0.5rem 1rem; color: var(--gray-500); font-weight: 500;">{{ __('ui.subtotal') }}</td>
                        <td style="padding: 0.5rem 1rem; text-align: right; font-weight: 600;">
                            ${{ number_format($return->usd_subtotal, 2) }}</td>
                    </tr>
                    @if ($return->usd_discount_total > 0)
                        <tr style="border-bottom: 1px solid var(--gray-100);">
                            <td style="padding: 0.5rem 1rem; color: var(--gray-500); font-weight: 500;">{{ __('ui.discount') }}</td>
                            <td style="padding: 0.5rem 1rem; text-align: right; font-weight: 600; color: var(--danger);">
                                -${{ number_format($return->usd_discount_total, 2) }}</td>
                        </tr>
                    @endif
                    <tr style="border-bottom: 1px solid var(--gray-100);">
                        <td style="padding: 0.5rem 1rem; color: var(--gray-500); font-weight: 500;">{{ __('ui.restocking_fee') }}</td>
                        <td style="padding: 0.5rem 1rem; text-align: right; font-weight: 600; color: var(--warning);">
                            ${{ number_format($return->usd_restocking_fee, 2) }}</td>
                    </tr>
                    <tr style="border-top: 2px solid var(--gray-200); background: #d1fae5;">
                        <td style="padding: 0.75rem 1rem; font-weight: 700; color: var(--gray-800);">{{ __('ui.refund_amount') }}</td>
                        <td
                            style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; font-size: 1.1rem; color: #059669;">
                            ${{ number_format($return->usd_refund_amount, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- ITEMS TABLE --}}
        <div class="sale-section">
            <div class="card-header-custom">
                <h5>
                    <i class="bi bi-list-ul"></i> Returned Items
                    <span class="header-badge">
                        {{ $return->items->count() }} items
                    </span>
                </h5>
                <div style="display: flex; gap: 0.5rem; font-size: 0.75rem; color: var(--gray-400);">
                    <span><i class="bi bi-box"></i> {{ $return->items->sum('qty_returned') }} total units</span>
                </div>
            </div>

            <div style="padding: 1.5rem;">
                <div class="table-responsive">
                    <table class="return-table">
                        <thead>
                            <tr>
                                <th style="min-width: 150px;">{{ __('ui.product') }}</th>
                                <th style="min-width: 80px;" class="text-center">Qty</th>
                                <th style="min-width: 120px;" class="text-end">{{ __('ui.unit_price') }}</th>
                                <th style="min-width: 120px;" class="text-end">{{ __('ui.total') }}</th>
                                <th style="min-width: 120px;">{{ __('ui.reason') }}</th>
                                <th style="min-width: 100px;">Condition</th>
                                <th style="min-width: 100px;">Restock Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($return->items as $item)
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: var(--gray-800);">
                                            {{ $item->product->name ?? 'Unknown' }}
                                        </div>
                                        @if ($item->reason_notes)
                                            <div style="font-size: 0.65rem; color: var(--gray-400);">
                                                <i class="bi bi-journal-text"></i> {{ $item->reason_notes }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold">{{ number_format($item->qty_returned, 2) }}</td>
                                    <td class="text-end">
                                        {{ $return->currency->symbol ?? '$' }}{{ number_format($item->unit_price, 2) }}
                                    </td>
                                    <td class="text-end fw-bold">
                                        {{ $return->currency->symbol ?? '$' }}{{ number_format($item->total, 2) }}</td>
                                    <td>
                                        <span class="reason-badge {{ $item->reason }}">
                                            {{ $item->reason_label ?? ucfirst($item->reason) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.75rem; color: var(--gray-600);">
                                            {{ $item->condition_label ?? ucfirst($item->condition) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $item->restocked ? 'success' : 'warning' }}">
                                            {{ $item->restocked ? 'Restocked' : 'Pending Restock' }}
                                        </span>
                                        @if ($item->restocked_at)
                                            <div style="font-size: 0.6rem; color: var(--gray-400);">
                                                {{ \Carbon\Carbon::parse($item->restocked_at)->format('M d, Y H:i') }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="bi bi-box-seam icon"></i>
                                            <div class="title">No items returned</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- ─── Status Update Form ─── --}}
    <form id="statusForm" method="POST" action="{{ route('admin.sale-returns.update-status', $return->id) }}"
        style="display: none;">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" id="statusInput">
        <input type="hidden" name="notes" id="statusNotes">
    </form>

@endsection

@section('js')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        function updateStatus(status) {
            const action = status === 'approved' ? 'approve' : 'reject';
            const title = action === 'approve' ? 'Approve Return' : 'Reject Return';
            const icon = action === 'approve' ? 'question' : 'warning';
            const confirmColor = action === 'approve' ? '#10b981' : '#ef4444';
            const text = action === 'approve' ?
                'This will mark the return as approved. You can process it later to restock inventory.' :
                'This will reject the return. The items will not be restocked.';

            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, ' + action + ' it!',
                input: 'textarea',
                inputLabel: 'Notes (optional)',
                inputPlaceholder: 'Add any notes about this decision...',
                inputAttributes: {
                    'aria-label': 'Notes'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#statusInput').val(status);
                    $('#statusNotes').val(result.value || '');
                    $('#statusForm').submit();
                }
            });
        }
    </script>
@endsection
