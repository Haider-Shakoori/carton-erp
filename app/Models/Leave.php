<?php
// app/Models/Leave.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate])
            ->orWhereBetween('end_date', [$startDate, $endDate]);
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
        ];

        return $colors[$this->status] ?? 'secondary';
    }
}
