<?php

use App\Http\Middleware\CheckPermissionWithFeedback;
use App\Models\AccountingSetting;
use App\Models\BOM;
use App\Models\GlAccount;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\PurchaseItem;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\BOMCalculatorService;
use App\Services\BOMCostingService;
use App\Services\FinishedGoodWeightAuditService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('keeps the trial balance and profit correct after source corrections and reversal', function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::factory()->create());

    $cash = GlAccount::query()->where('code', '1000')->firstOrFail();
    $revenue = GlAccount::query()->where('code', '4000')->firstOrFail();
    $accounting = app(AccountingService::class);
    $lines = fn (float $amount): array => [
        ['account_id' => $cash->id, 'debit' => $amount, 'credit' => 0],
        ['account_id' => $revenue->id, 'debit' => 0, 'credit' => $amount],
    ];

    $entry = $accounting->postOrReplace(
        rootKey: 'qa:financial-adjustment:1', date: now(),
        sourceType: 'qa_sale', sourceId: 1,
        description: 'Original sale', businessUnitId: null,
        lines: $lines(100.0),
    );
    $replaced = $accounting->postOrReplace(
        rootKey: 'qa:financial-adjustment:1', date: now(),
        sourceType: 'qa_sale', sourceId: 1,
        description: 'Corrected sale', businessUnitId: null,
        lines: $lines(120.0),
    );

    expect($entry->fresh()->status)->toBe('reversed')
        ->and($replaced->status)->toBe('posted')
        ->and(JournalEntry::query()->where('source_root_key', 'qa:financial-adjustment:1')->count())->toBe(2)
        ->and(JournalEntry::query()->where('reversal_of_id', $entry->id)->count())->toBe(1);

    $trial = $accounting->trialBalance(now());
    $pnl = $accounting->profitAndLoss(now()->startOfMonth(), now()->endOfMonth());

    // Auditable entries are additive, but 100 + (-100) + 120 must be 120, not 220.
    expect(round($trial['debit_usd'], 2))->toBe(round($trial['credit_usd'], 2))
        ->and(round($pnl['revenue_usd'], 2))->toBe(120.0);

    $same = $accounting->postOrReplace(
        rootKey: 'qa:financial-adjustment:1', date: now(),
        sourceType: 'qa_sale', sourceId: 1,
        description: 'Idempotent retry', businessUnitId: null,
        lines: $lines(120.0),
    );
    expect($same->id)->toBe($replaced->id);

    $accounting->reverseEntry($replaced, 'QA final cancellation');
    $final = $accounting->trialBalance(now());
    $finalPnl = $accounting->profitAndLoss(now()->startOfMonth(), now()->endOfMonth());
    expect(round($final['debit_usd'], 2))->toBe(round($final['credit_usd'], 2))
        ->and(round($finalPnl['revenue_usd'], 2))->toBe(0.0);
});

