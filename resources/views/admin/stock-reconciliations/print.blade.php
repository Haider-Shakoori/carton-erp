<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $blind ? 'Blind Count Sheet' : 'Stock Reconciliation' }} - {{ $stockReconciliation->reconciliation_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        h1 { margin: 0 0 4px; font-size: 20px; }
        .meta { color: #555; margin-bottom: 18px; }
        .notice { border: 1px solid #bbb; padding: 8px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 7px; vertical-align: top; }
        th { background: #eee; text-align: left; }
        .right { text-align: right; }
        .count-box { height: 20px; min-width: 90px; }
        .signatures { margin-top: 30px; display: flex; gap: 40px; }
        .signature { flex: 1; border-top: 1px solid #333; padding-top: 5px; margin-top: 25px; }
        @media print { .no-print { display: none; } body { margin: 10mm; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print</button>
    <h1>{{ $blind ? 'Blind Physical Count Sheet' : 'Stock Reconciliation' }}</h1>
    <div class="meta">
        <strong>{{ $stockReconciliation->reconciliation_no }}</strong>
        · Count Date {{ $stockReconciliation->count_date?->format('d M Y') }}
        · Snapshot {{ $stockReconciliation->snapshot_at?->format('d M Y H:i:s') }}
        · Status {{ ucfirst($stockReconciliation->status) }}
    </div>

    @if($blind)
        <div class="notice">
            <strong>Blind count:</strong> ERP/system quantities are intentionally hidden. Count the physical stock independently and write the result below.
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Material / Batch</th>
                @unless($blind)<th class="right">System Qty</th>@endunless
                <th class="right">Physical Qty</th>
                @unless($blind)
                    <th class="right">Variance</th>
                    <th>Reason</th>
                @endunless
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stockReconciliation->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->product?->name ?? 'Unknown Material' }}</strong><br>
                        {{ $item->purchase_no ?: 'PO N/A' }} · Batch {{ $item->batch_no ?: '#'.$item->purchase_item_id }} · {{ $item->inventory_unit }}
                    </td>
                    @unless($blind)<td class="right">{{ number_format((float) $item->system_quantity, 4) }}</td>@endunless
                    <td class="right count-box">
                        @unless($blind)
                            {{ $item->physical_quantity !== null ? number_format((float) $item->physical_quantity, 4) : '' }}
                        @endunless
                    </td>
                    @unless($blind)
                        <td class="right">{{ $item->variance_quantity !== null ? number_format((float) $item->variance_quantity, 4) : '' }}</td>
                        <td>{{ $reasonCodes[$item->reason_code] ?? ($item->reason_code ?: '') }}</td>
                    @endunless
                    <td>{{ $blind ? '' : ($item->notes ?: '') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signatures">
        <div class="signature">Counted By</div>
        <div class="signature">Warehouse Supervisor</div>
        <div class="signature">Reviewed / Approved By</div>
    </div>
</body>
</html>
