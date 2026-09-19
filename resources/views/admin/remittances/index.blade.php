@extends('layouts.admin.base')

@section('title', __('ui.remittances'))
@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">

    <style>
        #balanceDisplay {
            transition: all 0.2s ease-in-out;
            border-radius: 6px;
            font-size: 0.95rem;
        }
    </style>
@endsection

@section('content')
    <div class="card border-danger-subtle mb-4 border shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <h5 class="text-danger mb-0">
                <i class="bi bi-send-exclamation me-1"></i> Remittance Request
            </h5>
            <button class="btn btn-sm btn-outline-danger" id="toggleFormBtn">
                <i class="bi bi-plus-circle me-1"></i> Add Remittance
            </button>
        </div>

        <div class="card-body d-none pt-4" id="remittanceFormCard">
            <div id="balanceDisplay" class="d-none">
                <div class="card mb-3 border-0 shadow-sm" style="background: linear-gradient(135deg, #f0f4ff, #e0ecff); border-left: 6px solid #0d6efd;">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex justify-content-center align-items-center shadow-sm" style="width: 50px; height: 50px; background-color: #0d6efd1a;">
                                <img id="currencyFlagImg" src="/assets/flags/us.svg" alt="Flag" style="width: 30px; height: 22px; border-radius: 4px;">
                            </div>
                            <div>
                                <div class="fw-semibold text-muted small text-uppercase">{{ __('ui.available_balance') }}</div>
                                <div id="balanceAmount" class="fs-5 fw-bold text-primary">--</div>
                            </div>
                        </div>
                        <i class="bi bi-graph-up-arrow fs-4 text-primary d-none d-md-block opacity-100"></i>
                    </div>
                </div>
            </div>

            @include('admin.remittances.form')
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <h5 class="mb-0">Remittance Records</h5>
            <div class="d-flex flex-column flex-md-row gap-3">
                <!-- Available Balance Card -->
                <div class="d-flex align-items-center flex-row rounded p-3 text-white shadow-sm" style="background: linear-gradient(135deg, #4300c8, #000c0c); min-width: 240px;">
                    <div class="d-flex justify-content-center align-items-center rounded-circle me-3 bg-white bg-opacity-25" style="width: 45px; height: 45px;">
                        <i class="bi bi-people-fill fs-4 text-dark"></i>
                    </div>
                    <div>
                        <div class="small">Customers Balance</div>
                        <div id="customersBalance" class="fw-bold fs-5">--</div>
                    </div>
                </div>
                <div class="d-flex align-items-center flex-row rounded p-3 text-white shadow-sm" style="background: linear-gradient(135deg, #c86e00, #b0026a); min-width: 240px;">
                    <div class="d-flex justify-content-center align-items-center rounded-circle me-3 bg-white bg-opacity-25" style="width: 45px; height: 45px;">
                        <i class="bi bi-safe-fill fs-4 text-dark"></i>
                    </div>
                    <div>
                        <div class="small">Safe Balance</div>
                        <div id="safeBalance" class="fw-bold fs-5">--</div>
                    </div>
                </div>
                <div class="d-flex align-items-center flex-row rounded p-3 text-white shadow-sm" style="background: linear-gradient(135deg, #00c896, #02aab0); min-width: 240px;">
                    <div class="d-flex justify-content-center align-items-center rounded-circle me-3 bg-white bg-opacity-25" style="width: 45px; height: 45px;">
                        <i class="bi bi-wallet-fill fs-4 text-dark"></i>
                    </div>
                    <div>
                        <div class="small">{{ __('ui.available_balance') }}</div>
                        <div id="availableBalance" class="fw-bold fs-5">--</div>
                    </div>
                </div>

                <!-- Pending Remittance Card -->
                <div class="d-flex align-items-center flex-row rounded p-3 text-white shadow-sm" style="background: linear-gradient(135deg, #ff6a00, #ee0979); min-width: 240px;">
                    <div class="d-flex justify-content-center align-items-center rounded-circle me-3 bg-white bg-opacity-25" style="width: 45px; height: 45px;">
                        <i class="bi bi-clock-fill fs-4 text-dark"></i>
                    </div>
                    <div>
                        <div class="small">Pending Remittances</div>
                        <div id="pendingRemittance" class="fw-bold fs-5">--</div>
                    </div>
                </div>
            </div>

        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <select id="filterCustomer" class="select2 form-select" data-placeholder="{{ __('ui.filter_customer') }}">
                        <option value="">{{ __('ui.all_customers') }}</option>
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} - {{ $acc->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterCurrency" class="select2 form-select" data-placeholder="{{ __('ui.filter_currency') }}">
                        <option value="">{{ __('ui.all_currencies') }}</option>
                        @foreach ($currencies as $cur)
                            <option value="{{ $cur->id }}">{{ $cur->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterStatus" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending">{{ __('ui.pending') }}</option>
                        <option value="processed">{{ __('ui.processed') }}</option>
                    </select>
                </div>
            </div>

            <table class="table-bordered table-hover table text-center" id="remittance-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 10%;">{{ __('ui.customer') }}</th>
                        <th style="width: 10%;">{{ __('ui.amount') }}</th>
                        <th style="width: 10%;">Bank NAME</th>
                        <th style="width: 10%;">Bank Holder</th>
                        <th style="width: 10%;">Bank Account</th>
                        <th style="width: 10%;">{{ __('ui.bank_address') }}</th>
                        <th style="width: 10%;">{{ __('ui.phone_number') }}</th>
                        <th style="width: 10%;">{{ __('ui.status') }}</th>
                        <th style="width: 10%;">{{ __('ui.date') }}</th>
                        <th style="width: 10%; min-width: 100px; max-width: 100px;">{{ __('ui.action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

@endsection

@section('js')
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(function() {
            $('.select2').select2({
                width: '100%'
            });

            const table = $('#remittance-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.remittances.data') }}',
                    data: function(d) {
                        d.customer_id = $('#filterCustomer').val();
                        d.currency_id = $('#filterCurrency').val();
                        d.status = $('#filterStatus').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center fw-bold'
                    },
                    {
                        data: 'account',
                        name: 'account.name',
                        render: data => `<span class="fw-semibold text-primary">${data}</span>`
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        render: (data, type, row) =>
                            `<span class="text-success fw-semibold">${parseFloat(data).toLocaleString(undefined, { minimumFractionDigits: 0 })} ${row.currency_symbol}</span>`,
                        className: 'text-end'
                    },
                    {
                        data: null,
                        name: 'bank_name',
                        render: function(data, type, row) {
                            const bankInfo = `${row.bank_name}`;

                            return `
                                <div class="bank-info-copy" data-info="${bankInfo.replace(/"/g, '&quot;')}" style="cursor: pointer;" title="{{ __('ui.click_copy') }}">
                                    <div><i class="bi bi-bank2 me-1 text-muted"></i> ${row.bank_name}</div>
                                </div>
                            `;
                        }
                    },
                    {
                        data: null,
                        name: 'bank_holder',
                        render: function(data, type, row) {
                            const bankInfo = `${row.account_holder}`;

                            return `
                                <div class="bank-info-copy" data-info="${bankInfo.replace(/"/g, '&quot;')}" style="cursor: pointer;" title="{{ __('ui.click_copy') }}">
                                    <div><i class="bi bi-person-fill me-1 text-muted"></i> ${row.account_holder}</div>
                                </div>
                            `;
                        }
                    },
                    {
                        data: null,
                        name: 'bank_account',
                        render: function(data, type, row) {
                            const bankInfo = `${row.bank_account_number}`;

                            return `
                                <div class="bank-info-copy" data-info="${bankInfo.replace(/"/g, '&quot;')}" style="cursor: pointer;" title="{{ __('ui.click_copy') }}">
                                    <div>${row.bank_account_number}</div>
                                </div>
                            `;
                        }
                    },
                    {
                        data: null,
                        name: 'bank_address',
                        render: function(data, type, row) {
                            const bankInfo = `${row.bank_address}`;

                            return `
                                <div class="bank-info-copy" data-info="${bankInfo.replace(/"/g, '&quot;')}" style="cursor: pointer;" title="{{ __('ui.click_copy') }}">
                                    <div><i class="bi bi-building me-1 text-muted"></i> ${row.bank_address}</div>
                                </div>
                            `;
                        }
                    },

                    {
                        data: null,
                        name: 'phone_number',
                        render: function(data, type, row) {
                            const bankInfo = `+${row.phone_number}`;

                            return `
                                <div class="bank-info-copy" data-info="${bankInfo.replace(/"/g, '&quot;')}" style="cursor: pointer;" title="{{ __('ui.click_copy') }}">
                                    <div><i class="bi bi-phone-fill me-1 text-muted"></i> +${row.phone_number}</div>
                                </div>
                            `;
                        }
                    },

                    {
                        data: 'status',
                        name: 'status',
                        render: data => {
                            const color = data === 'processed' ? 'success' : (data === 'pending' ? 'warning' : 'secondary');
                            return `<span class="badge bg-${color} text-uppercase px-3 py-1">${data}</span>`;
                        },
                        className: 'text-center'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data) {
                            const date = new Date(data);
                            return `
                                <div class="d-inline-flex flex-column justify-content-center align-items-start gap-1">
                                    <span class="fw-bold">${date.toLocaleDateString('en-CA', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                                    <span class="text-muted small">${date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })}</span>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ]
            });

            $('#filterCustomer, #filterCurrency, #filterStatus').on('change', function() {
                table.ajax.reload();
                loadRemittanceSummary();
            });

            $('#remittanceForm').on('submit', function(e) {
                e.preventDefault();

                const isUpdate = $('#remittance_id').val() !== '';
                const remittanceId = $('#remittance_id').val();
                const actionUrl = isUpdate ?
                    `/admin/remittances/${remittanceId}/update` :
                    '{{ route('admin.remittances.store') }}';

                $.post(actionUrl, $(this).serialize())
                    .done(res => {
                        Swal.fire({
                            icon: 'success',
                            text: res.message,
                            timer: 1000,
                            showConfirmButton: false
                        });
                        $('#remittance-table').DataTable().ajax.reload();
                        loadRemittanceSummary();
                        $('#remittanceFormCard').addClass('d-none');
                        $('#remittanceForm')[0].reset();
                        $('#remittance_id').val(''); // clear ID
                        $('.select2').val(null).trigger('change');
                    })
                    .fail(err => {
                        Swal.fire('Error', err.responseJSON?.message || 'Failed to save.', 'error');
                    });
            });

            $('#toggleFormBtn').on('click', () => {
                $('#remittanceFormCard').toggleClass('d-none');
                $('#remittanceForm')[0].reset();
                $('#remittance_id').val('');
                $('.select2').val(null).trigger('change');
            });


            function loadRemittanceSummary() {
                $.get('{{ route('admin.remittances.summary') }}', function(res) {
                    $('#customersBalance').text(`${res.customers_balance} ${res.currency}`);
                    $('#safeBalance').text(`${res.safe_balance} ${res.currency}`);
                    $('#availableBalance').text(`${res.available_balance} ${res.currency}`);
                    $('#pendingRemittance').text(`${res.pending} ${res.currency}`);
                });
            }

            loadRemittanceSummary(); // initial load


            // Copy bank info to clipboard on click
            $(document).on('click', '.bank-info-copy', function() {
                const text = $(this).data('info');
                navigator.clipboard.writeText(text).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Copied!',
                        text: 'Bank information copied to clipboard.',
                        timer: 1000,
                        showConfirmButton: false
                    });
                }).catch(() => {
                    Swal.fire('Error', 'Failed to copy to clipboard.', 'error');
                });
            });


            $('[name="account_id"], [name="currency_id"]').on('change', function() {
                const accountId = $('[name="account_id"]').val();
                const currencyId = $('[name="currency_id"]').val();

                if (accountId && currencyId) {
                    $.get(`/admin/accounts/${accountId}/balance/${currencyId}`, function(res) {
                        const color = parseFloat(res.raw_amount) >= 0 ? 'text-primary' : 'text-danger';
                        const text = `${res.amount} ${res.currency || ''}`;

                        $('#balanceAmount')
                            .text(text)
                            .removeClass('text-primary text-danger')
                            .addClass(color);

                        // Use first two letters of currency code for flag filename
                        const flagCode = (res.currency || '').substring(0, 2).toLowerCase();
                        $('#currencyFlagImg').attr('src', `/assets/flags/${flagCode}.svg`);

                        $('#balanceDisplay').removeClass('d-none');
                    });
                } else {
                    $('#balanceDisplay').addClass('d-none');
                }
            });


            $(document).on('click', '.btn-approve', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Approve Remittance?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, approve',
                }).then(result => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Upload Receipt (optional)',
                            html: `
            <input type="file" id="receiptFile" class="form-control" accept="image/*" />
            <small class="text-muted d-block mt-2">You can approve without uploading a receipt.</small>
        `,
                            showCancelButton: true,
                            confirmButtonText: 'Approve',
                            cancelButtonText: 'Cancel',
                            focusConfirm: false,
                            preConfirm: () => {
                                const fileInput = document.getElementById('receiptFile');
                                return fileInput.files[0] || null;
                            }
                        }).then((uploadResult) => {
                            if (uploadResult.isConfirmed) {
                                const formData = new FormData();
                                formData.append('_token', '{{ csrf_token() }}');
                                if (uploadResult.value) {
                                    formData.append('receipt_file', uploadResult.value);
                                }

                                $.ajax({
                                    url: `/admin/remittances/${id}/approve`,
                                    method: 'POST',
                                    data: formData,
                                    processData: false,
                                    contentType: false,
                                    success: res => {
                                        Swal.fire({
                                            icon: 'success',
                                            text: res.message,
                                            timer: 1000,
                                            showConfirmButton: false
                                        });
                                        location.reload();
                                    },
                                    error: err => {
                                        Swal.fire('Error', err.responseJSON?.message || 'Failed to approve.', 'error');
                                    }
                                });
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.btn-edit', function() {
                const id = $(this).data('id');
                $.get(`/admin/remittances/${id}/edit`, function(res) {
                    const r = res.remittance;
                    $('#remittance_id').val(r.id);
                    $('[name="account_id"]').val(r.account_id).trigger('change');
                    $('[name="currency_id"]').val(r.currency_id).trigger('change');
                    $('[name="amount"]').val(r.amount);
                    $('[name="bank_name"]').val(r.bank_name);
                    $('[name="account_holder"]').val(r.account_holder);
                    $('[name="bank_account_number"]').val(r.bank_account_number);
                    $('[name="bank_address"]').val(r.bank_address);
                    $('[name="phone_number"]').val(r.phone_number);
                    $('[name="note"]').val(r.note);

                    $('#remittanceFormCard').removeClass('d-none');
                    $('html, body').animate({
                        scrollTop: $('#remittanceFormCard').offset().top
                    }, 300);
                });
            });

            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This remittance will be deleted!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!'
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/remittances/${id}`,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: res => {
                                table.ajax.reload();
                                Swal.fire('Deleted!', res.message, 'success');
                            },
                            error: () => Swal.fire('Error', 'Failed to delete.', 'error')
                        });
                    }
                });
            });
        });
    </script>
@endsection
