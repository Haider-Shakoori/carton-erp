<?php

namespace App\Services;

use App\Models\GatePass;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GatePassService
{
    public function createOrRefreshForSale(Sale $sale): GatePass
    {
        return DB::transaction(function () use ($sale): GatePass {
            $sale->loadMissing(['items.product']);

            $gatePass = GatePass::firstOrCreate(
                ['sale_id' => $sale->id],
                [
                    'gate_pass_no' => $this->nextNumber(),
                    'issued_at' => now(),
                    'created_by' => Auth::id(),
                ]
            );

            if (! $gatePass->wasRecentlyCreated) {
                $gatePass->issued_at = $gatePass->issued_at ?: now();
                $gatePass->created_by = $gatePass->created_by ?: Auth::id();
                $gatePass->save();
                $gatePass->items()->delete();
            }

            foreach ($sale->items as $item) {
                $gatePass->items()->create([
                    'sale_item_id' => $item->id,
                    'item_name' => $item->product?->name ?? 'Item',
                    'description' => $item->quotation_description
                        ?: ($item->product?->description ?? null),
                    'quantity' => (float) $item->qty,
                    'unit' => $item->product?->unit ?? null,
                ]);
            }

            return $gatePass->fresh(['sale.customer', 'items', 'createdBy']);
        });
    }

    private function nextNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "GP-{$year}-";

        $max = GatePass::query()
            ->where('gate_pass_no', 'like', $prefix . '%')
            ->pluck('gate_pass_no')
            ->map(fn (string $number) => (int) substr($number, strlen($prefix)))
            ->max() ?? 0;

        return $prefix . str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }
}
