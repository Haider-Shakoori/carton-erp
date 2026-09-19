<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Journal PDF Export</title>
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
    table-layout: fixed;
    word-wrap: break-word;
}

    thead {
        display: table-header-group;
    }

    tfoot {
        display: table-footer-group;
    }

    tr {
        page-break-inside: avoid;
    }

td, th {
    padding: 8px 6px;
    border: 1px solid #e5e7eb;
    vertical-align: top;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
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

    .footer {
        display: flex;
        justify-content: space-between;
        margin-top: 25px;
        padding-top: 10px;
        border-top: 2px solid #e5e7eb;
        font-size: 10px;
        color: #6b7280;
    }

    .footer-col {
        width: 33.33%;
    }

    .footer-col span {
        display: block;
        margin-bottom: 4px;
    }

    .page-number {
        text-align: right;
        font-size: 10px;
        color: #9ca3af;
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
            <div class="text-sm">Financial Journal Report</div>
        </div>
    </div>

    <div class="report-title">Transaction Journal</div>

    <table>
        <thead>
    <tr>
        <th style="width: 3%;" class="text-center">#</th>
        <th style="width: 13%;">{{ __('ui.date') }}</th>
        <th style="width: 20%;">{{ __('ui.account') }}</th>
        <th style="width: 28%;">Note</th>
        <th style="width: 10%;">{{ __('ui.type') }}</th>
        <th style="width: 13%;">{{ __('ui.operation') }}</th>
        <th style="width: 13%;" class="text-right">{{ __('ui.amount') }}</th>
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

    <table style="width: 100%; margin-top: 20px; font-size: 10px; border-top: 1px solid #e5e7eb; padding-top: 8px;">
    <tr>
        <td style="width: 33.33%; vertical-align: top;">
            <strong>{{ __('ui.phone_colon') }}</strong> {{ $setting->contact }}
        </td>
        <td style="width: 33.33%; text-align: center; vertical-align: top;">
            <strong>{{ __('ui.email_colon') }}</strong> {{ $setting->email }}
        </td>
        <td style="width: 33.33%; text-align: right; vertical-align: top;">
            <strong>{{ __('ui.address_colon') }}</strong> {{ $setting->address }}
        </td>
    </tr>
</table>

</div>
</body>
</html>
