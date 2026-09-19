<?php
// app/Models/ProfitDistributionItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class ProfitDistributionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'distribution_id',
        'shareholder_id',
        'share_percentage',
        'amount',
        'status',
        'transaction_id',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'share_percentage' => 'decimal:2',
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';

    public function distribution()
    {
        return $this->belongsTo(ProfitDistribution::class, 'distribution_id');
    }

    public function shareholder()
    {
        return $this->belongsTo(Shareholder::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function getStatusLabelAttribute()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Paid',
            self::STATUS_FAILED => 'Failed',
        ][$this->status] ?? 'Unknown';
    }

    public function getStatusBadgeAttribute()
    {
        return [
            self::STATUS_PENDING => 'warning',
            self::STATUS_PAID => 'success',
            self::STATUS_FAILED => 'danger',
        ][$this->status] ?? 'secondary';
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Create the shareholder sub-ledger transaction once.
     */
    public function createTransaction()
    {
        if ($this->transaction_id) {
            return $this->transaction;
        }

        $defaultCurrency = Currency::where('is_default', true)->first();
        if (!$defaultCurrency) {
            throw new RuntimeException('Default currency is not configured.');
        }

        $transaction = Transaction::create([
            'table_name' => 'profit_distributions',
            'table_row_id' => $this->distribution_id,
            'type' => 'adjustment',
            'account_id' => null,
            'shareholder_id' => $this->shareholder_id,
            'currency_id' => $defaultCurrency->id,
            'amount' => $this->amount,
            'usd_amount' => strtoupper((string) $defaultCurrency->code) === 'USD' ? $this->amount : null,
            'exchange_rate' => 1,
            'transaction_type' => 'credit',
            'is_cash' => false,
            'description' => "Profit Distribution - Shareholder: {$this->shareholder->name} - {$this->distribution->distribution_number}",
            'status' => 'active',
            'created_by' => auth()->id() ?? $this->distribution->created_by ?? 1,
            'is_visible' => true,
        ]);

        $this->update([
            'transaction_id' => $transaction->id,
            'status' => self::STATUS_PAID,
            'payment_date' => now(),
        ]);

        return $transaction;
    }
}
