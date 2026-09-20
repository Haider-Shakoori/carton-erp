<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One paper layer (or future physical component) of a board profile.
 *
 * The verified client source of truth is expressed with two rows:
 *   125 GSM x multiplication_layer 5
 *   145 GSM x multiplication_layer 1
 * Each row still follows the client formula:
 *   PaperKg = ReelLength x ReelHeight x GSM x MultiplicationLayer / 1,550,000
 */
class BoardProfileLayer extends Model
{
    use HasFactory;

    protected $table = 'board_profile_layers';

    protected $fillable = [
        'board_profile_id',
        'sort_order',
        'role',
        'component_type',
        'material_id',
        'gsm',
        'multiplication_layer',
        'flute_type',
        'take_up_factor',
        'commercial_work_enabled',
        'notes',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'gsm' => 'integer',
        'multiplication_layer' => 'decimal:4',
        'take_up_factor' => 'decimal:4',
        'commercial_work_enabled' => 'boolean',
    ];

    // ─── RELATIONSHIPS ───

    public function boardProfile(): BelongsTo
    {
        return $this->belongsTo(BoardProfile::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'material_id');
    }

    // ─── HELPERS ───

    /**
     * Physical paper kg for one finished carton using the verified client
     * formula. Reel dimensions are shared by every layer of a carton.
     */
    public function paperKgPerUnit(
        float $reelLengthInch,
        float $reelHeightInch,
        ?float $formulaConstant = null
    ): float {
        $gsm = (float) ($this->gsm ?? 0);
        $layers = (float) ($this->multiplication_layer ?? 1);
        $constant = max((float) ($formulaConstant ?? config('carton.formula_constant', 1550000)), 0.000001);

        if ($reelLengthInch <= 0 || $reelHeightInch <= 0 || $gsm <= 0 || $layers <= 0) {
            return 0.0;
        }

        return $reelLengthInch * $reelHeightInch * $gsm * $layers / $constant;
    }
}
