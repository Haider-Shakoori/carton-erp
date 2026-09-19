<?php
// app/Models/Shareholder.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shareholder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'address',
        'share_percentage',
        'capital_contribution',
        'joining_date',
        'is_active',
        'notes',
        'profile_image',
        'created_by',
    ];

    protected $casts = [
        'share_percentage' => 'decimal:2',
        'capital_contribution' => 'decimal:2',
        'joining_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function profitDistributions()
    {
        return $this->hasMany(ProfitDistributionItem::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(ShareholderWithdrawal::class);
    }

    /**
     * Shareholder sub-ledger transactions. These are deliberately separate
     * from Account transactions; shareholder IDs are not Account IDs.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'shareholder_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getStatusBadgeAttribute()
    {
        return $this->is_active ? 'success' : 'danger';
    }

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    /**
     * Current shareholder balance in the default currency.
     * Capital + active shareholder credits - active shareholder debits.
     */
    public function getBalanceAttribute()
    {
        $credits = $this->transactions()
            ->where('transaction_type', 'credit')
            ->where('status', 'active')
            ->sum('amount');

        $debits = $this->transactions()
            ->where('transaction_type', 'debit')
            ->where('status', 'active')
            ->sum('amount');

        return (float) ($this->capital_contribution ?? 0) + (float) $credits - (float) $debits;
    }

    public function getFormattedBalanceAttribute()
    {
        $defaultCurrency = Currency::where('is_default', true)->first();
        $symbol = $defaultCurrency?->symbol ?? '$';
        return $symbol . number_format($this->balance, 2);
    }

    public function getTotalProfitAttribute()
    {
        return (float) $this->transactions()
            ->where('table_name', 'profit_distributions')
            ->where('transaction_type', 'credit')
            ->where('status', 'active')
            ->sum('amount');
    }

    public function getTotalLossAttribute()
    {
        return (float) $this->transactions()
            ->where('table_name', 'profit_distributions')
            ->where('transaction_type', 'debit')
            ->where('status', 'active')
            ->sum('amount');
    }

    public function getNetProfitLossAttribute()
    {
        return $this->total_profit - $this->total_loss;
    }

    public function getTotalDistributedAttribute()
    {
        return $this->profitDistributions()
            ->where('status', ProfitDistributionItem::STATUS_PAID)
            ->sum('amount');
    }

    public function getTotalWithdrawnAttribute()
    {
        return $this->withdrawals()
            ->where('status', ShareholderWithdrawal::STATUS_PAID)
            ->sum('amount');
    }

    public function getPendingWithdrawalsCountAttribute()
    {
        return $this->withdrawals()
            ->where('status', ShareholderWithdrawal::STATUS_PENDING)
            ->count();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    public static function generateCode()
    {
        $last = self::orderBy('id', 'desc')->first();
        $number = $last ? intval(substr($last->code, -4)) + 1 : 1;
        return 'SH-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function calculateShare($totalProfit)
    {
        return $totalProfit * ((float) $this->share_percentage / 100);
    }

    public function hasSufficientBalance($amount)
    {
        return $this->balance >= $amount;
    }

    public function getSharePercentageFormattedAttribute()
    {
        return $this->share_percentage . '%';
    }

    public function getProfileImageUrlAttribute()
    {
        if ($this->profile_image) {
            return asset('storage/' . $this->profile_image);
        }
        return asset('images/default-avatar.png');
    }

    public function getInitialsAttribute()
    {
        $words = explode(' ', $this->name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($this->name, 0, 2));
    }

    public function canBeDeleted()
    {
        return !$this->profitDistributions()->exists() && !$this->withdrawals()->exists();
    }

    public function getDeletionWarningAttribute()
    {
        $warnings = [];

        if ($this->profitDistributions()->exists()) {
            $warnings[] = 'This shareholder has ' . $this->profitDistributions()->count() . ' profit distribution(s).';
        }

        if ($this->withdrawals()->exists()) {
            $warnings[] = 'This shareholder has ' . $this->withdrawals()->count() . ' withdrawal(s).';
        }

        return implode(' ', $warnings);
    }
}
