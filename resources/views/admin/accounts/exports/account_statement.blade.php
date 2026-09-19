<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Statement - {{ $account->code }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 20px 30px;
        }

        /* Header Section */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .logo-container {
            display: table-cell;
            width: 100px;
            vertical-align: middle;
        }

        .logo {
            max-height: 60px;
            max-width: 100px;
        }

        .company-block {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            padding-left: 20px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .company-line {
            font-size: 12px;
            color: #555;
            line-height: 1.4;
        }

        .label {
            font-weight: bold;
            color: #2980b9;
        }

        /* Title Section */
        .title-section {
            text-align: center;
            margin: 20px 0;
        }

        .main-title {
            font-size: 22px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .divider {
            border: 0;
            height: 1px;
            background: #3498db;
            margin: 5px 0 15px;
        }

        /* Customer Info Card */
        .customer-card {
            background-color: #e6f7ff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            border-left: 4px solid #3498db;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .customer-info {
            display: table;
            width: 100%;
        }

        .info-item {
            display: table-cell;
            padding-right: 20px;
        }

        .info-label {
            font-weight: bold;
            color: #2980b9;
        }

        /* Summary Cards - Fixed for DomPDF */
        .summary-section {
            width: 100%;
            margin-bottom: 30px;
            display: table;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 10px;
        }

        .summary-row {
            display: table-row;
        }

        .summary-card {
            display: table-cell;
            background: #3498db;
            border-radius: 6px;
            color: white;
            padding: 10px 15px;
            box-sizing: border-box;
            vertical-align: top;
            width: auto;
        }

        /* For DomPDF compatibility - fallback for older versions */
        .summary-card-fallback {
            float: left;
            margin-right: 15px;
            background: #3498db;
            border-radius: 6px;
            color: white;
            padding: 10px 15px;
            box-sizing: border-box;
        }

        .summary-card-fallback:last-child {
            margin-right: 0;
        }

        /* Width calculations based on number of cards */
        .summary-card-fallback.single {
            width: 100%;
            margin-right: 0;
        }

        .summary-card-fallback.double {
            width: calc(50% - 7.5px);
        }

        .summary-card-fallback.double:nth-child(2n) {
            margin-right: 0;
        }

        .summary-card-fallback.triple {
            width: calc(33.33% - 10px);
        }

        .summary-card-fallback.triple:nth-child(3n) {
            margin-right: 0;
        }

        .summary-card-fallback.quad {
            width: calc(25% - 11.25px);
        }

        .summary-card-fallback.quad:nth-child(4n) {
            margin-right: 0;
        }

        .currency-header {
            display: flex;
            align-items: center;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            padding-bottom: 5px;
        }

        .currency-flag {
            width: 18px;
            height: 12px;
            margin-right: 6px;
            border-radius: 2px;
            border: 1px solid #fff;
        }

        .summary-table {
            width: 100%;
            font-size: 11px;
            border-collapse: collapse;
            text-transform: uppercase;
        }

        .summary-table td {
            padding: 2px 0;
        }

        .summary-label {
            text-align: left;
            font-weight: bold;
            color: #e0f2ff;
        }

        .summary-value {
            text-align: right;
            font-weight: bold;
            color: white;
        }

        /* Transactions Table */
        .transactions-title {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin: 25px 0 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #3498db;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .transactions-table thead th {
            background-color: #3498db;
            color: white;
            padding: 8px 12px;
            text-align: left;
            font-weight: bold;
        }

        .transactions-table tbody td {
            padding: 8px 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .transactions-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .credit-amount {
            color: #27ae60;
            font-weight: bold;
        }

        .debit-amount {
            color: #e74c3c;
            font-weight: bold;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 11px;
            color: #7f8c8d;
        }

    </style>
</head>
<body>
    <div class="container">

        <!-- Header Section -->
        <div class="header">
            <div class="logo-container">
                <img src="{{ public_path('images/' . $setting->logo) }}" class="logo" alt="Company Logo">
            </div>
            <div class="company-block">
                <div class="company-name">{{ strtoupper($setting->company_name) }}</div>
                <div class="company-line">
                    <span class="label">{{ __('ui.email_colon') }}</span> {{ $setting->email ?? 'N/A' }} &nbsp;&nbsp;
                    <span class="label">{{ __('ui.phone_colon') }}</span> {{ $setting->contact ?? 'N/A' }} &nbsp;&nbsp;
                    <span class="label">{{ __('ui.address_colon') }}</span> {{ $setting->address ?? 'N/A' }}
                </div>
            </div>
        </div>

        <!-- Title Section -->
        <div class="title-section">
            <div class="main-title">Account Statement</div>
            <hr class="divider">
        </div>

        <!-- Customer Info Card -->
        <div class="customer-card">
            <div class="customer-info">
                <div class="info-item">
                    <span class="info-label">Account Code:</span> {{ $account->code }}
                </div>
                <div class="info-item">
                    <span class="info-label">{{ __('ui.customer_name_colon') }}</span> {{ $account->name }}
                </div>
                <div class="info-item">
                    <span class="info-label">{{ __('ui.contact_colon') }}</span> {{ $account->contact }}
                </div>
            </div>
        </div>

        <!-- Summary Cards Section - Fixed for DomPDF -->
        <div class="summary-section">
            <div class="summary-row">
                @php
                $currencyCount = count($summariesByCurrency);
                $cardClass = '';
                if ($currencyCount === 1) $cardClass = 'single';
                elseif ($currencyCount === 2) $cardClass = 'double';
                elseif ($currencyCount === 3) $cardClass = 'triple';
                @endphp

                @foreach ($summariesByCurrency as $currency => $summary)
                @php
                $credit = $summary['credit'];
                $debit = $summary['debit'];
                $balance = $credit - $debit;
                @endphp
                <div class="summary-card {{ $cardClass }}">
                    <div class="currency-header">
                        <img class="currency-flag" src="{{ public_path('assets/flags/' . strtolower(substr($currency, 0, 2)) . '.svg') }}" onerror="this.src='{{ public_path('assets/flags/default.svg') }}'" alt="{{ $currency }} Flag">
                        <span class="currency-code">{{ $currency }}</span>
                    </div>
                    <table width="100%" style="text-transform: uppercase; font-size: 12px;">
                        <tr>
                            <td style="font-weight: bold;">{{ __('ui.credit') }}</td>
                            <td align="right">{{ number_format($credit, 2) }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Debit</td>
                            <td align="right">{{ number_format($debit, 2) }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">{{ __('ui.balance') }}</td>
                            <td align="right">{{ number_format($balance, 2) }}</td>
                        </tr>
                    </table>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="transactions-title">Transaction History</div>
        <table class="transactions-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('ui.date') }}</th>
                    <th>{{ __('ui.description') }}</th>
                    <th>{{ __('ui.currency') }}</th>
                    <th>{{ __('ui.amount') }}</th>
                    <th>{{ __('ui.type') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $index => $tx)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $tx->created_at->format('d M Y') }}</td>
                    <td>{{ $tx->note ?? 'N/A' }}</td>
                    <td>{{ $tx->currency->code ?? '-' }}</td>
                    <td class="{{ $tx->type == 'credit' ? 'credit-amount' : 'debit-amount' }}">
                        {{ number_format($tx->amount, 2) }}
                    </td>
                    <td>{{ ucfirst($tx->type) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
            Generated on {{ now()->format('d F Y \a\t H:i') }} | &copy; {{ date('Y') }} {{ $setting->company_name }}
        </div>
    </div>
</body>
</html>
