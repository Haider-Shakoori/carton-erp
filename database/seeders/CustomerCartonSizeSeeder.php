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
        $legacyNameCounts = collect($rows)
            ->countBy(fn (array $row) => mb_strtolower(trim((string) ($row['product_name'] ?? ''))))
            ->all();
        $previousResolvedNames = $this->resolvedNeutralProductNames($rows, false);
        $resolvedNames = $this->resolvedNeutralProductNames($rows);

        $category = Category::firstOrCreate(
            ['name' => 'Custom Cartons'],
            [
                'description' => 'Customer-specific carton sizes and specifications.',
                'is_active' => true,
            ]
        );

        // Existing imports from the first client-master release carried the
        // customer/company name in Product::name. Rename only those exact
        // generated names. If an operator already renamed a product, preserve it.
        $this->syncPreviouslyGeneratedNeutralProductNames(
            $rows,
            $previousResolvedNames,
            $resolvedNames
        );
        $this->syncLegacyImportedProductNames($rows, $legacyNameCounts, $resolvedNames);

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
            $resolvedNames
        ): void {
            foreach ($rows as $row) {
                $customerName = trim((string) ($row['customer'] ?? ''));

                if ($customerName === '') {
                    throw new RuntimeException(
                        'Customer carton source contains a row without a customer name.'
                    );
                }

                $sourceKey = $this->sourceKey($row, $customerName);

                // Source identity wins over mutable display names.
                if (FinishedGoodSpecification::query()->where('source_key', $sourceKey)->exists()) {
                    continue;
                }

                $customerCacheKey = mb_strtolower($customerName);

                if (! isset($customerCache[$customerCacheKey])) {
                    [$customer, $customerWasCreated] = $this->findOrCreateCustomer($customerName);
                    $customerCache[$customerCacheKey] = $customer;

                    if ($customerWasCreated) {
                        $createdCustomers++;
                    }
                }

                $customer = $customerCache[$customerCacheKey];
                $productName = $resolvedNames[$sourceKey] ?? null;

                if (! $productName) {
                    throw new RuntimeException(
                        'Unable to resolve a customer-free finished-good name for source '.$sourceKey.'.'
                    );
                }

                $product = $this->createProduct($productName, $customerName, $category);
                $createdProducts++;

                $specification = FinishedGoodSpecification::firstOrCreate(
                    ['source_key' => $sourceKey],
                    $this->specificationAttributes($row, $customer->id, $product->id)
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

        $customer->account_sub_category_id = 1;
        $customer->save();

        return [$customer, true];
    }

    private function createProduct(
        string $name,
        string $customerName,
        Category $category
    ): Product {
        return Product::create([
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
    }

    /**
     * Finished-good display names intentionally contain no customer/company or
     * print-brand names. Size + pack information identifies the carton; neutral
     * Variant N suffixes keep otherwise identical rows distinct.
     *
     * @return array<string,string> keyed by immutable source_key
     */
    private function resolvedNeutralProductNames(
        array $rows,
        bool $stripPiecesUnit = true
    ): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $customerName = trim((string) ($row['customer'] ?? ''));
            $sourceKey = $this->sourceKey($row, $customerName);
            $baseName = $this->neutralBaseProductName($row, $stripPiecesUnit);
            $groupKey = mb_strtolower($baseName);

            $grouped[$groupKey][] = [
                'source_key' => $sourceKey,
                'base_name' => $baseName,
            ];
        }

        $resolved = [];

        foreach ($grouped as $group) {
            $count = count($group);

            foreach ($group as $index => $entry) {
                $name = $entry['base_name'];

                if ($count > 1) {
                    $name .= ' - Variant '.($index + 1);
                }

                $resolved[$entry['source_key']] = Str::limit($name, 250, '');
            }
        }

        return $resolved;
    }

    private function neutralBaseProductName(
        array $row,
        bool $stripPiecesUnit = true
    ): string {
        $size = $this->nullableText($row['size_raw'] ?? null);
        $pack = $this->finishedGoodPackLabel(
            $row['pcs_ml'] ?? null,
            $stripPiecesUnit
        );

        $parts = [
            $size ? 'Carton '.$size : 'Carton - Size pending',
        ];

        if ($pack) {
            $parts[] = $pack;
        }

        return implode(' - ', $parts);
    }

    private function finishedGoodPackLabel(
        mixed $value,
        bool $stripPiecesUnit
    ): ?string {
        $pack = $this->nullableText($value);

        if (! $pack || ! $stripPiecesUnit) {
            return $pack;
        }

        $pack = preg_replace('/\\s*pcs\\b/i', '', $pack);
        $pack = trim((string) preg_replace('/\\s{2,}/', ' ', (string) $pack));

        return $pack === '' ? null : $pack;
    }

    /**
     * The previous neutral-name release retained the workbook's "pcs" token in
     * Product::name. Rename only those exact generated names so re-seeding
     * upgrades existing installs without overwriting operator-customized names.
     */
    private function syncPreviouslyGeneratedNeutralProductNames(
        array $rows,
        array $previousResolvedNames,
        array $resolvedNames
    ): void {
        foreach ($rows as $row) {
            $customerName = trim((string) ($row['customer'] ?? ''));

            if ($customerName === '') {
                continue;
            }

            $sourceKey = $this->sourceKey($row, $customerName);
            $previousName = $previousResolvedNames[$sourceKey] ?? null;
            $desiredName = $resolvedNames[$sourceKey] ?? null;

            if (! $previousName || ! $desiredName || $previousName === $desiredName) {
                continue;
            }

            $specification = FinishedGoodSpecification::query()
                ->where('source_key', $sourceKey)
                ->with('product')
                ->first();

            if (! $specification?->product) {
                continue;
            }

            $currentName = trim((string) $specification->product->name);

            if (mb_strtolower($currentName) === mb_strtolower($previousName)) {
                $specification->product->update(['name' => $desiredName]);
            }
        }
    }

    private function syncLegacyImportedProductNames(
        array $rows,
        array $legacyNameCounts,
        array $resolvedNames
    ): void {
        foreach ($rows as $row) {
            $customerName = trim((string) ($row['customer'] ?? ''));

            if ($customerName === '') {
                continue;
            }

            $sourceKey = $this->sourceKey($row, $customerName);
            $specification = FinishedGoodSpecification::query()
                ->where('source_key', $sourceKey)
                ->with('product')
                ->first();

            if (! $specification?->product) {
                continue;
            }

            $legacyName = $this->legacyResolvedProductName($row, $legacyNameCounts);
            $desiredName = $resolvedNames[$sourceKey] ?? null;
            $currentName = trim((string) $specification->product->name);

            if (
                $desiredName
                && $legacyName
                && mb_strtolower($currentName) === mb_strtolower($legacyName)
                && mb_strtolower($currentName) !== mb_strtolower($desiredName)
            ) {
                $specification->product->update(['name' => $desiredName]);
            }
        }
    }

    /**
     * Exact name logic used by the first client-master release. This allows a
     * safe one-time rename without overwriting operator-customized product names.
     */
    private function legacyResolvedProductName(
        array $row,
        array $productNameCounts
    ): string {
        $baseName = trim((string) ($row['product_name'] ?? ''));

        if ($baseName === '') {
            return '';
        }

        $count = (int) ($productNameCounts[mb_strtolower($baseName)] ?? 0);

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
            'source_customer_label' => $this->nullableText($row['source_customer_label'] ?? null),
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
            'unit_price' => 0,
            'historical_rate_note' => $this->nullableText($row['rate_raw'] ?? null),
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
