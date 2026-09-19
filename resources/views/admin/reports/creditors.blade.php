@extends('layouts.admin.base')

@section('title', 'Creditors Report')

@section('css')
<link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}" />
<style>
    .totals-card {
        border-radius: 16px;
        padding: 20px;
        background: linear-gradient(to right, #2563eb, #1e3a8a);
        color: #fff;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        width: 280px;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.3s ease;
    }
    .totals-card:hover {
        transform: translateY(-4px);
    }
    .totals-card h6 {
        font-size: 1rem;
        margin-bottom: 0.5rem;
        color: #93c5fd;
    }
    .totals-card div {
        font-size: 0.88rem;
    }
    .totals-chart {
        height: 80px;
        margin-top: 10px;
    }
</style>
@endsection

@section('content')
<div class="card rounded-4 border-0 shadow">
    <div class="card-header">
        <h4 class="card-title mb-3">Creditors Report</h4>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">👤 Sub Category</label>
                <select id="filter_sub" class="select2 form-select">
                    <option value="">{{ __('ui.all_sub_categories') }}</option>
                    @foreach($subCategories as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('ui.currency_label') }}</label>
                <select id="filter_currency" class="select2 form-select">
                    <option value="">{{ __('ui.all_currencies') }}</option>
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}">{{ $currency->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <a href="#" id="exportPdfBtn" class="btn btn-sm btn-danger w-100" style="height: 47px;">{{ __('ui.export_pdf') }}</a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3 mb-4" id="totalsCards"></div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle" id="creditorsTable" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('ui.account') }}</th>
                        <th>{{ __('ui.balances') }}</th>
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
<script src="{{ asset('vendor/chartjs/chart-4.5.1.min.js') }}"></script>
<script>
    function renderMiniChart(canvasId, value) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Credit'],
                datasets: [{
                    data: [Math.abs(value), 0],
                    backgroundColor: ['#93c5fd', '#e2e8f0'],
                    borderWidth: 0,
                }]
            },
            options: {
                cutout: '75%',
                plugins: { legend: { display: false } },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    let table = $('#creditorsTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: '{{ route('admin.reports.creditors.data') }}',
            data: function(d) {
                d.sub_category_id = $('#filter_sub').val();
                d.currency_id = $('#filter_currency').val();
            },
            dataSrc: function(json) {
                const $totalsCards = $('#totalsCards');
                $totalsCards.empty();
                if (json.totals_by_currency) {
                    let index = 0;
                    for (let currency in json.totals_by_currency) {
                        const canvasId = 'miniChart' + index;
                        const row = json.totals_by_currency[currency];
                        $totalsCards.append(`
                            <div class="totals-card">
                                <h6>💱 ${currency}</h6>
                                <div><strong>Total Credit:</strong> ${row.balance}</div>
                                <div><strong>{{ __('ui.accounts_colon') }}</strong> ${row.count}</div>
                                <div class="totals-chart">
                                    <canvas id="${canvasId}"></canvas>
                                </div>
                            </div>
                        `);
                        setTimeout(() => renderMiniChart(canvasId, parseFloat(row.balance.replace(/,/g, ''))), 100);
                        index++;
                    }
                }
                return json.data;
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'account', name: 'account.name' },
            { data: 'balances', name: 'balances' },
        ]
    });

    $('#filter_sub, #filter_currency').on('change', function() {
        table.ajax.reload();
    });

    $('#exportPdfBtn').on('click', function(e) {
        e.preventDefault();
        const sub = $('#filter_sub').val() || '';
        const currency = $('#filter_currency').val() || '';
        const url = `{{ route('admin.reports.creditors.export.pdf') }}?sub_category_id=${sub}&currency_id=${currency}`;
        window.open(url, '_blank');
    });
</script>
@endsection
