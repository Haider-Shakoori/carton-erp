<?php

namespace App\Support\Business;

use App\Models\BusinessUnit;
use App\Models\Setting;
use App\Services\BusinessUnitProvisioningService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class BusinessUnitContext
{
    public const SESSION_KEY = 'active_business_unit_id';

    private ?bool $enabledCache = null;
    private ?Collection $availableCache = null;
    private ?BusinessUnit $currentCache = null;

    public function enabled(): bool
    {
        if ($this->enabledCache !== null) {
            return $this->enabledCache;
        }

        if (! Schema::hasTable('settings') || ! Schema::hasColumn('settings', 'separate_business_units_enabled')) {
            return $this->enabledCache = false;
        }

        return $this->enabledCache = (bool) (Setting::query()->value('separate_business_units_enabled') ?? false);
    }

    public function available(): Collection
    {
        if ($this->availableCache !== null) {
            return $this->availableCache;
        }

        if (! Schema::hasTable('business_units')) {
            return $this->availableCache = collect();
        }

        app(BusinessUnitProvisioningService::class)->ensureRequiredUnits();

        $query = BusinessUnit::query()->active();

        if (auth()->check()) {
            $user = auth()->user();
            $assignedIds = $user->businessUnits()->pluck('business_units.id');

            // Existing users remain backward-compatible until an administrator
            // explicitly assigns business access. Once assigned, the allow-list
            // becomes authoritative.
            if ($assignedIds->isNotEmpty()) {
                $query->whereIn('id', $assignedIds);
            }
        }

        return $this->availableCache = $query->get();
    }

    public function current(): ?BusinessUnit
    {
        if (! $this->enabled()) {
            $this->currentCache = null;
            session()->forget(self::SESSION_KEY);

            return null;
        }

        if ($this->currentCache) {
            return $this->currentCache;
        }

        $available = $this->available();
        if ($available->isEmpty()) {
            session()->forget(self::SESSION_KEY);

            return null;
        }

        $sessionId = (int) session(self::SESSION_KEY, 0);
        $current = $available->firstWhere('id', $sessionId);

        if ($current) {
            return $this->currentCache = $current;
        }

        $defaultId = 0;

        if (auth()->check() && method_exists(auth()->user(), 'businessUnits')) {
            $defaultId = (int) (
                auth()->user()
                    ->businessUnits()
                    ->wherePivot('is_default', true)
                    ->value('business_units.id') ?? 0
            );
        }

        if ($defaultId <= 0) {
            $defaultId = (int) (Setting::query()->value('default_business_unit_id') ?? 0);
        }

        $current = $available->firstWhere('id', $defaultId) ?? $available->first();

        session()->put(self::SESSION_KEY, $current->id);

        return $this->currentCache = $current;
    }

    public function reset(): void
    {
        $this->enabledCache = null;
        $this->availableCache = null;
        $this->currentCache = null;
    }

    public function switchTo(BusinessUnit $businessUnit): BusinessUnit
    {
        if (! $this->enabled()) {
            session()->forget(self::SESSION_KEY);

            throw new \RuntimeException('Separate business units are disabled in Settings.');
        }

        if (! $businessUnit->is_active) {
            throw new \RuntimeException('The selected business unit is inactive.');
        }

        if (! $this->available()->contains('id', $businessUnit->id)) {
            throw new \RuntimeException('You do not have access to the selected business unit.');
        }

        session()->put(self::SESSION_KEY, $businessUnit->id);
        $this->currentCache = $businessUnit;

        return $businessUnit;
    }
}
