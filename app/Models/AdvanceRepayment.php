<?php
// app/Models/AdvanceRepayment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvanceRepayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_advance_id',
        'amount',
        'payment_date',
        'payment_method',
        'notes',
        'receipt_number',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // ─── Relationships ───
    public function advance()
    {
        return $this->belongsTo(EmployeeAdvance::class, 'employee_advance_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
