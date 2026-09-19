<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Exchange extends Model
{
    use LogsActivity;

    protected static $logName = 'exchange';
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->useLogName('exchange')
            ->setDescriptionForEvent(fn(string $eventName) => "Exchange {$eventName}");
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
        'note'
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

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'table');
    }

}
