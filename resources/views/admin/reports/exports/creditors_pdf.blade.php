<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ __('ui.creditors_summary_report') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #333;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            width: 100px;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-top: 10px;
        }
        .summary-boxes {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 25px;
        }
        .summary-card {
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 12px;
            min-width: 180px;
            background-color: #f9f9f9;
        }
        .summary-card h4 {
            margin: 0 0 6px 0;
            font-size: 16px;
            color: #222;
        }
        .summary-card div {
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 8px 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #efefef;
        }
        .footer {
            margin-top: 40px;
            text-align: right;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('logo.png') }}" alt="Logo">
        <div class="title">{{ __('ui.creditors_summary_report') }}</div>
    </div>

    <div class="summary-boxes">
        @foreach($summary as $code => $meta)
            <div class="summary-card">
                <h4>{{ $code }}</h4>
                <div><strong>{{ __('ui.total_debt_colon') }}</strong> {{ $meta['balance'] }}</div>
                <div><strong>{{ __('ui.accounts_colon') }}</strong> {{ $meta['count'] }}</div>
            </div>
        @endforeach
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('ui.account') }}</th>
                <th>{{ __('ui.balances') }}</th>
            </tr>
        </thead>
        <tbody>
        @php $i = 1; @endphp
        @foreach($data as $item)
            <tr>
                <td>{{ $i++ }}</td>
                <td>{{ $item['account']->name }} ({{ $item['account']->code }})</td>
                <td>
                    @foreach($item['balances'] as $currency => $amount)
                        <div><strong>{{ $currency }}:</strong> {{ $amount }}</div>
                    @endforeach
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
