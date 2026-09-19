<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ExchangeRate extends Model
{
    use LogsActivity;

    protected static $logName = 'exchange_rate';
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->useLogName('exchange_rate')
            ->setDescriptionForEvent(fn(string $eventName) => "Exchange Rate {$eventName}");
    }

    protected $fillable = [
        'base_currency_id',
        'target_currency_id',
        'min_amount',
        'max_amount',
        'rate',
        'cost_rate',
    ];

    public function baseCurrency()
    {
        return $this->belongsTo(Currency::class, 'base_currency_id');
    }

    public function targetCurrency()
    {
        return $this->belongsTo(Currency::class, 'target_currency_id');
    }
}
