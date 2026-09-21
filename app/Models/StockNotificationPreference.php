<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'in_app_enabled',
        'email_enabled',
        'whatsapp_enabled',
        'minimum_escalation_level',
        'overdue_reminders_enabled',
        'recurrence_alerts_enabled',
        'weekly_review_alerts_enabled',
    ];

    protected $casts = [
        'in_app_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'minimum_escalation_level' => 'integer',
        'overdue_reminders_enabled' => 'boolean',
        'recurrence_alerts_enabled' => 'boolean',
        'weekly_review_alerts_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
