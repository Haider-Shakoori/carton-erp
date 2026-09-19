<?php
// app/Models/ShareholderWithdrawal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;

class ShareholderWithdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'withdrawal_number',
        'shareholder_id',
        'amount',
        'withdrawal_date',
        'status',
        'reason',
        'transaction_id',
        'approved_by',
        'approved_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'withdrawal_date' => 'date',
        'approved_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PAID = 'paid';
    const STATUS_REJECTED = 'rejected';

    const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_PAID => 'Paid',
        self::STATUS_REJECTED => 'Rejected',
    ];

    public function shareholder()
    {
        return $this->belongsTo(Shareholder::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($withdrawal) {
            if (empty($withdrawal->withdrawal_number)) {
                $withdrawal->withdrawal_number = 'WD-' . date('Y') . '-' . strtoupper(Str::random(8));
            }
        });
    }

    public function getStatusLabelAttribute()
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    public function getStatusBadgeAttribute()
    {
        return [
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'info',
            self::STATUS_PAID => 'success',
            self::STATUS_REJECTED => 'danger',
        ][$this->status] ?? 'secondary';
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

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
            'table_name' => 'shareholder_withdrawals',
            'table_row_id' => $this->id,
            'type' => 'adjustment',
            'account_id' => null,
            'shareholder_id' => $this->shareholder_id,
            'currency_id' => $defaultCurrency->id,
            'amount' => $this->amount,
            'usd_amount' => strtoupper((string) $defaultCurrency->code) === 'USD' ? $this->amount : null,
            'exchange_rate' => 1,
            'transaction_type' => 'debit',
            'is_cash' => true,
            'description' => "Shareholder Withdrawal - {$this->shareholder->name} - {$this->withdrawal_number}",
            'status' => 'active',
            'created_by' => auth()->id() ?? $this->created_by ?? 1,
            'is_visible' => true,
        ]);

        $this->update([
            'transaction_id' => $transaction->id,
            'status' => self::STATUS_PAID,
        ]);

        return $transaction;
    }

    public function processWithdrawal()
    {
        if ($this->status !== self::STATUS_APPROVED) {
            throw new RuntimeException('Withdrawal must be approved first.');
        }

        if ($this->shareholder->balance < $this->amount) {
            throw new RuntimeException('Insufficient balance for this withdrawal.');
        }

        return $this->createTransaction();
    }
}
