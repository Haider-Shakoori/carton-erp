{{-- resources/views/admin/profit-distributions/create.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Create Profit Distribution')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .form-section {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-section .section-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--gray-700);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-100);
        }
        .shareholder-row {
            padding: 0.75rem;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            margin-bottom: 0.5rem;
            background: var(--gray-50);
        }
        .shareholder-row .shareholder-name {
            font-weight: 600;
        }
        .shareholder-row .share-percent {
            color: var(--gray-500);
            font-size: 0.8rem;
        }
        .profit-summary {
            background: var(--primary-bg);
            border-radius: var(--radius-sm);
            padding: 1rem;
        }
        .profit-summary .amount {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
        }
        .profit-summary .label {
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            font-weight: 600;
        }
        .calculated-amount {
            font-weight: 700;
            color: var(--primary);
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
                        <i class="bi bi-plus-circle me-2"></i> {{ __('ui.create') }} <span class="accent">{{ __('ui.profit_distribution') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-currency-dollar me-1"></i> Distribute profits to shareholders
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.profit-distributions.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <form id="distributionForm" method="POST" action="{{ route('admin.profit-distributions.store') }}">
            @csrf

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Period Selection -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-calendar-range me-1"></i> Period Selection
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.period_start') }} <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="period_start" id="periodStart"
                                       value="{{ old('period_start', $startDate->format('Y-m-d')) }}" required>
                                @error('period_start')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.period_end') }} <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="period_end" id="periodEnd"
                                       value="{{ old('period_end', $endDate->format('Y-m-d')) }}" required>
                                @error('period_end')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <button type="button" class="btn btn-outline-primary" id="calculateProfit">
                                    <i class="bi bi-calculator me-1"></i> Calculate Profit
                                </button>
                                <span class="text-muted ms-2 small">Calculate total profit for the selected period</span>
                            </div>
                            <div class="col-12" id="profitResult" style="display: none;">
                                <div class="alert alert-success">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><i class="bi bi-check-circle me-1"></i> Total Profit for the period:</span>
                                        <span class="fw-bold fs-5" id="calculatedTotalProfit">{{ $defaultCurrency?->symbol ?? '$' }}0.00</span>
                                    </div>
                                </div>
                                <input type="hidden" name="total_profit" id="totalProfit">
                            </div>
                        </div>
                    </div>

                    <!-- Shareholder Distribution -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-people-fill me-1"></i> Shareholder Distribution
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-1"></i>
                            Total distribution amount must equal the total profit.
                        </div>

                        <div id="shareholderList">
                            @foreach($shareholders as $shareholder)
                                <div class="shareholder-row" data-id="{{ $shareholder->id }}" data-percentage="{{ $shareholder->share_percentage }}">
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <div class="shareholder-name">{{ $shareholder->name }}</div>
                                            <div class="share-percent">Share: {{ $shareholder->share_percentage }}%</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">{{ __('ui.amount') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ $defaultCurrency?->symbol ?? '$' }}</span>
                                                <input type="number" class="form-control form-control-sm shareholder-amount"
                                                       name="shareholders[{{ $loop->index }}][amount]"
                                                       data-id="{{ $shareholder->id }}"
                                                       step="0.01" min="0" value="0">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Calculated</label>
                                            <div class="fw-bold text-primary calculated-amount" data-id="{{ $shareholder->id }}">
                                                {{ $defaultCurrency?->symbol ?? '$' }}0.00
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-success auto-calculate" data-id="{{ $shareholder->id }}">
                                                <i class="bi bi-magic"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="shareholders[{{ $loop->index }}][id]" value="{{ $shareholder->id }}">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-sticky me-1"></i> {{ __('ui.notes') }}
                        </div>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Add any notes about this distribution...">{{ old('notes') }}</textarea>
                        @error('notes')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-info-circle me-1"></i> Summary
                        </div>

                        <div class="profit-summary text-center">
                            <div class="label">{{ __('ui.total_profit') }}</div>
                            <div class="amount" id="summaryTotalProfit">{{ $defaultCurrency?->symbol ?? '$' }}0.00</div>
                            <hr>
                            <div class="label">{{ __('ui.total_distributed') }}</div>
                            <div class="amount text-success" id="summaryTotalDistributed">{{ $defaultCurrency?->symbol ?? '$' }}0.00</div>
                            <hr>
                            <div class="label">{{ __('ui.remaining') }}</div>
                            <div class="amount text-warning" id="summaryRemaining">{{ $defaultCurrency?->symbol ?? '$' }}0.00</div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                                <i class="bi bi-save me-1"></i> Create Distribution
                            </button>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>{{ __('ui.note_colon') }}</strong> The distribution will be created as a <span class="fw-bold">{{ __('ui.draft') }}</span> and will require approval before distribution.
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Calculate Profit
            $('#calculateProfit').on('click', function() {
                const startDate = $('#periodStart').val();
                const endDate = $('#periodEnd').val();

                if (!startDate || !endDate) {
                    Swal.fire('Error', 'Please select both start and end dates.', 'error');
                    return;
                }

                if (new Date(startDate) > new Date(endDate)) {
                    Swal.fire('Error', 'Start date cannot be after end date.', 'error');
                    return;
                }

                $.ajax({
                    url: '{{ route("admin.profit-distributions.calculate") }}',
                    method: 'GET',
                    data: { period_start: startDate, period_end: endDate },
                    success: function(res) {
                        if (res.success) {
                            $('#profitResult').show();
                            $('#calculatedTotalProfit').text('{{ $defaultCurrency?->symbol ?? '$' }}' + res.data.total_profit.toFixed(2));
                            $('#totalProfit').val(res.data.total_profit);
                            $('#summaryTotalProfit').text('{{ $defaultCurrency?->symbol ?? '$' }}' + res.data.total_profit.toFixed(2));

                            // Auto-calculate for each shareholder
                            res.data.shareholders.forEach(function(item) {
                                const input = $(`input[name^="shareholders"][data-id="${item.shareholder_id}"]`);
                                if (input.length) {
                                    input.val(item.amount.toFixed(2));
                                    input.trigger('input');
                                }
                                const calcSpan = $(`.calculated-amount[data-id="${item.shareholder_id}"]`);
                                if (calcSpan.length) {
                                    calcSpan.text('{{ $defaultCurrency?->symbol ?? '$' }}' + item.amount.toFixed(2));
                                }
                            });

                            updateSummary();

                            Swal.fire('Success', 'Profit calculated successfully.', 'success');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to calculate profit.', 'error');
                    }
                });
            });

            // Auto-calculate single shareholder
            $(document).on('click', '.auto-calculate', function() {
                const id = $(this).data('id');
                const totalProfit = parseFloat($('#totalProfit').val()) || 0;
                const percentage = $(`.shareholder-row[data-id="${id}"]`).data('percentage') || 0;
                const amount = totalProfit * (percentage / 100);

                const input = $(`input[name^="shareholders"][data-id="${id}"]`);
                input.val(amount.toFixed(2));
                input.trigger('input');

                const calcSpan = $(`.calculated-amount[data-id="${id}"]`);
                calcSpan.text('{{ $defaultCurrency?->symbol ?? '$' }}' + amount.toFixed(2));

                updateSummary();
            });

            // Update summary on amount change
            $(document).on('input', '.shareholder-amount', function() {
                updateSummary();
            });

            function updateSummary() {
                let totalDistributed = 0;
                $('.shareholder-amount').each(function() {
                    const val = parseFloat($(this).val()) || 0;
                    totalDistributed += val;
                });

                const totalProfit = parseFloat($('#totalProfit').val()) || 0;
                const remaining = totalProfit - totalDistributed;

                $('#summaryTotalDistributed').text('{{ $defaultCurrency?->symbol ?? '$' }}' + totalDistributed.toFixed(2));
                $('#summaryRemaining').text('{{ $defaultCurrency?->symbol ?? '$' }}' + remaining.toFixed(2));

                // Highlight if distribution doesn't match profit
                if (Math.abs(remaining) > 0.01) {
                    $('#summaryRemaining').addClass('text-danger').removeClass('text-warning');
                } else {
                    $('#summaryRemaining').removeClass('text-danger').addClass('text-warning');
                }
            }

            // Form submit
            $('#distributionForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = $('#submitBtn');

                // Validate total distribution matches profit
                const totalProfit = parseFloat($('#totalProfit').val()) || 0;
                let totalDistributed = 0;
                $('.shareholder-amount').each(function() {
                    totalDistributed += parseFloat($(this).val()) || 0;
                });

                // Compare integer cents instead of binary floating-point values.
                // Example: 5502.35 - 5502.34 can become 0.0100000000002 in JS.
                const totalProfitCents = Math.round(totalProfit * 100);
                const totalDistributedCents = Math.round(totalDistributed * 100);

                if (totalDistributedCents !== totalProfitCents) {
                    Swal.fire({
                        title: 'Distribution Mismatch',
                        text: `Total distributed (${totalDistributed.toFixed(2)}) does not match total profit (${totalProfit.toFixed(2)}). Please adjust the amounts.`,
                        icon: 'warning',
                        confirmButtonColor: '#4f46e5'
                    });
                    return;
                }

                if (totalProfit <= 0) {
                    Swal.fire('Error', 'Total profit must be greater than 0.', 'error');
                    return;
                }

                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Creating...'
                );

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success').then(() => {
                                window.location.href = '{{ route("admin.profit-distributions.index") }}';
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            submitBtn.prop('disabled', false).html(
                                '<i class="bi bi-save me-1"></i> Create Distribution'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to create distribution.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-save me-1"></i> Create Distribution'
                        );
                    }
                });
            });
        });
    </script>
@endsection