it('moves actual production cost from raw material through WIP to finished goods exactly once', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $this->actingAs($user);
    $bom = BOM::query()->firstOrFail();
    $now = now();

    $orderId = DB::table('production_orders')->insertGetId([
        'order_number' => 'QA-GL-REAL-COST-'.uniqid(),
        'product_id' => $bom->product_id,
        'bom_id' => $bom->id,
        'quantity_ordered' => 100,
        'quantity_planned' => 100,
        'quantity_manufactured' => 100,
        'quantity_produced' => 100,
        'quantity_rejected' => 0,
        'status' => 'completed',
        'total_material_cost' => 30,
        'total_labor_cost' => 10,
        'total_overhead_cost' => 5,
        'total_cost' => 45,
        'created_by' => $user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $order = ProductionOrder::findOrFail($orderId);
    $accounting = app(AccountingService::class);

    $accounting->postProductionMaterialIssue($order);
    $order->update(['total_material_cost' => 35, 'total_cost' => 50]);
    $accounting->postProductionCompletion($order->fresh());
    $accounting->postProductionCompletion($order->fresh());

    $map = AccountingSetting::query()->firstOrFail();
    $trial = collect($accounting->trialBalance(now())['rows'])->keyBy('id');
    $net = fn (int $glId): float => round(
        (float) ($trial->get($glId)['balance_usd'] ?? 0), 2
    );

    expect($net($map->raw_material_inventory_account_id))->toBe(-35.0)
        ->and($net($map->wip_account_id))->toBe(0.0)
        ->and($net($map->finished_goods_inventory_account_id))->toBe(50.0)
        ->and($net($map->production_conversion_clearing_account_id))->toBe(-15.0)
        ->and(JournalEntry::query()->where('source_root_key', 'production_material:'.$order->id)->count())->toBe(2)
        ->and(JournalEntry::query()->where('source_root_key', 'production_conversion:'.$order->id)->count())->toBe(1)
        ->and(JournalEntry::query()->where('source_root_key', 'production_completion:'.$order->id)->count())->toBe(1);
});

it('uses measured finished weight only as an audit input without repricing or restocking paper', function () {
    $this->seed(DatabaseSeeder::class);

    $bom = BOM::query()
        ->where('description', 'not like', '[REVIEW REQUIRED]%')
        ->with('items.material')
        ->firstOrFail();
    $product = Product::findOrFail($bom->product_id);
    $calculator = app(BOMCalculatorService::class);

    $before = $calculator->calculateMaterialRequirements($bom, 120);
    $costBefore = app(BOMCostingService::class)->summarize($bom);
    $stockBefore = PurchaseItem::query()->sum('qty_kg_available');
    $product->update(['finished_weight_g' => 355.25]);

    $after = $calculator->calculateMaterialRequirements($bom->fresh(), 120);
    $costAfter = app(BOMCostingService::class)->summarize($bom->fresh());
    $audit = app(FinishedGoodWeightAuditService::class)->assess(
        $product->fresh(), $bom->fresh(['items.material'])
    );

    expect($after['material_requirements'])->toEqual($before['material_requirements'])
        ->and($after['stock_summary'])->toEqual($before['stock_summary'])
        ->and($costAfter['physical_material_cost_usd'])->toBe($costBefore['physical_material_cost_usd'])
        ->and($costAfter['selling_price_afn'])->toBe($costBefore['selling_price_afn'])
        ->and(round((float) PurchaseItem::query()->sum('qty_kg_available'), 4))->toBe(round((float) $stockBefore, 4))
        ->and($audit['measured_g'])->toBe(355.25);
});

it('imports an existing carton weight without moving FIFO stock or altering invoice/BOM financial amounts', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $bom = BOM::query()->with('product.category')->firstOrFail();
    $product = $bom->product;
    $beforeRevenue = DB::table('sales')->sum('grand_total');
    $beforeBOMCost = DB::table('boms')->where('id', $bom->id)->value('total_material_cost_usd');
    $stockBefore = DB::table('purchase_items')->selectRaw(
        'SUM(qty_available) AS qty, SUM(qty_kg_available) AS kg, SUM(qty_used) AS used, SUM(qty_kg_used) AS kg_used'
    )->first();
    $journalBefore = DB::table('journal_lines')->count();

    $stream = fopen('php://temp', 'w+');
    fputcsv($stream, ['product_id', 'name', 'category', 'weight_g']);
    fputcsv($stream, [$product->id, $product->name, $product->category->name, '255.5']);
    rewind($stream);
    $csv = UploadedFile::fake()->createWithContent('weight-update.csv', stream_get_contents($stream));
    fclose($stream);

    $this->withoutMiddleware(CheckPermissionWithFeedback::class)
        ->actingAs($user)
        ->post(route('admin.products.import.store'), ['csv' => $csv])
        ->assertRedirect();

    $stockAfter = DB::table('purchase_items')->selectRaw(
        'SUM(qty_available) AS qty, SUM(qty_kg_available) AS kg, SUM(qty_used) AS used, SUM(qty_kg_used) AS kg_used'
    )->first();

    expect((float) $product->fresh()->finished_weight_g)->toBe(255.5)
        ->and((float) DB::table('sales')->sum('grand_total'))->toBe((float) $beforeRevenue)
        ->and((float) DB::table('boms')->where('id', $bom->id)->value('total_material_cost_usd'))->toBe((float) $beforeBOMCost)
        ->and((array) $stockAfter)->toEqual((array) $stockBefore)
        ->and(DB::table('journal_lines')->count())->toBe($journalBefore);
});

it('checks landed purchase cost per kilogram including transport and rejects unknown roll weight', function () {
    $roll = new PurchaseItem([
        'qty' => 3, 'unit' => 'roll', 'kg_per_roll' => 900,
        'usd_total' => 5400, 'usd_expense_per_item' => 60,
    ]);

    $expected = (5400 + (3 * 60)) / (3 * 900);
    expect($roll->inventoryCostBasisUnit())->toBe('kg')
        ->and(abs($roll->landedCostPerKg() - $expected))->toBeLessThan(0.000001);

    $missing = new PurchaseItem([
        'qty' => 3, 'unit' => 'roll', 'usd_total' => 5400,
        'usd_expense_per_item' => 60,
    ]);

    expect($missing->landedCostPerKg())->toBe(0.0);
});
