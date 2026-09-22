<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockNotificationPreference extends Model
{
    protected $attributes = [
        'in_app_enabled' => true,
        'email_enabled' => false,
        'whatsapp_enabled' => false,
        'minimum_escalation_level' => 2,
        'assignment_alerts_enabled' => true,
        'overdue_reminders_enabled' => true,
        'recurrence_alerts_enabled' => true,
        'weekly_review_alerts_enabled' => true,
    ];

    protected $fillable = [
        'user_id',
        'in_app_enabled',
        'email_enabled',
        'whatsapp_enabled',
        'minimum_escalation_level',
        'assignment_alerts_enabled',
        'overdue_reminders_enabled',
        'recurrence_alerts_enabled',
        'weekly_review_alerts_enabled',
    ];

    protected $casts = [
        'in_app_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'minimum_escalation_level' => 'integer',
        'assignment_alerts_enabled' => 'boolean',
        'overdue_reminders_enabled' => 'boolean',
        'recurrence_alerts_enabled' => 'boolean',
        'weekly_review_alerts_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
