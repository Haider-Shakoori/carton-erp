<?php

namespace App\Support\Business;

use App\Models\BusinessUnit;
use App\Models\Setting;
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

        return $this->availableCache = BusinessUnit::query()->active()->get();
    }

    public function current(): ?BusinessUnit
    {
        if ($this->currentCache) {
            return $this->currentCache;
        }

        if (! $this->enabled()) {
            session()->forget(self::SESSION_KEY);

            return null;
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

        $defaultId = (int) (Setting::query()->value('default_business_unit_id') ?? 0);
        $current = $available->firstWhere('id', $defaultId) ?? $available->first();

        session()->put(self::SESSION_KEY, $current->id);

        return $this->currentCache = $current;
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

        session()->put(self::SESSION_KEY, $businessUnit->id);
        $this->currentCache = $businessUnit;

        return $businessUnit;
    }
}
