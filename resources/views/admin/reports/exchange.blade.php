@extends('layouts.admin.base')

@section('title', __('ui.exchange_report'))

@section('css')
<link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/datatables/buttons/buttons.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}" />
@endsection

@section('content')
<div class="card rounded-4 border-0 shadow">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start flex-wrap gap-3">
        <h4 class="card-title">{{ __('ui.exchange_report') }}</h4>
        <div class="d-flex w-100 flex-wrap gap-2">
            <div class="flex-fill" style="min-width: 200px;">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                    <select class="form-select form-select-sm rounded-start-0" id="filter_customer">
                        <option value="">{{ __('ui.all_customers') }}</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex-fill" style="min-width: 150px;">
                <div class="input-group">
                    <span class="input-group-text bg-light">From</span>
                    <select class="form-select form-select-sm rounded-start-0" id="filter_base">
                        <option value="">Base Currency</option>
                        @foreach($currencies as $cur)
                            <option value="{{ $cur->id }}">{{ $cur->code }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex-fill" style="min-width: 150px;">
                <div class="input-group">
                    <span class="input-group-text bg-light">To</span>
                    <select class="form-select form-select-sm rounded-start-0" id="filter_target">
                        <option value="">Target Currency</option>
                        @foreach($currencies as $cur)
                            <option value="{{ $cur->id }}">{{ $cur->code }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex-fill" style="min-width: 200px;">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-calendar"></i></span>
                    <input type="text" id="filter_date" class="form-control form-control-sm rounded-start-0" placeholder="{{ __('ui.date_range_plain') }}">
                </div>
            </div>
            <div style="min-width: 150px;">
                <a href="#" id="exportPdfBtn" class="btn btn-sm btn-danger w-100" style="height: 47px;">{{ __('ui.export_pdf') }}</a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div id="totalsCards" class="row g-3 mb-4"></div>

        <div class="table-responsive">
            <table class="table-bordered table-hover table text-center align-middle" id="reportTable" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('ui.customer') }}</th>
                        <th>From</th>
                        <th>To</th>
                        <th>{{ __('ui.amount') }}</th>
                        <th>{{ __('ui.rate') }}</th>
                        <th>{{ __('ui.received') }}</th>
                        <th>{{ __('ui.profit') }}</th>
                        <th>{{ __('ui.date') }}</th>
                    </tr>
                </thead>

            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('vendor/moment/moment-2.29.4.min.js') }}"></script>
<script src="{{ asset('vendor/daterangepicker/daterangepicker.min.js') }}"></script>
<script>

    $('#filter_date').daterangepicker({
        autoUpdateInput: false,
        locale: { cancelLabel: 'Clear' }
    });

    $('#filter_date').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        table.ajax.reload();
    });

    $('#filter_date').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        table.ajax.reload();
    });

    let table = $('#reportTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: '{{ route('admin.reports.exchange.data') }}',
            data: function(d) {
                d.customer_account_id = $('#filter_customer').val();
                d.base_currency_id = $('#filter_base').val();
                d.target_currency_id = $('#filter_target').val();
                d.date_range = $('#filter_date').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'customer', name: 'customerAccount.name' },
            { data: 'base_currency', name: 'baseCurrency.code' },
            { data: 'target_currency', name: 'targetCurrency.code' },
            { data: 'amount', name: 'base_amount' },
            { data: 'rate', name: 'rate' },
            { data: 'received', name: 'target_amount' },
            { data: 'profit', name: 'target_profit' },
            { data: 'created_at', name: 'created_at' },
        ],
    });

    table.on('xhr', function() {
    const json = table.ajax.json();

    const $totalsCards = $('#totalsCards');
    $totalsCards.empty();

    if (json.totals_by_currency) {
        for (let currency in json.totals_by_currency) {
            const totals = json.totals_by_currency[currency];

            $totalsCards.append(`
                <div class="col-md-4">
                    <div class="card shadow border-0 text-white h-100" style="background: linear-gradient(135deg, #00264d, #005580); border-radius: 1rem;">
                        <div class="card-body d-flex flex-column justify-content-center align-items-start py-4 px-4">
                            <h5 class="fw-bold mb-3 text-info" style="font-size: 1.4rem;">${currency} Totals</h5>
                            <p class="mb-2" style="font-size: 1.2rem;"><strong>{{ __('ui.amount_colon') }}</strong> ${totals.amount} ${totals.symbol}</p>
                            <p class="mb-2" style="font-size: 1.2rem;"><strong>Received:</strong> ${totals.received} ${totals.profit_symbol}</p>
                            <p class="mb-0" style="font-size: 1.2rem;"><strong>Profit:</strong> ${totals.profit} ${totals.profit_symbol}</p>
                        </div>
                    </div>
                </div>
            `);
        }
    }
});


    $('#filter_customer, #filter_base, #filter_target').on('change', function() {
        table.ajax.reload();
    });

    $('#exportPdfBtn').on('click', function(e) {
        e.preventDefault();
        const params = new URLSearchParams({
            customer_account_id: $('#filter_customer').val() || '',
            base_currency_id: $('#filter_base').val() || '',
            target_currency_id: $('#filter_target').val() || '',
            date_range: $('#filter_date').val() || ''
        });
        const url = `{{ route('admin.reports.exchange.export.pdf') }}?${params.toString()}`;
        window.open(url, '_blank');
    });
</script>
@endsection
