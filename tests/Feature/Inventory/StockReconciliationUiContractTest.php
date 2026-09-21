<?php

it('exposes weekly cycle count reporting, blind print, approval and audit controls', function () {
    $index = file_get_contents(resource_path('views/admin/stock-reconciliations/index.blade.php'));
    $show = file_get_contents(resource_path('views/admin/stock-reconciliations/show.blade.php'));
    $report = file_get_contents(resource_path('views/admin/stock-reconciliations/report.blade.php'));
    $print = file_get_contents(resource_path('views/admin/stock-reconciliations/print.blade.php'));
    $stockIndex = file_get_contents(resource_path('views/admin/stock/index.blade.php'));
    $productHistory = file_get_contents(resource_path('views/admin/products/show.blade.php'));
    $menu = file_get_contents(resource_path('views/layouts/admin/menu.blade.php'));
    $planning = file_get_contents(resource_path('views/admin/stock-reconciliations/planning.blade.php'));
    $trends = file_get_contents(resource_path('views/admin/stock-reconciliations/trends.blade.php'));
    $controlAnalysis = file_get_contents(resource_path('views/admin/stock-reconciliations/control-analysis.blade.php'));
    $investigationIndex = file_get_contents(resource_path('views/admin/stock-reconciliations/investigations/index.blade.php'));
    $investigationShow = file_get_contents(resource_path('views/admin/stock-reconciliations/investigations/show.blade.php'));
    $preventionIntelligence = file_get_contents(resource_path('views/admin/stock-reconciliations/investigations/intelligence.blade.php'));
    $managementControl = file_get_contents(resource_path('views/admin/stock-reconciliations/management-control/index.blade.php'));
    $managementEscalation = file_get_contents(resource_path('views/admin/stock-reconciliations/management-control/escalation.blade.php'));
    $managementReview = file_get_contents(resource_path('views/admin/stock-reconciliations/management-control/review.blade.php'));
    $stockNotifications = file_get_contents(resource_path('views/admin/stock-notifications/index.blade.php'));
    $navbar = file_get_contents(resource_path('views/layouts/admin/navbar.blade.php'));

    expect($index)
        ->toContain('New Cycle Count')
        ->toContain('Variance Report')
        ->toContain('Awaiting Approval')
        ->toContain('30-Day Negative Variance')
        ->toContain('Investigations')
        ->toContain('Unresolved Investigations')
        ->toContain('ABC Planner')
        ->toContain('Variance Trends')
        ->toContain('Production → Physical')
        ->toContain('Investigations')
        ->toContain('Prevention')
        ->toContain('Management Control');

    expect($show)
        ->toContain('Blind Count Sheet')
        ->toContain('Submit for Approval')
        ->toContain('Independent approval required')
        ->toContain('Independent Approver Required')
        ->toContain('Approve')
        ->toContain('Post Stock Adjustment')
        ->toContain('Posted Adjustment Ledger')
        ->toContain('Cancel Count');

    expect($report)
        ->toContain('Variance Report')
        ->toContain('Export CSV')
        ->toContain('Most Repeated / Highest-Value Variances')
        ->toContain('Shortage Value')
        ->toContain('Unresolved investigations only')
        ->toContain('Unresolved variance queue');

    expect($print)
        ->toContain('Blind Physical Count Sheet')
        ->toContain('ERP/system quantities are intentionally hidden')
        ->toContain('@unless($blind)<th class="right">System Qty</th>@endunless');

    expect($stockIndex)
        ->toContain("route('admin.products.show', \$product->id)")
        ->toContain('Inventory history');

    expect($productHistory)
        ->toContain('Reconciliation Adjustment History')
        ->toContain('Full Variance Report')
        ->toContain('$reconciliationAdjustments');

    expect($menu)
        ->toContain("admin.stock-reconciliations.*")
        ->toContain("route('admin.stock-reconciliations.index')")
        ->toContain('Stock Reconciliation');

    expect($planning)
        ->toContain('ABC Cycle Count Planner')
        ->toContain('Start Selected Count')
        ->toContain('Class A covers the highest-value ~80%');

    expect($trends)
        ->toContain('Variance Trends')
        ->toContain('Top Variance Reasons')
        ->toContain('Production → Physical Analysis');

    expect($controlAnalysis)
        ->toContain('Production → Physical Inventory Analysis')
        ->toContain('Reconciliation Difference')
        ->toContain('System @ Count')
        ->toContain('Inventory variance');

    expect($investigationIndex)
        ->toContain('Variance Investigations')
        ->toContain('Overdue')
        ->toContain('Unassigned')
        ->toContain('Active Variance Value');

    expect($investigationShow)
        ->toContain('Investigation Work')
        ->toContain('Root Cause Details')
        ->toContain('Corrective Action')
        ->toContain('Resolution Notes')
        ->toContain('Audit Timeline')
        ->toContain('Mark Resolved');

    expect($preventionIntelligence)
        ->toContain('Inventory Prevention Intelligence')
        ->toContain('Management Flags')
        ->toContain('Recurring Root-Cause Patterns')
        ->toContain('Material Variance Hotspots')
        ->toContain('Corrective-Action Follow-up')
        ->toContain('do not prove causation')
        ->toContain('Management Control');

    expect($managementControl)
        ->toContain('Stock Management Control')
        ->toContain('Current Weekly Control Review')
        ->toContain('Management Escalations')
        ->toContain('Recent Weekly Reviews')
        ->toContain('Level 3 = critical management attention')
        ->toContain('Notifications');

    expect($managementEscalation)
        ->toContain('Management Signal')
        ->toContain('Ownership & Review Deadline')
        ->toContain('Acknowledge Escalation')
        ->toContain('Close Management Escalation')
        ->toContain('Escalation Audit Timeline');

    expect($managementReview)
        ->toContain('Weekly Control Review')
        ->toContain('Escalations Included in This Review')
        ->toContain('Management Decisions / Actions')
        ->toContain('Complete Weekly Review')
        ->toContain('freezes its weekly management notes');

    expect($stockNotifications)
        ->toContain('Stock Control Notifications')
        ->toContain('Notification Center')
        ->toContain('Email / WhatsApp Delivery Audit')
        ->toContain('Notification Settings')
        ->toContain('Email and WhatsApp are opt-in')
        ->toContain('Assignment alerts')
        ->toContain('Retry')
        ->toContain('maskedNotificationEmail')
        ->toContain('maskedNotificationPhone')
        ->toContain('Save Notification Settings');

    expect($navbar)
        ->toContain('unreadNotifications()->count()')
        ->toContain("route('admin.stock-notifications.open'")
        ->toContain("route('admin.stock-notifications.index')")
        ->not->toContain('8 New');
});
