{{-- resources/views/admin/sales/create.blade.php --}}

@extends('layouts.admin.base')

@section('title', 'Create Sale Order')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .section-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 8px 32px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--gray-200);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .section-card:hover {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 12px 48px rgba(0, 0, 0, 0.06);
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-title i {
            color: var(--primary);
        }

        .form-label {
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--gray-600);
            margin-bottom: 0.35rem;
        }
        .form-label .text-danger {
            color: #ef4444;
        }

        .form-control, .form-select {
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-200);
            padding: 0.45rem 0.75rem;
            transition: all 0.3s ease;
            font-size: 0.875rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.08);
        }
        .form-control[readonly] {
            background: var(--gray-50);
            cursor: not-allowed;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35);
            color: white;
        }

        .btn-outline-secondary {
            border: 1.5px solid var(--gray-200);
            color: var(--gray-600);
            background: transparent;
            padding: 0.6rem 1.5rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-outline-secondary:hover {
            background: var(--gray-100);
            border-color: var(--gray-300);
        }

        /* ─── Select2 Customization ─── */
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 8px !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px !important;
            padding-left: 0.75rem !important;
            color: #1e293b !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }
        .select2-container--default .select2-dropdown {
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 8px !important;
            overflow: hidden !important;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 6px !important;
            padding: 0.5rem 0.75rem !important;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: #4f46e5 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1) !important;
        }
        .select2-container--default .select2-results__option {
            padding: 0.5rem 0.75rem !important;
        }
        .select2-container--default .select2-results__option--highlighted {
            background-color: #eef2ff !important;
            color: #4f46e5 !important;
        }
        .select2-container--default .select2-results__option[aria-selected="true"] {
            background-color: #eef2ff !important;
            color: #4f46e5 !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1) !important;
        }

        /* ─── Currency Badge ─── */
        .currency-badge {
            background: #e0e7ff;
            color: #4f46e5;
            padding: 0.1rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .section-card {
                padding: 1rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">

        {{-- ─── PAGE HEADER ─── --}}
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-plus-circle me-2"></i>
                        {{ __('ui.create') }} <span class="accent">{{ __('ui.sale_order') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-box-arrow-up-right me-1"></i>
                        Select customer, currency, and create a new sale order
                        <span class="currency-badge ms-2">
                            <i class="bi bi-currency-exchange me-1"></i>
                            {{ $defaultCurrency->symbol ?? '$' }}
                        </span>
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Sales
                    </a>
                </div>
            </div>
        </div>

        {{-- ─── MAIN FORM ─── --}}
        <form id="saleForm" method="POST" action="{{ route('admin.sales.store') }}">
            @csrf

            {{-- ─── SALE HEADER ─── --}}
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-file-text"></i>
                    Sale Information
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Sale Number</label>
                        <input type="text" name="sale_no" class="form-control" value="{{ $nextSaleNo }}" readonly>
                        <small class="text-muted">Auto-generated</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('ui.customer') }} <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-select select2-customer" required>
                            <option value="">Select Customer...</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                    @if ($customer->code)
                                        ({{ $customer->code }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                        <div class="text-danger" style="font-size: 0.8rem;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('ui.currency') }} <span class="text-danger">*</span></label>
                        <select name="currency_id" class="form-select select2-currency" required>
                            <option value="">Select Currency...</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}"
                                    {{ old('currency_id') == $currency->id ? 'selected' : '' }}>
                                    {{ $currency->name }} ({{ $currency->code }})
                                    @if ($currency->is_default)
                                        <span class="badge bg-primary ms-1">Default</span>
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('currency_id')
                        <div class="text-danger" style="font-size: 0.8rem;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('ui.sale_date') }}</label>
                        <input type="date" name="sale_date" class="form-control" value="{{ date('Y-m-d') }}">
                        @error('sale_date')
                        <div class="text-danger" style="font-size: 0.8rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('ui.shipping_address') }}</label>
                        <textarea name="shipping_address" class="form-control" rows="2" placeholder="Enter shipping address...">{{ old('shipping_address') }}</textarea>
                        @error('shipping_address')
                        <div class="text-danger" style="font-size: 0.8rem;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('ui.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('ui.additional_notes_dots') }}">{{ old('notes') }}</textarea>
                        @error('notes')
                        <div class="text-danger" style="font-size: 0.8rem;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ─── SUBMIT ─── --}}
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="createSaleBtn">
                    <i class="bi bi-check-lg me-1"></i> Create Sale Order
                </button>
                <button type="reset" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('ui.reset') }}
                </button>
            </div>

        </form>

    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // ─── Initialize Select2 ───
            $('.select2-customer').select2({
                placeholder: 'Search for a customer...',
                allowClear: true,
                theme: 'bootstrap-5',
                width: '100%'
            });

            $('.select2-currency').select2({
                placeholder: 'Search for a currency...',
                allowClear: true,
                theme: 'bootstrap-5',
                width: '100%'
            });

            // ─── Form Validation ───
            $('#saleForm').on('submit', function(e) {
                const customer = $('select[name="customer_id"]').val();
                const currency = $('select[name="currency_id"]').val();

                if (!customer) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Customer Required',
                        text: 'Please select a customer for this sale order.',
                        confirmButtonColor: '#4f46e5'
                    });
                    return false;
                }

                if (!currency) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Currency Required',
                        text: 'Please select a currency for this sale order.',
                        confirmButtonColor: '#4f46e5'
                    });
                    return false;
                }

                return true;
            });

            // ─── Auto-focus first field ───
            $('select[name="customer_id"]').focus();
        });
    </script>
@endsection
