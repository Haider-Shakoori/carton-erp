<?php
// app/Models/LeaveRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status',
        'approval_notes',
        'applied_date',
        'approved_date',
        'approved_by',
        'attachment',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'applied_date' => 'date',
        'approved_date' => 'date',
        'days' => 'integer',
    ];

    // ─── Relationships ───
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ─── Accessors ───
    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
        ];
        return $labels[$this->status] ?? 'Unknown';
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
        ];
        return $badges[$this->status] ?? 'secondary';
    }
}
