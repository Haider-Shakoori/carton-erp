@extends('layouts.admin.base')

@section('title', __('Dashboard') . ' | ' . config('app.name'))

@section('css')
    <link href="{{ asset('vendor/apexcharts/apexcharts-3.35.0.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}" />
    <style>
        .dashboard-card {
            border-radius: 1rem;
            box-shadow: 0 3px 15px rgb(0 0 0 / .05);
            border: none;
            height: 100%;
            transition: 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            justify-content: space-between
        }

        .dashboard-card:hover {
            transform: translateY(-4px)
        }

        .stat-card {
            padding: 1.25rem
        }

        .stat-title {
            font-size: .85rem;
            color: #6c757d;
            font-weight: 600
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 5px
        }

        .chart-container {
            height: 300px;
            min-height: 300px
        }

        .transaction-badge {
            font-size: 12px;
            padding: 5px 10px;
            border-radius: 20px
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 1rem
        }

        .card-body:empty::before {
            content: 'No data available';
            color: #aaa;
            text-align: center;
            width: 100%;
            display: block;
            padding: 3rem 0;
            font-weight: 500
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #007bff, #6610f2);
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #28a745, #218838);
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }

        .bg-gradient-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }

        .text-shadow {
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
        }

        .card .card-body {
            font-size: 0.95rem;
        }

        .card .card-body strong {
            font-weight: 600;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-4">

        <!-- Top Stats Row -->
        <div class="row g-4">
            @foreach ([['title' => 'Total Customers', 'count' => $totalCustomers, 'color' => '#1abc9c'], ['title' => 'Total Saraf', 'count' => $totalSaraf, 'color' => '#3498db'], ['title' => 'Total Banks', 'count' => $totalBanks, 'color' => '#9b59b6'], ['title' => 'Total Safe Accounts', 'count' => $totalSafe, 'color' => '#e67e22']] as $card)
                <div class="col-xl-3 col-md-6">
                    <div class="card dashboard-card">
                        <div class="card-body stat-card" style="border-left: 4px solid {{ $card['color'] }}">
                            <div class="stat-title">{{ $card['title'] }}</div>
                            <div class="stat-value">{{ $card['count'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Currency Wise Balance Comparisons -->
        <h6 class="section-title">Currency Balances Overview</h6>

        <div class="row g-4">
            {{-- Safe Accounts --}}
            <div class="col-xl-3 col-md-6">
                <div class="card bg-gradient-primary rounded-4 border-0 text-white shadow-lg">
                    <div class="card-body">
                        <h6 class="text-uppercase fw-bold fs-6 text-shadow mb-3 text-white">Safe Accounts</h6>
                        @foreach ($safeTotals as $currency => $amount)
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold fs-6 text-shadow">{{ $currency }}</span>
                                <strong class="fs-6 text-shadow">{{ number_format($amount, 2) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Customer Credits --}}
            <div class="col-xl-3 col-md-6">
                <div class="card bg-gradient-success rounded-4 border-0 text-white shadow-lg">
                    <div class="card-body">
                        <h6 class="text-uppercase fw-bold fs-6 mb-3 text-indigo-600">Customers/Saraf Credits</h6>
                        @foreach ($creditors as $currency => $amount)
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold fs-6 text-shadow">{{ $currency }}</span>
                                <strong class="fs-6 text-shadow">{{ number_format($amount, 2) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Customer Debits --}}
            <div class="col-xl-3 col-md-6">
                <div class="card bg-gradient-warning rounded-4 border-0 text-white shadow-lg">
                    <div class="card-body">
                        <h6 class="text-uppercase fw-bold fs-6 mb-3">Customers/Saraf Debits</h6>
                        @foreach ($debtors as $currency => $amount)
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold fs-6 text-shadow">{{ $currency }}</span>
                                <strong class="fs-6 text-shadow">{{ number_format($amount, 2) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Profits --}}
            @can('view profit')
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-gradient-danger rounded-4 border-0 text-white shadow-lg">
                        <div class="card-body">
                            <h6 class="text-uppercase fw-bold fs-6 mb-3">Profit Overview</h6>
                            @foreach ($profits as $currency => $amount)
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-semibold fs-6 text-shadow">{{ $currency }}</span>
                                    <strong class="fs-6 text-shadow">{{ number_format($amount, 2) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endcan
        </div>

        <!-- Transaction Chart with Filters -->
        <h6 class="section-title">Transaction Chart</h6>
        <div class="card dashboard-card mb-4">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>{{ __('ui.currency_colon') }}</label>
                        <select id="filter-currency" class="form-select">
                            <option value="">{{ __('ui.all') }}</option>
                            @foreach ($currencies as $cur)
                                <option value="{{ $cur->id }}">{{ $cur->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Account Type:</label>
                        <select id="filter-type" class="form-select">
                            <option value="">{{ __('ui.all') }}</option>
                            <option value="1">{{ __('ui.customer') }}</option>
                            <option value="2">{{ __('ui.saraf') }}</option>
                            <option value="3">Bank</option>
                            <option value="4">Safe</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Date Range:</label>
                        <input type="text" class="form-control" id="filter-date">
                    </div>
                </div>
                <div id="transactionChart" class="chart-container"></div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Monthly Profit --}}
            <h6 class="section-title">Profit Chart</h6>
            <div class="col-lg-12">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div id="profitChart" class="chart-container"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exchange Graphs -->
        <h6 class="section-title">Exchange Statistics</h6>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card dashboard-card">
                    <div class="card-header">Exchange Sales</div>
                    <div class="card-body">
                        <div id="exchangeChart" class="chart-container"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card dashboard-card">
                    <div class="card-header">Exchange Purchases</div>
                    <div class="card-body">
                        <div id="exchangePurchaseChart" class="chart-container"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Accounts -->
        <h6 class="section-title">Top Accounts by Volume</h6>
        <div class="row g-4">
            @foreach (['customers' => 'Customers', 'saraf' => 'Saraf', 'banks' => 'Banks'] as $key => $label)
                <div class="col-xl-4">
                    <div class="card dashboard-card">
                        <div class="card-header">Top {{ $label }}</div>
                        <div class="card-body">
                            @foreach ($top[$key] as $acc)
                                <div class="d-flex justify-content-between mb-2">
                                    <span>{{ $acc->name }}</span>
                                    <span class="badge bg-primary">{{ number_format($acc->total_volume, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pending Remittances -->
        <h6 class="section-title">Latest Pending Remittances</h6>
        <div class="card rounded-3 shadow-sm">
            <div class="card-body pb-0">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="card bg-gradient-dark text-white">
                            <div class="card-body">
                                <h6 class="card-title text-white">Total Pending Remittances</h6>
                                @foreach ($totalPendingRemittanceAmount as $currencyId => $amount)
                                    <p class="fs-5 fw-bold mb-0">{{ $currencies->firstWhere('id', $currencyId)->symbol }} {{ number_format($amount, 2) }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table-bordered table-hover table align-middle" id="remittance-table">
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
                        <tbody>
                            @foreach ($pendingRemittances as $r)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="badge bg-primary rounded-pill text-white">
                                            <i class="fas fa-user-circle me-1"></i> {{ $r->account->code }}
                                        </span> {{ $r->account->name }}
                                    </td>
                                    <td>{{ $r->currency->code }}</td>
                                    <td>{{ number_format($r->amount, 2) }}</td>
                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            {{ ucfirst($r->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $r->created_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('js')

    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/moment/moment-2.18.1.min.js') }}"></script>
    <script src="{{ asset('vendor/daterangepicker/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('vendor/apexcharts/apexcharts-7.1.0.min.js') }}"></script>

    <script>
        let transactionChartObj = null;
        let exchangeChartObj = null;
        let exchangePurchaseChartObj = null;
        let profitChartObj = null;

        $(document).ready(function() {
            $("#remittance-table").DataTable();

            // currency / account type change
            $("#filter-currency, #filter-type").on("change", function() {
                loadTransactionChart();
                loadProfitChart();
            });

            loadTransactionChart();
            loadExchangeChart();
            loadExchangePurchaseChart();
            loadProfitChart();
        });

        function loadTransactionChart() {
            $.get("/admin/dashboard/transactions", {
                currency_id: $("#filter-currency").val(),
                account_type: $("#filter-type").val()
            }, function(res) {
                if (transactionChartObj) transactionChartObj.destroy();

                transactionChartObj = new ApexCharts(document.querySelector("#transactionChart"), {
                    chart: {
                        type: "area",
                        height: 320
                    },
                    series: [{
                            name: "Credit",
                            data: res.credit
                        },
                        {
                            name: "Debit",
                            data: res.debit
                        }
                    ],
                    colors: ["#3B82F6", "#F59E0B"],
                    xaxis: {
                        categories: res.labels
                    },
                    stroke: {
                        curve: "smooth",
                        width: 3
                    },
                    tooltip: {
                        y: {
                            formatter: val => val.toLocaleString()
                        }
                    }
                });
                transactionChartObj.render();
            });
        }

        function loadProfitChart() {
    $.get("/admin/dashboard/profit-chart", {
        currency_id: $("#filter-currency").val()
    }, function (res) {
        if (profitChartObj) profitChartObj.destroy();

        profitChartObj = new ApexCharts(document.querySelector("#profitChart"), {
            chart: {
                type: "bar",
                height: 300
            },
            series: [{
                name: "Profit",
                data: res.profits
            }],
            colors: ["#28a745"],
            xaxis: {
                categories: res.labels,
                tickPlacement: "between",           // 👈 IMPORTANT
                labels: {
                    rotate: -60,                    // 👈 Half-vertical
                    style: {
                        fontSize: '11px'
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: val => val.toLocaleString()
                }
            }
        });

        profitChartObj.render();
    });
}


        function loadExchangeChart() {
            $.get("/admin/dashboard/exchange-chart", function(res) {
                if (exchangeChartObj) exchangeChartObj.destroy();
                exchangeChartObj = new ApexCharts(document.querySelector("#exchangeChart"), {
                    chart: {
                        type: "bar",
                        height: 300
                    },
                    series: [{
                        name: "Exchange Sales",
                        data: res.data
                    }],
                    xaxis: {
                        categories: res.labels
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: val => val
                    }
                });
                exchangeChartObj.render();
            });
        }

        function loadExchangePurchaseChart() {
            $.get("/admin/dashboard/exchange-purchase-chart", function(res) {
                if (exchangePurchaseChartObj) exchangePurchaseChartObj.destroy();
                exchangePurchaseChartObj = new ApexCharts(document.querySelector("#exchangePurchaseChart"), {
                    chart: {
                        type: "bar",
                        height: 300
                    },
                    series: [{
                        name: "Exchange Purchases",
                        data: res.data
                    }],
                    xaxis: {
                        categories: res.labels
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: val => val
                    }
                });
                exchangePurchaseChartObj.render();
            });
        }
    </script>
@endsection
