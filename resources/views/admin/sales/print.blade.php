<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['fa', 'ps']) ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('ui.invoice') }} #{{ $sale->sale_no }}</title>
    <style>
        /* ─── Reset & Base ─── */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #ffffff;
            padding: 0;
            color: #000000;
            -webkit-font-smoothing: antialiased;
        }

        /* ─── Print Container - Full Width ─── */
        .print-container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            background: #ffffff;
            padding: 40px 50px;
        }

        /* ─── Print Header ─── */
        .print-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 28px;
            border-bottom: 2px solid #d0d5dd;
            margin-bottom: 32px;
        }

        .print-header .brand {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        /* ─── Logo container – clean, no background block ─── */
        .print-header .brand .logo {
            width: 76px;
            height: 76px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            overflow: hidden;
            background: transparent;
            /* no dark block */
        }

        .print-header .brand .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            /* keeps aspect ratio, no distortion */
            display: block;
        }

        /* fallback text (if no logo) – clean & subtle */
        .print-header .brand .logo .no-logo {
            font-size: 28px;
            font-weight: 700;
            color: #1a2332;
            background: #eef0f3;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .print-header .brand .company-info .company-name {
            font-size: 22px;
            font-weight: 700;
            color: #000000;
            letter-spacing: -0.3px;
        }

        .print-header .brand .company-info .company-tagline {
            font-size: 13px;
            color: #3a4a5e;
            font-weight: 400;
            margin-top: 2px;
        }

        .print-header .invoice-title {
            text-align: right;
        }

        .print-header .invoice-title .title {
            font-size: 28px;
            font-weight: 800;
            color: #000000;
            letter-spacing: 1px;
        }

        .print-header .invoice-title .subtitle {
            font-size: 14px;
            color: #3a4a5e;
            font-weight: 400;
            margin-top: 4px;
        }

        /* ─── Status Badge ─── */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border: 1px solid #d0d5dd;
            background: #f2f4f7;
            color: #000000;
        }

        .status-badge.draft {
            background: #f2f4f7;
            color: #000000;
            border-color: #d0d5dd;
        }

        .status-badge.confirmed {
            background: #e6eff9;
            color: #000000;
            border-color: #b8cce4;
        }

        .status-badge.shipped {
            background: #fef3e6;
            color: #000000;
            border-color: #f0d5b8;
        }

        .status-badge.delivered {
            background: #e6f5ed;
            color: #000000;
            border-color: #b8ddd0;
        }

        .status-badge .dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            display: inline-block;
            background: #000000;
        }

        /* ─── Top Row ─── */
        .top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 28px;
        }

        .top-row .date {
            font-size: 14px;
            color: #3a4a5e;
        }

        .top-row .date strong {
            color: #000000;
            font-weight: 600;
        }

        /* ─── Info Grid ─── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 32px;
            padding: 24px 28px;
            background: #f5f7fa;
            border-radius: 12px;
        }

        .info-grid .info-section h4 {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #3a4a5e;
            margin-bottom: 8px;
        }

        .info-grid .info-section p {
            font-size: 14px;
            color: #000000;
            line-height: 1.7;
        }

        .info-grid .info-section p .label {
            color: #3a4a5e;
            font-weight: 500;
        }

        .info-grid .info-section p .value {
            font-weight: 500;
            color: #000000;
        }

        /* ─── Items Table ─── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 28px 0 24px;
            font-size: 14px;
        }

        .items-table thead th {
            padding: 12px 14px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #3a4a5e;
            border-bottom: 2px solid #d0d5dd;
            background: transparent;
        }

        .items-table thead th.text-end {
            text-align: right;
        }

        .items-table thead th.text-center {
            text-align: center;
        }

        .items-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #e5e9f0;
            vertical-align: middle;
            color: #000000;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .items-table tbody td .product-name {
            font-weight: 600;
            color: #000000;
            font-size: 14px;
        }

        .items-table tbody td .product-meta {
            font-size: 12px;
            color: #3a4a5e;
            margin-top: 2px;
        }

        .items-table tbody td .unit-badge {
            font-size: 11px;
            color: #000000;
            font-weight: 500;
            background: #eaedf2;
            padding: 1px 8px;
            border-radius: 4px;
            margin-left: 4px;
        }

        .items-table tbody td.text-end {
            text-align: right;
        }

        .items-table tbody td.text-center {
            text-align: center;
        }

        /* ─── Totals Section ─── */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 8px;
            padding-top: 20px;
            border-top: 2px solid #d0d5dd;
        }

        .totals-section .totals-box {
            width: 300px;
        }

        .totals-section .totals-box .total-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
            color: #000000;
        }

        .totals-section .totals-box .total-row .label {
            color: #3a4a5e;
        }

        .totals-section .totals-box .total-row .value {
            font-weight: 600;
            color: #000000;
        }

        .totals-section .totals-box .total-row.grand-total {
            padding: 14px 0 8px;
            border-top: 2px solid #000000;
            margin-top: 6px;
        }

        .totals-section .totals-box .total-row.grand-total .label {
            font-size: 15px;
            font-weight: 700;
            color: #000000;
        }

        .totals-section .totals-box .total-row.grand-total .value {
            font-size: 20px;
            font-weight: 800;
            color: #000000;
        }

        .totals-section .totals-box .total-row.discount .value {
            color: #b3363f;
        }

        /* ─── Payment Info ─── */
        .payment-info {
            margin-top: 18px;
            padding: 16px 22px;
            background: #f5f7fa;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .payment-info .payment-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-info .payment-item .payment-label {
            font-size: 13px;
            color: #3a4a5e;
            font-weight: 500;
        }

        .payment-info .payment-item .payment-value {
            font-size: 15px;
            font-weight: 700;
            color: #000000;
        }

        .payment-info .payment-item .payment-value.paid {
            color: #1a7a42;
        }

        .payment-info .payment-item .payment-value.due {
            color: #a0682a;
        }

        /* ─── Notes ─── */
        .notes-section {
            margin-top: 22px;
            padding: 16px 22px;
            background: #f8f9fc;
            border: 1px solid #e5e9f0;
            border-radius: 10px;
        }

        .notes-section .notes-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #3a4a5e;
        }

        .notes-section .notes-text {
            font-size: 14px;
            color: #000000;
            margin-top: 4px;
            line-height: 1.6;
        }

        .notes-section .notes-text .shipping-address {
            display: block;
            margin-bottom: 4px;
        }

        .notes-section .notes-text .shipping-address strong {
            color: #000000;
        }

        /* ─── Footer ─── */
        .print-footer {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid #d0d5dd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .print-footer .footer-text {
            font-size: 13px;
            color: #3a4a5e;
            line-height: 1.7;
        }

        .print-footer .footer-text strong {
            color: #000000;
            font-weight: 600;
        }

        .print-footer .signature {
            display: flex;
            align-items: center;
            gap: 35px;
        }

        .print-footer .signature .sig-line {
            text-align: center;
        }

        .print-footer .signature .sig-line .line {
            width: 120px;
            border-bottom: 1.5px solid #bcc3cc;
            margin-bottom: 5px;
        }

        .print-footer .signature .sig-line .label {
            font-size: 10px;
            color: #3a4a5e;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .print-container {
                padding: 24px 20px;
            }

            .print-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
                padding-bottom: 20px;
            }

            .print-header .invoice-title {
                text-align: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 16px;
                padding: 18px;
            }

            .items-table {
                font-size: 12px;
            }

            .items-table thead th,
            .items-table tbody td {
                padding: 8px 6px;
            }

            .totals-section {
                justify-content: center;
            }

            .totals-section .totals-box {
                width: 100%;
                max-width: 280px;
            }

            .print-footer {
                flex-direction: column;
                text-align: center;
            }

            .print-footer .signature {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                gap: 14px;
            }

            .payment-info {
                flex-direction: column;
                text-align: center;
                padding: 14px 18px;
            }

            .payment-info .payment-item {
                justify-content: center;
            }

            .top-row {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .print-container {
                padding: 40px 50px;
                max-width: 100%;
            }

            .print-actions {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    {{-- ─── Print Container ─── --}}
    <div class="print-container" id="invoiceContainer">

        {{-- ─── Header ─── --}}
        <div class="print-header">
            <div class="brand">
                <div class="logo">
                    @if ($companyLogo)
                        <img src="{{ asset('images/' . $companyLogo) }}" alt="{{ $companyName }}">
                    @else
                        <span class="no-logo">{{ substr($companyName, 0, 2) }}</span>
                    @endif
                </div>
                <div class="company-info">
                    <div class="company-name">{{ $companyName }}</div>
                    @if ($companyTagline ?? false)
                        <div class="company-tagline">{{ $companyTagline }}</div>
                    @endif
                </div>
            </div>
            <div class="invoice-title">
                <div class="title">{{ __('ui.invoice') }}</div>
                <div class="subtitle">#{{ $sale->sale_no }}</div>
            </div>
        </div>

        {{-- ─── Body ─── --}}
        <div class="print-body">

            {{-- ─── Top Row ─── --}}
            <div class="top-row">
                <div>
                    @php
                        $statusConfig = [
                            'draft' => ['class' => 'draft', 'label' => __('ui.draft')],
                            'confirmed' => ['class' => 'confirmed', 'label' => __('ui.confirmed')],
                            'shipped' => ['class' => 'shipped', 'label' => __('ui.shipped')],
                            'delivered' => ['class' => 'delivered', 'label' => __('ui.delivered')],
                        ];
                        $config = $statusConfig[$sale->status] ?? $statusConfig['draft'];
                    @endphp
                    <span class="status-badge {{ $config['class'] }}">
                        <span class="dot"></span>
                        {{ $config['label'] }}
                    </span>
                </div>
                <div class="date">
                    <strong>{{ __('ui.date_colon') }}</strong>
                    {{ $sale->sale_date ? date('F d, Y', strtotime($sale->sale_date)) : date('F d, Y') }}
                </div>
            </div>

            {{-- ─── Info Grid ─── --}}
            <div class="info-grid">
                <div class="info-section">
                    <h4>{{ __('ui.bill_to') }}</h4>
                    <p>
                        <strong
                            style="font-size: 16px; color:#000000;">{{ $sale->customer->name ?? 'N/A' }}</strong><br>
                        @if ($sale->customer && $sale->customer->address)
                            {{ $sale->customer->address }}<br>
                        @endif
                        @if ($sale->customer && $sale->customer->phone)
                            {{ $sale->customer->phone }}<br>
                        @endif
                        @if ($sale->customer && $sale->customer->email)
                            {{ $sale->customer->email }}
                        @endif
                    </p>
                </div>
                <div class="info-section">
                    <h4>{{ __('ui.invoice_details') }}</h4>
                    <p>
                        <span class="label">{{ __('ui.invoice_number_colon') }}</span> <span class="value">{{ $sale->sale_no }}</span><br>
                        <span class="label">{{ __('ui.date_colon') }}</span> <span
                            class="value">{{ $sale->sale_date ? date('F d, Y', strtotime($sale->sale_date)) : date('F d, Y') }}</span><br>
                        <span class="label">{{ __('ui.currency_colon') }}</span> <span class="value">{{ $currencyCode }}
                            ({{ $currencySymbol }})</span>
                    </p>
                </div>
            </div>

            {{-- ─── Items Table ─── --}}
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 42%;">{{ __('ui.description') }}</th>
                        <th class="text-center" style="width: 15%;">{{ __('ui.quantity') }}</th>
                        <th class="text-end" style="width: 20%;">{{ __('ui.unit_price') }}</th>
                        <th class="text-end" style="width: 23%;">{{ __('ui.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sale->items as $item)
                        @php
                            $qty = $item->qty;
                            $formattedQty =
                                is_float($qty) && floor($qty) != $qty ? number_format($qty, 2) : number_format($qty, 0);
                            $unit = $item->product->unit ?? '';
                        @endphp
                        <tr>
                            <td>
                                <div class="product-name">{{ $item->product->name ?? 'Unknown Product' }}</div>
                                @if ($item->remarks)
                                    <div class="product-meta">{{ $item->remarks }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                {{ $formattedQty }}
                                @if ($unit)
                                    <span class="unit-badge">{{ $unit }}</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $currencySymbol }}{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end">
                                <strong>{{ $currencySymbol }}{{ number_format($item->total, 2) }}</strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px; color: #3a4a5e;">
                                No items found for this sale.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- ─── Totals ─── --}}
            <div class="totals-section">
                <div class="totals-box">
                    <div class="total-row">
                        <span class="label">{{ __('ui.subtotal') }}</span>
                        <span class="value">{{ $currencySymbol }}{{ number_format($sale->subtotal, 2) }}</span>
                    </div>
                    @if ($sale->discount_total > 0)
                        <div class="total-row discount">
                            <span class="label">{{ __('ui.discount') }}</span>
                            <span
                                class="value">-{{ $currencySymbol }}{{ number_format($sale->discount_total, 2) }}</span>
                        </div>
                    @endif
                    @if ($sale->shipping_cost > 0)
                        <div class="total-row">
                            <span class="label">{{ __('ui.shipping') }}</span>
                            <span
                                class="value">{{ $currencySymbol }}{{ number_format($sale->shipping_cost, 2) }}</span>
                        </div>
                    @endif
                    @if ($sale->tax_total > 0)
                        <div class="total-row">
                            <span class="label">{{ __('ui.tax') }}</span>
                            <span class="value">{{ $currencySymbol }}{{ number_format($sale->tax_total, 2) }}</span>
                        </div>
                    @endif
                    <div class="total-row grand-total">
                        <span class="label">{{ __('ui.grand_total') }}</span>
                        <span class="value">{{ $currencySymbol }}{{ number_format($sale->grand_total, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- ─── Payment Information ─── --}}
            @if ($sale->advance_payment > 0 || $sale->due_amount > 0)
                <div class="payment-info">
                    <div class="payment-item">
                        <span class="payment-label">{{ __('ui.paid_amount') }}</span>
                        <span
                            class="payment-value paid">{{ $currencySymbol }}{{ number_format($sale->advance_payment, 2) }}</span>
                    </div>
                    @if ($sale->due_amount > 0)
                        <div class="payment-item">
                            <span class="payment-label">{{ __('ui.due_amount') }}</span>
                            <span
                                class="payment-value due">{{ $currencySymbol }}{{ number_format($sale->due_amount, 2) }}</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ─── Notes ─── --}}
            @if ($sale->notes || $sale->shipping_address)
                <div class="notes-section">
                    <div class="notes-label">{{ __('ui.notes') }}</div>
                    <div class="notes-text">
                        @if ($sale->shipping_address)
                            <span class="shipping-address"><strong>{{ __('ui.shipping_address_colon') }}</strong>
                                {{ $sale->shipping_address }}</span>
                        @endif
                        @if ($sale->notes)
                            {{ $sale->notes }}
                        @endif
                    </div>
                </div>
            @endif

        </div>

        {{-- ─── Footer ─── --}}
        <div class="print-footer">
            <div class="footer-text">
                <strong>{{ $companyName }}</strong><br>
                @if ($companyAddress)
                    {{ $companyAddress }}<br>
                @endif
                @if ($companyPhone)
                    Phone: {{ $companyPhone }}
                @endif
                @if ($companyEmail)
                    · Email: {{ $companyEmail }}
                @endif
            </div>
            <div class="signature">
                <div class="sig-line">
                    <div class="line"></div>
                    <div class="label">{{ __('ui.authorized_signature') }}</div>
                </div>
                {{-- Customer signature removed --}}
            </div>
        </div>

    </div>

    {{-- ─── Auto Print Script ─── --}}
    <script>
        window.onload = function() {
            window.print();
        };
    </script>

</body>

</html>
