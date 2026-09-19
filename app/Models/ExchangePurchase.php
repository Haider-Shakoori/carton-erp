<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ExchangePurchase extends Model
{
    use LogsActivity;

    protected static $logName = 'exchange_purchase';
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->useLogName('exchange_purchase')
            ->setDescriptionForEvent(fn(string $eventName) => "Exchange Purchase {$eventName}");
    }

    protected $fillable = [
        'customer_account_id',
        'base_currency_id',
        'target_currency_id',
        'base_amount',
        'rate',
        'target_amount',
        'cost_rate',
        'target_profit',
        'destination',
        'is_withdrawn',
        'office_account_id',
        'note',
        'is_cash',
        'status',
    ];

    public function customerAccount()
    {
        return $this->belongsTo(Account::class, 'customer_account_id');
    }

    public function baseCurrency()
    {
        return $this->belongsTo(Currency::class, 'base_currency_id');
    }

    public function targetCurrency()
    {
        return $this->belongsTo(Currency::class, 'target_currency_id');
    }

    public function officeAccount()
    {
        return $this->belongsTo(Account::class, 'office_account_id');
    }
}
