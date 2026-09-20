<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gate Pass {{ $gatePass->gate_pass_no }}</title>
    <style>
        * { box-sizing:border-box; }
        body { margin:0; font-family:DejaVu Sans, Arial, sans-serif; color:#111827; background:#f8fafc; }
        .page { width:210mm; min-height:148mm; margin:0 auto; background:white; padding:14mm; }
        .header { display:flex; justify-content:space-between; gap:24px; border-bottom:2px solid #111827; padding-bottom:12px; }
        h1,h2,p { margin:0; }
        h1 { font-size:20px; }
        h2 { font-size:24px; letter-spacing:1px; }
        .small { color:#6b7280; font-size:11px; margin-top:4px; }
        .meta { margin:16px 0; display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .box { border:1px solid #d1d5db; border-radius:7px; padding:10px; font-size:12px; }
        .label { color:#6b7280; font-size:9px; text-transform:uppercase; font-weight:700; margin-bottom:3px; }
        table { width:100%; border-collapse:collapse; margin-top:12px; }
        th { background:#f3f4f6; padding:8px; font-size:10px; text-transform:uppercase; border:1px solid #d1d5db; text-align:left; }
        td { padding:9px 8px; font-size:12px; border:1px solid #d1d5db; vertical-align:top; }
        .qty { text-align:right; white-space:nowrap; }
        .signatures { display:grid; grid-template-columns:1fr 1fr 1fr; gap:28px; margin-top:42px; }
        .sign { border-top:1px solid #374151; padding-top:5px; text-align:center; font-size:10px; color:#4b5563; }
        .actions { text-align:center; margin:12px; }
        .actions button { border:0; background:#111827; color:white; padding:9px 18px; border-radius:6px; cursor:pointer; }
        @media print { body { background:white; } .page { width:auto; min-height:auto; margin:0; } .actions { display:none; } }
    </style>
</head>
<body>
<div class="actions"><button onclick="window.print()">Print Gate Pass</button></div>
<div class="page">
    <div class="header">
        <div>
            <h1>{{ $companyName }}</h1>
            @if($companyAddress)<div class="small">{{ $companyAddress }}</div>@endif
            @if($companyPhone)<div class="small">{{ $companyPhone }}</div>@endif
        </div>
        <div style="text-align:right">
            <h2>GATE PASS</h2>
            <div class="small">{{ $gatePass->gate_pass_no }}</div>
        </div>
    </div>

    <div class="meta">
        <div class="box">
            <div class="label">Customer</div>
            <strong>{{ $sale->customer->name ?? 'Customer' }}</strong>
            @if($sale->shipping_address)<div class="small">{{ $sale->shipping_address }}</div>@endif
        </div>
        <div class="box">
            <div class="label">Delivery Reference</div>
            <div><strong>Invoice:</strong> {{ $sale->sale_no }}</div>
            <div><strong>Issued:</strong> {{ optional($gatePass->issued_at)->format('d M Y H:i') }}</div>
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width:6%">#</th>
            <th style="width:34%">Item Name</th>
            <th style="width:42%">Description</th>
            <th class="qty" style="width:18%">Quantity</th>
        </tr>
        </thead>
        <tbody>
        @foreach($gatePass->items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td><strong>{{ $item->item_name }}</strong></td>
                <td>{{ $item->description ?: '-' }}</td>
                <td class="qty">{{ number_format((float) $item->quantity, 2) }} {{ $item->unit }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="signatures">
        <div class="sign">Prepared By</div>
        <div class="sign">Security / Gate</div>
        <div class="sign">Received By</div>
    </div>
</div>
</body>
</html>
