<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ __('ui.exchange_report') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            width: 120px;
            margin-bottom: 10px;
        }
        .report-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .currency-header {
            margin-top: 30px;
            font-size: 16px;
            font-weight: bold;
            color: #444;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            border: 1px solid #ccc;
            text-align: center;
        }
        th {
            background-color: #f5f5f5;
        }
        .totals {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 50px;
            text-align: right;
            font-size: 12px;
            border-top: 1px solid #999;
            padding-top: 10px;
        }
    </style>
</head>
<body>
<div class="header">
    <img src="{{ public_path('logo.png') }}" alt="Logo">
    <div class="report-title">{{ __('ui.exchange_report') }}</div>
</div>

@foreach($data as $currency => $records)
    <div class="currency-header">Base Currency: {{ $currency }}</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('ui.customer') }}</th>
                <th>{{ __('ui.amount') }}</th>
                <th>To</th>
                <th>{{ __('ui.rate') }}</th>
                <th>{{ __('ui.received') }}</th>
                <th>{{ __('ui.profit') }}</th>
                <th>{{ __('ui.date') }}</th>
            </tr>
        </thead>
        <tbody>
        @php
            $i = 1;
            $total_amount = $total_received = $total_profit = 0;
        @endphp
        @foreach($records as $row)
            @php
                $total_amount += $row->base_amount;
                $total_received += $row->target_amount;
                $total_profit += $row->target_profit;
            @endphp
            <tr>
                <td>{{ $i++ }}</td>
                <td>{{ $row->customerAccount->name }} ({{ $row->customerAccount->code }})</td>
                <td>{{ number_format($row->base_amount, 2) }} {{ $currency }}</td>
                <td>{{ $row->targetCurrency->code }}</td>
                <td>{{ $row->rate }}</td>
                <td>{{ number_format($row->target_amount, 2) }} {{ $row->targetCurrency->code }}</td>
                <td>{{ number_format($row->target_profit, 2) }} {{ $row->targetCurrency->code }}</td>
                <td>{{ $row->created_at->format('Y-m-d') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr class="totals">
                <td colspan="2">{{ __('ui.total') }}</td>
                <td>{{ number_format($total_amount, 2) }} {{ $currency }}</td>
                <td>-</td>
                <td>-</td>
                <td>{{ number_format($total_received, 2) }} {{ $row->targetCurrency->code }}</td>
                <td>{{ number_format($total_profit, 2) }} {{ $row->targetCurrency->code }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
@endforeach

<div class="footer">
    Generated on: {{ now()->format('Y-m-d H:i') }}
</div>
</body>
</html>
