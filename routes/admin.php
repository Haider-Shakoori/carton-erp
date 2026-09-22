<?php
// routes/web.php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AccountsController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\AppSettingsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CurrenciesController;
use App\Http\Controllers\Admin\AccountCategoriesController;
use App\Http\Controllers\Admin\AccountSubCategoriesController;
use App\Http\Controllers\Admin\ExchangeController;
use App\Http\Controllers\Admin\ExchangePurchaseController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ExchangeRateController;
use App\Http\Controllers\Admin\ExpensesController;
use App\Http\Controllers\Admin\JournalController;
use App\Http\Controllers\Admin\OnlineUsersController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PurchaseExpenseController;
use App\Http\Controllers\Admin\PurchaseItemController;
use App\Http\Controllers\Admin\RemittanceController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SaleReturnController;
use App\Http\Controllers\Admin\SarafController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\StockReconciliationController;
use App\Http\Controllers\Admin\StockVarianceInvestigationController;
use App\Http\Controllers\Admin\StockControlManagementController;
use App\Http\Controllers\Admin\StockNotificationController;
use App\Http\Controllers\Admin\ReelInventoryController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\HR\DepartmentController;
use App\Http\Controllers\Admin\HR\HRDashboardController;
use App\Http\Controllers\Admin\HR\DesignationController;
use App\Http\Controllers\Admin\HR\HRSettingsController;
use App\Http\Controllers\Admin\TransactionsController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\Admin\BOMController;
use App\Http\Controllers\Admin\BusinessUnitSwitchController;
use App\Http\Controllers\Admin\CartonQuotationController;
use App\Http\Controllers\Admin\ProductionOrderController;
use App\Http\Controllers\Admin\ProcurementController;
use App\Http\Controllers\Admin\WorkOrderController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\ShareholderWithdrawalController;
use App\Http\Controllers\Admin\ShareholderController;
use App\Http\Controllers\Admin\ShareholderSettingsController;
use App\Http\Controllers\Admin\ProfitDistributionController;
use App\Models\Account;
use App\Http\Controllers\Admin\HR\EmployeeController;
use App\Http\Controllers\Admin\HR\AttendanceController;
use App\Http\Controllers\Admin\HR\LeaveController;
use App\Http\Controllers\Admin\HR\AdvanceController;
use App\Http\Controllers\Admin\HR\PayrollController;
use App\Http\Controllers\Admin\HR\ReportController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

Route::get('/purchase-orders/datatable/', [PurchaseOrderController::class, 'datatable'])
    ->name('admin.purchase-orders.datatable')
    ->middleware(['auth', 'permission.feedback:view purchase orders']);

