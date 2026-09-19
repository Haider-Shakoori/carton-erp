<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Transactions PDF Export</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 0;
            padding: 0;
        }

        .page-container {
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .logo {
            max-height: 45px;
        }

        .company-info {
            text-align: right;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
        }

        .date-stamp {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 5px;
            text-align: right;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            text-align: center;
            margin: 20px 0 10px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        th {
            background-color: #3b82f6;
            color: #ffffff;
            font-size: 11px;
            padding: 8px 6px;
            border: 1px solid #3b82f6;
            text-transform: uppercase;
        }

        td {
            padding: 8px 6px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .credit {
            color: #15803d;
            font-weight: bold;
        }

        .debit {
            color: #b91c1c;
            font-weight: bold;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .footer-table {
            width: 100%;
            margin-top: 20px;
            font-size: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
        }

        .footer-table td {
            vertical-align: top;
        }
    </style>
</head>
<body>
<div class="page-container">
    <div class="date-stamp">Generated on: {{ now()->format('Y-m-d H:i') }}</div>

    <div class="header">
        <img src="{{ public_path('images/' . $setting->logo) }}" class="logo" alt="Logo">
        <div class="company-info">
            <div class="company-name">{{ $setting->company_name }}</div>
            <div class="text-sm">Transaction Report</div>
        </div>
    </div>

    <div class="report-title">All Transactions</div>

    <table>
        <thead>
        <tr>
            <th class="text-center">#</th>
            <th>{{ __('ui.date') }}</th>
            <th>{{ __('ui.account') }}</th>
            <th>Note</th>
            <th>{{ __('ui.type') }}</th>
            <th>{{ __('ui.operation') }}</th>
            <th class="text-right">{{ __('ui.amount') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($transactions as $i => $txn)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $txn->created_at->format('Y-m-d H:i') }}</td>
                <td>[{{ $txn->account->code ?? '-' }}] - {{ $txn->account->name ?? '-' }}</td>
                <td>{{ $txn->note }}</td>
                <td class="{{ $txn->transaction_type === 'credit' ? 'credit' : 'debit' }}">
                    {{ ucfirst($txn->transaction_type) }}
                </td>
                <td>{{ ucfirst($txn->type) }}</td>
                <td class="text-right">{{ number_format($txn->amount, 2) }} {{ $txn->currency->code ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="footer-table">
        <tr>
            <td style="width: 33.33%;"><strong>{{ __('ui.phone_colon') }}</strong> {{ $setting->contact }}</td>
            <td style="width: 33.33%; text-align: center;"><strong>{{ __('ui.email_colon') }}</strong> {{ $setting->email }}</td>
            <td style="width: 33.33%; text-align: right;"><strong>{{ __('ui.address_colon') }}</strong> {{ $setting->address }}</td>
        </tr>
    </table>
</div>
</body>
</html>
