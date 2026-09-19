@extends('layouts.admin.base')

@section('title', __('ui.edit_purchase_order'))

@section('css')
    <link href="{{ asset('vendor/select2/select2.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}">
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>
        .info-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 8px 32px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }
        .info-card-header h5 {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--gray-600);
        }
    </style>
@endsection

@section('content')
    @php
        $isArrived = ($purchase->status ?? 'draft') === 'arrived';
        $isDisabled = $isArrived;
    @endphp
    <div class="container-fluid px-3 px-md-4">
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-pencil-square me-2"></i>
                        {{ __('ui.edit_purchase_order') }}
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-truck me-1"></i>
                        {{ $purchase->purchase_no }} &bull; {{ __('ui.edit_header_info') }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.purchase-orders.show', $purchase->id) }}" class="btn btn-light">
                        <i class="bi bi-arrow-left me-1"></i> {{ __('ui.back_to_order') }}
                    </a>
                </div>
            </div>
        </div>

        @if ($isArrived)
            <div class="alert alert-warning d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle"></i>
                <span>{{ __('ui.purchase_arrived_lock_msg') }}</span>
            </div>
        @endif

        <div class="table-card">
            <div class="card-header-custom">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i> {{ __('ui.purchase_order_header') }}
                </h6>
            </div>
            <div class="p-4">
                <form id="editPurchaseForm">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('ui.purchase_no') }}</label>
                            <input type="text" class="form-control" value="{{ $purchase->purchase_no }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('ui.supplier') }} <span class="text-danger">*</span></label>
                            <select class="form-select select2-supplier @error('supplier_id') is-invalid @enderror"
                                name="supplier_id" id="supplier_id" required {{ $isDisabled ? 'disabled' : '' }}>
                                <option value="">{{ __('ui.select_supplier') }}</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ (int) $purchase->supplier_id === (int) $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('ui.currency') }} <span class="text-danger">*</span></label>
                            <select class="form-select @error('currency_id') is-invalid @enderror"
                                name="currency_id" id="currency_id" required {{ $isDisabled ? 'disabled' : '' }}>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}"
                                        {{ (int) $purchase->currency_id === (int) $currency->id ? 'selected' : '' }}>
                                        {{ $currency->code }} ({{ $currency->symbol ?? '' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">{{ __('ui.purchase_date') }}</label>
                            <input type="date" class="form-control" name="purchase_date" id="purchase_date"
                                value="{{ $purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d') : '' }}"
                                {{ $isDisabled ? 'disabled' : '' }}>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('ui.arrival_date') }}</label>
                            <input type="date" class="form-control" name="arrival_date" id="arrival_date"
                                value="{{ $purchase->arrival_date ? $purchase->arrival_date->format('Y-m-d') : '' }}"
                                {{ $isDisabled ? 'disabled' : '' }}>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('ui.exchange_rate') }}</label>
                            <input type="number" step="0.000001" min="0" class="form-control" name="exchange_rate"
                                id="exchange_rate" value="{{ $purchase->exchange_rate }}"
                                placeholder="e.g. 66" {{ $isDisabled ? 'disabled' : '' }}>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('ui.status') }}</label>
                            <input type="text" class="form-control" value="{{ ucfirst($purchase->status ?? '-') }}"
                                readonly>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">{{ __('ui.notes') }}</label>
                            <textarea class="form-control" name="descriptions" id="descriptions" rows="2"
                                placeholder="{{ __('ui.optional_notes') }}"
                                {{ $isDisabled ? 'disabled' : '' }}>{{ $purchase->notes ?? '' }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="saveBtn"
                                {{ $isDisabled ? 'disabled' : '' }}>
                                <i class="bi bi-check2 me-1"></i> {{ __('ui.save_changes') }}
                            </button>
                            <a href="{{ route('admin.purchase-orders.show', $purchase->id) }}"
                                class="btn btn-secondary">
                                <i class="bi bi-x-lg me-1"></i> {{ __('ui.cancel') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('.select2-supplier').select2({
                placeholder: '{{ __('ui.select_supplier') }}',
                allowClear: true
            });

            $('#editPurchaseForm').on('submit', function(e) {
                e.preventDefault();

                if ($('#saveBtn').prop('disabled')) {
                    return;
                }

                let form = $(this);
                let data = form.serialize();

                $.ajax({
                    url: '{{ route('admin.purchase-orders.update', $purchase->id) }}',
                    method: 'PUT',
                    data: data,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        $('#saveBtn').prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm me-1"></span> {{ __('ui.saving') }}');
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                window.location.href =
                                    '{{ route('admin.purchase-orders.show', $purchase->id) }}';
                            }
                        } else {
                            $('#saveBtn').prop('disabled', false).html(
                                '<i class="bi bi-check2 me-1"></i> {{ __('ui.save_changes') }}');
                            alert(response.message || '{{ __('ui.error_saving') }}');
                        }
                    },
                    error: function(xhr) {
                        $('#saveBtn').prop('disabled', false).html(
                            '<i class="bi bi-check2 me-1"></i> {{ __('ui.save_changes') }}');
                        let msg = xhr.responseJSON?.message || '{{ __('ui.error_saving') }}';
                        alert(msg);
                    }
                });
            });
        });
    </script>
@endsection