// ============================================================
// MAIN ADMIN ROUTES - Authenticated access with route-level permissions
// ============================================================
Route::middleware(['auth'])->prefix('admin')->group(function () {

    // Online Users
    Route::get('/online-users', [OnlineUsersController::class, 'index'])->name('admin.online-users')->middleware('permission.feedback:view users');
    Route::get('/online-users/list', [OnlineUsersController::class, 'list'])->name('admin.online-users.list')->middleware('permission.feedback:view users');

    // Recalculate Balances
    Route::get('/recalculate-balances', function () {
        Artisan::call('balances:recalculate');
        return 'Balances recalculated successfully.';
    })->middleware('permission.feedback:update transactions');

    // Dashboard
    Route::post('/business-unit/switch/{businessUnit}', BusinessUnitSwitchController::class)
        ->name('admin.business-units.switch');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard')->middleware('permission.feedback:view dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('admin.dashboard.stats')->middleware('permission.feedback:view dashboard');
    Route::get('/dashboard/charts', [DashboardController::class, 'getChartData'])->name('admin.dashboard.charts')->middleware('permission.feedback:view dashboard');
    Route::get('/dashboard/recent', [DashboardController::class, 'getRecentActivity'])->name('admin.dashboard.recent')->middleware('permission.feedback:view dashboard');
    Route::get('/dashboard/alerts', [DashboardController::class, 'getAlerts'])->name('admin.dashboard.alerts')->middleware('permission.feedback:view dashboard');
    Route::get('/notifications/low-stock', [App\Http\Controllers\Admin\NotificationController::class, 'lowStock'])
        ->name('admin.notifications.low-stock')->middleware('permission.feedback:view stock');
    Route::get('/notifications/low-stock-data', [App\Http\Controllers\Admin\NotificationController::class, 'getLowStockData'])
        ->name('admin.notifications.low-stock-data')->middleware('permission.feedback:view stock');
    Route::get('/notifications/low-stock/export', [App\Http\Controllers\Admin\NotificationController::class, 'exportLowStockCSV'])
        ->name('admin.notifications.low-stock.export')->middleware('permission.feedback:view stock');
    Route::get('/exchange/get-latest', [App\Http\Controllers\Admin\ExchangeRateController::class, 'getLatestRate'])
        ->name('admin.exchange.get-latest')->middleware('permission.feedback:view exchange rates');

    // Stock Control Notifications
    Route::prefix('stock-notifications')->name('admin.stock-notifications.')->group(function () {
        Route::get('/', [StockNotificationController::class, 'index'])
            ->name('index')->middleware('permission.feedback:view stock reconciliations');
        Route::get('/{notification}/open', [StockNotificationController::class, 'open'])
            ->name('open')->middleware('permission.feedback:view stock reconciliations');
        Route::post('/{notification}/read', [StockNotificationController::class, 'markRead'])
            ->name('read')->middleware('permission.feedback:view stock reconciliations');
        Route::post('/read-all', [StockNotificationController::class, 'markAllRead'])
            ->name('read-all')->middleware('permission.feedback:view stock reconciliations');
        Route::patch('/settings', [StockNotificationController::class, 'updateSettings'])
            ->name('settings')->middleware('permission.feedback:view stock reconciliations');
    });

    // Physical Reel / Remnant Tracking
    Route::prefix('stock-reels')->name('admin.stock-reels.')->group(function () {
        Route::get('/', [ReelInventoryController::class, 'index'])
            ->name('index')->middleware('permission.feedback:view stock');
        Route::get('/batches/{purchaseItem}', [ReelInventoryController::class, 'show'])
            ->name('show')->middleware('permission.feedback:view stock');
        Route::post('/batches/{purchaseItem}/initialize', [ReelInventoryController::class, 'initialize'])
            ->name('initialize')->middleware('permission.feedback:update stock');
        Route::post('/batches/{purchaseItem}/reconciliation', [ReelInventoryController::class, 'startReconciliation'])
            ->name('reconciliation')->middleware('permission.feedback:create stock reconciliations');
        Route::post('/reels/{reel}/measure', [ReelInventoryController::class, 'measure'])
            ->name('measure')->middleware('permission.feedback:update stock');
        Route::patch('/reels/{reel}/status', [ReelInventoryController::class, 'status'])
            ->name('status')->middleware('permission.feedback:update stock');
        Route::post('/batches/{purchaseItem}/rebaseline', [ReelInventoryController::class, 'rebaseline'])
            ->name('rebaseline')->middleware('permission.feedback:update stock');
    });

    // Warehouses / location control
    Route::get('warehouses', [WarehouseController::class, 'index'])
        ->name('admin.warehouses.index')->middleware('permission.feedback:view warehouses');
    Route::get('warehouses/transfers', [WarehouseController::class, 'transfers'])
        ->name('admin.warehouses.transfers')->middleware('permission.feedback:view stock transfers');
    Route::get('warehouses/transfers/create', [WarehouseController::class, 'createTransfer'])
        ->name('admin.warehouses.transfers.create')->middleware('permission.feedback:create stock transfers');
    Route::post('warehouses/transfers', [WarehouseController::class, 'storeTransfer'])
        ->name('admin.warehouses.transfers.store')->middleware('permission.feedback:create stock transfers');
    Route::post('warehouses/transfers/{inventoryTransfer}/approve', [WarehouseController::class, 'approveTransfer'])
        ->name('admin.warehouses.transfers.approve')->middleware('permission.feedback:approve stock transfers');
    Route::post('warehouses/transfers/{inventoryTransfer}/complete', [WarehouseController::class, 'completeTransfer'])
        ->name('admin.warehouses.transfers.complete')->middleware('permission.feedback:update stock');
    Route::patch('warehouses/balances/{balance}/condition', [WarehouseController::class, 'updateCondition'])
        ->name('admin.warehouses.balances.condition')->middleware('permission.feedback:condition stock');

    // Stock
    Route::get('stock', [StockController::class, 'index'])->name('admin.stock.index')->middleware('permission.feedback:view stock');
    Route::get('/{product}/stock-in', [StockController::class, 'stockIn'])->name('admin.stock.stock-in')->middleware('permission.feedback:update stock');
    Route::get('/{product}/stock-out', [StockController::class, 'stockOut'])->name('admin.stock.stock-out')->middleware('permission.feedback:update stock');
    Route::get('products/{product}', [App\Http\Controllers\Admin\StockController::class, 'show'])->name('admin.products.show')->middleware('permission.feedback:view stock');
    Route::get('/fetch', [AccountsController::class, 'fetchAccounts'])->name('fetch')->middleware('permission.feedback:view customers');
    Route::get('/products/low-stock', [App\Http\Controllers\Admin\ProductController::class, 'getLowStockProducts'])->name('admin.products.low-stock')->middleware('permission.feedback:view stock');

    // Procure-to-Pay
    Route::prefix('procurement')->name('admin.procurement.')->group(function () {
        Route::get('/', [ProcurementController::class, 'index'])
            ->name('index')->middleware('permission.feedback:view purchase requests');

        Route::get('/requests/create', [ProcurementController::class, 'createRequest'])
            ->name('requests.create')->middleware('permission.feedback:create purchase requests');
        Route::post('/requests', [ProcurementController::class, 'storeRequest'])
            ->name('requests.store')->middleware('permission.feedback:create purchase requests');
        Route::post('/requests/{purchaseRequest}/submit', [ProcurementController::class, 'submitRequest'])
            ->name('requests.submit')->middleware('permission.feedback:submit purchase requests');
        Route::post('/requests/{purchaseRequest}/approve', [ProcurementController::class, 'approveRequest'])
            ->name('requests.approve')->middleware('permission.feedback:approve purchase requests');

        Route::get('/requests/{purchaseRequest}/rfq/create', [ProcurementController::class, 'createRfq'])
            ->name('rfqs.create')->middleware('permission.feedback:create rfq');
        Route::post('/requests/{purchaseRequest}/rfq', [ProcurementController::class, 'storeRfq'])
            ->name('rfqs.store')->middleware('permission.feedback:create rfq');
        Route::get('/rfqs/{rfq}', [ProcurementController::class, 'showRfq'])
            ->name('rfqs.show')->middleware('permission.feedback:view rfq');
        Route::post('/rfqs/{rfq}/open', [ProcurementController::class, 'openRfq'])
            ->name('rfqs.open')->middleware('permission.feedback:update rfq');
        Route::post('/rfqs/{rfq}/quotes', [ProcurementController::class, 'storeQuote'])
            ->name('rfqs.quotes.store')->middleware('permission.feedback:update rfq');
        Route::post('/quotes/{quote}/select', [ProcurementController::class, 'selectQuote'])
            ->name('quotes.select')->middleware('permission.feedback:award rfq');

        Route::get('/purchases/{purchase}/receipts/create', [ProcurementController::class, 'createReceipt'])
            ->name('receipts.create')->middleware('permission.feedback:create goods receipts');
        Route::post('/purchases/{purchase}/receipts', [ProcurementController::class, 'storeReceipt'])
            ->name('receipts.store')->middleware('permission.feedback:create goods receipts');
        Route::post('/receipts/{receipt}/post', [ProcurementController::class, 'postReceipt'])
            ->name('receipts.post')->middleware('permission.feedback:post goods receipts');

        Route::get('/purchases/{purchase}/invoices/create', [ProcurementController::class, 'createInvoice'])
            ->name('invoices.create')->middleware('permission.feedback:create supplier invoices');
        Route::post('/purchases/{purchase}/invoices', [ProcurementController::class, 'storeInvoice'])
            ->name('invoices.store')->middleware('permission.feedback:create supplier invoices');
        Route::post('/invoices/{invoice}/match', [ProcurementController::class, 'rematchInvoice'])
            ->name('invoices.match')->middleware('permission.feedback:match supplier invoices');
        Route::post('/invoices/{invoice}/approve', [ProcurementController::class, 'approveInvoice'])
            ->name('invoices.approve')->middleware('permission.feedback:approve supplier invoices');
        Route::post('/invoices/{invoice}/pay', [ProcurementController::class, 'payInvoice'])
            ->name('invoices.pay')->middleware('permission.feedback:pay supplier invoices');
    });

    Route::post('purchase-orders/{id}/approve', [PurchaseOrderController::class, 'approve'])
        ->name('admin.purchase-orders.approve')->middleware('permission.feedback:approve purchase orders');

    // Stock Reconciliation / Cycle Counts
    Route::prefix('stock-reconciliations')->name('admin.stock-reconciliations.')->group(function () {
        Route::get('/', [StockReconciliationController::class, 'index'])
            ->name('index')->middleware('permission.feedback:view stock reconciliations');
        Route::get('/create', [StockReconciliationController::class, 'create'])
            ->name('create')->middleware('permission.feedback:create stock reconciliations');
        Route::post('/', [StockReconciliationController::class, 'store'])
            ->name('store')->middleware('permission.feedback:create stock reconciliations');
        Route::get('/planning', [StockReconciliationController::class, 'planning'])
            ->name('planning')->middleware('permission.feedback:view stock reconciliations');
        Route::post('/planning/start', [StockReconciliationController::class, 'startPlannedCount'])
            ->name('planning.start')->middleware('permission.feedback:create stock reconciliations');
        Route::get('/trends', [StockReconciliationController::class, 'trends'])
            ->name('trends')->middleware('permission.feedback:view stock reconciliations');
        Route::get('/control-analysis', [StockReconciliationController::class, 'controlAnalysis'])
            ->name('control-analysis')->middleware('permission.feedback:view stock reconciliations');
        Route::get('/investigations', [StockVarianceInvestigationController::class, 'index'])
            ->name('investigations.index')->middleware('permission.feedback:view stock reconciliations');
        Route::get('/investigations/intelligence', [StockVarianceInvestigationController::class, 'intelligence'])
            ->name('investigations.intelligence')->middleware('permission.feedback:view stock reconciliations');
        Route::get('/investigations/{investigation}', [StockVarianceInvestigationController::class, 'show'])
            ->name('investigations.show')->middleware('permission.feedback:view stock reconciliations');
        Route::post('/adjustment-items/{stockAdjustmentItem}/investigation', [StockVarianceInvestigationController::class, 'store'])
            ->name('investigations.store')->middleware('permission.feedback:investigate stock reconciliations');
        Route::patch('/investigations/{investigation}', [StockVarianceInvestigationController::class, 'update'])
            ->name('investigations.update')->middleware('permission.feedback:investigate stock reconciliations');
        Route::post('/investigations/{investigation}/resolve', [StockVarianceInvestigationController::class, 'resolve'])
            ->name('investigations.resolve')->middleware('permission.feedback:resolve stock reconciliations');
        Route::get('/management-control', [StockControlManagementController::class, 'index'])
            ->name('management-control.index')->middleware('permission.feedback:view stock reconciliations');
        Route::post('/management-control/sync', [StockControlManagementController::class, 'sync'])
            ->name('management-control.sync')->middleware('permission.feedback:escalate stock reconciliations');
        Route::get('/management-control/escalations/{escalation}', [StockControlManagementController::class, 'showEscalation'])
            ->name('management-control.escalations.show')->middleware('permission.feedback:view stock reconciliations');
        Route::patch('/management-control/escalations/{escalation}/assign', [StockControlManagementController::class, 'assign'])
            ->name('management-control.escalations.assign')->middleware('permission.feedback:escalate stock reconciliations');
        Route::post('/management-control/escalations/{escalation}/acknowledge', [StockControlManagementController::class, 'acknowledge'])
            ->name('management-control.escalations.acknowledge')->middleware('permission.feedback:escalate stock reconciliations');
        Route::post('/management-control/escalations/{escalation}/close', [StockControlManagementController::class, 'close'])
            ->name('management-control.escalations.close')->middleware('permission.feedback:escalate stock reconciliations');
        Route::get('/management-control/reviews/{review}', [StockControlManagementController::class, 'showReview'])
            ->name('management-control.reviews.show')->middleware('permission.feedback:view stock reconciliations');
        Route::patch('/management-control/reviews/{review}', [StockControlManagementController::class, 'updateReview'])
            ->name('management-control.reviews.update')->middleware('permission.feedback:review stock reconciliations');
        Route::post('/management-control/reviews/{review}/complete', [StockControlManagementController::class, 'completeReview'])
            ->name('management-control.reviews.complete')->middleware('permission.feedback:review stock reconciliations');
        Route::get('/report', [StockReconciliationController::class, 'report'])
            ->name('report')->middleware('permission.feedback:view stock reconciliations');
        Route::get('/report/export-csv', [StockReconciliationController::class, 'exportCsv'])
            ->name('report.export-csv')->middleware('permission.feedback:view stock reconciliations');
        Route::get('/{stockReconciliation}', [StockReconciliationController::class, 'show'])
            ->name('show')->middleware('permission.feedback:view stock reconciliations');
        Route::patch('/{stockReconciliation}/counts', [StockReconciliationController::class, 'updateCounts'])
            ->name('counts.update')->middleware('permission.feedback:update stock reconciliations');
        Route::patch('/{stockReconciliation}/items/{item}', [StockReconciliationController::class, 'updateItem'])
            ->name('items.update')->middleware('permission.feedback:update stock reconciliations');
        Route::get('/{stockReconciliation}/print', [StockReconciliationController::class, 'print'])
            ->name('print')->middleware('permission.feedback:view stock reconciliations');
        Route::post('/{stockReconciliation}/cancel', [StockReconciliationController::class, 'cancel'])
            ->name('cancel')->middleware('permission.feedback:cancel stock reconciliations');
        Route::post('/{stockReconciliation}/submit', [StockReconciliationController::class, 'submit'])
            ->name('submit')->middleware('permission.feedback:submit stock reconciliations');
        Route::post('/{stockReconciliation}/approve', [StockReconciliationController::class, 'approve'])
            ->name('approve')->middleware('permission.feedback:approve stock reconciliations');
        Route::post('/{stockReconciliation}/reject', [StockReconciliationController::class, 'reject'])
            ->name('reject')->middleware('permission.feedback:approve stock reconciliations');
        Route::post('/{stockReconciliation}/post', [StockReconciliationController::class, 'post'])
            ->name('post')->middleware('permission.feedback:post stock reconciliations');
    });

    // ==================== ACCOUNTS ROUTES ====================
    Route::prefix('accounts')->name('admin.accounts.')->middleware('permission.feedback:view customers')->group(function () {
        Route::get('/', [AccountsController::class, 'index'])->name('index')->middleware('permission.feedback:view customers');
        Route::post('/', [AccountsController::class, 'store'])->name('store')->middleware('permission.feedback:create customers');
        Route::get('/{id}/edit', [AccountsController::class, 'edit'])->name('edit')->middleware('permission.feedback:update customers');
        Route::put('/{id}', [AccountsController::class, 'update'])->name('update')->middleware('permission.feedback:update customers');
        Route::get('/{id}', [AccountsController::class, 'show'])->name('show')->middleware('permission.feedback:view customers');
        Route::delete('/{id}', [AccountsController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete customers');
        Route::get('/update-stats', [AccountsController::class, 'updateStats'])->name('update-stats')->middleware('permission.feedback:update customers');
        Route::get('/{account}/print', [AccountsController::class, 'print'])->name('print');
        Route::get('/{account}/export-pdf', [AccountsController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/{account}/export-view', [AccountsController::class, 'exportView'])->name('export-view');
        Route::get('/{id}/info', [AccountsController::class, 'getAccountInfo'])->name('info');
        Route::get('/{id}/account', [AccountsController::class, 'showAccount'])->name('show-account');
        Route::get('/{id}/balances', [AccountsController::class, 'getBalancesByAccount'])->name('balances');
        Route::get('/{account}/balance/{currency}', [AccountsController::class, 'getCurrencyBalance'])->name('currency-balance');
        Route::get('/{id}/transactions/{type}', [AccountsController::class, 'transactionData'])->name('transaction-data');
        Route::get('/{id}/send-whatsapp', [AccountsController::class, 'sendWhatsApp'])->name('send-whatsapp')->middleware('permission.feedback:view customers');
        Route::post('/create-user', [AccountsController::class, 'createUserForAccount'])->name('create-user')->middleware('permission.feedback:create users');
        Route::post('/check-code', [AccountsController::class, 'checkCode'])->name('checkCode')->middleware('permission.feedback:create customers');
    });

    // ==================== PRODUCT ROUTES ====================
    Route::prefix('products')->name('admin.products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index')->middleware('permission.feedback:view products');
        Route::post('/', [ProductController::class, 'store'])->name('store')->middleware('permission.feedback:create products');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update')->middleware('permission.feedback:update products');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete products');
    });

    // ==================== PURCHASE ORDER ROUTES ====================
    Route::prefix('purchase-orders')->name('admin.purchase-orders.')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('index')->middleware('permission.feedback:view purchase orders');
        Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create')->middleware('permission.feedback:create purchase orders');
        Route::post('/', [PurchaseOrderController::class, 'store'])->name('store')->middleware('permission.feedback:create purchase orders');
        Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('show')->middleware('permission.feedback:view purchase orders');
        Route::get('/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('edit')->middleware('permission.feedback:update purchase orders');
        Route::put('/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('update')->middleware('permission.feedback:update purchase orders');
        Route::delete('/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete purchase orders');
        Route::patch('/{purchaseOrder}/status', [PurchaseOrderController::class, 'updateStatus'])->name('update-status')->middleware('permission.feedback:update purchase orders');
    });

    // Purchase Items Routes
    Route::prefix('purchase-items')->name('admin.purchase-items.')->group(function () {
        Route::post('/', [PurchaseItemController::class, 'store'])->name('store')->middleware('permission.feedback:create purchase orders');
        Route::get('/{purchaseItem}', [PurchaseItemController::class, 'show'])->name('show')->middleware('permission.feedback:view purchase orders');
        Route::put('/{purchaseItem}', [PurchaseItemController::class, 'update'])->name('update')->middleware('permission.feedback:update purchase orders');
        Route::delete('/{purchaseItem}', [PurchaseItemController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete purchase orders');
    });

    // Purchase Expenses Routes
    Route::prefix('purchase-expenses')->name('admin.purchase-expenses.')->group(function () {
        Route::post('/', [PurchaseExpenseController::class, 'store'])->name('store')->middleware('permission.feedback:create purchase orders');
        Route::get('/{purchaseExpense}', [PurchaseExpenseController::class, 'show'])->name('show')->middleware('permission.feedback:view purchase orders');
        Route::put('/{purchaseExpense}', [PurchaseExpenseController::class, 'update'])->name('update')->middleware('permission.feedback:update purchase orders');
        Route::delete('/{purchaseExpense}', [PurchaseExpenseController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete purchase orders');
    });

    // ==================== SALES ROUTES ====================
    Route::prefix('sales')->name('admin.sales.')->group(function () {
        Route::get('/{id}/check-status', [App\Http\Controllers\Admin\SaleController::class, 'checkStatus'])
            ->name('check-status')->middleware('permission.feedback:view sales');
        // Price Adjustment Routes
        Route::post('/item/{item}/apply-discount', [SaleController::class, 'applyDiscount'])
            ->name('item.apply-discount')->middleware('permission.feedback:update sales');
        Route::patch('/item/{item}/manual-price', [SaleController::class, 'updateManualPrice'])
            ->name('item.manual-price')->middleware('permission.feedback:update sales');

        Route::post('/item/{item}/reset-price', [SaleController::class, 'resetPrice'])
            ->name('item.reset-price')->middleware('permission.feedback:update sales');

        Route::post('/{sale}/bulk-discount', [SaleController::class, 'bulkApplyDiscount'])
            ->name('bulk-discount')->middleware('permission.feedback:update sales');
        Route::get('/boms-for-product', [SaleController::class, 'getBomsForProduct'])->name('boms-for-product')->middleware('permission.feedback:create sales');
        Route::get('/bom-details', [SaleController::class, 'getBomDetails'])->name('bom-details')->middleware('permission.feedback:create sales');
        Route::post('/add-item-with-bom', [SaleController::class, 'addItemWithBOM'])->name('add-item-with-bom')->middleware('permission.feedback:create sales');
        // Simple carton specification quotation (dimensions + board profile).
        Route::get('/carton-spec/options', [CartonQuotationController::class, 'options'])->name('carton-spec.options')->middleware('permission.feedback:create sales');
        Route::post('/{sale}/carton-spec/calculate', [CartonQuotationController::class, 'calculate'])->name('carton-spec.calculate')->middleware('permission.feedback:create sales');
        Route::post('/{sale}/carton-spec/add', [CartonQuotationController::class, 'add'])->name('carton-spec.add')->middleware('permission.feedback:create sales');
        Route::get('/', [SaleController::class, 'index'])->name('index')->middleware('permission.feedback:view sales');
        Route::get('/create', [SaleController::class, 'create'])->name('create')->middleware('permission.feedback:create sales');
        Route::post('/', [SaleController::class, 'store'])->name('store')->middleware('permission.feedback:create sales');
        Route::get('/{id}', [SaleController::class, 'show'])->name('show')->middleware('permission.feedback:view sales');
        Route::delete('/{id}', [SaleController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete sales');
        Route::get('/available-batches', [SaleController::class, 'getAvailableBatches'])->name('available-batches')->middleware('permission.feedback:create sales');
        Route::post('/add-item', [SaleController::class, 'addItem'])->name('add-item')->middleware('permission.feedback:create sales');
        Route::delete('/remove-item/{id}', [SaleController::class, 'removeItem'])->name('remove-item')->middleware('permission.feedback:delete sales');
        Route::get('/{id}/confirmation-data', [SaleController::class, 'getConfirmationData'])->name('confirmation-data')->middleware('permission.feedback:update sales');
        Route::post('/{id}/confirm', [SaleController::class, 'confirmSale'])->name('confirm')->middleware('permission.feedback:update sales');
        Route::patch('/{id}/status', [SaleController::class, 'updateStatus'])->name('update-status')->middleware('permission.feedback:update sales');
        Route::post('/{sale}/start-production', [SaleController::class, 'startProductionFromSale'])->name('start-production')->middleware('permission.feedback:update sales');
        Route::post('/{sale}/deliver', [SaleController::class, 'deliver'])->name('deliver')->middleware('permission.feedback:update sales');
        Route::get('/{id}/quotation', [SaleController::class, 'quotation'])->name('quotation')->middleware('permission.feedback:view sales');
        Route::patch('/item/{item}/quotation-description', [SaleController::class, 'updateQuotationDescription'])->name('item.quotation-description')->middleware('permission.feedback:update sales');
        Route::get('/{id}/gate-pass', [SaleController::class, 'gatePass'])->name('gate-pass')->middleware('permission.feedback:view sales');
        Route::get('/{id}/print', [SaleController::class, 'printInvoice'])->name('print')->middleware('permission.feedback:view sales');
        Route::get('/deleted', [SaleController::class, 'deleted'])->name('deleted')->middleware('permission.feedback:delete sales');
        Route::post('/{id}/restore', [SaleController::class, 'restore'])->name('restore')->middleware('permission.feedback:delete sales');
        Route::delete('/{id}/force', [SaleController::class, 'forceDelete'])->name('force-delete')->middleware('permission.feedback:delete sales');
    });

    // ==================== SUPPLIER ROUTES ====================
    Route::prefix('suppliers')->name('admin.suppliers.')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('index')->middleware('permission.feedback:view suppliers');
        Route::post('/', [SupplierController::class, 'store'])->name('store')->middleware('permission.feedback:create suppliers');
        Route::post('/check-code', [SupplierController::class, 'checkCode'])->name('check-code')->middleware('permission.feedback:create suppliers');
        Route::get('/generate-code', [SupplierController::class, 'generateCode'])->name('generate-code')->middleware('permission.feedback:create suppliers');
        Route::get('/{id}/edit', [SupplierController::class, 'edit'])->name('edit')->middleware('permission.feedback:update suppliers');
        Route::put('/{id}', [SupplierController::class, 'update'])->name('update')->middleware('permission.feedback:update suppliers');
        Route::get('/{id}', [SupplierController::class, 'show'])->name('show')->middleware('permission.feedback:view suppliers');
        Route::delete('/{id}', [SupplierController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete suppliers');
        Route::get('/{id}/print', [SupplierController::class, 'print'])->name('print')->middleware('permission.feedback:view suppliers');
        Route::get('/{id}/send-whatsapp', [SupplierController::class, 'sendWhatsApp'])->name('send-whatsapp')->middleware('permission.feedback:view suppliers');
    });

    // ==================== SARAF ROUTES ====================
    Route::prefix('sarafs')->name('admin.sarafs.')->group(function () {
        Route::get('/', [SarafController::class, 'index'])->name('index')->middleware('permission.feedback:view sarafs');
        Route::post('/', [SarafController::class, 'store'])->name('store')->middleware('permission.feedback:create sarafs');
        Route::post('/check-code', [SarafController::class, 'checkCode'])->name('check-code')->middleware('permission.feedback:create sarafs');
        Route::get('/generate-code', [SarafController::class, 'generateCode'])->name('generate-code')->middleware('permission.feedback:create sarafs');
        Route::get('/{id}/edit', [SarafController::class, 'edit'])->name('edit')->middleware('permission.feedback:update sarafs');
        Route::put('/{id}', [SarafController::class, 'update'])->name('update')->middleware('permission.feedback:update sarafs');
        Route::get('/{id}', [SarafController::class, 'show'])->name('show')->middleware('permission.feedback:view sarafs');
        Route::delete('/{id}', [SarafController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete sarafs');
        Route::get('/{id}/print', [SarafController::class, 'print'])->name('print')->middleware('permission.feedback:view sarafs');
        Route::get('/{id}/send-whatsapp', [SarafController::class, 'sendWhatsApp'])->name('send-whatsapp')->middleware('permission.feedback:view sarafs');
    });

    // ==================== CUSTOMER ROUTES ====================
    Route::prefix('customers')->name('admin.customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index')->middleware('permission.feedback:view customers');
        Route::post('/', [CustomerController::class, 'store'])->name('store')->middleware('permission.feedback:create customers');
        Route::post('/check-code', [CustomerController::class, 'checkCode'])->name('check-code')->middleware('permission.feedback:create customers');
        Route::get('/generate-code', [CustomerController::class, 'generateCode'])->name('generate-code')->middleware('permission.feedback:create customers');
        Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('edit')->middleware('permission.feedback:update customers');
        Route::put('/{id}', [CustomerController::class, 'update'])->name('update')->middleware('permission.feedback:update customers');
        Route::get('/{id}', [CustomerController::class, 'show'])->name('show')->middleware('permission.feedback:view customers');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete customers');
        Route::get('/{id}/print', [CustomerController::class, 'print'])->name('print')->middleware('permission.feedback:view customers');
        Route::get('/{id}/export-pdf', [CustomerController::class, 'exportPdf'])->name('export-pdf')->middleware('permission.feedback:view customers');
        Route::get('/{id}/export-csv', [CustomerController::class, 'exportCsv'])->name('export-csv')->middleware('permission.feedback:view customers');
        Route::get('/{id}/send-whatsapp', [CustomerController::class, 'sendWhatsApp'])->name('send-whatsapp')->middleware('permission.feedback:view customers');
        Route::get('/{id}/transaction-data', [CustomerController::class, 'transactionData'])->name('transaction-data')->middleware('permission.feedback:view customers');
        Route::get('/{id}/sales-data', [CustomerController::class, 'salesData'])->name('sales-data')->middleware('permission.feedback:view customers');
        Route::get('/{id}/balance-summary', [CustomerController::class, 'balanceSummary'])->name('balance-summary')->middleware('permission.feedback:view customers');
    });

    // ==================== AGENT ROUTES ====================
    Route::prefix('agents')->name('admin.agents.')->group(function () {
        Route::get('/', [AgentController::class, 'index'])->name('index')->middleware('permission.feedback:view agents');
        Route::post('/', [AgentController::class, 'store'])->name('store')->middleware('permission.feedback:create agents');
        Route::get('/fetch', [AgentController::class, 'fetchAgents'])->name('fetch')->middleware('permission.feedback:view agents');
        Route::get('/update-stats', [AgentController::class, 'updateStats'])->name('update-stats')->middleware('permission.feedback:update agents');
        Route::post('/check-code', [AgentController::class, 'checkCode'])->name('check-code')->middleware('permission.feedback:create agents');
        Route::get('/{id}/edit', [AgentController::class, 'edit'])->name('edit')->middleware('permission.feedback:update agents');
        Route::put('/{id}', [AgentController::class, 'update'])->name('update')->middleware('permission.feedback:update agents');
        Route::get('/{id}', [AgentController::class, 'show'])->name('show')->middleware('permission.feedback:view agents');
        Route::delete('/{id}', [AgentController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete agents');
        Route::get('/{id}/print', [AgentController::class, 'print'])->name('print')->middleware('permission.feedback:view agents');
        Route::get('/{id}/account', [AgentController::class, 'showAccount'])->name('show-account')->middleware('permission.feedback:view agents');
        Route::get('/{id}/send-whatsapp', [AccountsController::class, 'sendWhatsApp'])->name('send-whatsapp')->middleware('permission.feedback:view agents');
    });

    // ==================== APP SETTINGS ====================
    Route::prefix('app')->name('admin.app.')->controller(AppSettingsController::class)->group(function () {
        Route::get('company', 'company')->name('company.index')->middleware('permission.feedback:view company settings');
        Route::get('categories', 'categories')->name('categories.index')->middleware('permission.feedback:view categories');
        Route::post('categories/store', 'storeCategory')->name('categories.store')->middleware('permission.feedback:create categories');
        Route::post('categories/update/{id}', 'updateCategory')->name('categories.update')->middleware('permission.feedback:update categories');
        Route::delete('categories/delete/{id}', 'deleteCategory')->name('categories.delete')->middleware('permission.feedback:delete categories');
        Route::get('units', 'units')->name('units.index')->middleware('permission.feedback:view units');
        Route::get('invoice-templates', 'invoiceTemplates')->name('invoice-templates.index')->middleware('permission.feedback:view invoice templates');
    });

    // ==================== EXCHANGE RATES ====================
    Route::prefix('exchange-rates')->name('admin.exchange-rates.')->group(function () {
        Route::get('/', [ExchangeRateController::class, 'index'])->name('index')->middleware('permission.feedback:view exchange rates');
        Route::get('/data', [ExchangeRateController::class, 'data'])->name('data')->middleware('permission.feedback:view exchange rates');
        Route::post('/', [ExchangeRateController::class, 'store'])->name('store')->middleware('permission.feedback:create exchange rates');
        Route::get('/{id}', [ExchangeRateController::class, 'show'])->name('show')->middleware('permission.feedback:view exchange rates');
        Route::post('/send-whatsapp', [ExchangeRateController::class, 'sendRateMessage'])->name('send-whatsapp')->middleware('permission.feedback:view exchange rates');
        Route::put('/{id}', [ExchangeRateController::class, 'update'])->name('update')->middleware('permission.feedback:update exchange rates');
        Route::get('/fetch', [ExchangeRateController::class, 'fetch'])->name('fetch')->middleware('permission.feedback:view exchange rates');
        Route::delete('/{id}', [ExchangeRateController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete exchange rates');
    });

    // ==================== EXCHANGE ====================
    Route::prefix('exchange')->name('admin.exchange.')->middleware('permission.feedback:view exchange sales')->group(function () {
        Route::get('/', [ExchangeController::class, 'index'])->name('index');
        Route::post('/', [ExchangeController::class, 'store'])->name('store')->middleware('permission.feedback:create exchange sales');
        Route::get('/data', [ExchangeController::class, 'data'])->name('data');
        Route::get('/totals', [ExchangeController::class, 'totals'])->name('totals');
        Route::get('/{id}/receipt', [ExchangeController::class, 'showReceipt'])->name('receipt')->middleware('permission.feedback:view exchange sales');
        Route::get('/get-rate', [ExchangeController::class, 'getRate'])->name('getRate');
        Route::get('/{id}', [ExchangeController::class, 'show'])->name('show')->middleware('permission.feedback:view exchange sales');
        Route::post('/{id}/send-whatsapp', [ExchangeController::class, 'sendWhatsApp'])->name('sendWhatsApp')->middleware('permission.feedback:view exchange sales');
        Route::delete('/{exchange}', [ExchangeController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete exchange sales');
    });

    // ==================== EXCHANGE PURCHASE ====================
    Route::prefix('exchange-purchase')->name('admin.exchange-purchase.')->group(function () {
        Route::get('/', [ExchangePurchaseController::class, 'index'])->name('index')->middleware('permission.feedback:view exchange purchase');
        Route::post('/', [ExchangePurchaseController::class, 'store'])->name('store')->middleware('permission.feedback:create exchange purchase');
        Route::get('/data', [ExchangePurchaseController::class, 'data'])->name('data')->middleware('permission.feedback:view exchange purchase');
        Route::get('/{id}/receipt', [ExchangePurchaseController::class, 'showReceipt'])->name('receipt')->middleware('permission.feedback:view exchange purchase');
        Route::get('/totals', [ExchangePurchaseController::class, 'totals'])->name('totals')->middleware('permission.feedback:view exchange purchase');
        Route::get('/{id}', [ExchangePurchaseController::class, 'show'])->name('show')->middleware('permission.feedback:view exchange purchase');
        Route::post('/{id}/process', [ExchangePurchaseController::class, 'process'])->name('process')->middleware('permission.feedback:update exchange purchase');
        Route::post('/{id}/send-whatsapp', [ExchangePurchaseController::class, 'sendWhatsApp'])->name('sendWhatsApp')->middleware('permission.feedback:view exchange purchase');
        Route::delete('/{exchange}', [ExchangePurchaseController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete exchange purchase');
    });

    // ==================== REMITTANCES ====================
    Route::prefix('remittances')->name('admin.remittances.')->group(function () {
        Route::get('/', [RemittanceController::class, 'index'])->name('index')->middleware('permission.feedback:view remittances');
        Route::post('/', [RemittanceController::class, 'store'])->name('store')->middleware('permission.feedback:create remittances');
        Route::get('/data', [RemittanceController::class, 'data'])->name('data')->middleware('permission.feedback:view remittances');
        Route::get('/summary', [RemittanceController::class, 'summary'])->name('summary')->middleware('permission.feedback:view remittances');
        Route::get('/{id}', [RemittanceController::class, 'show'])->name('show')->middleware('permission.feedback:view remittances');
        Route::get('/{id}/edit', [RemittanceController::class, 'edit'])->name('edit')->middleware('permission.feedback:update remittances');
        Route::post('/{id}/update', [RemittanceController::class, 'update'])->name('update')->middleware('permission.feedback:update remittances');
        Route::get('/{id}/whatsapp', [RemittanceController::class, 'sendWhatsApp'])->name('whatsapp')->middleware('permission.feedback:view remittances');
        Route::post('/{id}/approve', [RemittanceController::class, 'approve'])->name('approve')->middleware('permission.feedback:update remittances');
        Route::get('/{id}/print', [RemittanceController::class, 'print'])->name('print')->middleware('permission.feedback:view remittances');
        Route::delete('/{id}', [RemittanceController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete remittances');
    });

    // ==================== REPORTS ====================
    Route::prefix('reports')->name('admin.reports.')->group(function () {
        Route::get('/customers', [ReportsController::class, 'customers'])->name('customers')->middleware('permission.feedback:view customers reports');
        Route::get('/customers/data', [ReportsController::class, 'customersData'])->name('customers.data')->middleware('permission.feedback:view customers reports');
        Route::get('/customers/export/pdf', [ReportsController::class, 'customersPdf'])->name('customers.export.pdf')->middleware('permission.feedback:view customers reports');
        Route::get('/customers/sub-category', [ReportsController::class, 'getCustomersBySubCategory'])->name('getCustomersBySubCategory')->middleware('permission.feedback:view customers reports');
        Route::get('/exchange', [ReportsController::class, 'exchange'])->name('exchange')->middleware('permission.feedback:view reports');
        Route::get('/exchange/data', [ReportsController::class, 'exchangeData'])->name('exchange.data')->middleware('permission.feedback:view reports');
        Route::get('/exchange/export/pdf', [ReportsController::class, 'exchangePdf'])->name('exchange.export.pdf')->middleware('permission.feedback:view reports');
        Route::get('/remittance', [ReportsController::class, 'remittance'])->name('remittance')->middleware('permission.feedback:view reports');
        Route::get('/remittance/data', [ReportsController::class, 'remittanceData'])->name('remittance.data')->middleware('permission.feedback:view reports');
        Route::get('/remittance/export/pdf', [ReportsController::class, 'remittancePdf'])->name('remittance.export.pdf')->middleware('permission.feedback:view reports');
        Route::get('/creditors', [ReportsController::class, 'creditors'])->name('creditors')->middleware('permission.feedback:view reports');
        Route::get('/creditors/data', [ReportsController::class, 'creditorsData'])->name('creditors.data')->middleware('permission.feedback:view reports');
        Route::get('/creditors/export/pdf', [ReportsController::class, 'creditorsPdf'])->name('creditors.export.pdf')->middleware('permission.feedback:view reports');
        Route::get('/debtors', [ReportsController::class, 'debtors'])->name('debtors')->middleware('permission.feedback:view reports');
        Route::get('/debtors/data', [ReportsController::class, 'debtorsData'])->name('debtors.data')->middleware('permission.feedback:view reports');
        Route::get('/debtors/export/pdf', [ReportsController::class, 'debtorsPdf'])->name('debtors.export.pdf')->middleware('permission.feedback:view reports');
    });

    // ==================== USERS ====================
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', [UsersController::class, 'index'])->name('index')->middleware('permission.feedback:view users');
        Route::get('/fetch', [UsersController::class, 'fetchUsers'])->name('fetch')->middleware('permission.feedback:view users');
        Route::get('/create', [UsersController::class, 'create'])->name('create')->middleware('permission.feedback:create users');
        Route::post('/', [UsersController::class, 'store'])->name('store')->middleware('permission.feedback:create users');
        Route::get('/{id}', [UsersController::class, 'show'])->name('show')->middleware('permission.feedback:view users');
        Route::get('/{id}/edit', [UsersController::class, 'edit'])->name('edit')->middleware('permission.feedback:update users');
        Route::put('/{id}', [UsersController::class, 'update'])->name('update')->middleware('permission.feedback:update users');
        Route::delete('/{id}', [UsersController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete users');
        Route::get('/{id}/update-status', [UsersController::class, 'updateStatus'])->name('update-status')->middleware('permission.feedback:update users');
        Route::put('/{id}/update-password', [UsersController::class, 'updatePassword'])->name('update-password')->middleware('permission.feedback:update users');
        Route::post('/{id}/assign-permissions', [UsersController::class, 'assignPermissions'])->name('assign-permissions')->middleware('permission.feedback:update users');
        Route::post('/{id}/update-permissions', [UsersController::class, 'updatePermissions'])->name('update-permissions')->middleware('permission.feedback:update users');
        Route::post('/{id}/business-units', [UsersController::class, 'updateBusinessUnits'])->name('business-units')->middleware('permission.feedback:update users');
    });

    // ==================== ROLES ====================
    Route::get('roles', [RolesController::class, 'index'])->name('admin.roles.index')->middleware('permission.feedback:view roles');
    Route::post('roles', [RolesController::class, 'store'])->name('admin.roles.store')->middleware('permission.feedback:create roles');
    Route::put('roles/{role}', [RolesController::class, 'update'])->name('admin.roles.update')->middleware('permission.feedback:update roles');
    Route::delete('roles/{role}', [RolesController::class, 'destroy'])->name('admin.roles.destroy')->middleware('permission.feedback:delete roles');
    Route::get('/roles/{role}/permissions-json', [PermissionsController::class, 'rolePermissionsJson'])->middleware('permission.feedback:view permissions');

    // ==================== PERMISSIONS ====================
    Route::prefix('permissions')->name('admin.permissions.')->middleware(['permission.feedback:view permissions'])->group(function () {
        Route::get('/data', [PermissionsController::class, 'data'])->name('data');
        Route::post('/assign', [PermissionsController::class, 'assign'])->name('assign')->middleware('permission.feedback:update permissions');
        Route::get('/', [PermissionsController::class, 'index'])->name('index');
        Route::post('/', [PermissionsController::class, 'store'])->name('store')->middleware('permission.feedback:create permissions');
        Route::put('/{id}', [PermissionsController::class, 'update'])->name('update')->middleware('permission.feedback:update permissions');
        Route::delete('/{id}', [PermissionsController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete permissions');
    });

    // ==================== CURRENCIES ====================
    Route::prefix('currencies')->name('admin.currencies.')->middleware('permission.feedback:view currencies')->group(function () {
        Route::get('/', [CurrenciesController::class, 'index'])->name('index');
        Route::post('/', [CurrenciesController::class, 'store'])->name('store')->middleware('permission.feedback:create currencies');
        Route::get('/fetch', [CurrenciesController::class, 'fetch'])->name('fetch');
        Route::get('/{currency}/edit', [CurrenciesController::class, 'edit'])->name('edit')->middleware('permission.feedback:update currencies');
        Route::put('/{currency}', [CurrenciesController::class, 'update'])->name('update')->middleware('permission.feedback:update currencies');
        Route::delete('/{currency}', [CurrenciesController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete currencies');
        Route::post('/{currency}/toggle', [CurrenciesController::class, 'toggleActive'])->name('toggle')->middleware('permission.feedback:update currencies');
    });

    // ==================== EXPENSE ROUTES ====================
    Route::prefix('expenses')->name('admin.expenses.')->group(function () {
        Route::get('/', [ExpensesController::class, 'index'])->name('index')->middleware('permission.feedback:view expenses');
        Route::post('/', [ExpensesController::class, 'store'])->name('store')->middleware('permission.feedback:create expenses');
        Route::post('/check-code', [ExpensesController::class, 'checkCode'])->name('check-code')->middleware('permission.feedback:create expenses');
        Route::get('/generate-code', [ExpensesController::class, 'generateCode'])->name('generate-code')->middleware('permission.feedback:create expenses');
        Route::get('/{id}/edit', [ExpensesController::class, 'edit'])->name('edit')->middleware('permission.feedback:update expenses');
        Route::put('/{id}', [ExpensesController::class, 'update'])->name('update')->middleware('permission.feedback:update expenses');
        Route::get('/{id}', [ExpensesController::class, 'show'])->name('show')->middleware('permission.feedback:view expenses');
        Route::delete('/{id}', [ExpensesController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete expenses');
        Route::get('/{id}/print', [ExpensesController::class, 'print'])->name('print')->middleware('permission.feedback:view expenses');
    });

    // ==================== SALE RETURNS ====================
    Route::prefix('sale-returns')->name('admin.sale-returns.')->group(function () {
        Route::get('/', [SaleReturnController::class, 'index'])->name('index')->middleware('permission.feedback:view sale returns');
        Route::get('/create', [SaleReturnController::class, 'create'])->name('create')->middleware('permission.feedback:create sale returns');
        Route::post('/', [SaleReturnController::class, 'store'])->name('store')->middleware('permission.feedback:create sale returns');
        Route::get('/get-sale-items', [SaleReturnController::class, 'getSaleItems'])->name('get-sale-items')->middleware('permission.feedback:view sale returns');
        Route::get('/{id}', [SaleReturnController::class, 'show'])->name('show')->middleware('permission.feedback:view sale returns');
        Route::put('/{id}', [SaleReturnController::class, 'update'])->name('update')->middleware('permission.feedback:update sale returns');
        Route::patch('/{id}/status', [SaleReturnController::class, 'updateStatus'])->name('update-status')->middleware('permission.feedback:update sale returns');
        Route::post('/{id}/process', [SaleReturnController::class, 'process'])->name('process')->middleware('permission.feedback:update sale returns');
        Route::delete('/{id}', [SaleReturnController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete sale returns');
    });

    // ==================== TRANSACTIONS ====================
    Route::prefix('transactions')->name('admin.transactions.')->middleware('permission.feedback:view transactions')->group(function () {
        Route::get('/fetch/{id}', [TransactionsController::class, 'fetch'])->name('fetch');
        Route::get('/account-balances', [TransactionsController::class, 'getAccountBalances'])->name('account-balances');
        Route::get('/export', [TransactionsController::class, 'export'])->name('export');
        Route::get('/balances', [TransactionsController::class, 'balances'])->name('balances');
        Route::get('/get-accounts-by-type', [TransactionsController::class, 'getAccountsByType'])->name('get-accounts-by-type');
        Route::delete('/{transaction}', [TransactionsController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete transactions');
        Route::get('/', [TransactionsController::class, 'index'])->name('index');
        Route::post('/', [TransactionsController::class, 'store'])->name('store')->middleware('permission.feedback:create transactions');
    });

    // ==================== ACCOUNT CATEGORIES ====================
    Route::prefix('account-categories')->name('admin.account-categories.')->group(function () {
        Route::get('/', [AccountCategoriesController::class, 'index'])->name('index')->middleware('permission.feedback:view account categories');
        Route::get('/data', [AccountCategoriesController::class, 'data'])->name('data')->middleware('permission.feedback:view account categories');
        Route::post('/', [AccountCategoriesController::class, 'store'])->name('store')->middleware('permission.feedback:create account categories');
        Route::put('/{id}', [AccountCategoriesController::class, 'update'])->name('update')->middleware('permission.feedback:update account categories');
        Route::delete('/{id}', [AccountCategoriesController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete account categories');
    });

    // ==================== ACCOUNT SUB CATEGORIES ====================
    Route::prefix('account-sub-categories')->name('admin.account-sub-categories.')->group(function () {
        Route::get('/', [AccountSubCategoriesController::class, 'index'])->name('index')->middleware('permission.feedback:view account sub categories');
        Route::post('/', [AccountSubCategoriesController::class, 'store'])->name('store')->middleware('permission.feedback:create account sub categories');
        Route::get('/{id}', [AccountSubCategoriesController::class, 'show'])->name('show')->middleware('permission.feedback:view account sub categories');
        Route::put('/{id}', [AccountSubCategoriesController::class, 'update'])->name('update')->middleware('permission.feedback:update account sub categories');
        Route::delete('/{id}', [AccountSubCategoriesController::class, 'destroy'])->name('destroy')->middleware('permission.feedback:delete account sub categories');
    });

    // ==================== JOURNAL ====================
    Route::prefix('journal')->name('admin.journal.')->middleware('permission.feedback:view journal')->group(function () {
        Route::get('/', [JournalController::class, 'index'])->name('index');
        Route::get('/data', [JournalController::class, 'data'])->name('data');
        Route::get('/summary', [JournalController::class, 'summary'])->name('summary');
        Route::get('/export', [JournalController::class, 'export'])->name('export');
    });

    // ==================== SYSTEM SETTINGS ====================
    Route::prefix('settings')->name('admin.settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index')->middleware('permission.feedback:view settings');
        Route::post('/', [SettingsController::class, 'update'])->name('update')->middleware('permission.feedback:update settings');
    });

    // ==================== WHATSAPP ====================
    Route::prefix('whatsapp')->name('admin.whatsapp.')->middleware('permission.feedback:view settings')->group(function () {
        Route::get('/', [WhatsAppController::class, 'index'])->name('index');
        Route::get('/fetch-session-id', [WhatsAppController::class, 'fetchSessionId']);
    });

    // ============================================================
    // BOM MANAGEMENT
    // ============================================================
    Route::get('bom/calculator', [BOMController::class, 'calculator'])->name('bom.calculator')->middleware('permission.feedback:view bom');
    Route::get('bom/get-by-product', [BOMController::class, 'getBOMByProduct'])->name('bom.get-by-product')->middleware('permission.feedback:view bom');
    Route::get('/bom', [BOMController::class, 'index'])->name('bom.index')->middleware('permission.feedback:view bom');
    Route::get('/bom/create', [BOMController::class, 'create'])->name('bom.create')->middleware('permission.feedback:create bom');
    Route::post('/bom', [BOMController::class, 'store'])->name('bom.store')->middleware('permission.feedback:create bom');
    Route::get('/bom/{bom}', [BOMController::class, 'show'])->name('bom.show')->middleware('permission.feedback:view bom');
    Route::get('/bom/{bom}/edit', [BOMController::class, 'edit'])->name('bom.edit')->middleware('permission.feedback:update bom');
    Route::put('/bom/{bom}', [BOMController::class, 'update'])->name('bom.update')->middleware('permission.feedback:update bom');
    Route::delete('/bom/{bom}', [BOMController::class, 'destroy'])->name('bom.destroy')->middleware('permission.feedback:delete bom');
    Route::post('/bom/{bom}/clone', [BOMController::class, 'clone'])->name('bom.clone')->middleware('permission.feedback:create bom');
    Route::post('/bom/{bom}/toggle-status', [BOMController::class, 'toggleStatus'])->name('bom.toggle-status')->middleware('permission.feedback:update bom');
    Route::post('/bom/calculate', [BOMController::class, 'calculate'])->name('bom.calculate')->middleware('permission.feedback:create bom');
    Route::get('bom/get-material-cost/{material_id}', [BOMController::class, 'getMaterialCost'])->name('bom.get-material-cost')->middleware('permission.feedback:view bom');

    // ============================================================
    // PRODUCTION MANAGEMENT
    // ============================================================

    Route::get('products/list', [ProductController::class, 'list'])->name('admin.products.list')->middleware('permission.feedback:view products');
    Route::get('production-orders', [ProductionOrderController::class, 'index'])->name('production-orders.index')->middleware('permission.feedback:view production orders');
    Route::get('production-orders/create', [ProductionOrderController::class, 'create'])->name('production-orders.create')->middleware('permission.feedback:create production orders');
    Route::post('production-orders', [ProductionOrderController::class, 'store'])->name('production-orders.store')->middleware('permission.feedback:create production orders');
    Route::post('production-orders/materials', [ProductionOrderController::class, 'getProductionMaterials'])->name('production-orders.materials')->middleware('permission.feedback:create production orders');
    Route::get('production-orders/{productionOrder}', [ProductionOrderController::class, 'show'])->name('production-orders.show')->middleware('permission.feedback:view production orders');
    Route::get('production-orders/{productionOrder}/edit', [ProductionOrderController::class, 'edit'])->name('production-orders.edit')->middleware('permission.feedback:update production orders');
    Route::put('production-orders/{productionOrder}', [ProductionOrderController::class, 'update'])->name('production-orders.update')->middleware('permission.feedback:update production orders');
    Route::post('production-orders/{productionOrder}/start', [ProductionOrderController::class, 'startProduction'])->name('production-orders.start')->middleware('permission.feedback:update production orders');
    Route::post('production-orders/{productionOrder}/approve', [ProductionOrderController::class, 'approveProduction'])->name('production-orders.approve')->middleware('permission.feedback:approve production orders');
    Route::post('production-orders/{productionOrder}/complete', [ProductionOrderController::class, 'completeProduction'])->name('production-orders.complete')->middleware('permission.feedback:update production orders');
    Route::post('production-orders/{productionOrder}/close', [ProductionOrderController::class, 'closeProduction'])->name('production-orders.close')->middleware('permission.feedback:close production orders');
    Route::post('production-orders/{productionOrder}/reopen', [ProductionOrderController::class, 'reopenProduction'])->name('production-orders.reopen')->middleware('permission.feedback:reopen production orders');
    Route::post('production-orders/{productionOrder}/reverse-completion', [ProductionOrderController::class, 'reverseCompletion'])->name('production-orders.reverse-completion')->middleware('permission.feedback:reverse production orders');
    Route::post('production-orders/{productionOrder}/cancel', [ProductionOrderController::class, 'cancelProduction'])->name('production-orders.cancel')->middleware('permission.feedback:update production orders');
    Route::get('production-orders/data', [ProductionOrderController::class, 'data'])->name('production-orders.data')->middleware('permission.feedback:view production orders');
    Route::get('sales/{sale}/details', [SaleController::class, 'getSaleDetails'])->name('admin.sales.details')->middleware('permission.feedback:view sales');
    Route::get('sales/{id}/currency', [SaleController::class, 'getSaleCurrency'])
        ->name('admin.sales.currency')->middleware('permission.feedback:view sales');
    Route::post('sales/add-calculated-item', [App\Http\Controllers\Admin\SaleController::class, 'addCalculatedItem'])
        ->name('admin.sales.add-calculated-item')->middleware('permission.feedback:create sales');
    Route::post('sales/create-bom-from-calculator', [App\Http\Controllers\Admin\SaleController::class, 'createBOMFromCalculator'])
        ->name('admin.sales.create-bom-from-calculator')->middleware('permission.feedback:create bom');
    Route::get('sales/get-material-stock-cost', [App\Http\Controllers\Admin\SaleController::class, 'getMaterialStockCost'])
        ->name('admin.sales.get-material-stock-cost')->middleware('permission.feedback:create sales');

    // Work Orders
    Route::get('work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index')->middleware('permission.feedback:view work orders');
    Route::get('work-orders/create', [WorkOrderController::class, 'create'])->name('work-orders.create')->middleware('permission.feedback:create work orders');
    Route::post('work-orders', [WorkOrderController::class, 'store'])->name('work-orders.store')->middleware('permission.feedback:create work orders');
    Route::get('work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show')->middleware('permission.feedback:view work orders');
    Route::get('work-orders/{workOrder}/edit', [WorkOrderController::class, 'edit'])->name('work-orders.edit')->middleware('permission.feedback:update work orders');
    Route::put('work-orders/{workOrder}', [WorkOrderController::class, 'update'])->name('work-orders.update')->middleware('permission.feedback:update work orders');
    Route::delete('work-orders/{workOrder}', [WorkOrderController::class, 'destroy'])->name('work-orders.destroy')->middleware('permission.feedback:delete work orders');
    Route::post('work-orders/{workOrder}/start', [WorkOrderController::class, 'start'])->name('work-orders.start')->middleware('permission.feedback:update work orders');
    Route::post('work-orders/{workOrder}/complete', [WorkOrderController::class, 'complete'])->name('work-orders.complete')->middleware('permission.feedback:update work orders');
    Route::get('work-orders/data', [WorkOrderController::class, 'data'])->name('work-orders.data')->middleware('permission.feedback:view work orders');

    // ============================================================
    // AUDIT LOGS
    // ============================================================
    Route::prefix('audit')->name('admin.audit.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index')->middleware('permission.feedback:view audit logs');
        Route::get('/data', [AuditLogController::class, 'data'])
            ->name('data')
            ->middleware('permission:view audit logs');

        Route::get('/{id}', [AuditLogController::class, 'show'])
            ->name('show')
            ->middleware('permission:view audit logs');

        Route::post('/clear', [AuditLogController::class, 'clear'])
            ->name('clear')
            ->middleware('permission:view audit logs');

        Route::get('/export', [AuditLogController::class, 'export'])
            ->name('export')
            ->middleware('permission:view audit logs');

        Route::get('/stats', [AuditLogController::class, 'getStats'])
            ->name('stats')
            ->middleware('permission:view audit logs');
    });

    // ============================================================
    // HUMAN RESOURCES ROUTES
    // ============================================================
    Route::prefix('hr')->name('admin.hr.')->group(function () {
        // HR Dashboard
        Route::get('dashboard', [HRDashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware('permission:view hr dashboard');

        // ─── Departments ───
        Route::resource('departments', DepartmentController::class)
            ->except(['create', 'edit'])
            ->middleware('permission:view departments|create departments|edit departments|delete departments');
        Route::post('departments/{department}/toggle-status', [DepartmentController::class, 'toggleStatus'])
            ->name('departments.toggle-status')
            ->middleware('permission:edit departments');

        // ─── Designations ───
        Route::resource('designations', DesignationController::class)
            ->except(['create'])
            ->middleware('permission:view designations|create designations|edit designations|delete designations');
        Route::post('designations/{designation}/toggle-status', [DesignationController::class, 'toggleStatus'])
            ->name('designations.toggle-status')
            ->middleware('permission:edit designations');

        // ─── Employees ───
        Route::resource('employees', EmployeeController::class)
            ->middleware('permission:view employees|create employees|edit employees|delete employees');
        Route::post('employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])
            ->name('employees.toggle-status')
            ->middleware('permission:edit employees');
        Route::get('employees/export', [EmployeeController::class, 'export'])
            ->name('employees.export')
            ->middleware('permission:view employees');

        // ─── Attendance ───
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])
                ->name('index')
                ->middleware('permission:view attendance');
            Route::get('/', [AttendanceController::class, 'index'])->name('index')->middleware('permission:view attendance');
            Route::get('/get-data', [AttendanceController::class, 'getData'])->name('get-data')->middleware('permission:view attendance');
            Route::post('/save-bulk', [AttendanceController::class, 'saveBulk'])->name('save-bulk')->middleware('permission:create attendance');
            Route::get('/export', [AttendanceController::class, 'export'])->name('export')->middleware('permission:export attendance');
            Route::get('/monthly', [AttendanceController::class, 'monthly'])
                ->name('monthly')
                ->middleware('permission:view attendance');
            Route::get('/report', [AttendanceController::class, 'report'])
                ->name('report')
                ->middleware('permission:view attendance report');
            Route::post('/check-in', [AttendanceController::class, 'checkIn'])
                ->name('check-in')
                ->middleware('permission:create attendance');
            Route::post('/check-out', [AttendanceController::class, 'checkOut'])
                ->name('check-out')
                ->middleware('permission:create attendance');
            Route::post('/bulk', [AttendanceController::class, 'bulkStore'])
                ->name('bulk-store')
                ->middleware('permission:create attendance');
            Route::get('/export', [AttendanceController::class, 'export'])
                ->name('export')
                ->middleware('permission:export attendance');
        });

        // ─── Leave Management ───
        Route::prefix('leaves')->name('leaves.')->group(function () {
            Route::get('/', [LeaveController::class, 'index'])
                ->name('index')
                ->middleware('permission:view leave requests');
            Route::get('/create', [LeaveController::class, 'create'])
                ->name('create')
                ->middleware('permission:create leave requests');
            Route::post('/', [LeaveController::class, 'store'])
                ->name('store')
                ->middleware('permission:create leave requests');
            Route::get('/{leave}/edit', [LeaveController::class, 'edit'])
                ->name('edit')
                ->middleware('permission:edit leave requests');
            Route::put('/{leave}', [LeaveController::class, 'update'])
                ->name('update')
                ->middleware('permission:edit leave requests');
            Route::post('/{leave}/approve', [LeaveController::class, 'approve'])
                ->name('approve')
                ->middleware('permission:approve leave requests');
            Route::post('/{leave}/reject', [LeaveController::class, 'reject'])
                ->name('reject')
                ->middleware('permission:reject leave requests');
            Route::delete('/{leave}', [LeaveController::class, 'destroy'])
                ->name('destroy')
                ->middleware('permission:delete leave requests');
            Route::get('/types', [LeaveController::class, 'types'])
                ->name('types')
                ->middleware('permission:view leave types');
            Route::post('/types', [LeaveController::class, 'storeType'])
                ->name('types.store')
                ->middleware('permission:create leave types');
            Route::put('/types/{leaveType}', [LeaveController::class, 'updateType'])
                ->name('types.update')
                ->middleware('permission:edit leave types');
            Route::delete('/types/{leaveType}', [LeaveController::class, 'destroyType'])
                ->name('types.destroy')
                ->middleware('permission:delete leave types');
        });

        // ─── Advances & Loans ───
        Route::prefix('advances')->name('advances.')->group(function () {
            Route::get('/', [AdvanceController::class, 'index'])
                ->name('index')
                ->middleware('permission:view employee advances');
            Route::get('/create', [AdvanceController::class, 'create'])
                ->name('create')
                ->middleware('permission:create employee advances');
            Route::post('/', [AdvanceController::class, 'store'])
                ->name('store')
                ->middleware('permission:create employee advances');
            Route::get('/{advance}/edit', [AdvanceController::class, 'edit'])
                ->name('edit')
                ->middleware('permission:edit employee advances');
            Route::put('/{advance}', [AdvanceController::class, 'update'])
                ->name('update')
                ->middleware('permission:edit employee advances');
            Route::post('/{advance}/approve', [AdvanceController::class, 'approve'])
                ->name('approve')
                ->middleware('permission:approve employee advances');
            Route::post('/{advance}/reject', [AdvanceController::class, 'reject'])
                ->name('reject')
                ->middleware('permission:reject employee advances');
            Route::post('/{advance}/deduct', [AdvanceController::class, 'deduct'])
                ->name('deduct')
                ->middleware('permission:deduct employee advances');
            Route::delete('/{advance}', [AdvanceController::class, 'destroy'])
                ->name('destroy')
                ->middleware('permission:delete employee advances');
        });

        // ─── Payroll ───
        Route::prefix('payroll')->name('payroll.')->group(function () {
            Route::get('/', [PayrollController::class, 'index'])
                ->name('index')
                ->middleware('permission:view payroll');
            Route::get('/generate', [PayrollController::class, 'generate'])
                ->name('generate')
                ->middleware('permission:create payroll');
            Route::post('/generate', [PayrollController::class, 'store'])
                ->name('store')
                ->middleware('permission:create payroll');
            Route::get('/{payroll}/show', [PayrollController::class, 'show'])
                ->name('show')
                ->middleware('permission:view payroll');
            Route::get('/{payroll}/edit', [PayrollController::class, 'edit'])
                ->name('edit')
                ->middleware('permission:edit payroll');
            Route::put('/{payroll}', [PayrollController::class, 'update'])
                ->name('update')
                ->middleware('permission:edit payroll');
            Route::post('/{payroll}/process', [PayrollController::class, 'process'])
                ->name('process')
                ->middleware('permission:process payroll');
            Route::post('/{payroll}/pay', [PayrollController::class, 'markAsPaid'])
                ->name('pay')
                ->middleware('permission:pay payroll');
            Route::get('/{payroll}/download', [PayrollController::class, 'download'])
                ->name('download')
                ->middleware('permission:view payroll');
            Route::delete('/{payroll}', [PayrollController::class, 'destroy'])
                ->name('destroy')
                ->middleware('permission:delete payroll');
        });

        // ─── HR Reports ───
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/employees', [ReportController::class, 'employees'])
                ->name('employees')
                ->middleware('permission:view employee reports');
            Route::get('/attendance', [ReportController::class, 'attendance'])
                ->name('attendance')
                ->middleware('permission:view attendance reports');
            Route::get('/payroll', [ReportController::class, 'payroll'])
                ->name('payroll')
                ->middleware('permission:view payroll reports');
            Route::get('/leaves', [ReportController::class, 'leaves'])
                ->name('leaves')
                ->middleware('permission:view leave reports');
            Route::get('/advances', [ReportController::class, 'advances'])
                ->name('advances')
                ->middleware('permission:view advances reports');
            Route::get('/export', [ReportController::class, 'export'])
                ->name('export')
                ->middleware('permission:export reports');
        });

        // ─── HR Settings ───
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/general', [HRSettingsController::class, 'general'])
                ->name('general')
                ->middleware('permission:view hr settings');
            Route::post('/general', [HRSettingsController::class, 'updateGeneral'])
                ->name('general.update')
                ->middleware('permission:edit hr settings');
            Route::get('/attendance', [HRSettingsController::class, 'attendance'])
                ->name('attendance')
                ->middleware('permission:view hr settings');
            Route::post('/attendance', [HRSettingsController::class, 'updateAttendance'])
                ->name('attendance.update')
                ->middleware('permission:edit hr settings');
            Route::get('/payroll', [HRSettingsController::class, 'payroll'])
                ->name('payroll')
                ->middleware('permission:view hr settings');
            Route::post('/payroll', [HRSettingsController::class, 'updatePayroll'])
                ->name('payroll.update')
                ->middleware('permission:edit hr settings');
        });

        // ─── Chart Data Routes ───
        Route::get('attendance/chart-data', [HRDashboardController::class, 'getAttendanceChartData'])
            ->name('attendance.chart')
            ->middleware('permission:view attendance');
        Route::get('departments/chart-data', [HRDashboardController::class, 'getDepartmentChartData'])
            ->name('departments.chart')
            ->middleware('permission:view departments');
        Route::get('payroll/chart-data', [HRDashboardController::class, 'getPayrollChartData'])
            ->name('payroll.chart')
            ->middleware('permission:view payroll');
        Route::get('leaves/balance', [LeaveController::class, 'getBalance'])->name('leaves.balance')->middleware('permission:view leave requests');
    });

    // ============================================================
    // SHAREHOLDERS ROUTES
    // ============================================================
    Route::prefix('shareholders')->name('admin.shareholders.')->group(function () {
        Route::get('/', [ShareholderController::class, 'index'])
            ->name('index')
            ->middleware('permission:view shareholders');

        Route::get('/create', [ShareholderController::class, 'create'])
            ->name('create')
            ->middleware('permission:create shareholders');

        Route::post('/', [ShareholderController::class, 'store'])
            ->name('store')
            ->middleware('permission:create shareholders');

        Route::get('/{shareholder}', [ShareholderController::class, 'show'])
            ->name('show')
            ->middleware('permission:view shareholder profiles');

        Route::get('/{shareholder}/edit', [ShareholderController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:edit shareholders');

        Route::put('/{shareholder}', [ShareholderController::class, 'update'])
            ->name('update')
            ->middleware('permission:edit shareholders');

        Route::delete('/{shareholder}', [ShareholderController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:delete shareholders');

        Route::post('/{shareholder}/toggle-status', [ShareholderController::class, 'toggleStatus'])
            ->name('toggle-status')
            ->middleware('permission:edit shareholders');

        Route::get('/{shareholder}/balance', [ShareholderController::class, 'getBalance'])
            ->name('balance')
            ->middleware('permission:view shareholder balances');

        Route::post('/{shareholder}/withdraw', [ShareholderController::class, 'requestWithdrawal'])
            ->name('withdraw')
            ->middleware('permission:view shareholder profiles');
    });

    // ============================================================
    // PROFIT DISTRIBUTION ROUTES
    // ============================================================

    Route::prefix('profit-distributions')->name('admin.profit-distributions.')->group(function () {
        // ─── Specific routes (MUST come before resource routes) ───
        Route::get('/calculate', [ProfitDistributionController::class, 'calculateProfit'])
            ->name('calculate')
            ->middleware('permission:create profit distributions');

        Route::get('/summary/{distribution}', [ProfitDistributionController::class, 'getSummary'])
            ->name('summary')
            ->middleware('permission:view profit distributions');

        // ─── Resource routes ───
        Route::get('/', [ProfitDistributionController::class, 'index'])
            ->name('index')
            ->middleware('permission:view profit distributions');

        Route::get('/create', [ProfitDistributionController::class, 'create'])
            ->name('create')
            ->middleware('permission:create profit distributions');

        Route::post('/', [ProfitDistributionController::class, 'store'])
            ->name('store')
            ->middleware('permission:create profit distributions');

        Route::get('/{distribution}', [ProfitDistributionController::class, 'show'])
            ->name('show')
            ->middleware('permission:view profit distributions');

        Route::get('/{distribution}/edit', [ProfitDistributionController::class, 'edit'])
            ->name('edit')
            ->middleware('permission:edit profit distributions');

        Route::put('/{distribution}', [ProfitDistributionController::class, 'update'])
            ->name('update')
            ->middleware('permission:edit profit distributions');

        Route::delete('/{distribution}', [ProfitDistributionController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:delete profit distributions');
    });

    // ============================================================
    // SHAREHOLDER WITHDRAWAL ROUTES
    // ============================================================
    Route::prefix('shareholder-withdrawals')->name('admin.shareholder-withdrawals.')->group(function () {
        Route::get('/', [ShareholderWithdrawalController::class, 'index'])
            ->name('index')
            ->middleware('permission:view shareholder withdrawals');

        Route::get('/pending', [ShareholderWithdrawalController::class, 'pending'])
            ->name('pending')
            ->middleware('permission:approve shareholder withdrawals');

        Route::get('/{withdrawal}', [ShareholderWithdrawalController::class, 'show'])
            ->name('show')
            ->middleware('permission:view shareholder withdrawals');

        Route::post('/{withdrawal}/approve', [ShareholderWithdrawalController::class, 'approve'])
            ->name('approve')
            ->middleware('permission:approve shareholder withdrawals');

        Route::post('/{withdrawal}/reject', [ShareholderWithdrawalController::class, 'reject'])
            ->name('reject')
            ->middleware('permission:reject shareholder withdrawals');

        Route::post('/{withdrawal}/process', [ShareholderWithdrawalController::class, 'process'])
            ->name('process')
            ->middleware('permission:process shareholder withdrawals');
    });

    Route::prefix('shareholder-settings')->name('admin.shareholder-settings.')->group(function () {
        Route::get('/', [ShareholderSettingsController::class, 'index'])
            ->name('index')
            ->middleware('permission:edit shareholders');

        Route::post('/update-percentages', [ShareholderSettingsController::class, 'updatePercentages'])
            ->name('update-percentages')
            ->middleware('permission:edit shareholders');

        Route::post('/auto-distribute', [ShareholderSettingsController::class, 'autoDistribute'])
            ->name('auto-distribute')
            ->middleware('permission:edit shareholders');

        Route::get('/validate', [ShareholderSettingsController::class, 'validatePercentages'])
            ->name('validate')
            ->middleware('permission:view shareholders');
    });
});
