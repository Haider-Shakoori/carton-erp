<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinishedGoodSpecification;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CustomerCartonSizeSeeder extends Seeder
{
    public const SOURCE_FILE = 'Carton order and Price Description.xlsx';

    public function run(): void
    {
        $rows = $this->rows();

        $productNameCounts = collect($rows)
            ->countBy(
                fn (array $row) => mb_strtolower(
                    trim((string) ($row['product_name'] ?? ''))
                )
            )
            ->all();

        $category = Category::firstOrCreate(
            ['name' => 'Custom Cartons'],
            [
                'description' => 'Customer-specific carton sizes and specifications.',
                'is_active' => true,
            ]
        );

        $createdCustomers = 0;
        $createdProducts = 0;
        $createdSpecifications = 0;
        $customerCache = [];

        DB::transaction(function () use (
            $rows,
            $category,
            &$createdCustomers,
            &$createdProducts,
            &$createdSpecifications,
            &$customerCache,
            $productNameCounts
        ): void {
            foreach ($rows as $row) {
                $customerName = trim((string) ($row['customer'] ?? ''));

                if ($customerName === '') {
                    throw new RuntimeException(
                        'Customer carton source contains a row without a customer name.'
                    );
                }

                $customerCacheKey = mb_strtolower($customerName);

                if (! isset($customerCache[$customerCacheKey])) {
                    [$customer, $customerWasCreated] = $this->findOrCreateCustomer(
                        $customerName
                    );
                    $customerCache[$customerCacheKey] = $customer;

                    if ($customerWasCreated) {
                        $createdCustomers++;
                    }
                }

                $customer = $customerCache[$customerCacheKey];

                [$product, $productWasCreated] = $this->findOrCreateProduct(
                    $row,
                    $customerName,
                    $category,
                    $productNameCounts
                );

                if ($productWasCreated) {
                    $createdProducts++;
                }

                $sourceKey = $this->sourceKey($row, $customerName);

                $specification = FinishedGoodSpecification::firstOrCreate(
                    ['source_key' => $sourceKey],
                    $this->specificationAttributes(
                        $row,
                        $customer->id,
                        $product->id
                    )
                );

                if ($specification->wasRecentlyCreated) {
                    $createdSpecifications++;
                }
            }
        });

        $this->command?->info(sprintf(
            'Customer carton master ready: %d source rows, %d customers created, %d finished goods created, %d specifications created.',
            count($rows),
            $createdCustomers,
            $createdProducts,
            $createdSpecifications
        ));
    }

    private function rows(): array
    {
        $path = database_path('data/carton_customer_sizes.json');

        if (! is_file($path)) {
            throw new RuntimeException(
                'Customer carton source data file is missing: '.$path
            );
        }

        $rows = json_decode(
            file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (! is_array($rows) || count($rows) !== 171) {
            throw new RuntimeException(
                'Customer carton source data must contain exactly 171 extracted variants.'
            );
        }

        return $rows;
    }

    private function findOrCreateCustomer(string $name): array
    {
        $normalized = mb_strtolower(trim($name));

        $existing = Account::query()
            ->where('account_type', Account::TYPE_CUSTOMER)
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
            ->first();

        if ($existing) {
            return [$existing, false];
        }

        $customer = Account::create([
            'name' => trim($name),
            'code' => Account::generateCode(Account::TYPE_CUSTOMER),
            'account_type' => Account::TYPE_CUSTOMER,
            'is_active' => true,
            'notes' => 'Imported from '.self::SOURCE_FILE.'.',
        ]);

        // account_sub_category_id is a legacy classification column that is
        // intentionally not mass-assignable on Account.
        $customer->account_sub_category_id = 1;
        $customer->save();

        return [$customer, true];
    }

    private function findOrCreateProduct(
        array $row,
        string $customerName,
        Category $category,
        array $productNameCounts
    ): array {
        $name = $this->resolvedProductName($row, $productNameCounts);

        if ($name === '') {
            throw new RuntimeException(
                'Customer carton source contains a row without a product name.'
            );
        }

        $existing = Product::query()
            ->where('type', Product::TYPE_FINISHED_GOOD)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return [$existing, false];
        }

        $product = Product::create([
            'name' => $name,
            'unit' => 'pcs',
            'category_id' => $category->id,
            'type' => Product::TYPE_FINISHED_GOOD,
            'description' => sprintf(
                'Customer-specific carton master for %s imported from %s. Historical workbook rates are reference-only; live pricing comes from the canonical carton/BOM costing flow.',
                $customerName,
                self::SOURCE_FILE
            ),
            'is_active' => true,
        ]);

        return [$product, true];
    }

    private function resolvedProductName(
        array $row,
        array $productNameCounts
    ): string {
        $baseName = trim((string) ($row['product_name'] ?? ''));

        if ($baseName === '') {
            return '';
        }

        $count = (int) (
            $productNameCounts[mb_strtolower($baseName)]
            ?? 0
        );

        if ($count <= 1) {
            return $baseName;
        }

        $disambiguator = $this->nullableText($row['print_spec'] ?? null)
            ?: $this->nullableText($row['source_customer_label'] ?? null)
            ?: 'Source row '.((int) ($row['source_row'] ?? 0));

        return Str::limit(
            $baseName.' - '.$disambiguator,
            250,
            ''
        );
    }

    private function specificationAttributes(
        array $row,
        int $customerId,
        int $productId
    ): array {
        $printSpec = $this->nullableText($row['print_spec'] ?? null);
        $layerRaw = $this->nullableText($row['layer'] ?? null);

        return [
            'product_id' => $productId,
            'customer_id' => $customerId,
            'source_row' => $this->nullableInteger($row['source_row'] ?? null),
            'source_customer_label' => $this->nullableText(
                $row['source_customer_label'] ?? null
            ),
            'source_size_raw' => $this->nullableText($row['size_raw'] ?? null),
            'source_unit' => $this->nullableText($row['source_unit'] ?? null),
            'source_layer_raw' => $layerRaw,
            'length' => $this->nullableNumber($row['length_mm'] ?? null),
            'width' => $this->nullableNumber($row['width_mm'] ?? null),
            'height' => $this->nullableNumber($row['height_mm'] ?? null),
            'weight' => null,
            'fold_count' => 0,
            'flute_type' => null,
            'ply' => $this->parsePly(
                implode(' ', array_filter([
                    $layerRaw,
                    $printSpec,
                    $this->nullableText($row['remark'] ?? null),
                ]))
            ),
            'color_count' => $this->parseColorCount($printSpec),
            'printing_type' => $this->parsePrintingType($printSpec),
            'finish_type' => $this->parseFinishType($printSpec),
            'print_spec' => $printSpec,
            'reel_cut' => $this->nullableText($row['reel_cut'] ?? null),
            'pack_description' => $this->nullableText($row['pcs_ml'] ?? null),
            'pieces_per_carton' => max(
                1,
                $this->nullableInteger($row['pieces_per_carton'] ?? null) ?? 1
            ),
            'carton_size' => null,

            // Never promote historical workbook prices into current selling
            // prices. They are kept below as reference text only.
            'unit_price' => 0,
            'historical_rate_note' => $this->nullableText(
                $row['rate_raw'] ?? null
            ),
            'source_remark' => $this->nullableText($row['remark'] ?? null),
            'source_extra' => $this->nullableText($row['extra'] ?? null),
            'minimum_order_quantity' => 1,
        ];
    }

    private function sourceKey(array $row, string $customerName): string
    {
        $sourceRow = (int) ($row['source_row'] ?? 0);

        if ($sourceRow < 1) {
            throw new RuntimeException(
                'Customer carton source row is missing its original worksheet row number.'
            );
        }

        return 'client-carton-order:'.hash(
            'sha256',
            mb_strtolower(trim($customerName)).'|'.$sourceRow
        );
    }

    private function parsePly(string $value): ?int
    {
        if (preg_match('/(?:^|\D)(3|5|7)\s*(?:ply)?(?:\D|$)/i', $value, $match)) {
            return (int) $match[1];
        }

        return null;
    }

    private function parseColorCount(?string $value): int
    {
        $value = mb_strtolower(trim((string) $value));

        if ($value === '' || str_contains($value, 'simple')) {
            return 0;
        }

        $map = [
            4 => ['four color', '4 color', '4color'],
            3 => ['three color', '3 color', '3color'],
            2 => ['two color', '2 color', '2color'],
            1 => ['one color', '1 color', '1color', 'single color'],
        ];

        foreach ($map as $count => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($value, $needle)) {
                    return $count;
                }
            }
        }

        return str_contains($value, 'print') ? 1 : 0;
    }

    private function parsePrintingType(?string $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));

        if (str_contains($value, 'flaxo') || str_contains($value, 'flexo')) {
            return 'Flexo';
        }

        if (str_contains($value, 'offset')) {
            return 'Offset';
        }

        if (str_contains($value, 'digital')) {
            return 'Digital';
        }

        return null;
    }

    private function parseFinishType(?string $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));

        if (str_contains($value, 'limination') || str_contains($value, 'lamination')) {
            return 'Lamination';
        }

        if (str_contains($value, 'gloss')) {
            return 'Glossy';
        }

        if (str_contains($value, 'matte')) {
            return 'Matte';
        }

        if (str_contains($value, 'uv')) {
            return 'UV';
        }

        return null;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function nullableNumber(mixed $value): ?float
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' || ! is_numeric($value)
            ? null
            : (float) $value;
    }

    private function nullableInteger(mixed $value): ?int
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' || ! is_numeric($value)
            ? null
            : (int) round((float) $value);
    }
}
