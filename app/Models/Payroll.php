<?php
// app/Models/Payroll.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'payroll_number',
        'period_start',
        'period_end',
        'payment_date',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'medical_allowance',
        'other_allowances',
        'overtime_pay',
        'bonus',
        'commission',
        'other_earnings',
        'tax_deduction',
        'social_security',
        'advance_deduction',
        'loan_deduction',
        'penalty_deduction',
        'other_deductions',
        'total_earnings',
        'total_deductions',
        'net_salary',
        'status',
        'notes',
        'attachment',
        'created_by',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'payment_date' => 'date',
        'basic_salary' => 'decimal:2',
        'housing_allowance' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'medical_allowance' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'bonus' => 'decimal:2',
        'commission' => 'decimal:2',
        'other_earnings' => 'decimal:2',
        'tax_deduction' => 'decimal:2',
        'social_security' => 'decimal:2',
        'advance_deduction' => 'decimal:2',
        'loan_deduction' => 'decimal:2',
        'penalty_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeMonth($query, $month, $year)
    {
        return $query->whereMonth('period_start', $month)
            ->whereYear('period_start', $year);
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'draft' => 'Draft',
            'processed' => 'Processed',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'draft' => 'warning',
            'processed' => 'info',
            'paid' => 'success',
            'cancelled' => 'danger',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getMonthYearAttribute()
    {
        return $this->period_start->format('F Y');
    }

    public function getTotalEarningsAttribute()
    {
        return $this->basic_salary +
            $this->housing_allowance +
            $this->transport_allowance +
            $this->medical_allowance +
            $this->other_allowances +
            $this->overtime_pay +
            $this->bonus +
            $this->commission +
            $this->other_earnings;
    }

    public function getTotalDeductionsAttribute()
    {
        return $this->tax_deduction +
            $this->social_security +
            $this->advance_deduction +
            $this->loan_deduction +
            $this->penalty_deduction +
            $this->other_deductions;
    }

    public function getNetSalaryAttribute()
    {
        return $this->total_earnings - $this->total_deductions;
    }

    public static function generatePayrollNumber()
    {
        $prefix = 'PAY-' . date('Y') . '-';
        $lastPayroll = self::where('payroll_number', 'like', $prefix . '%')
            ->orderBy('payroll_number', 'desc')
            ->first();

        if ($lastPayroll) {
            $lastNumber = intval(substr($lastPayroll->payroll_number, -6));
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return $prefix . $newNumber;
    }
}
