@extends('layouts.admin.base')

@section('title', __('ui.accounts'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}">
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <style>
        /* DataTables customization to match premium UI */
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
            border-radius: var(--radius-xs);
            border: 1px solid var(--gray-200);
            margin: 0 0.1rem;
            background: white;
            color: var(--gray-600);
            font-weight: 500;
            transition: var(--transition);
        }

        .dataTables_paginate .paginate_button:hover {
            background: var(--primary-bg);
            border-color: var(--primary);
            color: var(--primary);
        }

        .dataTables_paginate .paginate_button.current {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .dataTables_paginate .paginate_button.disabled {
            opacity: 0.4;
            pointer-events: none;
        }

        /* Avatar circle - matching product avatar style */
        .avatar-circle {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            text-align: center;
            font-size: 0.75rem;
            flex-shrink: 0;
            background: linear-gradient(135deg, var(--gray-100), var(--gray-50));
            border: 2px solid var(--gray-200);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        tr:hover .avatar-circle {
            border-color: var(--primary-light);
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.15);
        }

        /* Toast styling */
        .toast-container {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 1055;
        }

        .toast {
            border: none;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-lg);
            padding: 0.75rem 1rem;
        }

        .toast .toast-body {
            font-weight: 500;
            font-size: 0.875rem;
        }

        /* Account filters - matching search style */
        .account-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            margin-bottom: 1rem;
        }

        .account-filters .input-group {
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 2px solid var(--gray-200);
            background: white;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            flex: 1 1 180px;
            min-width: 140px;
        }

        .account-filters .input-group:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 6px rgba(79, 70, 229, 0.08), 0 4px 16px rgba(79, 70, 229, 0.06);
            transform: scale(1.01);
        }

        .account-filters .input-group-text {
            background: white;
            border: none;
            padding-right: 0;
            color: var(--gray-400);
            font-size: 0.9rem;
        }

        .account-filters .form-control,
        .account-filters .form-select {
            border: none;
            padding: 0.4rem 0.5rem;
            font-size: 0.8rem;
            background: transparent;
            box-shadow: none;
        }

        .account-filters .form-control:focus,
        .account-filters .form-select:focus {
            box-shadow: none;
            border-color: transparent;
        }

        .account-filters .btn-filter-search {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.35rem 1rem;
            font-weight: 700;
            font-size: 0.75rem;
            transition: all 0.4s ease;
            white-space: nowrap;
            border-radius: 0;
        }

        .account-filters .btn-filter-search:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.3);
        }

        .account-filters .btn-create {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.4rem 1.25rem;
            border-radius: var(--radius-xs);
            font-weight: 700;
            font-size: 0.8rem;
            transition: all 0.4s ease;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .account-filters .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35);
            color: white;
        }

        /* Account cells */
        .account-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .account-cell .info .name {
            font-weight: 700;
            color: var(--gray-800);
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        tr:hover .account-cell .info .name {
            color: var(--primary);
        }

        .account-cell .info .email {
            font-size: 0.7rem;
            color: var(--gray-400);
        }

        .account-type-badge {
            background: linear-gradient(135deg, var(--gray-100), var(--gray-50));
            color: var(--gray-600);
            padding: 0.15rem 0.7rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.65rem;
            border: 1px solid var(--gray-200);
            transition: border-color 0.3s ease, color 0.3s ease;
        }

        tr:hover .account-type-badge {
            border-color: var(--primary-light);
            color: var(--primary);
        }

        .account-balance {
            font-weight: 700;
            font-size: 0.875rem;
        }

        .account-balance.positive {
            color: var(--success);
        }

        .account-balance.negative {
            color: var(--danger);
        }

        .account-balance .currency-sm {
            font-size: 0.6rem;
            font-weight: 500;
            color: var(--gray-400);
            margin-right: 1px;
        }

        .account-code {
            font-family: monospace;
            font-size: 0.75rem;
            color: var(--gray-500);
            background: var(--gray-50);
            padding: 0.15rem 0.5rem;
            border-radius: var(--radius-xs);
            border: 1px solid var(--gray-200);
        }

        .account-details {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .action-buttons {
            display: flex;
            gap: 0.3rem;
            justify-content: flex-end;
        }

        .action-buttons .btn {
            padding: 0.2rem 0.4rem;
            font-size: 0.8rem;
            border-radius: var(--radius-xs);
        }

        /* Offcanvas full width fix */
        .offcanvas {
            width: 600px !important;
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

        {{-- ============================================================
        PAGE HEADER
        ============================================================ --}}
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-people me-2"></i>
                        {{ __('ui.accounts') }} <span class="accent">{{ __('ui.ledger') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-wallet2 me-1"></i>
                        Manage customer accounts, balances, and financial tracking
                    </p>
                </div>
            </div>
        </div>

        {{-- ============================================================
        STATS CARDS
        ============================================================ --}}
        @php
            $stats = [
                [
                    'label' => 'Total Accounts',
                    'id' => 'total_accounts',
                    'icon' => 'bi-people-fill',
                    'accent' => 'purple',
                    'iconClass' => 'purple',
                ],
                [
                    'label' => 'Total Credit',
                    'id' => 'total_credit',
                    'icon' => 'bi-cash-coin',
                    'accent' => 'green',
                    'iconClass' => 'green',
                ],
                [
                    'label' => 'Total Debit',
                    'id' => 'total_debit',
                    'icon' => 'bi-cash-stack',
                    'accent' => 'red',
                    'iconClass' => 'red',
                ],
                [
                    'label' => 'Balance',
                    'id' => 'balance',
                    'icon' => 'bi-wallet',
                    'accent' => 'blue',
                    'iconClass' => 'blue',
                ],
            ];
        @endphp
        <div class="stats-grid">
            @foreach ($stats as $stat)
                <div class="stat-card {{ $stat['accent'] }}-accent">
                    <div class="stat-top">
                        <div class="stat-icon {{ $stat['iconClass'] }}">
                            <i class="bi {{ $stat['icon'] }}"></i>
                        </div>
                        <div class="stat-value" id="{{ $stat['id'] }}">0</div>
                    </div>
                    <div class="stat-label">{{ $stat['label'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- ============================================================
        MAIN TABLE
        ============================================================ --}}
        <div class="table-card">
            <div class="card-header-custom">
                <h5>
                    <i class="bi bi-people"></i> Account Directory
                    <span class="header-badge">
                        <i class="bi bi-database me-1"></i> <span id="total-accounts-display">0</span> accounts
                    </span>
                </h5>
                <div class="d-flex gap-2">
                    <span class="header-badge" style="background: var(--success-bg); color: var(--success);">
                        <i class="bi bi-check-circle me-1"></i> {{ __('ui.active') }}
                    </span>
                    <span class="header-badge" style="background: var(--danger-bg); color: var(--danger);">
                        <i class="bi bi-exclamation-circle me-1"></i> {{ __('ui.inactive') }}
                    </span>
                </div>
            </div>

            <div class="p-3">
                {{-- Filters --}}
                <div class="account-filters">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" id="search" class="form-control border-start-0"
                            placeholder="{{ __('ui.search_accounts') }}" style="border-left: none; box-shadow: none;">
                    </div>

                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-diagram-3 text-muted"></i>
                        </span>
                        <select id="account_type" class="form-select border-start-0"
                            style="border-left: none; box-shadow: none;">
                            <option value="">{{ __('ui.all_types') }}</option>
                            @foreach ($accountCategories as $item)
                                <option value="{{ $item->id }}">{{ Str::plural($item->name) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-currency-dollar text-muted"></i>
                        </span>
                        <select id="currency_id" class="form-select border-start-0"
                            style="border-left: none; box-shadow: none;">
                            <option value="">{{ __('ui.all_currencies') }}</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}">{{ $currency->code }} ({{ $currency->symbol }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-filter-circle text-muted"></i>
                        </span>
                        <select id="balance_filter" class="form-select border-start-0"
                            style="border-left: none; box-shadow: none;">
                            <option value="">All Balances</option>
                            <option value="creditors">Creditors (largest first)</option>
                            <option value="debtors">Debtors (most negative)</option>
                            <option value="mixed">Mixed (+ & –)</option>
                        </select>
                    </div>

                    <div class="input-group" style="flex: 0 0 100px;">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-list-nested text-muted"></i>
                        </span>
                        <select id="per-page" class="form-select border-start-0"
                            style="border-left: none; box-shadow: none;">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    @can('create customers')
                        <button class="btn-create" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddUser">
                            <i class="bi bi-plus-circle"></i> Create Customer
                        </button>
                    @endcan
                </div>

                {{-- Table --}}
                <div class="table-responsive-custom">
                    <table class="table-ledger" id="yajra-datatable">
                        <thead>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>{{ __('ui.customer_name') }}</th>
                                <th style="width: 120px;">{{ __('ui.code') }}</th>
                                <th style="width: 120px;">{{ __('ui.type') }}</th>
                                <th>{{ __('ui.details') }}</th>
                                <th style="width: 140px;" class="text-end">{{ __('ui.balance') }}</th>
                                <th style="width: 160px;" class="text-end">{{ __('ui.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
    MODALS & OFF-CANVAS
    ============================================================ --}}
    @include('admin.accounts.partials.create-form')
    @include('admin.accounts.partials.create-user-modal')

    {{-- Fullscreen Modal --}}
    <div class="modal fade" id="accountPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-file-text me-2"></i> {{ __('ui.account_summary') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" id="accountPreviewContent" style="background: var(--gray-50);"></div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    @include('admin.accounts.partials.scripts')
    <script>
        $(document).ready(function() {
            // Show toasts
            const toastElements = document.querySelectorAll('.toast');
            toastElements.forEach(toast => new bootstrap.Toast(toast).show());

            // Update total accounts display
            function updateTotalAccounts(count) {
                $('#total-accounts-display').text(count);
            }

            // DataTable instance - USING CORRECT ROUTE NAME
            var table = $('#yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('fetch') }}", // CORRECT: 'fetch' (admin/fetch) not 'fetch-accounts'
                    data: function(d) {
                        d.search_term = $('#search').val();
                        d.account_type_id = $('#account_type').val();
                        d.currency_id = $('#currency_id').val();
                        d.balance_filter = $('#balance_filter').val();
                        d.per_page = $('#per-page').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name_with_profile',
                        name: 'name'
                    },
                    {
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'account_type_with_icon',
                        name: 'account_type_with_icon',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'contact_details',
                        name: 'contact_details',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'balance',
                        name: 'balance',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    }
                ],
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                language: {
                    info: "Showing _START_ to _END_ of _TOTAL_ accounts",
                    infoEmpty: "Showing 0 to 0 of 0 accounts",
                    infoFiltered: "(filtered from _MAX_ total accounts)",
                    emptyTable: "No accounts found",
                    zeroRecords: "No matching accounts found"
                },
                drawCallback: function(settings) {
                    var info = this.api().page.info();
                    updateTotalAccounts(info.recordsTotal);

                    // Update stats
                    updateStats();
                }
            });

            // Reload on filter change
            $('#search, #account_type, #currency_id, #balance_filter, #per-page').on('change keyup', function() {
                table.ajax.reload();
            });

            // Debounce search
            let searchTimeout;
            $('#search').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    table.ajax.reload();
                }, 500);
            });

            // Update stats function - USING CORRECT ROUTE NAME
            function updateStats() {
                $.ajax({
                    url: "{{ route('admin.accounts.update-stats') }}", // CORRECT: 'update-stats' not 'update-accounts-stats'
                    data: {
                        currency_id: $('#currency_id').val(),
                        account_category_id: $('#account_type').val()
                    },
                    success: function(response) {
                        $('#total_accounts').text(response.total_accounts || 0);
                        $('#total_credit').text(response.total_credit || '0.00');
                        $('#total_debit').text(response.total_debit || '0.00');
                        $('#balance').text(response.balance || '0.00');
                    }
                });
            }

            // Initial stats load
            updateStats();

            // Edit account button handler
            $(document).on('click', '.btn-edit-account', function() {
                var id = $(this).data('id');
                $.get('{{ url('admin/accounts') }}/' + id + '/edit', function(data) {
                    $('#edit_account_id').val(data.id);
                    $('#edit_name').val(data.name);
                    $('#edit_code').val(data.code);
                    $('#edit_contact').val(data.contact);
                    $('#edit_address').val(data.address);
                    $('#edit_company').val(data.company);
                    $('#edit_email').val(data.email);
                    $('#editModal').modal('show');
                });
            });

            // Delete account handler
            $(document).on('click', '.btn-delete-account', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                if (confirm('Are you sure you want to delete customer: ' + name + '?')) {
                    $.ajax({
                        url: '{{ url('admin/accounts') }}/' + id,
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                table.ajax.reload();
                                updateStats();
                                toastr.success(response.message);
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function() {
                            toastr.error('Error deleting customer');
                        }
                    });
                }
            });

            // Preview account handler
            $(document).on('click', '.btn-preview-account', function() {
                var id = $(this).data('id');
                $('#accountPreviewContent').html(
                    '<div class="d-flex justify-content-center align-items-center" style="height: 200px;"><div class="spinner-border text-primary" role="status"></div></div>'
                );
                $('#accountPreviewModal').modal('show');
                $.get('{{ url('admin/accounts') }}/' + id + '/print', function(data) {
                    $('#accountPreviewContent').html(data);
                });
            });

            // Create user handler
            $(document).on('click', '.btn-create-user', function() {
                var accountId = $(this).data('id');
                $('#create_user_account_id').val(accountId);

                // Fetch account info
                $.get('{{ url('admin/accounts') }}/' + accountId + '/info', function(data) {
                    $('#create_user_account_name').text(data.name);
                    $('#create_user_account_code').text(data.code);
                    $('#create_user_username').val(data.code);
                });

                $('#createUserModal').modal('show');
            });

            // Create user form submission
            $('#createUserForm').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    url: "{{ route('admin.accounts.create-user') }}",
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#createUserModal').modal('hide');
                            toastr.success(response.message);
                            table.ajax.reload();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        var errors = xhr.responseJSON.errors;
                        if (errors) {
                            var errorMsg = '';
                            $.each(errors, function(key, value) {
                                errorMsg += value[0] + '\n';
                            });
                            toastr.error(errorMsg);
                        } else {
                            toastr.error('Error creating user');
                        }
                    }
                });
            });

            // Create account form submission
            $('#createAccountForm').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    url: "{{ route('admin.accounts.store') }}",
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#offcanvasAddUser').offcanvas('hide');
                            toastr.success(response.message);
                            table.ajax.reload();
                            updateStats();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        var errors = xhr.responseJSON.errors;
                        if (errors) {
                            var errorMsg = '';
                            $.each(errors, function(key, value) {
                                errorMsg += value[0] + '\n';
                            });
                            toastr.error(errorMsg);
                        } else {
                            toastr.error('Error creating account');
                        }
                    }
                });
            });

            // Toastr configuration if not already set
            if (typeof toastr !== 'undefined') {
                toastr.options = {
                    "closeButton": true,
                    "progressBar": true,
                    "positionClass": "toast-top-right",
                    "timeOut": "5000",
                    "extendedTimeOut": "1000"
                };
            }
        });
    </script>
@endsection
