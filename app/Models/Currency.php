<?php
// app/Models/Currency.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'code', 
        'symbol', 
        'country', 
        'flag', 
        'exchange_rate', 
        'is_default', 
        'is_active'
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($currency) {
            // If this currency is set as default, remove default from all others
            if ($currency->is_default) {
                static::where('id', '!=', $currency->id)->update(['is_default' => false]);
            }
        });

        static::creating(function ($currency) {
            // If this is the first currency, make it default
            if (static::count() === 0) {
                $currency->is_default = true;
            }
        });
    }

    // Scope for active currencies
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for default currency
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Get default currency (static method)
    public static function getDefaultCurrency()
    {
        return static::where('is_default', true)->first();
    }

    // Get default currency ID
    public static function getDefaultCurrencyId()
    {
        $default = static::getDefaultCurrency();
        return $default ? $default->id : null;
    }

    // Check if this is default currency
    public function isDefault()
    {
        return $this->is_default === true;
    }

    // Convert amount from this currency to default currency
    public function convertToDefault($amount)
    {
        $defaultCurrency = self::getDefaultCurrency();
        
        // If this is the default currency or no default found, return original amount
        if (!$defaultCurrency || $this->is_default) {
            return $amount;
        }
        
        // Convert using exchange rate
        return $amount * $this->exchange_rate;
    }

    // Convert amount from default currency to this currency
    public function convertFromDefault($amount)
    {
        // If this is the default currency, return original amount
        if ($this->is_default || $this->exchange_rate == 0) {
            return $amount;
        }
        
        // Convert using exchange rate
        return $amount / $this->exchange_rate;
    }

    // Format amount with currency symbol
    public function formatAmount($amount)
    {
        return $this->symbol . ' ' . number_format($amount, 2);
    }

    // Get exchange rate against default currency
    public function getRateAgainstDefaultAttribute()
    {
        $defaultCurrency = self::getDefaultCurrency();
        
        if (!$defaultCurrency || $this->is_default) {
            return 1;
        }
        
        return $this->exchange_rate;
    }

    // Get all currencies except default
    public function scopeNonDefault($query)
    {
        return $query->where('is_default', false);
    }

    // Search currencies
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                     ->orWhere('code', 'like', "%{$search}%")
                     ->orWhere('country', 'like', "%{$search}%");
    }

    // Get flag URL
    public function getFlagUrlAttribute()
    {
        if ($this->flag) {
            return asset($this->flag);
        }
        return asset('assets/flags/default.svg');
    }

    // Get full currency info
    public function getFullInfoAttribute()
    {
        return "{$this->name} ({$this->code}) - {$this->symbol}";
    }
}