<?php
// app/Models/EmployeeAdvance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmployeeAdvance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'advance_number',
        'amount',
        'paid_amount',
        'remaining_amount',
        'installments',
        'installments_paid',
        'installment_amount',
        'type',
        'reason',
        'request_date',
        'approval_date',
        'disbursement_date',
        'first_installment_date',
        'status',
        'approved_by',
        'rejection_reason',
        'attachment',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'request_date' => 'date',
        'approval_date' => 'date',
        'disbursement_date' => 'date',
        'first_installment_date' => 'date',
        'installments' => 'integer',
        'installments_paid' => 'integer',
    ];

    // ─── Boot Method ───
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($advance) {
            if (empty($advance->advance_number)) {
                $advance->advance_number = 'ADV-' . date('Y') . '-' . strtoupper(Str::random(8));
            }
        });
    }

    // ─── Relationships ───
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function repayments()
    {
        return $this->hasMany(AdvanceRepayment::class);
    }

    // ─── Accessors ───
    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'disbursed' => 'Disbursed',
            'partially_paid' => 'Partially Paid',
            'fully_paid' => 'Fully Paid',
            'rejected' => 'Rejected',
        ];
        return $labels[$this->status] ?? 'Unknown';
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->amount <= 0) {
            return 0;
        }
        return min(100, ($this->paid_amount / $this->amount) * 100);
    }

    public function getIsFullyPaidAttribute()
    {
        return $this->status === 'fully_paid' || $this->remaining_amount <= 0;
    }
}
