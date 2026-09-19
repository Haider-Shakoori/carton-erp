<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AccountCategory extends Model
{
    use LogsActivity;

    protected static $logName = 'account_category';
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->useLogName('account_category')
            ->setDescriptionForEvent(fn(string $eventName) => "Account Category {$eventName}");
    }

    protected $fillable = [
        'name',
        'code_prefix',
        'bi_icon',
        'bi_icon_color',
    ];

    protected static function booted()
    {
        static::creating(function ($category) {
            $name = strtolower($category->name);

            $iconMap = [
                'client' => 'bi-person',
                'agents' => 'bi-people',
                'expense' => 'bi-receipt',
                'employee' => 'bi-person-badge',
                'cash' => 'bi-cash-coin',
            ];

            $colorMap = [
                'client' => '#007bff',  // Blue
                'agents' => '#6f42c1',    // Purple
                'expense' => '#fd7e14',   // Orange
                'employee' => '#ffc107',  // Yellow
                'cash' => '#28a745',      // Green
            ];

            $category->bi_icon = $iconMap[$name] ?? 'bi-person';
            $category->bi_icon_color = $colorMap[$name] ?? '#6c757d'; // Default gray
        });
    }

    public function subCategories()
    {
        return $this->hasMany(AccountSubCategory::class, 'account_category_id');
    }
}
