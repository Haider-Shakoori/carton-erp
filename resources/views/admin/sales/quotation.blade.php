<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quotation {{ $sale->sale_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 0; color: #1f2937; background: #f8fafc; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; background: white; padding: 14mm; }
        .header { display:flex; justify-content:space-between; gap:24px; border-bottom:2px solid #111827; padding-bottom:14px; }
        .brand h1 { margin:0 0 5px; font-size:22px; }
        .brand p, .meta p { margin:2px 0; font-size:12px; color:#4b5563; }
        .title { text-align:right; }
        .title h2 { margin:0; font-size:26px; letter-spacing:1px; }
        .customer { margin:20px 0; display:flex; justify-content:space-between; gap:20px; }
        .box { border:1px solid #d1d5db; border-radius:8px; padding:12px; flex:1; }
        .box .label { font-size:10px; text-transform:uppercase; color:#6b7280; font-weight:700; margin-bottom:4px; }
        table { width:100%; border-collapse:collapse; margin-top:14px; }
        th { background:#f3f4f6; font-size:11px; text-transform:uppercase; text-align:left; padding:9px 8px; border-bottom:1px solid #d1d5db; }
        td { padding:10px 8px; border-bottom:1px solid #e5e7eb; font-size:12px; vertical-align:top; }
        .num { text-align:right; white-space:nowrap; }
        .desc { color:#4b5563; font-size:11px; margin-top:3px; white-space:pre-wrap; }
        .totals { width:42%; margin-left:auto; margin-top:18px; }
        .totals div { display:flex; justify-content:space-between; padding:5px 0; font-size:12px; }
        .totals .grand { border-top:2px solid #111827; margin-top:5px; padding-top:9px; font-size:15px; font-weight:700; }
        .footer { margin-top:34px; padding-top:12px; border-top:1px solid #e5e7eb; font-size:10px; color:#6b7280; }
        .actions { text-align:center; margin:14px; }
        .actions button { border:0; background:#111827; color:white; padding:9px 18px; border-radius:6px; cursor:pointer; }
        @media print { body { background:white; } .page { margin:0; width:auto; min-height:auto; } .actions { display:none; } }
    </style>
</head>
<body>
<div class="actions"><button onclick="window.print()">Print Quotation</button></div>
<div class="page">
    <div class="header">
        <div class="brand">
            <h1>{{ $companyName }}</h1>
            @if($companyAddress)<p>{{ $companyAddress }}</p>@endif
            @if($companyPhone)<p>{{ $companyPhone }}</p>@endif
            @if($companyEmail)<p>{{ $companyEmail }}</p>@endif
        </div>
        <div class="title">
            <h2>QUOTATION</h2>
            <div class="meta">
                <p><strong>Reference:</strong> {{ $sale->sale_no }}</p>
                <p><strong>Date:</strong> {{ optional($sale->sale_date)->format('d M Y') ?? now()->format('d M Y') }}</p>
                <p><strong>Currency:</strong> {{ $currencyCode }}</p>
            </div>
        </div>
    </div>

    <div class="customer">
        <div class="box">
            <div class="label">Quotation For</div>
            <strong>{{ $sale->customer->name ?? 'Customer' }}</strong>
            @if($sale->customer?->address)<div>{{ $sale->customer->address }}</div>@endif
            @if($sale->customer?->contact)<div>{{ $sale->customer->contact }}</div>@endif
        </div>
        <div class="box">
            <div class="label">Notes</div>
            <div>{{ $sale->notes ?: 'Commercial quotation based on the listed items and quantities.' }}</div>
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width:5%">#</th>
            <th style="width:43%">Item / Quotation Description</th>
            <th class="num" style="width:12%">Qty</th>
            <th class="num" style="width:18%">Unit Price</th>
            <th class="num" style="width:22%">Total</th>
        </tr>
        </thead>
        <tbody>
        @forelse($sale->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                    <strong>{{ $item->product->name ?? 'Item' }}</strong>
                    @if($item->quotation_description)
                        <div class="desc">{{ $item->quotation_description }}</div>
                    @endif
                </td>
                <td class="num">{{ number_format((float) $item->qty, 2) }}</td>
                <td class="num">{{ $currencySymbol }}{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="num"><strong>{{ $currencySymbol }}{{ number_format((float) $item->total, 2) }}</strong></td>
            </tr>
        @empty
            <tr><td colspan="5">No quotation items.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="totals">
        <div><span>Subtotal</span><span>{{ $currencySymbol }}{{ number_format((float) $sale->subtotal, 2) }}</span></div>
        @if((float)$sale->discount_total > 0)
            <div><span>Discount</span><span>-{{ $currencySymbol }}{{ number_format((float) $sale->discount_total, 2) }}</span></div>
        @endif
        <div class="grand"><span>Quotation Total</span><span>{{ $currencySymbol }}{{ number_format((float) $sale->grand_total, 2) }}</span></div>
    </div>

    <div class="footer">
        This quotation intentionally contains no BOM, raw-material, costing, or internal production details.
    </div>
</div>
</body>
</html>
