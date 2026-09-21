<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagementNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'in_app_enabled',
        'email_enabled',
        'whatsapp_enabled',
        'level_2_enabled',
        'level_3_enabled',
        'assignment_enabled',
        'overdue_enabled',
        'recurrence_enabled',
        'weekly_review_enabled',
    ];

    protected $casts = [
        'in_app_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'level_2_enabled' => 'boolean',
        'level_3_enabled' => 'boolean',
        'assignment_enabled' => 'boolean',
        'overdue_enabled' => 'boolean',
        'recurrence_enabled' => 'boolean',
        'weekly_review_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
