@extends('layouts.admin.base')

@section('title', __('ui.journal'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/datatables/buttons/buttons.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}" />
    <script src="{{ asset('vendor/chartjs/chart-4.5.1.min.js') }}"></script>
<style>
    .glass-card {
        border-radius: 16px;
        backdrop-filter: blur(8px);
        transition: all 0.3s ease;
        overflow: hidden;
        position: relative;
    }

    .glass-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.25);
    }

    .custom-glow-card::before {
        content: "";
        position: absolute;
        top: -40%;
        left: -30%;
        width: 180%;
        height: 180%;
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
        animation: shineRotate 5s linear infinite;
        z-index: 0;
    }

    .custom-glow-card > .card-body {
        position: relative;
        z-index: 1;
    }

    @keyframes shineRotate {
        0%   { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>


@endsection

@section('content') <div class="card border-0 shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="text-primary mb-0"><i class="bi bi-journal-text me-2"></i>Journal Entries</h5>
            <div> <button class="btn btn-sm btn-outline-primary" id="export_btn"><i class="bi bi-download me-1"></i> Export</button> <button class="btn btn-sm btn-outline-secondary"
                    id="print_btn"><i class="bi bi-printer me-1"></i> Print</button> </div>
        </div>
        <div class="card-body">

            <div id="currency_nets" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-3"></div>
            <div id="journal_summary" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-4"></div>


            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">{{ __('ui.date_range') }}</label>
                    <input type="text" id="date_range" class="form-control" placeholder="{{ __('ui.select_date_range') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">👤 Account</label>
                    <select id="account_filter" class="select2 form-select">
                        <option value="">{{ __('ui.all_accounts') }}</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}">[{{ $account->code }}] - {{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('ui.currency_label') }}</label>
                    <select id="currency_filter" class="select2 form-select">
                        <option value="">{{ __('ui.all_currencies') }}</option>
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->id }}">{{ $currency->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">📌 Type</label>
                    <select id="type_filter" class="form-select">
                        <option value="">{{ __('ui.all') }}</option>
                        <option value="credit">{{ __('ui.credit') }}</option>
                        <option value="debit">Debit</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-hover table-bordered table align-middle" id="journal-table">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th style="max-width: 80px;">{{ __('ui.date') }}</th>
                            <th>{{ __('ui.account') }}</th>
                            <th style="max-width: 400px; min-width: 400px;">Note</th>
                            <th style="max-width: 60px;">{{ __('ui.type') }}</th>
                            <th style="max-width: 60px;">{{ __('ui.operation') }}</th>
                            <th style="max-width: 200px;">{{ __('ui.amount') }}</th>
                            <th class="text-center" style="max-width: 100px;">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/buttons/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/buttons/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/buttons/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('vendor/moment/moment-2.29.4.min.js') }}"></script>
    <script src="{{ asset('vendor/daterangepicker/daterangepicker.min.js') }}"></script>

    <script>
        $(function() {
            $('.select2').select2({
                width: '100%'
            });

            $('#date_range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                }
            });

            $('#date_range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                table.draw();
                loadSummary();
            }).on('cancel.daterangepicker', function() {
                $(this).val('');
                table.draw();
                loadSummary();
            });

            const table = $('#journal-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [
                    [1, 'desc']
                ],
                ajax: {
                    url: '{{ route('admin.journal.data') }}',
                    data: function(d) {
                        d.date_range = $('#date_range').val();
                        d.account_id = $('#account_filter').val();
                        d.currency_id = $('#currency_filter').val();
                        d.transaction_type = $('#type_filter').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data) {
                            const date = new Date(data);
                            return `
                                ${date.toLocaleString('en-CA', { year: 'numeric', month: '2-digit', day: '2-digit' })}
                                <br>
                                <span class="badge rounded-pill bg-light text-dark">
                                    ${new Intl.DateTimeFormat('en-US', { hour: 'numeric', minute: 'numeric', hour12: true }).format(date)}
                                </span>
                            `;
                        }
                    },
                    {
                        data: 'account',
                        name: 'account.code',
                        render: (data, type, row) => `<span class="fw-bold text-primary">[${row.account.code}] - ${row.account_name}</span>`
                    },
                    {
                        data: 'note',
                        name: 'note'
                    },
                    {
                        data: 'transaction_type',
                        name: 'transaction_type',
                        render: function(data) {
                            const classList = data === 'credit' ? 'badge rounded-pill bg-success' : 'badge rounded-pill bg-danger';
                            const text = data.charAt(0).toUpperCase() + data.slice(1);
                            return `<span class="${classList}">${text}</span>`;
                        }
                    },
                    {
                        data: 'type',
                        name: 'type',
                        render: function(data) {
                            const classList = {
                                journal: 'badge rounded-pill bg-primary',
                                exchange: 'badge rounded-pill bg-info',
                                'exchange-purchase': 'badge rounded-pill bg-warning',
                                remittance: 'badge rounded-pill bg-secondary'
                            }[data] || 'badge rounded-pill bg-dark';
                            const text = data.charAt(0).toUpperCase() + data.slice(1);
                            return `<span class="${classList}">${text}</span>`;
                        }
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        render: (data, type, row) => `<strong>${parseFloat(data).toLocaleString()} ${row.currency_symbol}</strong>`
                    },
                    {
                        data: 'id',
                        name: 'id',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(id, type, row) {
                            const whatsappText = encodeURIComponent(
                                `Transaction: ${row.transaction_type}\nAccount: ${row.account_name}\nAmount: ${row.amount} ${row.currency_code}\nDate: ${row.created_at}`
                            );
                            const whatsappLink = `https://wa.me/?text=${whatsappText}`;

                            return `
                                <div class="btn-group btn-group-sm gap-2" role="group">
                                    @can('delete journal')
                                        <button class="btn btn-outline-danger rounded-pill btn-sm btn-delete" data-id="${id}" title="{{ __('ui.delete') }}"><i class="bi bi-trash"></i></button>
                                    @endcan
                                    @can('send-whatsapp journal')
                                    <a href="${whatsappLink}" target="_blank" class="btn btn-outline-success rounded-pill btn-sm" title="{{ __('ui.send_on_whatsapp') }}"><i class="bi bi-whatsapp"></i></a>
                                    @endcan
                                </div>
                            `;
                        }
                    }
                ]
            });

            $('#account_filter, #currency_filter, #type_filter').on('change', function() {
                table.draw();
                loadSummary();
            });

            $('#export_btn, #print_btn').on('click', function () {
                const params = new URLSearchParams({
                    date_range: $('#date_range').val(),
                    account_id: $('#account_filter').val(),
                    currency_id: $('#currency_filter').val(),
                    transaction_type: $('#type_filter').val()
                });

                const url = `{{ route('admin.journal.export') }}?${params.toString()}`;

                if (this.id === 'export_btn') {
                    window.open(url, '_blank');
                } else {
                    const win = window.open(url, '_blank');
                    win.onload = () => win.print();
                }
            });



            // Updated Journal Summary JS with:
            // 1. Yesterday = all balance before range
            // 2. Today = yesterday balance + current range net
            // 3. Summary chart and currency total display

            function loadSummary() {
                $('#currency_nets').html('<div class="text-muted">Loading summary...</div>');

                $.get('{{ route('admin.journal.summary') }}', {
                    account_id: $('#account_filter').val(),
                    currency_id: $('#currency_filter').val(),
                    date_range: $('#date_range').val(),
                }, function(res) {
                    renderSummaryCards(res.summary || {});
                });


            }

            function normalizeZero(value) {
                return Math.abs(value) < 0.000001 ? 0 : value;
            }

            function renderSummaryCards(summary) {
                const container = document.getElementById('currency_nets');
                container.innerHTML = '';

                const gradients = [
                    'linear-gradient(135deg, #4300c8, #000c0c)',
                    'linear-gradient(135deg, #c86e00, #b0026a)'
                ];

                let colorIndex = 0;

                Object.entries(summary).forEach(([currency, data]) => {
                    const credit = parseFloat(data.credit ?? 0);
                    const debit = parseFloat(data.debit ?? 0);
                    const balance = parseFloat(data.balance ?? 0);
                    const symbol = data.symbol ?? ''; // Ensure symbol is passed in the summary data
                    const flag = `/assets/flags/${currency.substring(0, 2).toLowerCase()}.svg`;

                    const balanceIcon = balance > 0 ? '📈' : balance < 0 ? '📉' : '⚖️';
                    const cardColor = gradients[colorIndex % gradients.length];
                    colorIndex++;

                    container.innerHTML += `
                        <div class="col">
                            <div class="card glass-card border-0 shadow-lg custom-glow-card" style="background: ${cardColor}; color: white;">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-4">
                                        <img src="${flag}" alt="${currency}" width="36" height="26" class="me-3 rounded border border-white shadow-sm">
                                        <h5 class="mb-0 fw-bold text-white text-uppercase fs-5">${currency}</h5>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center fs-6 mb-3">
                                        <span class="fw-semibold text-white">💰 Total Credit</span>
                                        <span class="fw-bold fs-5">${symbol} ${Math.round(credit).toLocaleString()}</span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center fs-6 mb-3">
                                        <span class="fw-semibold text-white">📤 Total Debit</span>
                                        <span class="fw-bold fs-5">${symbol} ${Math.round(debit).toLocaleString()}</span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center fs-6">
                                        <span class="fw-semibold text-white">${balanceIcon} Balance</span>
                                        <span class="fw-bold fs-5">${symbol} ${Math.round(balance).toLocaleString()}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }

            function cleanNumber(value) {
                return (value === 0 ? 0 : value).toLocaleString();
            }

            $(document).on('change', 'input[name="time_filter"]', function() {
                loadSummary(true);
            });

            $(document).ready(function() {
                loadSummary(true);
            });

            // Optional: Add toggle functionality
            $(document).on('click', '#toggleBalances', function() {
                $('.glass-card .card-body > canvas').closest('.card-body').toggleClass('d-none');
                $(this).text(function(i, text) {
                    return text === 'Hide Balances' ? 'Show Balances' : 'Hide Balances';
                });
            });

        });


        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');

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
                                location.reload();
                            });
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to delete transaction.', 'error');
                        }
                    });
                }
            });
        });
    </script>

@endsection
