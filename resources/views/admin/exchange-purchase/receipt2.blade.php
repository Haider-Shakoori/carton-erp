<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('ui.purchase_receipt') }}</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap-5.3.2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fonts/montserrat/montserrat-playfair.css') }}" rel="stylesheet">
    <style>
        body {
            background: #f9f9f9;
            font-family: 'Montserrat', sans-serif;
            padding: 1rem;
        }
        .receipt-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            max-width: 500px;
            margin: auto;
            border-left: 4px solid #0d6efd;
            position: relative;
            margin-top: 20px;
        }
        .receipt-header {
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }
        .receipt-title {
            font-family: 'Playfair Display', serif;
            color: #333;
            font-weight: 500;
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        .receipt-date {
            color: #888;
            font-size: 0.75rem;
        }
        .receipt-section {
            margin-bottom: 1rem;
            padding: 0.75rem 0;
        }
        .receipt-section-title {
            color: #0d6efd;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .text-label {
            color: #777;
            font-size: 0.75rem;
            margin-bottom: 0.1rem;
        }
        .text-value {
            font-weight: 500;
            color: #333;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }
        .amount-highlight {
            font-weight: 600;
            color: #0d6efd;
        }
        .profit-highlight {
            font-weight: 600;
            color: #2ecc71;
            font-size: 0.85rem;
        }
        .status-badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
            background: #e8f5e9;
            color: #2ecc71;
        }
        .footer-note {
            text-align: center;
            font-size: 0.7rem;
            color: #888;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }
        .company-name {
            color: #0d6efd;
            font-weight: 600;
        }
        .divider-dot {
            color: #ccc;
            margin: 0 0.3rem;
            font-size: 0.6rem;
            vertical-align: middle;
        }
        .logo-container {
            background: #f8f7ff;
            padding: 0.5rem;
            border-radius: 8px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="receipt-box">
        <div class="receipt-header">
            <div class="logo-container">
                <img src="{{ asset('images/' . $setting->logo) }}" height="40" alt="Logo">
            </div>
            <div class="text-end">
                <div class="receipt-title">{{ __('ui.exchange_receipt') }}</div>
                <div class="receipt-date">{{ $purchase->created_at->format('M j, Y g:i A') }}</div>
            </div>
        </div>

        <div class="receipt-section">
            <div class="receipt-section-title">{{ __('ui.customer') }}</div>
            <div class="row">
                <div class="col-4">
                    <div class="text-label">{{ __('ui.customer_code') }}</div>
                    <div class="text-value">{{ $purchase->customerAccount->code }}</div>
                </div>
                <div class="col-4">
                    <div class="text-label">{{ __('ui.name') }}</div>
                    <div class="text-value">{{ $purchase->customerAccount->name }}</div>
                </div>
                <div class="col-4">
                    <div class="text-label">{{ __('ui.contact') }}</div>
                    <div class="text-value">{{ $purchase->customerAccount->contact }}</div>
                </div>
            </div>
        </div>

        <div class="receipt-section">
            <div class="receipt-section-title">Transaction</div>
            <div class="row">
                <div class="col-4">
                    <div class="text-label">{{ __('ui.amount') }}</div>
                    <div class="text-value amount-highlight">{{ $purchase->baseCurrency->code }} {{ number_format($purchase->base_amount, 2) }}</div>
                </div>
                <div class="col-4">
                    <div class="text-label">{{ __('ui.rate') }}</div>
                    <div class="text-value">{{ number_format($purchase->rate, 4) }}</div>
                </div>
                <div class="col-4">
                    <div class="text-label">{{ __('ui.received') }}</div>
                    <div class="text-value amount-highlight">{{ $purchase->targetCurrency->code }} {{ number_format($purchase->target_amount, 2) }}</div>
                </div>
            </div>
        </div>

        @if ($purchase->note)
            <div class="receipt-section">
                <div class="receipt-section-title">Note</div>
                <div class="text-value">{{ $purchase->note }}</div>
            </div>
        @endif

        <div class="footer-note">
            <span class="company-name">{{ $setting->company_name }}</span>
            <span class="divider-dot">•</span>
            {{ $setting->email }}
            <span class="divider-dot">•</span>
            {{ $setting->contact }}
            <div class="mt-1">Thank you for your trust</div>
        </div>
    </div>
</body>
</html>
