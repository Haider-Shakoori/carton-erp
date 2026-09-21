<?php

namespace App\Models\Concerns;

use App\Models\BusinessUnit;
use App\Support\Business\BusinessUnitContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToBusinessUnit
{
    public static function bootBelongsToBusinessUnit(): void
    {
        static::creating(function ($model): void {
            if (! empty($model->business_unit_id)) {
                return;
            }

            try {
                $context = app(BusinessUnitContext::class);
                $current = $context->current();

                if ($context->enabled() && $current) {
                    $model->business_unit_id = $current->id;
                }
            } catch (\Throwable $e) {
                // Keep legacy/unified mode non-blocking during migrations,
                // console commands, and bootstrap before the feature is ready.
            }
        });

        static::addGlobalScope('business_unit', function (Builder $builder): void {
            try {
                $context = app(BusinessUnitContext::class);

                if (! $context->enabled()) {
                    return;
                }

                $current = $context->current();
                if (! $current) {
                    return;
                }

                $qualifiedColumn = $builder->getModel()->qualifyColumn('business_unit_id');

                $builder->where(function (Builder $query) use ($qualifiedColumn, $current): void {
                    $query->where($qualifiedColumn, $current->id)
                        ->orWhereNull($qualifiedColumn);
                });
            } catch (\Throwable $e) {
                // Unified behavior is the safe fallback.
            }
        });
    }

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function scopeForBusinessUnit(Builder $query, BusinessUnit|int $businessUnit): Builder
    {
        $id = $businessUnit instanceof BusinessUnit ? $businessUnit->id : $businessUnit;

        return $query->withoutGlobalScope('business_unit')
            ->where($this->qualifyColumn('business_unit_id'), $id);
    }
}
