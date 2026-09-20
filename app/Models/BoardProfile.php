<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A board profile is a reusable manufacturing recipe (ply + paper layers),
 * NOT a carton size. Carton specifications resolve one profile into the
 * technical paper/adhesive rows used for costing, stock and production.
 */
class BoardProfile extends Model
{
    use HasFactory;

    protected $table = 'board_profiles';

    protected $fillable = [
        'name',
        'code',
        'description',
        'ply',
        'flute_type',
        'wastage_percentage',
        'is_active',
        'version',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ply' => 'integer',
        'wastage_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ─── RELATIONSHIPS ───

    public function layers(): HasMany
    {
        return $this->hasMany(BoardProfileLayer::class)->orderBy('sort_order')->orderBy('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ─── SCOPES ───

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── HELPERS ───

    public function isUsable(): bool
    {
        return $this->is_active;
    }

    public function paperLayers()
    {
        return $this->layers
            ->filter(fn (BoardProfileLayer $layer) => $layer->component_type === 'paper')
            ->values();
    }

    /**
     * Frozen profile representation stored inside sale item snapshots so an
     * old order never depends on today's profile row.
     */
    public function snapshot(): array
    {
        $this->loadMissing('layers.material');

        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'ply' => (int) $this->ply,
            'flute_type' => $this->flute_type,
            'wastage_percentage' => (float) $this->wastage_percentage,
            'version' => $this->version,
            'layers' => $this->layers->map(function (BoardProfileLayer $layer) {
                return [
                    'id' => (int) $layer->id,
                    'sort_order' => (int) $layer->sort_order,
                    'role' => $layer->role,
                    'component_type' => $layer->component_type,
                    'material_id' => $layer->material_id ? (int) $layer->material_id : null,
                    'material_name' => $layer->material?->name,
                    'gsm' => $layer->gsm !== null ? (int) $layer->gsm : null,
                    'multiplication_layer' => (float) $layer->multiplication_layer,
                    'flute_type' => $layer->flute_type,
                    'take_up_factor' => $layer->take_up_factor !== null ? (float) $layer->take_up_factor : null,
                    'commercial_work_enabled' => (bool) $layer->commercial_work_enabled,
                    'notes' => $layer->notes,
                ];
            })->values()->all(),
        ];
    }
}
