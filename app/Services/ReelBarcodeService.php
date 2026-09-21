<?php

namespace App\Services;

use App\Models\PurchaseItemReel;
use Illuminate\Support\Collection;

class ReelBarcodeService
{
    private const NARROW = 2;
    private const WIDE = 5;
    private const HEIGHT = 64;
    private const QUIET_ZONE = 20;

    /**
     * Code 39 character encodings. A set bit marks a wide bar/space.
     * Positions alternate bar, space, bar ... across nine elements.
     */
    private const CODE39 = [
        '0' => 0x034, '1' => 0x121, '2' => 0x061, '3' => 0x160,
        '4' => 0x031, '5' => 0x130, '6' => 0x070, '7' => 0x025,
        '8' => 0x124, '9' => 0x064,
        'A' => 0x109, 'B' => 0x049, 'C' => 0x148, 'D' => 0x019,
        'E' => 0x118, 'F' => 0x058, 'G' => 0x00D, 'H' => 0x10C,
        'I' => 0x04C, 'J' => 0x01C,
        'K' => 0x103, 'L' => 0x043, 'M' => 0x142, 'N' => 0x013,
        'O' => 0x112, 'P' => 0x052, 'Q' => 0x007, 'R' => 0x106,
        'S' => 0x046, 'T' => 0x016,
        'U' => 0x181, 'V' => 0x0C1, 'W' => 0x1C0, 'X' => 0x091,
        'Y' => 0x190, 'Z' => 0x0D0,
        '-' => 0x085, '.' => 0x184, ' ' => 0x0C4, '$' => 0x0A8,
        '/' => 0x0A2, '+' => 0x08A, '%' => 0x02A,
        '*' => 0x094,
    ];

    public function scanKey(PurchaseItemReel $reel): string
    {
        return $reel->scannerKey();
    }

    public function resolve(string $input): ?PurchaseItemReel
    {
        $value = strtoupper(trim($input));

        if ($value === '') {
            return null;
        }

        if (preg_match('/^REEL-(\d+)$/', $value, $matches)) {
            return PurchaseItemReel::query()->find((int) $matches[1]);
        }

        return PurchaseItemReel::query()
            ->whereRaw('UPPER(reel_code) = ?', [$value])
            ->first();
    }

    public function labelData(PurchaseItemReel $reel): array
    {
        $reel->loadMissing([
            'purchaseItem.product',
            'purchaseItem.purchase',
        ]);

        $scanKey = $this->scanKey($reel);
        $batch = $reel->purchaseItem;

        return [
            'reel_id' => (int) $reel->id,
            'reel_code' => (string) $reel->reel_code,
            'scan_key' => $scanKey,
            'barcode_svg' => $this->barcodeSvg($scanKey),
            'material_name' => $batch?->product?->name ?? 'Unknown material',
            'batch_no' => $batch?->batch_no ?: ($batch ? 'Batch #'.$batch->id : '—'),
            'purchase_no' => $batch?->purchase?->purchase_no ?? '—',
            'registered_weight_kg' => (float) $reel->registered_weight_kg,
            'system_remaining_weight_kg' => (float) $reel->system_remaining_weight_kg,
            'status' => (string) $reel->status,
            'landed_cost_per_kg' => $batch ? $batch->landedCostPerKg() : 0.0,
        ];
    }

    public function labelsFor(Collection $reels): Collection
    {
        return $reels->map(
            fn (PurchaseItemReel $reel): array => $this->labelData($reel)
        );
    }

    public function barcodeSvg(string $value): string
    {
        $encoded = strtoupper(trim($value));

        if ($encoded === '' || preg_match('/[^0-9A-Z. $\/+%\-]/', $encoded)) {
            throw new \InvalidArgumentException(
                'Barcode value contains unsupported Code 39 characters.'
            );
        }

        $encoded = '*'.$encoded.'*';
        $x = self::QUIET_ZONE;
        $rects = [];

        foreach (str_split($encoded) as $character) {
            $pattern = self::CODE39[$character];

            for ($index = 0; $index < 9; $index++) {
                $wide = (($pattern >> (8 - $index)) & 1) === 1;
                $width = $wide ? self::WIDE : self::NARROW;

                if ($index % 2 === 0) {
                    $rects[] = sprintf(
                        '<rect x="%d" y="0" width="%d" height="%d"/>',
                        $x,
                        $width,
                        self::HEIGHT
                    );
                }

                $x += $width;
            }

            $x += self::NARROW;
        }

        $width = $x + self::QUIET_ZONE;

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" role="img" aria-label="Reel barcode" preserveAspectRatio="none"><g fill="#000">%s</g></svg>',
            $width,
            self::HEIGHT,
            implode('', $rects)
        );
    }
}
