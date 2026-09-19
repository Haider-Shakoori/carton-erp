{{-- resources/views/admin/customers/export-pdf.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Statement - {{ $customer->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #fff;
            padding: 30px;
            color: #1e293b;
            font-size: 12px;
        }

        .header {
            border-bottom: 3px solid #1a56db;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .company-name {
            font-size: 20px;
            font-weight: 700;
            color: #1a56db;
        }

        .document-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }

        .customer-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            flex-wrap: wrap;
            gap: 10px;
        }

        .info-group {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .info-group .label {
            font-size: 10px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .info-group .value {
            font-size: 12px;
            font-weight: 500;
            color: #1e293b;
        }

        .info-group .value .code {
            color: #1a56db;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 11px;
        }

        table thead {
            background: #f1f5f9;
        }

        table thead th {
            padding: 8px 12px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 2px solid #e2e8f0;
        }

        table tbody td {
            padding: 7px 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        table tbody tr:nth-child(even) {
            background: #fafbfc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-primary {
            color: #1a56db;
            font-weight: 600;
        }

        .text-danger {
            color: #991b1b;
            font-weight: 600;
        }

        .text-success {
            color: #065f46;
            font-weight: 600;
        }

        .badge {
            display: inline-block;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background: #fecaca;
            color: #991b1b;
        }

        .badge-primary {
            background: #dbeafe;
            color: #1a56db;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-secondary {
            background: #f1f5f9;
            color: #64748b;
        }

        .summary-box {
            margin-top: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 30px;
            flex-wrap: wrap;
        }

        .summary-item {
            text-align: right;
        }

        .summary-item .label {
            font-size: 10px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .summary-item .value {
            font-size: 18px;
            font-weight: 700;
        }

        .summary-item .value.positive {
            color: #1a56db;
        }

        .summary-item .value.negative {
            color: #991b1b;
        }

        .summary-item .value.zero {
            color: #94a3b8;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #94a3b8;
        }

        .no-data i {
            font-size: 40px;
            display: block;
            margin-bottom: 10px;
        }

        @page {
            margin: 15mm;
        }
    </style>
</head>

<body>

    {{-- Header --}}
    <div class="header">
        <div class="header-top">
            <div>
                <div class="company-name">{{ config('app.name', 'Your Company') }}</div>
                <div style="font-size: 10px; color: #94a3b8; margin-top: 3px;">
                    {{ config('app.address', '123 Business Street, City') }}
                </div>
            </div>
            <div class="document-title">{{ __('ui.customer_statement') }}</div>
        </div>
    </div>

    {{-- Customer Info --}}
    <div class="customer-info">
        <div class="info-group">
            <span class="label">{{ __('ui.customer') }}</span>
            <span class="value">{{ $customer->name }}</span>
        </div>
        <div class="info-group">
            <span class="label">{{ __('ui.code') }}</span>
            <span class="value code">{{ $customer->code }}</span>
        </div>
        <div class="info-group">
            <span class="label">{{ __('ui.currency') }}</span>
            <span class="value">{{ $currency->code ?? 'USD' }} - {{ $currency->name ?? 'US Dollar' }}</span>
        </div>
        <div class="info-group">
            <span class="label">{{ __('ui.statement_date') }}</span>
            <span class="value">{{ now()->format('F d, Y') }}</span>
        </div>
        @if ($customer->contact)
            <div class="info-group">
                <span class="label">{{ __('ui.contact') }}</span>
                <span class="value">{{ $customer->contact }}</span>
            </div>
        @endif
    </div>

    {{-- Transactions Table --}}
    @if ($transactions->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">{{ __('ui.date') }}</th>
                    <th style="width: 10%;">{{ __('ui.type') }}</th>
                    <th style="width: 40%;">{{ __('ui.description') }}</th>
                    <th style="width: 17%;" class="text-right">{{ __('ui.amount') }}</th>
                    <th style="width: 18%;" class="text-right">{{ __('ui.balance') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $transaction)
                    @php
                        $isCredit = $transaction->transaction_type === 'credit';
                        $amountClass = $isCredit ? 'text-primary' : 'text-danger';
                        $sign = $isCredit ? '+' : '-';
                        $balanceClass = ($transaction->running_balance ?? 0) >= 0 ? 'text-primary' : 'text-danger';
                    @endphp
                    <tr>
                        <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <span class="badge {{ $isCredit ? 'badge-primary' : 'badge-danger' }}">
                                {{ ucfirst($transaction->transaction_type) }}
                            </span>
                        </td>
                        <td>{{ $transaction->description ?? 'N/A' }}</td>
                        <td class="text-right {{ $amountClass }}">
                            {{ $sign }} {{ number_format($transaction->amount, 2) }}
                        </td>
                        <td class="text-right {{ $balanceClass }}">
                            {{ number_format($transaction->running_balance ?? 0, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Summary --}}
        <div class="summary-box">
            <div class="summary-item">
                <div class="label">{{ __('ui.total_credit') }}</div>
                <div class="value positive">
                    {{ number_format($transactions->where('transaction_type', 'credit')->sum('amount'), 2) }}
                </div>
            </div>
            <div class="summary-item">
                <div class="label">{{ __('ui.total_debit') }}</div>
                <div class="value negative">
                    {{ number_format($transactions->where('transaction_type', 'debit')->sum('amount'), 2) }}
                </div>
            </div>
            <div class="summary-item">
                <div class="label">{{ __('ui.net_balance') }}</div>
                <div class="value {{ $balance >= 0 ? 'positive' : 'negative' }}">
                    {{ number_format($balance, 2) }}
                </div>
            </div>
        </div>
    @else
        <div class="no-data">
            <p style="font-size: 16px;">📄</p>
            <p>No transactions found for this customer in {{ $currency->code ?? 'USD' }}.</p>
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y h:i A') }}</p>
        <p style="margin-top: 3px;">This is a computer-generated statement. No signature is required.</p>
    </div>

</body>

</html>
