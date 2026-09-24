<?php

use App\Models\ProductionOrder;
use App\Services\ProductionQuantityService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Repair completed production orders that were linked to their sale item
     * only after completion. This is intentionally one-way: invoice quantities
     * become the recorded good/usable production output.
     */
    public function up(): void
    {
        $service = app(ProductionQuantityService::class);

        ProductionOrder::query()
            ->where('status', ProductionOrder::STATUS_COMPLETED)
            ->where('quantity_produced', '>', 0)
            ->where(function ($query) {
                $query->whereNotNull('sale_id')
                    ->orWhereNotNull('sale_item_id');
            })
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($service): void {
                foreach ($orders as $order) {
                    $service->reconcileCompletedInvoice($order);
                }
            });
    }

    public function down(): void
    {
        // Historical invoice reconciliation is not reversed automatically.
    }
};
