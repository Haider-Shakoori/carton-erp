<?php

use App\Http\Controllers\Admin\ProductionGovernanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin/enterprise')->name('admin.enterprise.')->group(function () {
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
