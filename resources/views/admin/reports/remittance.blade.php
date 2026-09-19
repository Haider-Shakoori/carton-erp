@extends('layouts.admin.base')

@section('title', 'Remittance Report')

@section('css')
<link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}" />
<link href="{{ asset('vendor/select2/select2.min.css') }}" rel="stylesheet" />
<style>
    .totals-card {
        border: 1px solid #ddd;
        border-radius: 12px;
        padding: 10px 15px;
        background-color: #f8f9fa;
        min-width: 200px;
    }
</style>
@endsection

@section('content')
<div class="card rounded-4 border-0 shadow">
    <div class="card-header">
        <h4 class="card-title mb-3">{{ __('ui.remittance_report') }}</h4>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">{{ __('ui.date_range') }}</label>
                <input type="text" id="filter_date" class="form-control" placeholder="{{ __('ui.select_date_range') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">👤 Customer</label>
                <select id="filter_customer" class="select2 form-select">
                    <option value="">{{ __('ui.all_customers') }}</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}">[{{ $c->code }}] - {{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('ui.currency_label') }}</label>
                <select id="filter_currency" class="select2 form-select">
                    <option value="">{{ __('ui.all_currencies') }}</option>
                    @foreach ($currencies as $currency)
                        <option value="{{ $currency->id }}">{{ $currency->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">📌 Status</label>
                <select id="filter_status" class="form-select">
                    <option value="">{{ __('ui.all_status') }}</option>
                    <option value="pending">{{ __('ui.pending') }}</option>
                    <option value="processed">{{ __('ui.processed') }}</option>
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-end mb-2">
            <a href="#" id="exportPdfBtn" class="btn btn-sm btn-danger">{{ __('ui.export_pdf') }}</a>
        </div>
    </div>
    <div class="card-body">
        <div id="totalsCards" class="d-flex flex-wrap gap-3 mb-3"></div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle" id="reportTable" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('ui.customer') }}</th>
                        <th>{{ __('ui.currency') }}</th>
                        <th>{{ __('ui.amount') }}</th>
                        <th>{{ __('ui.status') }}</th>
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
<script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
<script>
    $('.select2').select2({ placeholder: 'Select an option', allowClear: true });

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
            url: '{{ route('admin.reports.remittance.data') }}',
            data: function(d) {
                d.account_id = $('#filter_customer').val();
                d.currency_id = $('#filter_currency').val();
                d.status = $('#filter_status').val();
                d.date_range = $('#filter_date').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'customer', name: 'account.name' },
            { data: 'currency', name: 'currency.code' },
            { data: 'amount', name: 'amount' },
            { data: 'status', name: 'status' },
            { data: 'date', name: 'created_at' },
        ],
        drawCallback: function(settings) {
            const json = settings.json;
            const $totalsCards = $('#totalsCards');
            $totalsCards.html('');

            if (json.totals_by_currency) {
                for (let currency in json.totals_by_currency) {
                    let row = json.totals_by_currency[currency];

                    $totalsCards.append(`
                        <div class="col-md-4">
                            <div class="card shadow border-0 text-white h-100" style="background: linear-gradient(135deg, #00264d, #005580); border-radius: 1rem;">
                                <div class="card-body d-flex flex-column justify-content-center align-items-start py-4 px-4">
                                    <h5 class="fw-bold mb-3 text-info" style="font-size: 1.4rem;">${currency} Totals</h5>
                                    <p class="mb-2" style="font-size: 1.2rem;"><strong>Total Amount:</strong> ${row.amount} ${row.symbol}</p>
                                    <p class="mb-0" style="font-size: 1.2rem;"><strong>Count:</strong> ${row.count}</p>
                                </div>
                            </div>
                        </div>
                    `);
                }
            }
        }

    });

    $('#filter_customer, #filter_currency, #filter_status').on('change', function() {
        table.ajax.reload();
    });

    $('#exportPdfBtn').on('click', function(e) {
        e.preventDefault();
        const params = new URLSearchParams({
            account_id: $('#filter_customer').val() || '',
            currency_id: $('#filter_currency').val() || '',
            status: $('#filter_status').val() || '',
            date_range: $('#filter_date').val() || ''
        });
        const url = `{{ route('admin.reports.remittance.export.pdf') }}?${params.toString()}`;
        window.open(url, '_blank');
    });
</script>
@endsection
