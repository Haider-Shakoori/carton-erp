<?php

namespace App\Services;

use App\Models\BOM;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class BOMGovernanceService
{
    public function createRevision(BOM $source, User $user): BOM
    {
        return DB::transaction(function () use ($source, $user): BOM {
            $source = BOM::query()
                ->withoutGlobalScope('business_unit')
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($source->id);

            $query = BOM::query()
                ->withoutGlobalScope('business_unit')
                ->where('product_id', $source->product_id);

            if ($source->business_unit_id) {
                $query->where('business_unit_id', $source->business_unit_id);
            } else {
                $query->whereNull('business_unit_id');
            }

            $next = max((int) $query->max('revision_sequence') + 1, 2);

            $revision = $source->replicate([
                'id',
                'code',
                'status',
                'is_active',
                'effective_from',
                'effective_to',
                'supersedes_bom_id',
                'approved_by',
                'approved_at',
                'locked_by',
                'locked_at',
                'created_at',
                'updated_at',
            ]);

            $baseCode = preg_replace('/-R\d+(?:-[A-Z0-9]+)?$/i', '', (string) $source->code);
            $code = $baseCode.'-R'.$next;

            if (BOM::query()->withoutGlobalScope('business_unit')->where('code', $code)->exists()) {
                $code .= '-'.Str::upper(Str::random(4));
            }

            $revision->code = $code;
            $revision->version = 'R'.$next;
            $revision->revision_sequence = $next;
            $revision->status = 'draft';
            $revision->is_active = false;
            $revision->effective_from = null;
            $revision->effective_to = null;
            $revision->supersedes_bom_id = $source->id;
            $revision->approved_by = null;
            $revision->approved_at = null;
            $revision->locked_by = null;
            $revision->locked_at = null;
            $revision->created_by = $user->id;
            $revision->updated_by = $user->id;
            $revision->save();

            foreach ($source->items as $item) {
                $copy = $item->replicate(['id', 'bom_id', 'created_at', 'updated_at']);
                $copy->bom_id = $revision->id;
                $copy->save();
            }

            return $revision->fresh(['items.material', 'product']);
        });
    }

    public function approveRevision(
        BOM $revision,
        User $user,
        Carbon|string|null $effectiveFrom = null
    ): BOM {
        return DB::transaction(function () use ($revision, $user, $effectiveFrom): BOM {
            $revision = BOM::query()
                ->withoutGlobalScope('business_unit')
                ->lockForUpdate()
                ->findOrFail($revision->id);

            if ($revision->status !== 'draft') {
                throw new RuntimeException('Only draft BOM revisions can be approved.');
            }

            if ($revision->items()->count() === 0) {
                throw new RuntimeException('A BOM revision cannot be approved without material lines.');
            }

            $effective = $effectiveFrom instanceof Carbon
                ? $effectiveFrom->copy()->startOfDay()
                : Carbon::parse($effectiveFrom ?: now())->startOfDay();

            $previousQuery = BOM::query()
                ->withoutGlobalScope('business_unit')
                ->where('product_id', $revision->product_id)
                ->whereKeyNot($revision->id)
                ->where(function ($query) {
                    $query->where('status', 'active')
                        ->orWhere('is_active', true);
                });

            if ($revision->business_unit_id) {
                $previousQuery->where('business_unit_id', $revision->business_unit_id);
            } else {
                $previousQuery->whereNull('business_unit_id');
            }

            $previous = $previousQuery->lockForUpdate()->get();
            foreach ($previous as $bom) {
                $bom->status = 'archived';
                $bom->is_active = false;
                $bom->effective_to = $effective->copy()->subDay()->toDateString();
                $bom->locked_by ??= $user->id;
                $bom->locked_at ??= now();
                $bom->saveQuietly();
            }

            $revision->status = 'active';
            $revision->is_active = true;
            $revision->effective_from = $effective->toDateString();
            $revision->effective_to = null;
            $revision->approved_by = $user->id;
            $revision->approved_at = now();
            $revision->locked_by = $user->id;
            $revision->locked_at = now();
            $revision->updated_by = $user->id;
            $revision->saveQuietly();

            return $revision->fresh(['items.material', 'product', 'approvedBy']);
        });
    }
}
