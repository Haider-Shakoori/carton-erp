@extends('layouts.admin.base')

@section('title', 'Currency Exchange')
@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
@endsection

@section('content')
    <div class="row mb-4" style="display: none;">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="mb-1">Total Profit (USD)</h6>
                    <h4 class="fw-bold text-success" id="total-profit">$0.00</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-primary-subtle mb-4 border shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <h5 class="text-primary mb-0">
                <i class="bi bi-currency-exchange me-1"></i> Currency Exchange
            </h5>
            <button class="btn btn-sm btn-outline-primary" id="toggleFormBtn">
                <i class="bi bi-plus-circle me-1"></i> Add Exchange
            </button>
        </div>

        <div class="card-body d-none pt-4" id="exchangeFormCard">
            <div class="row">
                <div class="col-md-12">
                    @include('admin.exchanges.form')
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

            <div class="table-responsive">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" id="filter_code" class="form-control" placeholder="Filter by Customer Code">
                    </div>
                </div>

                <table class="table-bordered table-hover nowrap table" id="exchange-table">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('ui.customer') }}</th>
                            <th>From</th>
                            <th>To</th>
                            <th>{{ __('ui.amount') }}</th>
                            <th style="min-width: 75px;">Sale Rate</th>
                            @can('view cost rate')
                                <th style="min-width: 75px;">{{ __('ui.cost_rate') }}</th>
                            @endcan
                            <th>{{ __('ui.received') }}</th>
                            @can('view profit')
                                <th>{{ __('ui.profit') }}</th>
                            @endcan
                            <th>{{ __('ui.date') }}</th>
                            <th>{{ __('ui.action') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="purchasePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-white">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('ui.exchange_sale_receipt') }}</h5>
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

            $('select[name="office_account_id"]').on('change', function() {
                const id = $(this).val();
                fetchAndRenderBalances(id, false);
            });

            $('select[name="customer_account_id"]').on('change', function() {
                const id = $(this).val();
                fetchAndRenderBalances(id, true);
                $('#customerDetailsCard').fadeIn(); // if you want to keep this visible toggle
            });

            // function loadExchangeTotals() {
            //     $.get("{{ route('admin.exchange.totals') }}", function (res) {
            //         let html = '';
            //         const flagMap = {
            //             'USD': '/assets/flags/us.svg',
            //         };
            //         const currencySymbol = {
            //             'USD': '$',
            //         };

            //         let totalExchanges = 0;
            //         const currencySums = {};

            //         const row1 = [], row2 = [];

            //         // 1. Exchange summary (Base and Target totals)
            //         res.details.forEach(t => {
            //             totalExchanges += t.count;

            //             const base = t.base_currency;
            //             const target = t.target_currency;

            //             if (!currencySums[base]) currencySums[base] = { base: 0, target: 0 };
            //             if (!currencySums[target]) currencySums[target] = { base: 0, target: 0 };

            //             currencySums[base].base += parseFloat(t.total_base);
            //             currencySums[target].target += parseFloat(t.total_target);
            //         });

            //         // 2. Total exchanges card
            //         row1.push(`
        //             <div class="col mb-3">
        //                 <div class="card bg-gradient-danger text-white border-0 rounded-4 shadow">
        //                     <div class="card-body d-flex align-items-center justify-content-between">
        //                         <div>
        //                             <div class="text-uppercase small opacity-100">Total Exchanges</div>
        //                             <h2 class="fw-bold fs-large text-white mb-0">${totalExchanges}</h2>
        //                         </div>
        //                         <i class="bi bi-arrow-left-right display-5 opacity-50"></i>
        //                     </div>
        //                 </div>
        //             </div>
        //         `);

            //         // 3. Base/Target currency breakdown cards
            //         Object.entries(currencySums).forEach(([currency, values]) => {
            //             const flag = flagMap[currency] || '';
            //             const symbol = currencySymbol[currency] || '';
            //             row1.push(`
        //                 <div class="col mb-3">
        //                     <div class="card text-white border-0 rounded-4 shadow"
        //                         style="min-height: 110px; background: linear-gradient(135deg, #4300c8, #000c0c);">
        //                         <div class="card-body d-flex align-items-center">
        //                             <img src="${flag}" class="me-3 rounded-circle border" style="width:44px;height:44px;object-fit:cover;">
        //                             <div>
        //                                 <h6 class="fw-bold mb-1 text-white">${currency}</h6>
        //                                 <div style="font-size: 15px;">
        //                                     <div>Base: <strong>${symbol}${values.base.toLocaleString()}</strong></div>
        //                                     <div>Target: <strong>${symbol}${values.target.toLocaleString()}</strong></div>
        //                                 </div>
        //                             </div>
        //                         </div>
        //                     </div>
        //                 </div>
        //             `);
            //         });

            //         // 4. Balance cards from purchased - sold
            //         const allCurrencies = new Set([
            //                 ...Object.keys(res.purchased || {}),
            //                 ...Object.keys(res.sold || {}),
            //                 ...Object.keys(res.safe || {})
            //             ]);

            //         allCurrencies.forEach(currency => {
            //             const purchased = parseFloat(res.purchased?.[currency] || 0);
            //             const sold = parseFloat(res.sold?.[currency] || 0);
            //             const safe = parseFloat(res.safe?.[currency] || 0);
            //             const balance = safe + purchased - sold;

            //             const flag = flagMap[currency] || '';
            //             const symbol = currencySymbol[currency] || '';

            //             row1.push(`
        //                 <div class="col mb-2">
        //                     <div class="card text-white border-0 rounded-4 shadow"
        //                         style="min-height: 110px; background: linear-gradient(135deg, ${balance >= 0 ? '#004d27' : '#6d0000'}, #000c0c);">
        //                         <div class="card-body py-2 px-3 d-flex align-items-center">
        //                             <img src="${flag}" class="me-2 rounded-circle border" style="width:36px;height:36px;object-fit:cover;">
        //                             <div>
        //                                 <div class="fw-bold">${currency} Balance</div>
        //                                 <div style="font-size: 14px;">
        //                                     Safe: <strong>${symbol}${safe.toLocaleString()}</strong><br>
        //                                     Purchased: <strong>${symbol}${purchased.toLocaleString()}</strong><br>
        //                                     Sold: <strong>${symbol}${sold.toLocaleString()}</strong><br>
        //                                     Balance: <strong>${symbol}${balance.toLocaleString(undefined, { minimumFractionDigits: 2 })}</strong>
        //                                 </div>
        //                             </div>
        //                         </div>
        //                     </div>
        //                 </div>
        //             `);
            //         });

            //         // 5. Final HTML layout
            //         html += `<div class="row gx-3 gy-2 mb-1">${row1.join('')}</div>`;
            //         // html += `<div class="row gx-2 gy-2">${row2.join('')}</div>`;
            //         $('#exchange-summary').html(html);
            //     });
            // }

            function loadExchangeTotals() {
                $.get("{{ route('admin.exchange.totals') }}", function(res) {
                    const flagMap = {
                        'USD': '/assets/flags/us.svg',
                        'AFN': '/assets/flags/af.svg'
                    };
                    const symbolMap = {
                        'USD': '$',
                        'AFN': '؋'
                    };

                    let html = `<div class="row g-3">`;

                    // CARD 1: Total Exchanges
                    html += `
            <div class="col-md-2 col-sm-6">
                <div class="card h-100" style="
                    border: none;
                    border-radius: 12px;
                    background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
                    box-shadow: 0 4px 12px rgba(106, 17, 203, 0.15);
                    color: white;
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
                            <div style="font-size: 1rem; opacity: 0.8;">{{ __('ui.total_exchanges') }}</div>
                            <div style="font-weight: 700; font-size: 1.5rem;">${res.total_exchanges}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;

                    // 🛠 Helper Function: Generate uniform full-width card
                    function generateCurrencyCard(title, icon, data, gradient) {
                        let rows = '';
                        const entries = Object.entries(data || {});
                        if (entries.length === 0) {
                            rows = `<div class="text-white-50 text-center py-2">{{ __('ui.no_data') }}</div>`;
                        } else {
                            entries.forEach(([currency, amount]) => {
                                const flag = flagMap[currency] || '';
                                const symbol = symbolMap[currency] || '';
                                rows += `
                        <div class="d-flex justify-content-between align-items-center py-2" style="
                            border-bottom: 1px solid rgba(255,255,255,0.08);
                            font-size: 1rem;
                        ">
                            <div class="d-flex align-items-center">
                                <img src="${flag}" style="width:20px; height:20px; border-radius:50%; margin-right: 8px; border: 1px solid rgba(255,255,255,0.2);">
                                <span>${currency}</span>
                            </div>
                            <div style="font-weight: 600;">${symbol}${parseFloat(amount).toLocaleString()}</div>
                        </div>`;
                            });
                        }

                        return `
                <div class="col d-flex">
                    <div class="card h-100 w-100" style="
                        border: none;
                        border-radius: 12px;
                        background: ${gradient};
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                        color: white;
                    ">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-3">
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
                                    <i class="${icon}" style="font-size: 1rem;"></i>
                                </div>
                                <div style="font-weight: 600; font-size: 1rem;">${title}</div>
                            </div>
                            <div style="max-height: 160px; overflow-y: auto; padding-right: 4px;">
                                ${rows}
                            </div>
                        </div>
                    </div>
                </div>
            `;

                    }

                    // CARD 2: Base Totals
                    html += generateCurrencyCard(
                        'BASE TOTALS',
                        'bi bi-box-arrow-up-right',
                        res.base_totals,
                        'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)'
                    );

                    // CARD 3: Target Totals
                    html += generateCurrencyCard(
                        'TARGET TOTALS',
                        'bi bi-download',
                        res.target_totals,
                        'linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%)'
                    );

                    // CARD 4: ExchangePurchase Target Totals
                    html += generateCurrencyCard(
                        'PURCHASED (EX.PURCHASE)',
                        'bi bi-cart-check',
                        res.purchased_totals,
                        'linear-gradient(135deg, #396afc 0%, #2948ff 100%)'
                    );

                    // CARD 5: Final Balances (All accounts)
                    html += generateCurrencyCard(
                        'TOTAL BALANCES',
                        'bi bi-cash-coin',
                        res.balances,
                        'linear-gradient(135deg, #4776E6 0%, #8E54E9 100%)'
                    );

                    html += `</div>`;
                    $('#exchange-summary').html(html);
                });
            }


            $(document).ready(function() {
                loadExchangeTotals();
            });

            const table = $('#exchange-table').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                ajax: {
                    url: '{{ route('admin.exchange.data') }}',
                    data: function(d) {
                        d.code = $('#filter_code').val(); // send filter value
                    },
                    dataSrc: function(json) {
                        let total = 0;
                        json.data.forEach(row => total += parseFloat(row.profit.replace(/,/g, '')) || 0);
                        $('#total-profit').text('$' + total.toFixed(2));
                        return json.data;
                    },
                    dataSrc: function(json) {
                        let total = 0;
                        json.data.forEach(row => total += parseFloat(row.profit.replace(/,/g, '')) || 0);
                        $('#total-profit').text('$' + total.toFixed(2));
                        return json.data;
                    }
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
                                <div class="d-flex align-items-center">
                                    <strong class="me-1">${data}</strong>
                                    <span class="badge rounded-pill bg-light text-dark">${row.customer_code}</span>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'from_currency',
                        name: 'baseCurrency.code',
                        render: function(data) {
                            const flag = `/assets/flags/${data.substring(0, 2).toLowerCase()}.svg`;
                            return `<img src="${flag}" height="16" class="me-1"> <span class="fw-medium">${data}</span>`;
                        }
                    },
                    {
                        data: 'to_currency',
                        name: 'targetCurrency.code',
                        render: function(data) {
                            const flag = `/assets/flags/${data.substring(0, 2).toLowerCase()}.svg`;
                            return `<img src="${flag}" height="16" class="me-1"> <span class="fw-medium">${data}</span>`;
                        }
                    },
                    {
                        data: 'amount',
                        name: 'base_amount',
                        render: function(data, type, row) {
                            const from_currency = row.from_currency || '';
                            const amount = parseFloat(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                            return `<span class="text-danger fw-semibold">${amount} ${from_currency}</span>`;
                        }
                    },
                    {
                        data: 'rate',
                        name: 'rate',
                        render: function(data, type, row) {
                            const rate = parseFloat(data).toFixed(3);
                            const cost_rate = isNaN(row.cost_rate) ? '-' : parseFloat(row.cost_rate).toFixed(3);
                            return `
                                <div class="d-flex flex-column align-items-start gap-1">
                                    <span class="badge rounded-pill bg-info text-white fw-semibold">${rate}</span>
                                </div>
                            `;
                        }
                    },
                    @can('view cost rate')
                        {
                            data: 'rate',
                            name: 'rate',
                            render: function(data, type, row) {
                                const rate = parseFloat(data).toFixed(3);
                                const cost_rate = isNaN(row.cost_rate) ? '-' : parseFloat(row.cost_rate).toFixed(3);
                                return `
                                <div class="d-flex flex-column align-items-start gap-1">
                                    <span class="badge rounded-pill bg-light text-dark fw-semibold">${cost_rate}</span>
                                </div>
                            `;
                            }
                        },
                    @endcan {
                        data: 'received',
                        name: 'target_amount',
                        render: function(data, type, row) {
                            const currency = row.to_currency || '';
                            return `<span class="text-success fw-semibold">${parseFloat(data).toLocaleString()} ${currency}</span>`;
                        }
                    },
                    @can('view profit')
                        {
                            data: 'profit',
                            name: 'target_profit',
                            render: function(data, type, row) {
                                let value = parseFloat(data);

                                if (isNaN(value)) value = 0;

                                const profit = value.toLocaleString(undefined, {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });

                                const currency = row.to_currency || '';

                                const badgeClass = value > 0 ?
                                    'bg-success-subtle text-primary' :
                                    'bg-danger-subtle text-muted text-danger';

                                return `<span class="badge ${badgeClass} fw-semibold">${profit} ${currency}</span>`;
                            }
                        },
                    @endcan {
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data) {
                            const date = new Date(data);
                            return `
                                <div class="d-inline-flex flex-column gap-1 align-items-start">
                                    <span class="fw-bold">${date.toLocaleDateString('en-CA', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                                    <span class="text-muted small">${new Intl.DateTimeFormat('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }).format(date)}</span>
                                </div>
                            `;
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

            $('#filter_code').on('keyup change', function() {
                table.ajax.reload();
            });

            $(document).on('click', '.btn-preview-sale', function() {
                const id = $(this).data('id');

                $('#purchasePreviewContent').html('<div class="p-5 text-center">{{ __('ui.loading_preview') }}</div>');

                $.get(`/admin/exchange/${id}/receipt`, function(html) {
                    $('#purchasePreviewContent').html(html);
                    const modal = new bootstrap.Modal(document.getElementById('purchasePreviewModal'));
                    modal.show();
                }).fail(function() {
                    $('#purchasePreviewContent').html('<div class="p-5 text-danger text-center">{{ __('ui.failed_load_preview') }}</div>');
                });
            });

            $('#toggleFormBtn').on('click', () => {
                $('#exchangeFormCard').toggleClass('d-none');
                $('[name="base_currency_id"]').trigger('change');
            });

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

            $('#base_currency, #target_currency, #exchange_rate').on('change keyup', function() {
                refreshCurrencyOptions();

                const base_currency_id = $('#base_currency').val();
                const target_currency_id = $('#target_currency').val();
                const userEnteredRate = $('#exchange_rate').val();
                const amount = parseFloat($('#exchange_amount').val().replace(/,/g, '')) || 0;

                $('#profit_label').text(`Profit (${$('#target_currency option:selected').data('code')})`);

                if (base_currency_id && target_currency_id) {
                    $.get('{{ route('admin.exchange.getRate') }}', {
                        base_currency_id,
                        target_currency_id,
                        amount
                    }, res => {
                        const rate = parseFloat(res.rate);
                        const cost = parseFloat(res.cost_rate);
                        const exchangeAmount = parseFloat($('#exchange_amount').val().replace(/,/g, '')) || 0;

                        if (exchangeAmount === 0) {
                            $('#exchange_amount').focus();
                            $('#target_currency').val(null).trigger('change');
                            return;
                        }

                        if (!rate || isNaN(rate)) {
                            $('#exchange_rate').val('');
                            $('#converted_amount').val('');
                            $('#cost_rate').val('');
                            $('#target_profit').val('');
                            Swal.fire({
                                icon: 'warning',
                                title: 'No Rate Found',
                                text: 'Please add the exchange rate before proceeding.',
                                confirmButtonText: 'Go to Rates'
                            }).then(result => {
                                if (result.isConfirmed) {
                                    window.location.href = '{{ url('admin/exchange-rates') }}';
                                }
                            });
                            return;
                        }

                        // Only fill exchange_rate if the user hasn't entered anything
                        if (!userEnteredRate || isNaN(parseFloat(userEnteredRate))) {
                            $('#exchange_rate').val(rate.toFixed(3));
                        }

                        $('#exchange_rate').data('cost', cost);
                        $('#cost_rate').val(cost.toFixed(3));

                        // Use user rate if provided, else fallback to AJAX rate
                        const finalRate = parseFloat(userEnteredRate) || rate;
                        const target = exchangeAmount / finalRate;
                        const cost_total = exchangeAmount / cost;
                        const profit = target - cost_total;

                        $('#converted_amount').val(target.toFixed(2));
                        $('#target_profit').val(profit.toFixed(2));
                    }).fail(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: 'Failed to fetch exchange rate. Please try again later.',
                            confirmButtonText: 'OK'
                        });
                    });
                }
            });


            $('#exchange_amount').on('keyup change', function() {
                const amount = parseFloat($(this).val().replace(/,/g, '')) || 0;
                const rate = parseFloat($('#exchange_rate').val()) || 0;
                const cost = parseFloat($('#exchange_rate').data('cost')) || 0;
                if (rate && amount > 0) {
                    const target = amount / rate;
                    const cost_total = amount / cost;
                    const profit = target - cost_total;
                    $('#converted_amount').val(target.toFixed(2));
                    $('#target_profit').val(profit.toFixed(2));
                }
            });

            $(document).on('click', '.btn-whatsapp', function() {
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
                            url: `/admin/exchange/${id}/send-whatsapp`,
                            type: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                            },
                            success: function(res) {
                                Swal.fire('Success', res.message, 'success');
                            },
                            error: function(err) {
                                console.error('AJAX Error:', err.responseJSON?.error);
                                let msg = err.responseJSON?.error || 'Failed to send message';
                                Swal.fire('Error', msg, 'error');
                            }
                        });
                    }
                });
            });


            $('#exchangeForm').on('submit', function(e) {
                e.preventDefault();
                const form = this;

                $.post('{{ route('admin.exchange.store') }}', $(form).serialize())
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

            $(document).on('click', '.btn-edit', function() {
                const id = $(this).data('id');

                $.get(`/admin/exchange/${id}`, function(res) {
                    $('#exchange_id').val(res.id);

                    setTimeout(() => {
                        if ($('[name="customer_account_id"]').length) {
                            $('[name="customer_account_id"]').val(res.customer_account_id).trigger('change');
                        }
                        if ($('[name="office_account_id"]').length) {
                            $('[name="office_account_id"]').val(res.office_account_id).trigger('change');
                        }
                        if ($('#base_currency').length) {
                            $('#base_currency').val(res.base_currency_id).trigger('change');
                        }
                        if ($('#target_currency').length) {
                            $('#target_currency').val(res.target_currency_id).trigger('change');
                        }
                    }, 100);

                    $('#exchange_amount').val(res.base_amount);
                    $('#exchange_rate').val(res.rate);
                    $('#converted_amount').val(res.target_amount);
                    $('#target_profit').val(res.target_profit);
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
                            url: `/admin/exchange/${id}`,
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
