<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockControlReviewItem extends Model
{
    protected $fillable = [
        'stock_control_review_id',
        'stock_control_escalation_id',
        'severity_snapshot',
        'level_snapshot',
        'status_snapshot',
        'notes',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(
            StockControlReview::class,
            'stock_control_review_id'
        );
    }

    public function escalation(): BelongsTo
    {
        return $this->belongsTo(
            StockControlEscalation::class,
            'stock_control_escalation_id'
        );
    }
}
