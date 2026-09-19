<?php
// app/Models/Advance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advance extends Model
{
    protected $fillable = [
        'employee_id',
        'amount',
        'type',
        'status',
        'request_date',
        'deduction_start_date',
        'deduction_end_date',
        'deduction_amount',
        'remaining_amount',
        'reason',
        'rejection_reason',
        'approved_at',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'request_date' => 'date',
        'deduction_start_date' => 'date',
        'deduction_end_date' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeAdvance($query)
    {
        return $query->where('type', 'advance');
    }

    public function scopeLoan($query)
    {
        return $query->where('type', 'loan');
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'paid' => 'Paid',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'approved' => 'primary',
            'rejected' => 'danger',
            'paid' => 'success',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getTypeLabelAttribute()
    {
        return ucfirst($this->type);
    }

    public function isFullyPaid()
    {
        return $this->remaining_amount <= 0;
    }
}
