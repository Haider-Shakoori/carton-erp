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

            $activeBusinessUnitId = (int) session(BusinessUnitContext::SESSION_KEY, 0);

            if ($activeBusinessUnitId > 0) {
                $model->business_unit_id = $activeBusinessUnitId;
            }
        });

        static::addGlobalScope('business_unit', function (Builder $builder): void {
            $activeBusinessUnitId = (int) session(BusinessUnitContext::SESSION_KEY, 0);

            if ($activeBusinessUnitId <= 0) {
                return;
            }

            $qualifiedColumn = $builder->getModel()->qualifyColumn('business_unit_id');

            $builder->where(function (Builder $query) use ($qualifiedColumn, $activeBusinessUnitId): void {
                $query->where($qualifiedColumn, $activeBusinessUnitId)
                    ->orWhereNull($qualifiedColumn);
            });
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
