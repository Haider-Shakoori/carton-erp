<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AccountSubCategory extends Model
{
    use LogsActivity;

    protected static $logName = 'account_sub_category';
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->useLogName('account_sub_category')
            ->setDescriptionForEvent(fn(string $eventName) => "Account Sub Category {$eventName}");
    }

    protected $fillable = ['name', 'account_category_id'];

    public function accountCategory()
    {
        return $this->belongsTo(AccountCategory::class, 'account_category_id');
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }
}
