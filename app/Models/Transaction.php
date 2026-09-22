<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusinessUnit;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Transaction extends Model
{
    use LogsActivity, BelongsToBusinessUnit;

    protected static $logName = 'transaction';
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->useLogName('transaction')
            ->setDescriptionForEvent(fn(string $eventName) => "Transaction {$eventName}");
    }

    protected $fillable = [
        'table_name',
        'table_row_id',
        'type',
        'account_id',
        'shareholder_id',
        'currency_id',
        'amount',
        'usd_amount',
        'exchange_rate',
        'transaction_type',
        'is_cash',
        'description', // This is the field you have
        'status',
        'created_by',
        'is_visible',
        'account_type',
        'remittance_account_id',
        'remittance_status'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function shareholder()
    {
        return $this->belongsTo(Shareholder::class, 'shareholder_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Generate automated description
     */
    public function generateDescription(): string
    {
        $parts = [];

        // Add transaction type
        $typeMap = [
            'purchase' => 'Purchase Order',
            'sale' => 'Sale Order',
            'return' => 'Return',
            'waste' => 'Waste',
            'transfer' => 'Transfer',
        ];
        $parts[] = $typeMap[$this->type] ?? ucfirst($this->type);

        // Add reference number from table_row_id
        if ($this->table_row_id && $this->table_name) {
            $parts[] = '#' . $this->table_row_id;
        }

        // Add transaction type (credit/debit)
        $parts[] = '(' . ucfirst($this->transaction_type) . ')';

        // Add account name
        if ($this->account) {
            $parts[] = '- ' . $this->account->name;
        }

        // Add currency
        if ($this->currency) {
            $parts[] = '[' . $this->currency->code . ']';
        }

        // Add amount
        $parts[] = number_format($this->amount, 2);

        // If there's a custom description, append it
        if ($this->description && !str_contains($this->description, ' - ')) {
            $parts[] = '- ' . $this->description;
        }

        return implode(' ', $parts);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate description before creating
        static::creating(function ($transaction) {
            if (empty($transaction->description)) {
                $transaction->description = $transaction->generateDescription();
            }
        });
    }
}
