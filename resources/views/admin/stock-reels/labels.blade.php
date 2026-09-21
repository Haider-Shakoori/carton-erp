<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #eef1f5;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: #fff;
            border-bottom: 1px solid #d1d5db;
        }
        .toolbar button, .toolbar a {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 12px;
            background: #fff;
            color: #111827;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }
        .toolbar button {
            background: #111827;
            color: #fff;
            border-color: #111827;
        }
        .labels {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 16px;
            padding: 20px;
        }
        .label {
            width: 100mm;
            min-height: 70mm;
            padding: 6mm;
            background: #fff;
            border: 1px solid #111827;
            page-break-after: always;
            break-after: page;
        }
        .label:last-child {
            page-break-after: auto;
            break-after: auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: flex-start;
            padding-bottom: 3mm;
            margin-bottom: 3mm;
            border-bottom: 1px solid #cbd5e1;
        }
        .title {
            font-size: 17px;
            font-weight: 700;
            line-height: 1.15;
        }
        .status {
            padding: 3px 7px;
            border: 1px solid #64748b;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .reel-code {
            margin-top: 1mm;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: .4px;
        }
        .barcode {
            width: 100%;
            height: 22mm;
            margin: 2mm 0 1mm;
        }
        .barcode svg {
            display: block;
            width: 100%;
            height: 100%;
        }
        .scan-key {
            text-align: center;
            font-family: "Courier New", monospace;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 3mm;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2mm 5mm;
            font-size: 11px;
        }
        .field span {
            display: block;
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: .5mm;
        }
        .field strong {
            font-size: 12px;
        }
        .footer {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-top: 3mm;
            padding-top: 2mm;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #64748b;
        }
        @page {
            size: 100mm 70mm;
            margin: 0;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .labels { display: block; padding: 0; }
            .label {
                margin: 0;
                border: 0;
                width: 100mm;
                height: 70mm;
                min-height: 70mm;
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <strong>{{ $title }}</strong>
            <div style="font-size:12px;color:#64748b;">Code 39 labels · {{ $labels->count() }} reel(s)</div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="javascript:history.back()">Back</a>
            <button type="button" onclick="window.print()">Print Labels</button>
        </div>
    </div>

    <main class="labels">
        @foreach($labels as $label)
            @php $reel = $label['reel']; @endphp
            <section class="label">
                <div class="header">
                    <div>
                        <div class="title">{{ $label['material_name'] }}</div>
                        <div class="reel-code">{{ $reel->reel_code }}</div>
                    </div>
                    <div class="status">{{ $label['status'] }}</div>
                </div>

                <div class="barcode">{!! $label['barcode_svg'] !!}</div>
                <div class="scan-key">{{ $label['scan_key'] }}</div>

                <div class="grid">
                    <div class="field">
                        <span>Batch</span>
                        <strong>{{ $label['batch_no'] }}</strong>
                    </div>
                    <div class="field">
                        <span>Purchase</span>
                        <strong>{{ $label['purchase_no'] }}</strong>
                    </div>
                    <div class="field">
                        <span>Original weight</span>
                        <strong>{{ number_format($label['registered_weight_kg'], 4) }} kg</strong>
                    </div>
                    <div class="field">
                        <span>System remaining</span>
                        <strong>{{ number_format($label['system_remaining_weight_kg'], 4) }} kg</strong>
                    </div>
                </div>

                <div class="footer">
                    <span>Scanner key: {{ $label['scan_key'] }}</span>
                    <span>ERP reel ID {{ $reel->id }}</span>
                </div>
            </section>
        @endforeach
    </main>
</body>
</html>
