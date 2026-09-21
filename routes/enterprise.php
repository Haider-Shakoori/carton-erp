<?php

use App\Http\Controllers\Admin\ProductionGovernanceController;
use App\Http\Controllers\Admin\PurchaseControlController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin/enterprise')->name('admin.enterprise.')->group(function () {
    Route::post('/purchases/{purchase}/approve', [PurchaseControlController::class, 'approve'])
        ->name('purchases.approve')
        ->middleware('permission.feedback:approve purchase orders');

    Route::post('/purchases/{purchase}/receive', [PurchaseControlController::class, 'receive'])
        ->name('purchases.receive')
        ->middleware('permission.feedback:receive purchase orders');

    Route::post('/purchases/{purchase}/supplier-invoice', [PurchaseControlController::class, 'invoice'])
        ->name('purchases.invoice')
        ->middleware('permission.feedback:match purchase invoices');

    Route::post('/purchases/{purchase}/supplier-invoices/{invoice}/match', [PurchaseControlController::class, 'match'])
        ->name('purchases.match')
        ->middleware('permission.feedback:match purchase invoices');

    Route::post('/production-orders/{productionOrder}/approve', [ProductionGovernanceController::class, 'approve'])
        ->name('production.approve')
        ->middleware('permission.feedback:approve production orders');

    Route::post('/production-orders/{productionOrder}/close', [ProductionGovernanceController::class, 'close'])
        ->name('production.close')
        ->middleware('permission.feedback:close production orders');

    Route::post('/production-orders/{productionOrder}/reopen', [ProductionGovernanceController::class, 'reopen'])
        ->name('production.reopen')
        ->middleware('permission.feedback:reopen production orders');

    Route::post('/production-orders/{productionOrder}/reverse', [ProductionGovernanceController::class, 'reverse'])
        ->name('production.reverse')
        ->middleware('permission.feedback:reverse production orders');
});
