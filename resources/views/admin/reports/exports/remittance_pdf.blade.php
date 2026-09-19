<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ __('ui.remittance_report') }}</title>
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
    <div class="report-title">{{ __('ui.remittance_report') }}</div>
</div>

@foreach($data as $currency => $records)
    <div class="currency-header">Currency: {{ $currency }}</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('ui.customer') }}</th>
                <th>{{ __('ui.amount') }}</th>
                <th>Bank</th>
                <th>{{ __('ui.status') }}</th>
                <th>{{ __('ui.date') }}</th>
            </tr>
        </thead>
        <tbody>
        @php
            $i = 1;
            $total_amount = 0;
        @endphp
        @foreach($records as $row)
            @php $total_amount += $row->amount; @endphp
            <tr>
                <td>{{ $i++ }}</td>
                <td>{{ $row->account->name }} ({{ $row->account->code }})</td>
                <td>{{ number_format($row->amount, 2) }}</td>
                <td>{{ $row->bank_name }}</td>
                <td>{{ ucfirst($row->status) }}</td>
                <td>{{ $row->created_at->format('Y-m-d') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr class="totals">
                <td colspan="2">{{ __('ui.total') }}</td>
                <td>{{ number_format($total_amount, 2) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
@endforeach

<div class="footer">
    Generated on: {{ now()->format('Y-m-d H:i') }}
</div>
</body>
</html>
