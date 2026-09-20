<?php

it('exposes weekly cycle count reporting, blind print, approval and audit controls', function () {
    $index = file_get_contents(resource_path('views/admin/stock-reconciliations/index.blade.php'));
    $show = file_get_contents(resource_path('views/admin/stock-reconciliations/show.blade.php'));
    $report = file_get_contents(resource_path('views/admin/stock-reconciliations/report.blade.php'));
    $print = file_get_contents(resource_path('views/admin/stock-reconciliations/print.blade.php'));

    expect($index)
        ->toContain('New Cycle Count')
        ->toContain('Variance Report')
        ->toContain('Awaiting Approval')
        ->toContain('30-Day Negative Variance');

    expect($show)
        ->toContain('Blind Count Sheet')
        ->toContain('Submit for Approval')
        ->toContain('Approve')
        ->toContain('Post Stock Adjustment')
        ->toContain('Posted Adjustment Ledger')
        ->toContain('Cancel Count');

    expect($report)
        ->toContain('Variance Report')
        ->toContain('Export CSV')
        ->toContain('Most Repeated / Highest-Value Variances')
        ->toContain('Shortage Value');

    expect($print)
        ->toContain('Blind Physical Count Sheet')
        ->toContain('ERP/system quantities are intentionally hidden')
        ->toContain('@unless($blind)<th class="right">System Qty</th>@endunless');
});
