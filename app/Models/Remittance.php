<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Remittance extends Model
{
    use HasFactory, LogsActivity;

    protected static $logName = 'remittance';
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->useLogName('remittance')
            ->setDescriptionForEvent(fn(string $eventName) => "Remittance {$eventName}");
    }

    protected $fillable = [
        'account_id',
        'currency_id',
        'amount',
        'bank_name',
        'account_holder',
        'bank_account_number',
        'bank_address',
        'phone_number',
        'note',
        'receipt_file',
        'status',
        'created_by',
    ];

    public function account() {
        return $this->belongsTo(Account::class);
    }

    public function currency() {
        return $this->belongsTo(Currency::class);
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
