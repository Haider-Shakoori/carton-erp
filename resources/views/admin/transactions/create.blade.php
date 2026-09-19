@extends('layouts.admin.base')

@section('title', __('ui.transactions'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}" />
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-dark: #4338ca;
            --success-color: #10b981;
            --danger-color: #ef4444;
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
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 8px 32px rgba(0, 0, 0, 0.04);
        }

        .select2-container--bootstrap-5 {
            width: 100% !important;
        }

        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-200);
            font-size: 0.875rem;
            transition: all 0.3s ease;
            background: white;
        }

        .select2-container--bootstrap-5 .select2-selection:hover {
            border-color: var(--primary-color);
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding: 0.3rem 0.75rem;
            color: var(--gray-800);
            line-height: 1.5;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 36px;
            color: var(--gray-400);
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow b {
            border-color: var(--gray-400) transparent transparent transparent;
        }

        .select2-container--bootstrap-5 .select2-dropdown {
            border-radius: var(--radius-sm);
            border-color: var(--gray-200);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            background: white !important;
            overflow: hidden;
        }

        .select2-container--bootstrap-5 .select2-results {
            background: white !important;
            padding: 4px 0;
        }

        .select2-container--bootstrap-5 .select2-results>.select2-results__options {
            max-height: 280px !important;
            overflow-y: auto !important;
            background: white !important;
        }

        .select2-container--bootstrap-5 .select2-results__option {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            background: white !important;
            color: var(--gray-700) !important;
            border-bottom: 1px solid var(--gray-50);
            cursor: pointer;
        }

        .select2-container--bootstrap-5 .select2-results__option:last-child {
            border-bottom: none;
        }

        .select2-container--bootstrap-5 .select2-results__option--highlighted {
            background: linear-gradient(135deg, #eef2ff, #e0e7ff) !important;
            color: var(--primary-color) !important;
            border-left: 3px solid var(--primary-color);
        }

        .select2-container--bootstrap-5 .select2-results__option[aria-selected="true"] {
            background: #f8faff !important;
            color: var(--primary-color) !important;
        }

        .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected="true"] {
            background: linear-gradient(135deg, #eef2ff, #dbeafe) !important;
            color: var(--primary-dark) !important;
            border-left: 3px solid var(--primary-dark);
        }

        .select2-container--bootstrap-5 .select2-search--dropdown {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-200);
            padding: 0.4rem 0.75rem;
            font-size: 0.875rem;
            outline: none;
            transition: all 0.3s ease;
            width: 100%;
        }

        .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.08);
        }

        .card-credit {
            border-color: var(--primary-color) !important;
        }

        .card-debit {
            border-color: var(--danger-color) !important;
        }

        .card-credit .card-header {
            border-bottom-color: var(--primary-color) !important;
        }

        .card-debit .card-header {
            border-bottom-color: var(--danger-color) !important;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.75rem;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.25rem;
        }

        .form-control,
        .form-select {
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-200);
            padding: 0.45rem 0.75rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.08);
        }

        .form-control-sm,
        .form-select-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        .table-transactions td,
        .table-transactions th {
            padding: 8px 10px !important;
            vertical-align: middle;
            line-height: 1.3;
        }

        .table-transactions td {
            white-space: nowrap;
        }

        .action-container {
            display: flex;
            gap: 4px;
            justify-content: center;
        }

        .btn-table-action {
            padding: 2px 6px !important;
            font-size: 11px !important;
        }

        #account-balance-summary {
            display: flex !important;
            align-items: center;
            justify-content: flex-end;
            min-height: 40px;
            min-width: 200px;
            position: relative;
        }

        #account-balance-summary .card {
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
            margin-bottom: 0 !important;
        }

        #account-balance-summary .card-header {
            display: none;
        }

        #account-balance-summary .card-body {
            padding: 0 !important;
        }

        #account-balance-summary table,
        #account-balance-summary tbody {
            display: flex !important;
            flex-direction: row !important;
            gap: 8px;
            align-items: center;
            margin: 0;
        }

        #account-balance-summary tr {
            display: flex !important;
            align-items: center;
            padding: 4px 12px;
            background: #f8f9fa;
            border-radius: 50px;
            border: 1px solid #e9ecef;
            white-space: nowrap;
            height: 32px;
        }

        #account-balance-summary td {
            border: none !important;
            padding: 0 4px !important;
        }

        .loading-balance {
            font-size: 11px;
            color: #6c757d;
            position: absolute;
            right: 0;
        }

        #filter-container {
            display: none;
        }

        .pagination-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding: 0.75rem 0 0 0;
            border-top: 1px solid var(--gray-200);
        }

        .pagination-wrap .info-text {
            font-size: 0.8125rem;
            color: var(--gray-500);
        }

        .pagination-wrap .pagination {
            margin: 0;
        }

        .pagination-wrap .page-link {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
            border-radius: 6px;
            border: 1px solid var(--gray-200);
            color: var(--gray-700);
            transition: all 0.2s ease;
        }

        .pagination-wrap .page-link:hover {
            background: var(--gray-50);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .pagination-wrap .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .table-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .table-loading .spinner-border {
            width: 2rem;
            height: 2rem;
        }

        @media (max-width: 768px) {
            .horizontal-form-row {
                flex-direction: column;
                align-items: stretch;
            }

            .form-group-item {
                min-width: 100%;
            }

            #account-balance-summary table,
            #account-balance-summary tbody {
                flex-wrap: wrap;
            }

            .table-responsive-transactions {
                overflow-x: auto;
            }

            .pagination-wrap {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <div class="row g-3">
            {{-- LEFT COLUMN: Transaction Form --}}
            <div class="col-lg-9">
                <div class="card border-1 shadow" id="cardBody">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white py-2">
                        <h5 class="text-primary mb-0 flex-shrink-0 d-flex align-items-center">
                            <i class="bi bi-plus-circle me-2"></i>
                            <span>Add Transaction</span>
                        </h5>
                        <div id="account-balance-summary"></div>
                    </div>
                    <div class="card-body py-3">
                        <form method="POST" action="{{ route('admin.transactions.store') }}" id="transactionForm">
                            @csrf

                            <input type="hidden" name="transaction_id" id="transaction_id">
                            <input type="hidden" name="status" value="active">
                            <input type="hidden" name="created_by" value="{{ auth()->id() }}">

                            <div class="mb-3">
                                <label class="form-label">Apply Payment to Sale <span class="text-muted">(optional)</span></label>
                                <select name="sale_id" class="form-select">
                                    <option value="">General account transaction</option>
                                    @foreach ($openSales as $openSale)
                                        <option value="{{ $openSale->id }}">
                                            {{ $openSale->sale_no }} — {{ $openSale->customer->name ?? 'Unknown' }} —
                                            {{ $openSale->currency->code ?? '' }} {{ number_format($openSale->due_amount, 2) }} due
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row g-3">
                                {{-- Row 1: Account Type, Account, Currency --}}
                                <div class="col-md-3">
                                    <label class="form-label">Account Type <span class="text-danger">*</span></label>
                                    <select id="account_type" class="form-select" required>
                                        <option value="">Select Type</option>
                                        <option value="customer">{{ __('ui.customer') }}</option>
                                        <option value="supplier">{{ __('ui.supplier') }}</option>
                                        <option value="agent">Agent</option>
                                        <option value="saraf">{{ __('ui.saraf') }}</option>
                                        <option value="expense">{{ __('ui.expense') }}</option>
                                    </select>
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label">{{ __('ui.account') }} <span class="text-danger">*</span></label>
                                    <select name="account_id" id="account_id" class="form-select select2" required>
                                        <option value="">{{ __('ui.select_account_type_first') }}</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">{{ __('ui.currency') }} <span class="text-danger">*</span></label>
                                    <select name="currency_id" class="form-select" required>
                                        <option value="">{{ __('ui.select_currency') }}</option>
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->id }}">{{ $currency->code }}
                                                ({{ $currency->symbol }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Row 2: Type, Amount, Description, Submit --}}
                                <div class="col-md-2">
                                    <label class="form-label" id="transactionTypeLabel">{{ __('ui.type') }} <span
                                            class="text-danger">*</span></label>
                                    <select name="transaction_type" id="transaction_type" class="form-select" required>
                                        <option value="credit">Credit +</option>
                                        <option value="debit">Debit -</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">{{ __('ui.amount') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" step="0.01" class="form-control"
                                        placeholder="0.00" required>
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label">{{ __('ui.description') }}</label>
                                    <input type="text" name="description" class="form-control"
                                        placeholder="{{ __('ui.optional_description') }}">
                                </div>

                                <div class="col-md-3 d-grid">
                                    <label class="form-label">&nbsp;</label>
                                    @can('create transactions')
                                        <button class="btn btn-primary w-100 save-btn" type="submit">
                                            <i class="bi bi-check-circle me-1"></i>
                                            <span class="btn-text">Save</span>
                                            <span class="spinner-border spinner-border-sm d-none" role="status"
                                                aria-hidden="true"></span>
                                        </button>
                                    @endcan
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: Cash Record --}}
            <div class="col-lg-3">
                <div class="card border-success border-2 shadow-sm rounded-3">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white py-1 px-2">
                        <h6 class="text-success mb-0 d-flex align-items-center p-2">
                            <i class="bi bi-bank me-1 text-success"></i>
                            <span class="ms-1">Cash Record</span>
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="d-flex border-bottom">
                            <select id="currency_id_filter"
                                class="form-select form-select-sm border-0 rounded-0 border-end">
                                @foreach ($currencies as $index => $currency)
                                    <option value="{{ $currency->id }}" {{ $index === 0 ? 'selected' : '' }}>
                                        {{ $currency->code }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="date" id="balance_date"
                                class="form-control form-control-sm border-0 rounded-0"
                                value="{{ \Carbon\Carbon::today()->toDateString() }}">
                        </div>
                        <div id="balancesContainer" class="small"></div>
                    </div>
                </div>
            </div>

            {{-- TRANSACTIONS TABLE --}}
            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-header d-flex align-items-center justify-content-between bg-white p-3 px-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-list-ul fs-5 text-primary me-2"></i>
                            <h5 class="text-secondary mb-0">{{ __('ui.recent_transactions') }}</h5>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-light border" id="btn-toggle-filter">
                                <i class="bi bi-funnel me-1"></i> Filter
                            </button>
                            <button class="btn btn-sm btn-outline-primary" id="export_btn">
                                <i class="bi bi-download"></i> Export
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" id="print_btn">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        {{-- FILTERS --}}
                        <div id="filter-container" class="bg-white p-2 rounded mx-0 border mb-3">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div style="flex: 1.5; min-width: 200px;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white"><i class="bi bi-calendar3"></i></span>
                                        <input type="text" id="date_range" class="form-control form-control-sm"
                                            placeholder="Select Date Range">
                                    </div>
                                </div>

                                <div style="flex: 1; min-width: 150px;">
                                    <select id="account_filter" class="select2 form-select form-select-sm">
                                        <option value="">{{ __('ui.all_accounts') }}</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->name }}
                                                ({{ $account->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div style="flex: 1; min-width: 100px;">
                                    <select id="currency_filter" class="select2 form-select form-select-sm">
                                        <option value="">{{ __('ui.all_currencies') }}</option>
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->id }}">{{ $currency->code }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div style="flex: 1; min-width: 100px;">
                                    <select id="type_filter" class="form-select form-select-sm">
                                        <option value="">{{ __('ui.all_types') }}</option>
                                        <option value="credit">{{ __('ui.credit') }}</option>
                                        <option value="debit">Debit</option>
                                    </select>
                                </div>

                                <div style="width: 80px;">
                                    <select id="page_len_filter" class="form-select form-select-sm">
                                        <option value="15">15</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>

                                <div class="flex-shrink-0">
                                    <button id="applyFilters" class="btn btn-primary btn-sm px-3">
                                        <i class="bi bi-search"></i>
                                    </button>
                                    <button id="resetFilters" class="btn btn-outline-secondary btn-sm px-3">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- TABLE --}}
                        <div class="table-responsive-transactions">
                            <div id="table-loading" class="table-loading d-none">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">{{ __('ui.loading') }}</span>
                                </div>
                            </div>
                            <table class="table table-hover table-bordered align-middle table-transactions"
                                id="transactions-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 3%">#</th>
                                        <th style="width: 12%">{{ __('ui.date') }}</th>
                                        <th style="width: 20%">{{ __('ui.account') }}</th>
                                        <th style="width: 30%">{{ __('ui.description') }}</th>
                                        <th style="width: 18%">{{ __('ui.amount') }}</th>
                                        <th class="text-center" style="width: 12%">{{ __('ui.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="transactions-body">
                                    @include('admin.transactions.partials.table-rows', [
                                        'transactions' => $transactions,
                                    ])
                                </tbody>
                            </table>
                        </div>

                        {{-- PAGINATION --}}
                        @if ($transactions->hasPages())
                            <div class="pagination-wrap">
                                <span class="info-text">
                                    Showing {{ $transactions->firstItem() }} – {{ $transactions->lastItem() }}
                                    of {{ $transactions->total() }} entries
                                </span>
                                {{ $transactions->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/moment/moment-2.29.4.min.js') }}"></script>
    <script src="{{ asset('vendor/daterangepicker/daterangepicker.min.js') }}"></script>

    <script>
        $(document).ready(function() {

            // ─── Select2 Custom Template ───
            function formatAccountOption(option) {
                if (!option.id) return option.text;
                var $option = $(option.element);
                var code = $option.data('code') || '';
                var name = option.text;

                return $('<div class="d-flex align-items-center justify-content-between">' +
                    '<span class="select2-product-name">' + name + '</span>' +
                    (code ?
                        '<span class="badge bg-light text-dark ms-2" style="font-size: 0.65rem; font-weight: 600; color: var(--gray-400);">' +
                        code +
                        '</span>' : '') +
                    '</div>');
            }

            function formatAccountSelection(option) {
                if (!option.id) return option.text;
                return option.text;
            }

            // ─── Initialize Select2 ───
            $('.select2').each(function() {
                var isAccountSelect = $(this).attr('id') === 'account_id' || $(this).attr('id') ===
                    'account_filter';
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: $(this).data('placeholder') || 'Select...',
                    allowClear: true,
                    templateResult: isAccountSelect ? formatAccountOption : undefined,
                    templateSelection: isAccountSelect ? formatAccountSelection : undefined,
                    escapeMarkup: function(m) {
                        return m;
                    }
                });
            });

            // ─── Transaction Type Styling ───
            $('#transaction_type').on('change', function() {
                if ($(this).val() === 'credit') {
                    $(this).css('border', '2px solid var(--primary-color)');
                    $('#cardBody').removeClass('border-danger card-debit').addClass(
                        'border-primary card-credit');
                    $('#transactionTypeLabel').removeClass('text-danger').addClass('text-primary');
                    $('.save-btn').removeClass('btn-danger').addClass('btn-primary');
                } else {
                    $(this).css('border', '2px solid var(--danger-color)');
                    $('#cardBody').removeClass('border-primary card-credit').addClass(
                        'border-danger card-debit');
                    $('#transactionTypeLabel').removeClass('text-primary').addClass('text-danger');
                    $('.save-btn').removeClass('btn-primary').addClass('btn-danger');
                }
            }).trigger('change');

            // ─── Date Range Picker ───
            $('#date_range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                }
            });

            $('#date_range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format(
                    'YYYY-MM-DD'));
                loadTransactions();
            });

            $('#date_range').on('cancel.daterangepicker', function() {
                $(this).val('');
                loadTransactions();
            });

            // ─── Account Type Change → Load Accounts ───
            $('#account_type').on('change', function() {
                const type = $(this).val();
                const $accountSelect = $('#account_id');

                if (!type) {
                    $accountSelect.html('<option value="">{{ __('ui.select_account_type_first') }}</option>');
                    $accountSelect.trigger('change');
                    return;
                }

                $accountSelect.html('<option value="">{{ __('ui.loading') }}</option>');
                $accountSelect.prop('disabled', true);

                $.ajax({
                    url: '{{ route('admin.transactions.get-accounts-by-type') }}',
                    method: 'GET',
                    data: {
                        account_type: type
                    },
                    success: function(res) {
                        const accounts = res.accounts || [];
                        let html = '<option value="">Select Account</option>';
                        accounts.forEach(function(account) {
                            html +=
                                `<option value="${account.id}" data-code="${account.code}">${account.name}</option>`;
                        });
                        $accountSelect.html(html);
                        $accountSelect.prop('disabled', false);
                        $accountSelect.trigger('change');
                    },
                    error: function() {
                        $accountSelect.html('<option value="">Error loading accounts</option>');
                        $accountSelect.prop('disabled', false);
                    }
                });
            });

            // ─── Load Balance Summary on Account Select ───
            $('#account_id').on('change', function() {
                const accountId = $(this).val();
                const summaryBox = $('#account-balance-summary');

                if (!accountId) {
                    summaryBox.html('');
                    return;
                }

                summaryBox.html('<small class="text-muted loading-balance">Loading balance...</small>');

                $.ajax({
                    url: '{{ route('admin.transactions.account-balances') }}',
                    method: 'GET',
                    data: {
                        account_id: accountId
                    },
                    success: function(res) {
                        const balances = res.balances || {};
                        let rows = '';

                        Object.entries(balances).forEach(([currency, data]) => {
                            const balance = parseFloat(data.balance).toLocaleString();
                            const balanceClass = data.balance < 0 ? 'text-danger' :
                                'text-success';
                            const flag =
                                `/assets/flags/${currency.substring(0, 2).toLowerCase()}.svg`;

                            rows += `
                                <tr>
                                    <td class="align-middle">
                                        <img src="${flag}" onerror="this.src='/assets/flags/default.svg'" height="20" class="me-2 border rounded">
                                        <strong>${currency}</strong>
                                    </td>
                                    <td class="text-end align-middle fw-bold ${balanceClass}">${balance}</td>
                                </tr>
                            `;
                        });

                        const html = `
                            <div class="card border border-primary shadow-sm">
                                <div class="card-header bg-primary text-white fw-normal py-1">
                                    💰 Balance
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-borderless mb-0">
                                        <tbody>
                                            ${rows || '<tr><td colspan="2" class="text-muted text-center py-3">No balance found.</td></tr>'}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        `;
                        summaryBox.html(html);
                    },
                    error: function() {
                        summaryBox.html(
                            '<small class="text-danger">Error loading balance</small>');
                    }
                });
            });

            // ─── Toggle Filters ───
            $('#btn-toggle-filter').on('click', function() {
                $('#filter-container').slideToggle(200);
                $(this).toggleClass('btn-primary btn-light');
            });

            function loadTransactions(page = 1) {
                const $loading = $('#table-loading');
                const $body = $('#transactions-body');
                const $pagination = $('.pagination-wrap');

                $loading.removeClass('d-none');
                $body.html('<tr><td colspan="6" class="text-center text-muted py-4">{{ __('ui.loading') }}</td></tr>');
                if ($pagination.length) $pagination.hide();

                const params = {
                    page: page,
                    date_range: $('#date_range').val(),
                    account_id: $('#account_filter').val(),
                    currency_id: $('#currency_filter').val(),
                    transaction_type: $('#type_filter').val(),
                    per_page: $('#page_len_filter').val()
                };

                console.log('Loading transactions with params:', params);

                $.ajax({
                    url: '{{ route('admin.transactions.index') }}',
                    method: 'GET',
                    data: params,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        console.log('Transactions loaded:', response);

                        if (response.html) {
                            $body.html(response.html);
                        } else {
                            $body.html(
                                '<tr><td colspan="6" class="text-center text-danger py-4">Invalid response format</td></tr>'
                            );
                        }

                        if (response.pagination) {
                            if ($pagination.length) {
                                $pagination.show().html(response.pagination);
                            } else {
                                $('.card-body').append(
                                    `<div class="pagination-wrap">${response.pagination}</div>`);
                            }
                        } else if ($pagination.length) {
                            $pagination.hide();
                        }

                        // Re-initialize action buttons
                        initializeActionButtons();
                    },
                    error: function(xhr) {
                        console.error('Load transactions error:', xhr);
                        $body.html(
                            '<tr><td colspan="6" class="text-center text-danger py-4">Failed to load transactions. Please refresh the page.</td></tr>'
                        );
                        if ($pagination.length) $pagination.hide();
                    },
                    complete: function() {
                        $loading.addClass('d-none');
                    }
                });
            }
            // ─── Initialize Action Buttons ───
            function initializeActionButtons() {
                // Edit buttons
                $('.btn-edit').off('click').on('click', function() {
                    const id = $(this).data('id');
                    const accountId = $(this).data('account-id');
                    const currencyId = $(this).data('currency-id');
                    const type = $(this).data('transaction-type');
                    const amount = $(this).data('amount');
                    const description = $(this).data('description');

                    $('#transaction_id').val(id);
                    $('#transaction_type').val(type);
                    $('[name="amount"]').val(amount);
                    $('[name="description"]').val(description);
                    $('[name="currency_id"]').val(currencyId).trigger('change');

                    $.get(`/admin/transactions/fetch/${id}`, function(res) {
                        const account = res.account;
                        $('#account_type').val(account.account_type).trigger('change');
                        setTimeout(() => {
                            $('#account_id').val(account.id).trigger('change');
                        }, 400);
                    });

                    $('html, body').animate({
                        scrollTop: $("#transactionForm").offset().top
                    }, 500);
                });

                // Delete buttons
                $('.btn-delete').off('click').on('click', function() {
                    const id = $(this).data('delete-id');
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'This will permanently delete the transaction.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: `/admin/transactions/${id}`,
                                method: 'DELETE',
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },
                                success: function() {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: 'Transaction deleted.',
                                        icon: 'success',
                                        showConfirmButton: false,
                                        timer: 3000
                                    }).then(() => {
                                        loadTransactions();
                                        if ($('#account_id').val()) {
                                            $('#account_id').trigger('change');
                                        }
                                        fetchBalances();
                                    });
                                },
                                error: function() {
                                    Swal.fire('Error', 'Failed to delete transaction.',
                                        'error');
                                }
                            });
                        }
                    });
                });
            }

            // ─── Apply Filters ───
            $('#applyFilters').on('click', function() {
                loadTransactions(1);
            });

            // ─── Reset Filters ───
            $('#resetFilters').on('click', function() {
                $('#date_range').val('');
                $('#account_filter').val('').trigger('change');
                $('#currency_filter').val('').trigger('change');
                $('#type_filter').val('');
                $('#page_len_filter').val('15');
                loadTransactions(1);
            });

            // ─── Page Length Change ───
            $('#page_len_filter').on('change', function() {
                loadTransactions(1);
            });

            // ─── Pagination Click Handler (delegated) ───
            $(document).on('click', '.pagination-wrap .page-link', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                if (url) {
                    const page = new URL(url, window.location.origin).searchParams.get('page');
                    if (page) {
                        loadTransactions(page);
                    }
                }
            });


            // ─── FIXED: Form Submission with Toastify ───
            $('#transactionForm').on('submit', function(e) {

                const $form = $(this);
                const $submitBtn = $form.find('.save-btn');
                const $btnText = $submitBtn.find('.btn-text');
                const $spinner = $submitBtn.find('.spinner-border');
                const $icon = $submitBtn.find('.bi-check-circle');

                // Validate required fields
                const accountId = $('#account_id').val();
                const currencyId = $('[name="currency_id"]').val();
                const amount = $('[name="amount"]').val();
                const transactionType = $('#transaction_type').val();

                if (!accountId || !currencyId || !amount || !transactionType) {
                    e.preventDefault();
                    showToast('Please fill in all required fields: Account, Currency, Amount, and Type.',
                        'error');
                    return;
                }

                // Use the controller's normal POST/redirect path. This remains
                // reliable even when optional AJAX UI dependencies are unavailable.
                return true;

                // Disable button and show loading state
                $submitBtn.prop('disabled', true);
                $btnText.text('Processing...');
                $spinner.removeClass('d-none');
                $icon.addClass('d-none');

                console.log('Submitting form data:', $form.serialize());

                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: $form.serialize(),
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        console.log('Success response:', response);

                        // Reset button state
                        $submitBtn.prop('disabled', false);
                        $btnText.text('Save');
                        $spinner.addClass('d-none');
                        $icon.removeClass('d-none');

                        if (response.success) {
                            // Show success toast
                            showToast(response.message || 'Transaction saved successfully.',
                                'success');

                            // Reset form after a small delay
                            setTimeout(function() {
                                $form[0].reset();
                                $('#transaction_id').val('');
                                $('#account_id').html(
                                    '<option value="">{{ __('ui.select_account_type_first') }}</option>'
                                );
                                $('#account_id').trigger('change');
                                $('.select2').val(null).trigger('change');
                                $('#transaction_type').trigger('change');

                                // Clear balance summary
                                $('#account-balance-summary').html('');

                                // Reload transactions
                                loadTransactions();

                                // Refresh cash balances
                                fetchBalances();
                            }, 500);
                        } else {
                            showToast(response.message || 'Something went wrong.', 'error');
                        }
                    },
                    error: function(xhr) {
                        console.log('Error response:', xhr);

                        // Reset button state
                        $submitBtn.prop('disabled', false);
                        $btnText.text('Save');
                        $spinner.addClass('d-none');
                        $icon.removeClass('d-none');

                        let errorMessage = 'An error occurred while saving the transaction.';

                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON?.errors;
                            if (errors) {
                                errorMessage = Object.values(errors).flat().join('\n');
                            }
                        } else if (xhr.responseJSON?.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            // Check if response is HTML (redirect)
                            if (xhr.responseText.includes('<!DOCTYPE html>') || xhr.responseText
                                .includes('<html')) {
                                errorMessage =
                                    'Server returned HTML response. Please check if the route is correct.';
                            } else {
                                try {
                                    const json = JSON.parse(xhr.responseText);
                                    if (json.message) errorMessage = json.message;
                                } catch (e) {
                                    // Keep default message
                                }
                            }
                        }

                        showToast(errorMessage, 'error');
                    }
                });
            });

            // ─── Delete with Toastify ───
            function initializeActionButtons() {
                // Edit buttons
                $('.btn-edit').off('click').on('click', function() {
                    const id = $(this).data('id');
                    const accountId = $(this).data('account-id');
                    const currencyId = $(this).data('currency-id');
                    const type = $(this).data('transaction-type');
                    const amount = $(this).data('amount');
                    const description = $(this).data('description');

                    $('#transaction_id').val(id);
                    $('#transaction_type').val(type);
                    $('[name="amount"]').val(amount);
                    $('[name="description"]').val(description);
                    $('[name="currency_id"]').val(currencyId).trigger('change');

                    $.get(`/admin/transactions/fetch/${id}`, function(res) {
                        const account = res.account;
                        $('#account_type').val(account.account_type).trigger('change');
                        setTimeout(() => {
                            $('#account_id').val(account.id).trigger('change');
                        }, 400);
                    });

                    $('html, body').animate({
                        scrollTop: $("#transactionForm").offset().top
                    }, 500);
                });

                // Delete buttons with Toastify confirmation
                $('.btn-delete').off('click').on('click', function() {
                    const id = $(this).data('delete-id');
                    const $btn = $(this);

                    // Show confirmation using Toastify with custom HTML
                    if (confirm('Are you sure you want to delete this transaction?')) {
                        // Disable button and show loading
                        $btn.prop('disabled', true);
                        $btn.html(
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
                        );

                        $.ajax({
                            url: `/admin/transactions/${id}`,
                            method: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                showToast('Transaction deleted successfully.', 'success');

                                // Reload transactions
                                loadTransactions();

                                // Refresh balances
                                if ($('#account_id').val()) {
                                    $('#account_id').trigger('change');
                                }
                                fetchBalances();

                                // Reset button
                                $btn.prop('disabled', false);
                                $btn.html('<i class="bi bi-trash"></i>');
                            },
                            error: function() {
                                showToast('Failed to delete transaction.', 'error');
                                $btn.prop('disabled', false);
                                $btn.html('<i class="bi bi-trash"></i>');
                            }
                        });
                    }
                });
            }

            // ─── Load Transactions (AJAX) ───
            function loadTransactions(page = 1) {
                const $loading = $('#table-loading');
                const $body = $('#transactions-body');
                const $pagination = $('.pagination-wrap');

                $loading.removeClass('d-none');
                $body.html('<tr><td colspan="6" class="text-center text-muted py-4">{{ __('ui.loading') }}</td></tr>');
                if ($pagination.length) $pagination.hide();

                const params = {
                    page: page,
                    date_range: $('#date_range').val(),
                    account_id: $('#account_filter').val(),
                    currency_id: $('#currency_filter').val(),
                    transaction_type: $('#type_filter').val(),
                    per_page: $('#page_len_filter').val()
                };

                console.log('Loading transactions with params:', params);

                $.ajax({
                    url: '{{ route('admin.transactions.index') }}',
                    method: 'GET',
                    data: params,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        console.log('Transactions loaded:', response);

                        if (response.html) {
                            $body.html(response.html);
                        } else {
                            $body.html(
                                '<tr><td colspan="6" class="text-center text-danger py-4">Invalid response format</td></tr>'
                            );
                        }

                        if (response.pagination) {
                            if ($pagination.length) {
                                $pagination.show().html(response.pagination);
                            } else {
                                $('.card-body').append(
                                    `<div class="pagination-wrap">${response.pagination}</div>`);
                            }
                        } else if ($pagination.length) {
                            $pagination.hide();
                        }

                        // Re-initialize action buttons
                        initializeActionButtons();
                    },
                    error: function(xhr) {
                        console.error('Load transactions error:', xhr);
                        $body.html(
                            '<tr><td colspan="6" class="text-center text-danger py-4">Failed to load transactions. Please refresh the page.</td></tr>'
                        );
                        if ($pagination.length) $pagination.hide();
                        showToast('Failed to load transactions. Please refresh the page.', 'error');
                    },
                    complete: function() {
                        $loading.addClass('d-none');
                    }
                });
            }

            // ─── Apply Filters ───
            $('#applyFilters').on('click', function() {
                loadTransactions(1);
                showToast('Filters applied', 'info');
            });

            // ─── Reset Filters ───
            $('#resetFilters').on('click', function() {
                $('#date_range').val('');
                $('#account_filter').val('').trigger('change');
                $('#currency_filter').val('').trigger('change');
                $('#type_filter').val('');
                $('#page_len_filter').val('15');
                loadTransactions(1);
                showToast('Filters reset', 'info');
            });

            // ─── Page Length Change ───
            $('#page_len_filter').on('change', function() {
                loadTransactions(1);
            });

            // ─── Pagination Click Handler ───
            $(document).on('click', '.pagination-wrap .page-link', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                if (url) {
                    const page = new URL(url, window.location.origin).searchParams.get('page');
                    if (page) {
                        loadTransactions(page);
                    }
                }
            });

            // ─── Export ───
            $('#export_btn, #print_btn').on('click', function() {
                const params = new URLSearchParams({
                    account_id: $('#account_filter').val(),
                    currency_id: $('#currency_filter').val(),
                    transaction_type: $('#type_filter').val()
                });

                const url = `{{ route('admin.transactions.export') }}?${params.toString()}`;

                if (this.id === 'export_btn') {
                    window.open(url, '_blank');
                    showToast('Export started', 'info');
                } else {
                    const win = window.open(url, '_blank');
                    if (win) {
                        win.onload = () => win.print();
                        showToast('Print preview opened', 'info');
                    }
                }
            });

            // ─── Toastify Helper (already in layout) ───
            // Make sure showToast is defined globally
            // If not, add this:
            function showToast(message, type = 'success') {
                const colors = {
                    success: 'linear-gradient(135deg, #10b981, #059669)',
                    error: 'linear-gradient(135deg, #ef4444, #dc2626)',
                    warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
                    info: 'linear-gradient(135deg, #3b82f6, #2563eb)'
                };

                Toastify({
                    text: message,
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    stopOnFocus: true,
                    style: {
                        background: colors[type] || colors.success,
                        borderRadius: '8px',
                        boxShadow: '0 8px 32px rgba(0,0,0,0.12)',
                        padding: '12px 20px',
                        fontFamily: 'Inter, sans-serif',
                        fontWeight: '500',
                        fontSize: '14px'
                    },
                    close: true,
                    className: 'toastify-custom'
                }).showToast();
            }
            // ─── Cash Record Balances ───
            function fetchBalances() {
                let currency_id = $('#currency_id_filter').val();
                let date = $('#balance_date').val();

                $.ajax({
                    url: "{{ route('admin.transactions.balances') }}",
                    type: "GET",
                    data: {
                        currency_id: currency_id,
                        date: date
                    },
                    success: function(res) {
                        let container = $('#balancesContainer');
                        container.empty();

                        res.forEach(function(item) {
                            let s = item.currency_symbol;
                            container.append(`
                                <div class="border-bottom p-2">
                                    <div class="fw-bold text-center mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05rem;">
                                        ${item.currency_name} — ${item.date}
                                    </div>
                                    <div class="d-flex justify-content-between py-1">
                                        <span class="text-muted">Starting</span>
                                        <span class="fw-semibold">${s}${item.starting_balance.toLocaleString()}</span>
                                    </div>
                                    <div class="d-flex justify-content-between py-1 text-primary">
                                        <span>Credit (+)</span>
                                        <span class="fw-bold">+ ${s}${item.today_credit.toLocaleString()}</span>
                                    </div>
                                    <div class="d-flex justify-content-between py-1 text-danger">
                                        <span>Debit (-)</span>
                                        <span class="fw-bold">- ${s}${item.today_debit.toLocaleString()}</span>
                                    </div>
                                    <div class="d-flex justify-content-between pt-2 mt-1 border-top fw-bold">
                                        <span>Today Balance</span>
                                        <span>${s}${item.closing_balance.toLocaleString()}</span>
                                    </div>
                                </div>
                            `);
                        });
                    }
                });
            }

            // ─── Export ───
            $('#export_btn, #print_btn').on('click', function() {
                const params = new URLSearchParams({
                    account_id: $('#account_filter').val(),
                    currency_id: $('#currency_filter').val(),
                    transaction_type: $('#type_filter').val()
                });

                const url = `{{ route('admin.transactions.export') }}?${params.toString()}`;

                if (this.id === 'export_btn') {
                    window.open(url, '_blank');
                } else {
                    const win = window.open(url, '_blank');
                    if (win) {
                        win.onload = () => win.print();
                    }
                }
            });

            // ─── Trigger on load and on change ───
            $('#currency_id_filter, #balance_date').on('change', fetchBalances);
            fetchBalances();

            // ─── Initialize action buttons on first load ───
            initializeActionButtons();

        });
    </script>
@endsection
