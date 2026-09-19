@extends('layouts.admin.base')

@section('title', 'Currency Exchange')
@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
@endsection

@section('content')


    <div class="card border-primary-subtle mb-4 border shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <h5 class="text-primary mb-0">
                <i class="bi bi-currency-exchange me-1"></i> Purchase Currencies
            </h5>
            <button class="btn btn-sm btn-outline-primary" id="toggleFormBtn">
                <i class="bi bi-plus-circle me-1"></i> Add Exchange (Purchase)
            </button>
        </div>

        <div class="card-body d-none pt-4" id="exchangeFormCard">
            <div class="row">
                <div class="col-md-12">
                    @include('admin.exchange-purchase.form')
                </div>
            </div>
        </div>
    </div>


    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ __('ui.exchange_records') }}</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3" id="exchange-summary"></div>

            <table class="table-bordered table-hover table" id="exchange-table">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" id="filter_code" class="form-control" placeholder="Filter by Saraf/Bank Code">
                    </div>
                </div>

                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('ui.customer') }}</th>
                        <th>From</th>
                        <th>To</th>
                        <th>{{ __('ui.amount') }}</th>
                        <th>{{ __('ui.rate') }}</th>
                        <th>{{ __('ui.received') }}</th>
                        <th>{{ __('ui.date') }}</th>
                        <th>{{ __('ui.status') }}</th>
                        <th style="min-width: 210px; max-width: 220px;" class="nowrap">{{ __('ui.action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="purchasePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-white">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('ui.exchange_purchase_receipt') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="purchasePreviewContent" style="background: #f4f4f4;"></div>
        </div>
    </div>
</div>
@endsection

@section('js')
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>


    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%'
            });

            $('#base_currency').on('change', function () {
                const selectedText = $(this).find("option:selected").text();
                $('#amountLabel').text(`Amount (${selectedText})`);
                if (selectedText === 'USD') {
                    $('#amountIcon').text('$');
                } else if (selectedText === 'CNY') {
                    $('#amountIcon').text('¥');
                } else {
                    $('#amountIcon').text(`${selectedText.toLowerCase()}`);
                }
            });

            $(document).on('click', '.btn-preview-purchase', function () {
                const id = $(this).data('id');

                $('#purchasePreviewContent').html('<div class="p-5 text-center">{{ __('ui.loading_preview') }}</div>');

                $.get(`/admin/exchange-purchase/${id}/receipt`, function (html) {
                    $('#purchasePreviewContent').html(html);
                    const modal = new bootstrap.Modal(document.getElementById('purchasePreviewModal'));
                    modal.show();
                }).fail(function () {
                    $('#purchasePreviewContent').html('<div class="p-5 text-danger text-center">{{ __('ui.failed_load_preview') }}</div>');
                });
            });

            $(document).on('click', '.btn-whatsapp', function () {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Send WhatsApp Message?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Send',
                    cancelButtonText: 'Cancel',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/exchange-purchase/${id}/send-whatsapp`,
                            type: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                            },
                            success: function (res) {
                                Swal.fire('Success', res.message, 'success');
                            },
                            error: function (err) {
                                console.error('AJAX Error:', err.responseJSON?.error);
                                let msg = err.responseJSON?.error || 'Failed to send message';
                                Swal.fire('Error', msg, 'error');
                            }
                        });
                    }
                });
            });




            function fetchAndRenderBalances(accountId, isCustomer = true) {
                if (!accountId) return;

                $.get(`{{ route('admin.accounts.balances', ['id' => ':id']) }}`.replace(':id', accountId), function(res) {
                    const container = isCustomer ? '#customer_balance_cards' : '#office_balance_cards';
                    const title = isCustomer ? '#detail_name' : '#office_name';
                    const code = isCustomer ? '#detail_code' : '#office_code';

                    $(title).text(res.name);
                    $(code).text(res.code);

                    let html = '';
                    res.balances.forEach(balance => {
                        const colorClass = parseFloat(balance.amount) < 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success';
                        html += `
                            <div class="col-6 col-md-6 col-lg-6">
                                <div class="card shadow-sm border-0 ${colorClass} p-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="${balance.flag}" height="24" class="rounded">
                                        <div>
                                            <div class="fw-semibold fs-6">${balance.amount}</div>
                                            <small class="text-muted">${balance.currency}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    $(container).html(html);
                });
            }

            $('select[name="office_account_id"]').on('change', function () {
                const id = $(this).val();
                fetchAndRenderBalances(id, false);
            });

            $('select[name="customer_account_id"]').on('change', function () {
                const id = $(this).val();
                fetchAndRenderBalances(id, true);
                $('#customerDetailsCard').fadeIn(); // if you want to keep this visible toggle
            });



        });


        function loadExchangeTotals() {
            $.get("{{ route('admin.exchange-purchase.totals') }}", function (res) {
                const flagMap = {
                    'USD': '/assets/flags/us.svg',
                    'CNY': '/assets/flags/cn.svg'
                };
                const symbolMap = {
                    'USD': '$',
                    'CNY': '¥'
                };

                let html = `<div class="row g-3">`; // Reduced gutter spacing

                // CARD 1: Total Exchanges - Compact with icon integration
                html += `
                    <div class="col-md-3">
                        <div class="card h-100" style="
                            border: none;
                            border-radius: 12px;
                            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
                            box-shadow: 0 4px 12px rgba(106, 17, 203, 0.15);
                            color: white;
                            overflow: hidden;
                        ">
                            <div class="card-body p-3 d-flex align-items-center">
                                <div style="
                                    background: rgba(255,255,255,0.2);
                                    width: 42px;
                                    height: 42px;
                                    border-radius: 10px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    margin-right: 12px;
                                    flex-shrink: 0;
                                ">
                                    <i class="bi bi-arrow-left-right" style="font-size: 1.25rem;"></i>
                                </div>
                                <div>
                                    <div style="font-size: 1rem; opacity: 0.8; letter-spacing: 0.5px;">{{ __('ui.total_exchanges') }}</div>
                                    <div style="font-weight: 700; font-size: 1.5rem; line-height: 1.2;">${res.total_exchanges}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // CARD 2: Base Totals - Compact list view
                let baseRows = '';
                Object.entries(res.total_base || {}).forEach(([currency, amount]) => {
                    const flag = flagMap[currency] || '';
                    const symbol = symbolMap[currency] || '';
                    baseRows += `
                        <div class="d-flex justify-content-between align-items-center py-2" style="
                            border-bottom: 1px solid rgba(255,255,255,0.08);
                            font-size: 1rem;
                        ">
                            <div style="display: flex; align-items: center;">
                                <img src="${flag}" style="width:20px; height:20px; border-radius:50%; margin-right: 8px; border: 1px solid rgba(255,255,255,0.2);">
                                <span>${currency}</span>
                            </div>
                            <div style="font-weight: 600;">${symbol}${amount.toLocaleString()}</div>
                        </div>`;
                });

                html += `
                    <div class="col-md-3">
                        <div class="card h-100" style="
                            border: none;
                            border-radius: 12px;
                            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
                            box-shadow: 0 4px 12px rgba(17, 153, 142, 0.15);
                            color: white;
                        ">
                            <div class="card-body p-3">
                                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                                    <div style="
                                        background: rgba(255,255,255,0.2);
                                        width: 32px;
                                        height: 32px;
                                        border-radius: 8px;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        margin-right: 10px;
                                    ">
                                        <i class="bi bi-box-arrow-up-right" style="font-size: 1rem;"></i>
                                    </div>
                                    <div style="font-weight: 600; font-size: 1rem;">BASE TOTALS</div>
                                </div>
                                <div style="max-height: 150px; overflow-y: auto; padding-right: 4px;">
                                    ${baseRows || '<div style="color: rgba(255,255,255,0.7); font-size: 1rem; text-align: center; padding: 8px 0;">{{ __('ui.no_data') }}</div>'}
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // CARD 3: Target Totals - Compact list view
                let targetRows = '';
                Object.entries(res.total_target || {}).forEach(([currency, amount]) => {
                    const flag = flagMap[currency] || '';
                    const symbol = symbolMap[currency] || '';
                    targetRows += `
                        <div class="d-flex justify-content-between align-items-center py-2" style="
                            border-bottom: 1px solid rgba(255,255,255,0.08);
                            font-size: 1rem;
                        ">
                            <div style="display: flex; align-items: center;">
                                <img src="${flag}" style="width:20px; height:20px; border-radius:50%; margin-right: 8px; border: 1px solid rgba(255,255,255,0.2);">
                                <span>${currency}</span>
                            </div>
                            <div style="font-weight: 600;">${symbol}${amount.toLocaleString()}</div>
                        </div>`;
                });

                html += `
                    <div class="col-md-3">
                        <div class="card h-100" style="
                            border: none;
                            border-radius: 12px;
                            background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%);
                            box-shadow: 0 4px 12px rgba(252, 74, 26, 0.15);
                            color: white;
                        ">
                            <div class="card-body p-3">
                                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                                    <div style="
                                        background: rgba(255,255,255,0.2);
                                        width: 32px;
                                        height: 32px;
                                        border-radius: 8px;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        margin-right: 10px;
                                    ">
                                        <i class="bi bi-download" style="font-size: 1rem;"></i>
                                    </div>
                                    <div style="font-weight: 600; font-size: 1rem;">TARGET TOTALS</div>
                                </div>
                                <div style="max-height: 150px; overflow-y: auto; padding-right: 4px;">
                                    ${targetRows || '<div style="color: rgba(255,255,255,0.7); font-size: 1rem; text-align: center; padding: 8px 0;">{{ __('ui.no_data') }}</div>'}
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // CARD 4: Final Balances - Compact list view
                let balanceRows = '';
                Object.entries(res.balances || {}).forEach(([currency, amount]) => {
                    const flag = flagMap[currency] || '';
                    const symbol = symbolMap[currency] || '';
                    const textClass = amount >= 0 ? 'color: white;' : 'color: #ffeb3b;';
                    balanceRows += `
                        <div class="d-flex justify-content-between align-items-center py-2" style="
                            border-bottom: 1px solid rgba(255,255,255,0.08);
                            font-size: 1rem;
                            ${textClass}
                        ">
                            <div style="display: flex; align-items: center;">
                                <img src="${flag}" style="width:20px; height:20px; border-radius:50%; margin-right: 8px; border: 1px solid rgba(255,255,255,0.2);">
                                <span>${currency}</span>
                            </div>
                            <div style="font-weight: 600;">${symbol}${amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
                        </div>`;
                });

                html += `
                    <div class="col-md-3">
                        <div class="card h-100" style="
                            border: none;
                            border-radius: 12px;
                            background: linear-gradient(135deg, #4776E6 0%, #8E54E9 100%);
                            box-shadow: 0 4px 12px rgba(71, 118, 230, 0.15);
                            color: white;
                        ">
                            <div class="card-body p-3">
                                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                                    <div style="
                                        background: rgba(255,255,255,0.2);
                                        width: 32px;
                                        height: 32px;
                                        border-radius: 8px;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        margin-right: 10px;
                                    ">
                                        <i class="bi bi-cash-coin" style="font-size: 0.9rem;"></i>
                                    </div>
                                    <div style="font-weight: 600; font-size: 1rem;">TOTAL BALANCES</div>
                                </div>
                                <div style="max-height: 150px; overflow-y: auto; padding-right: 4px;">
                                    ${balanceRows || '<div style="color: rgba(255,255,255,0.7); font-size: 1rem; text-align: center; padding: 8px 0;">{{ __('ui.no_data') }}</div>'}
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                html += `</div>`;

                $('#exchange-summary').html(html);
            });
        }


        $(document).ready(function () {
            loadExchangeTotals();
        });
    </script>
    <script>
        $(document).ready(function() {
            const table = $('#exchange-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.exchange-purchase.data') }}',
                    data: function (d) {
                        d.code = $('#filter_code').val(); // send filter value
                    },
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'customer',
                        name: 'customer.name',
                        render: function(data, type, row) {
                            return `
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <span class="badge rounded-pill bg-primary me-2" style="color: ${row.customer.profile_bg};">${row.customer_code}</span>
                                    <span style="font-weight: bold;">${data}</span>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'from_currency',
                        name: 'baseCurrency.code'
                    },
                    {
                        data: 'to_currency',
                        name: 'targetCurrency.code'
                    },
                    {
                        data: 'amount',
                        name: 'base_amount',
                        render: function(data, type, row) {
                            const amount = parseFloat(data).toLocaleString('en-GB', { minimumFractionDigits: 2 });
                            const flag = `/assets/flags/${row.from_currency.substring(0, 2).toLowerCase()}.svg`;
                            const colorClass = 'text-danger';
                            return `<img src="${flag}" alt="${row.from_currency}" class="me-2" height="16"> <strong class="${colorClass}">${amount}</strong>`;
                        }
                    },
                    {
                        data: 'rate',
                        name: 'rate'
                    },
                    {
                        data: 'received',
                        name: 'target_amount',
                        render: function(data, type, row) {
                            const target_amount = parseFloat(data).toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            const flag = `/assets/flags/${row.to_currency.substring(0, 2).toLowerCase()}.svg`;
                            const colorClass = 'text-primary';
                            return `<img src="${flag}" alt="${row.to_currency}" class="me-2" height="16"> <strong class="${colorClass}">${target_amount}</strong>`;
                        }
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data) {
                            const date = new Date(data);
                            return `
                                <span style="font-size: 12px;">
                                    ${date.toLocaleString('en-CA', { year: 'numeric', month: '2-digit', day: '2-digit' })}
                                </span>
                                <br>
                                <span class="badge rounded-pill bg-light text-dark" style="font-size: 12px;">
                                    ${new Intl.DateTimeFormat('en-US', { hour: 'numeric', minute: 'numeric', hour12: true }).format(date)}
                                </span>
                            `;
                        }
                    },

                    {
                        data: 'status',
                        name: 'status',
                        render: function(data) {
                            const colorClass = data === 'pending' ? 'text-warning' : data === 'success' ? 'text-success' : 'text-white';
                            return `<span class="badge rounded-pill ${colorClass}" style="font-size: 12px;">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                        }
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            $('#filter_code').on('keyup change', function () {
                table.ajax.reload();
            });

            $('#toggleFormBtn').on('click', () => $('#exchangeFormCard').toggleClass('d-none'));

            function refreshCurrencyOptions() {
                const baseId = $('#base_currency').val();
                const targetId = $('#target_currency').val();

                $('#base_currency option, #target_currency option').prop('disabled', false);
                if (targetId) $('#base_currency option[value="' + targetId + '"]').prop('disabled', true);
                if (baseId) $('#target_currency option[value="' + baseId + '"]').prop('disabled', true);

                $('#base_currency, #target_currency').select2({
                    width: '100%'
                });
            }


            $('#exchange_amount, #exchange_rate, #base_currency, #target_currency').on('keyup change', function () {
                const amount = parseFloat($('#exchange_amount').val().replace(/,/g, '')) || 0;
                const rate = parseFloat($('#exchange_rate').val()) || 0;

                const baseCurrency = $('#base_currency option:selected').text().trim();
                const targetCurrency = $('#target_currency option:selected').text().trim();

                let converted = 0;
                if (amount > 0 && rate > 0) {
                    if (baseCurrency === 'USD' && targetCurrency === 'CNY') {
                        converted = amount * rate;
                    } else if (baseCurrency === 'CNY' && targetCurrency === 'USD') {
                        converted = amount / rate;
                    } else {
                        converted = amount / rate; // default case
                    }
                }

                $('#converted_amount').val(converted.toFixed(2));
            });


            $('#exchangeForm').on('submit', function(e) {
                e.preventDefault();
                const form = this;

                Swal.fire({
                    title: 'Is this a cash transaction?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then(result => {
                    const isCash = result.isConfirmed ? 1 : 0;

                    $.post('{{ route('admin.exchange-purchase.store') }}', $(form).serialize() + '&is_cash=' + isCash)
                        .done(res => {
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: res.message || 'Exchange saved!',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                table.ajax.reload();
                                loadExchangeTotals();
                                $('#exchangeFormCard').addClass('d-none');
                                $('#exchangeForm')[0].reset();
                                $('.select2').val(null).trigger('change');
                                $('#exchangeForm button[type="submit"]').html('<i class="bi bi-check-circle me-1"></i> Save Exchange');
                                $('#exchange_id').val('');
                            } else {
                                Swal.fire('Warning', res.message || 'Unknown issue occurred.', 'warning');
                            }
                        })
                        .fail(err => {
                            console.error('AJAX Error:', err);
                            Swal.fire('Error', err.responseJSON?.message || 'Exchange failed.', 'error');
                        });
                });
            });

            $(document).on('click', '.btn-process', function () {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Mark this exchange as processed?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Process it'
                }).then(result => {
                    if (result.isConfirmed) {
                        $.post(`/admin/exchange-purchase/${id}/process`, {
                            _token: '{{ csrf_token() }}'
                        }).done(res => {
                            Swal.fire('Success', res.message, 'success');
                            $('#exchange-table').DataTable().ajax.reload();
                            loadExchangeTotals();
                        }).fail(err => {
                            Swal.fire('Error', err.responseJSON?.message || 'Processing failed.', 'error');
                        });
                    }
                });
            });


            $(document).on('click', '.btn-approve', function () {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Approve this exchange purchase?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Approve it'
                }).then(result => {
                    if (result.isConfirmed) {
                        $.post(`/admin/exchange-purchase/${id}/process`, {
                            _token: '{{ csrf_token() }}'
                        }).done(res => {
                            Swal.fire('Success', res.message, 'success');
                            $('#exchange-table').DataTable().ajax.reload();
                            loadExchangeTotals();
                        }).fail(err => {
                            Swal.fire('Error', err.responseJSON?.message || 'Approve failed.', 'error');
                        });
                    }
                });
            });

            $(document).on('click', '.btn-edit', function() {
                const id = $(this).data('id');

                $.get(`/admin/exchange-purchase/${id}`, function(res) {
                    $('#exchange_id').val(res.id);
                    $('[name="customer_account_id"]').val(res.customer_account_id).trigger('change');
                    $('[name="office_account_id"]').val(res.office_account_id).trigger('change');
                    $('#base_currency').val(res.base_currency_id).trigger('change');
                    $('#target_currency').val(res.target_currency_id).trigger('change');
                    $('#exchange_amount').val(res.base_amount);
                    $('#exchange_rate').val(res.rate);
                    $('#converted_amount').val(res.target_amount);
                    $('[name="note"]').val(res.note);

                    $('#exchangeFormCard').removeClass('d-none');
                    $('html, body').animate({
                        scrollTop: $('#exchangeFormCard').offset().top - 100
                    }, 400);

                    $('#exchangeForm button[type="submit"]').html('<i class="bi bi-check-circle me-1"></i> Update Exchange');
                });
            });

            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This exchange will be deleted!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!'
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/exchange-purchase/${id}`,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                table.ajax.reload();
                                loadExchangeTotals();
                                Swal.fire({
                                    icon: 'success',
                                    text: res.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            },
                            error: function() {
                                Swal.fire('Error', 'Something went wrong!', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
