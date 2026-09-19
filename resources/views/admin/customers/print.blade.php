{{-- resources/views/admin/customers/print.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Statement - {{ $customer->name }}</title>
    <style>
        @import url('{{ asset('vendor/fonts/inter/inter-800.css') }}');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: #ffffff;
            padding: 30px 35px;
            color: #1e293b;
            font-size: 12px;
            line-height: 1.6;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 20px;
            border-bottom: 3px solid #1a56db;
            margin-bottom: 25px;
        }

        .header-left .company-name {
            font-size: 22px;
            font-weight: 800;
            color: #1a56db;
            letter-spacing: -0.5px;
        }

        .header-left .company-tagline {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        .header-right {
            text-align: right;
        }

        .header-right .doc-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
        }

        .header-right .doc-subtitle {
            font-size: 11px;
            color: #64748b;
        }

        .header-right .doc-number {
            font-size: 13px;
            font-weight: 600;
            color: #1a56db;
            margin-top: 4px;
        }

        .customer-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 22px;
            margin-bottom: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px 20px;
        }

        .customer-card .field {
            display: flex;
            flex-direction: column;
        }

        .customer-card .field .label {
            font-size: 9px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .customer-card .field .value {
            font-size: 13px;
            font-weight: 500;
            color: #0f172a;
            margin-top: 2px;
        }

        .customer-card .field .value .highlight {
            color: #1a56db;
            font-weight: 600;
        }

        .table-wrapper {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            margin: 20px 0 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        table thead {
            background: #f1f5f9;
        }

        table thead th {
            padding: 10px 16px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        table tbody td {
            padding: 9px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        table tbody tr:last-child td {
            border-bottom: none;
        }

        table tbody tr:hover {
            background: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-credit {
            background: #dbeafe;
            color: #1a56db;
        }

        .badge-debit {
            background: #fecaca;
            color: #991b1b;
        }

        .amount-credit {
            color: #1a56db;
            font-weight: 600;
        }

        .amount-debit {
            color: #991b1b;
            font-weight: 600;
        }

        .balance-positive {
            color: #1a56db;
            font-weight: 700;
        }

        .balance-negative {
            color: #991b1b;
            font-weight: 700;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 22px;
            margin-top: 20px;
        }

        .summary-item {
            text-align: center;
        }

        .summary-item .label {
            font-size: 9px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-item .value {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 4px;
        }

        .summary-item .value.positive {
            color: #1a56db;
        }

        .summary-item .value.negative {
            color: #991b1b;
        }

        .footer {
            margin-top: 30px;
            padding-top: 18px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
            color: #94a3b8;
        }

        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }

        .no-data .icon {
            font-size: 48px;
            display: block;
            margin-bottom: 12px;
        }

        .no-data p {
            font-size: 14px;
            color: #64748b;
        }

        .no-print {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        .no-print button {
            padding: 10px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }

        .no-print .btn-print {
            background: #1a56db;
            color: #ffffff;
            border: none;
        }

        .no-print .btn-print:hover {
            background: #1e3a5f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 86, 219, 0.3);
        }

        .no-print .btn-close {
            background: #ffffff;
            color: #1e293b;
            border: 1px solid #e2e8f0;
        }

        .no-print .btn-close:hover {
            background: #f1f5f9;
        }

        @page {
            margin: 10mm 12mm;
            size: A4 portrait;
        }

        @media print {
            body {
                padding: 15px 20px;
                font-size: 11px;
            }

            .no-print {
                display: none !important;
            }

            .customer-card {
                background: #f8fafc !important;
                border: 1px solid #e2e8f0 !important;
            }

            .summary-grid {
                background: #f8fafc !important;
                border: 1px solid #e2e8f0 !important;
            }

            .table-wrapper {
                border: 1px solid #e2e8f0 !important;
            }

            table thead {
                background: #f1f5f9 !important;
            }

            table tbody tr:hover {
                background: transparent !important;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .header {
                flex-direction: column;
                gap: 10px;
            }

            .header-right {
                text-align: left;
                width: 100%;
            }

            .customer-card {
                grid-template-columns: 1fr 1fr;
            }

            .summary-grid {
                grid-template-columns: 1fr 1fr;
            }

            .no-print {
                flex-direction: column;
                align-items: center;
            }

            .no-print button {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="header-left">
            <div class="company-name">{{ config('app.name', 'Your Company') }}</div>
            <div class="company-tagline">{{ config('app.address', '123 Business Street, City, Country') }}</div>
        </div>
        <div class="header-right">
            <div class="doc-title">Statement of Account</div>
            <div class="doc-subtitle">{{ __('ui.customer_statement') }}</div>
            <div class="doc-number">#{{ $customer->code }}-{{ now()->format('Ymd') }}</div>
        </div>
    </div>

    <div class="customer-card">
        <div class="field">
            <span class="label">{{ __('ui.customer') }}</span>
            <span class="value">{{ $customer->name }}</span>
        </div>
        <div class="field">
            <span class="label">{{ __('ui.code') }}</span>
            <span class="value"><span class="highlight">{{ $customer->code }}</span></span>
        </div>
        <div class="field">
            <span class="label">{{ __('ui.currency') }}</span>
            <span class="value">{{ $currency->code ?? 'USD' }}</span>
        </div>
        <div class="field">
            <span class="label">{{ __('ui.statement_date') }}</span>
            <span class="value">{{ now()->format('F d, Y') }}</span>
        </div>
        @if ($customer->contact)
            <div class="field">
                <span class="label">{{ __('ui.contact') }}</span>
                <span class="value">{{ $customer->contact }}</span>
            </div>
        @endif
        @if ($customer->email)
            <div class="field">
                <span class="label">{{ __('ui.email') }}</span>
                <span class="value">{{ $customer->email }}</span>
            </div>
        @endif
    </div>

    @if ($transactions->isNotEmpty())
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width: 13%;">{{ __('ui.date') }}</th>
                        <th style="width: 10%;">{{ __('ui.type') }}</th>
                        <th style="width: 37%;">{{ __('ui.description') }}</th>
                        <th style="width: 10%;" class="text-center">Ref</th>
                        <th style="width: 15%;" class="text-right">{{ __('ui.amount') }}</th>
                        <th style="width: 15%;" class="text-right">{{ __('ui.balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $runningBalance = 0;
                    @endphp
                    @foreach ($transactions as $transaction)
                        @php
                            $isCredit = $transaction->transaction_type === 'credit';
                            $amount = $isCredit ? $transaction->amount : -$transaction->amount;
                            $runningBalance += $amount;
                            $sign = $isCredit ? '+' : '-';
                        @endphp
                        <tr>
                            <td>{{ $transaction->created_at->format('d M Y') }}</td>
                            <td>
                                <span class="badge {{ $isCredit ? 'badge-credit' : 'badge-debit' }}">
                                    {{ $isCredit ? 'Credit' : 'Debit' }}
                                </span>
                            </td>
                            <td>{{ $transaction->description ?? '—' }}</td>
                            <td class="text-center">{{ $transaction->id }}</td>
                            <td class="text-right amount-{{ $isCredit ? 'credit' : 'debit' }}">
                                {{ $sign }}{{ number_format($transaction->amount, 2) }}
                            </td>
                            <td class="text-right balance-{{ $runningBalance >= 0 ? 'positive' : 'negative' }}">
                                {{ number_format($runningBalance, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="summary-grid">
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
            <div class="summary-item">
                <div class="label">{{ __('ui.transactions') }}</div>
                <div class="value positive">
                    {{ $transactions->count() }}
                </div>
            </div>
        </div>
    @else
        <div class="no-data">
            <span class="icon">📋</span>
            <p>No transactions found for this customer in {{ $currency->code ?? 'USD' }}</p>
        </div>
    @endif

    <div class="footer">
        <span>Generated: {{ now()->format('d M Y, h:i A') }}</span>
        <span>{{ config('app.name', 'Your Company') }} &bull; All rights reserved</span>
    </div>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Print Statement</button>
        <button class="btn-close" onclick="window.close()">✕ Close</button>
    </div>

</body>

</html>
