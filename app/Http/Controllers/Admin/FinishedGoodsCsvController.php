<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\BOM;
use App\Services\FinishedGoodWeightAuditService;
use App\Models\FinishedGoodSpecification;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinishedGoodsCsvController extends Controller
{
    public const HEADERS = [
        'product_id', 'sku', 'name', 'category', 'unit', 'weight_g',
        'length_mm', 'width_mm', 'height_mm', 'ply', 'flute_type',
        'color_count', 'printing_type', 'finish_type', 'print_spec',
        'reel_cut', 'pieces_per_carton', 'pack_description',
        'description', 'is_active',
    ];

    public function index()
    {
        return view('admin.products.import-finished-goods');
    }

    public function weightAudit(FinishedGoodWeightAuditService $auditor)
    {
        $finishedProducts = Product::query()
            ->finishedGoods()
            ->with(['boms' => function ($query) {
                $query->with('items.material')
                    ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                    ->latest('id');
            }])
            ->orderBy('name')
            ->paginate(50);

        return view('admin.products.weight-audit', compact('finishedProducts', 'auditor'));
    }

    /**
     * Export stable product IDs so existing customer cartons can be weighed
     * and re-imported without creating duplicate records.
     */
    public function export(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, self::HEADERS);

            Product::query()->finishedGoods()->with(['category', 'finishedGoodSpecifications'])
                ->chunkById(200, function ($products) use ($output): void {
                    foreach ($products as $product) {
                        $spec = $product->finishedGoodSpecifications
                            ->firstWhere('source_key', 'finished-good-csv:' . $product->id)
                            ?? $product->finishedGoodSpecifications->first();

                        fputcsv($output, [
                            $product->id,
                            $product->sku ?: '',
                            $product->name,
                            $product->category?->name ?? '',
                            $product->unit ?? 'pcs',
                            $product->finished_weight_g ?? '',
                            $spec?->length ?? '',
                            $spec?->width ?? '',
                            $spec?->height ?? '',
                            $spec?->ply ?? '',
                            $spec?->flute_type ?? '',
                            $spec?->color_count ?? '',
                            $spec?->printing_type ?? '',
                            $spec?->finish_type ?? '',
                            $spec?->print_spec ?? '',
                            $spec?->reel_cut ?? '',
                            $spec?->pieces_per_carton ?? '',
                            $spec?->pack_description ?? '',
                            $product->description ?? '',
                            (int) $product->is_active,
                        ]);
                    }
                });

            fclose($output);
        }, 'existing-finished-goods.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, self::HEADERS);
            fputcsv($output, [
                '', 'CARTON-001', 'Carton 45 x 30 x 20', 'Finished Goods',
                'pcs', '245', '450', '300', '200', '5', 'B',
                '2', 'Flexo', '', '', '', '1', '', '', '1',
            ]);
            fclose($output);
        }, 'finished-goods-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $file = $request->file('csv');
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['csv' => 'Unable to read uploaded CSV.']);
        }

        try {
            $header = fgetcsv($handle, 0, ',', '"', '');
            if (! is_array($header)) {
                throw ValidationException::withMessages(['csv' => 'The CSV is empty.']);
            }

            $header = array_map(
                fn ($value) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $value))),
                $header
            );

            if (count($header) !== count(array_unique($header))
                || array_diff($header, self::HEADERS)
                || array_diff(['name', 'category', 'weight_g'], $header)) {
                throw ValidationException::withMessages([
                    'csv' => 'Invalid CSV headers. Download the template and keep name, category and weight_g columns.',
                ]);
            }

            $rows = [];
            $line = 1;
            while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                $line++;
                if (count($values) === 1 && trim((string) $values[0]) === '') {
                    continue;
                }

                if (count($values) !== count($header)) {
                    throw ValidationException::withMessages([
                        'csv' => "Row {$line} has a different column count from the header.",
                    ]);
                }

                if (count($rows) >= 5000) {
                    throw ValidationException::withMessages(['csv' => 'A file may contain at most 5,000 finished goods.']);
                }

                $row = array_combine($header, array_map(
                    fn ($value) => trim((string) $value),
                    $values
                ));

                $validator = Validator::make($row, [
                    'product_id' => ['nullable', 'integer', 'min:1'],
                    'sku' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
                    'name' => ['required', 'string', 'max:255'],
                    'category' => ['required', 'string', 'max:255'],
                    'unit' => ['nullable', 'string', 'max:100'],
                    'weight_g' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
                    'length_mm' => ['nullable', 'numeric', 'gt:0', 'max:99999999'],
                    'width_mm' => ['nullable', 'numeric', 'gt:0', 'max:99999999'],
                    'height_mm' => ['nullable', 'numeric', 'gt:0', 'max:99999999'],
                    'ply' => ['nullable', 'integer', 'min:1', 'max:9'],
                    'flute_type' => ['nullable', 'string', 'max:100'],
                    'color_count' => ['nullable', 'integer', 'min:0', 'max:100'],
                    'printing_type' => ['nullable', 'string', 'max:255'],
                    'finish_type' => ['nullable', 'string', 'max:255'],
                    'print_spec' => ['nullable', 'string', 'max:2000'],
                    'reel_cut' => ['nullable', 'string', 'max:255'],
                    'pieces_per_carton' => ['nullable', 'integer', 'min:1'],
                    'pack_description' => ['nullable', 'string', 'max:255'],
                    'description' => ['nullable', 'string', 'max:4000'],
                    'is_active' => ['nullable', 'in:0,1'],
                ]);

                if ($validator->fails()) {
                    throw ValidationException::withMessages([
                        'csv' => "Row {$line}: " . $validator->errors()->first(),
                    ]);
                }

                if (empty($row['sku']) && empty($row['product_id'])) {
                    throw ValidationException::withMessages([
                        'csv' => "Row {$line}: provide SKU for new goods, or product_id for existing goods.",
                    ]);
                }

                $rows[] = ['line' => $line, 'data' => $row];
            }

            if ($rows === []) {
                throw ValidationException::withMessages(['csv' => 'The CSV has no data rows.']);
            }
        } finally {
            fclose($handle);
        }

        $result = DB::transaction(function () use ($rows): array {
            $created = 0;
            $updated = 0;
            $seen = [];

            foreach ($rows as $entry) {
                $row = $entry['data'];
                $line = $entry['line'];
                $sku = strtoupper($row['sku'] ?? '');
                $product = ! empty($row['product_id'])
                    ? Product::query()->whereKey((int) $row['product_id'])->lockForUpdate()->first()
                    : Product::query()->where('sku', $sku)->lockForUpdate()->first();

                if (! empty($row['product_id']) && ! $product) {
                    throw ValidationException::withMessages(['csv' => "Row {$line}: product_id not found."]);
                }
                // Product IDs are not permission to rename an unrelated legacy carton.
                // A stable SKU explicitly identifies a rename-capable record.
                if ($product && $sku === '' && $product->name !== $row['name']) {
                    throw ValidationException::withMessages([
                        'csv' => "Row {$line}: product_id/name mismatch. Use the exported name or provide a verified SKU.",
                    ]);
                }
                if ($product && $product->type !== Product::TYPE_FINISHED_GOOD) {
                    throw ValidationException::withMessages(['csv' => "Row {$line}: item is not a finished good."]);
                }
                if ($product && $sku !== '' && $product->sku
                    && strcasecmp($product->sku, $sku) !== 0) {
                    throw ValidationException::withMessages(['csv' => "Row {$line}: SKU does not match existing product."]);
                }
                if ($sku !== '' && Product::query()->where('sku', $sku)
                    ->when($product, fn ($query) => $query->where('id', '!=', $product->id))->exists()) {
                    throw ValidationException::withMessages(['csv' => "Row {$line}: SKU is assigned to another product."]);
                }

                $category = Category::query()->where('name', $row['category'])->first();
                if (! $category) {
                    throw ValidationException::withMessages([
                        'csv' => "Row {$line}: category '{$row['category']}' does not exist. Create it in the catalog first.",
                    ]);
                }

                $identity = $product ? 'id:' . $product->id : 'sku:' . $sku;
                if (isset($seen[$identity]) || ($sku !== '' && isset($seen['sku:' . $sku]))) {
                    throw ValidationException::withMessages(['csv' => "Row {$line}: duplicate finished good in the CSV."]);
                }

                $data = [
                    'name' => $row['name'],
                    'category_id' => $category->id,
                    'type' => Product::TYPE_FINISHED_GOOD,
                    'finished_weight_g' => $row['weight_g'],
                ];
                if ($sku !== '') {
                    $data['sku'] = $sku;
                }
                if (! empty($row['unit'])) {
                    $data['unit'] = $row['unit'];
                } elseif (! $product) {
                    $data['unit'] = 'pcs';
                }
                if ($row['description'] ?? '' !== '') {
                    $data['description'] = $row['description'];
                }
                if (($row['is_active'] ?? '') !== '') {
                    $data['is_active'] = (bool) $row['is_active'];
                } elseif (! $product) {
                    $data['is_active'] = true;
                }

                if ($product) {
                    $product->update($data);
                    $updated++;
                } else {
                    $product = Product::create($data);
                    $created++;
                }

                $seen['id:' . $product->id] = true;
                if ($sku !== '') {
                    $seen['sku:' . $sku] = true;
                }

                // A dedicated CSV master preserves older customer/workbook specifications.
                $specData = ['product_id' => $product->id, 'weight' => $row['weight_g']];
                foreach ([
                    'length_mm' => 'length', 'width_mm' => 'width', 'height_mm' => 'height',
                    'ply' => 'ply', 'flute_type' => 'flute_type',
                    'color_count' => 'color_count', 'printing_type' => 'printing_type',
                    'finish_type' => 'finish_type', 'print_spec' => 'print_spec',
                    'reel_cut' => 'reel_cut', 'pieces_per_carton' => 'pieces_per_carton',
                    'pack_description' => 'pack_description',
                ] as $csvField => $column) {
                    if (($row[$csvField] ?? '') !== '') {
                        $specData[$column] = $row[$csvField];
                    }
                }

                FinishedGoodSpecification::query()->updateOrCreate(
                    ['source_key' => 'finished-good-csv:' . $product->id],
                    $specData
                );
            }

            return compact('created', 'updated');
        });

        return redirect()
            ->route('admin.products.import.index')
            ->with('success', "{$result['created']} created, {$result['updated']} updated. Weights saved; BOMs and stock were not overwritten.");
    }
}
