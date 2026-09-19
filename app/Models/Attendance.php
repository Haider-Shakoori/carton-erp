<?php
// app/Models/Attendance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'status',
        'working_hours',
        'overtime_hours',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'working_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
    ];

    // ─── Relationships ───
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ─── Accessors ───
    public function getStatusLabelAttribute()
    {
        $labels = [
            'present' => 'Present',
            'absent' => 'Absent',
            'half_day' => 'Half Day',
        ];
        return $labels[$this->status] ?? 'Unknown';
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'present' => 'success',
            'absent' => 'danger',
            'half_day' => 'warning',
        ];
        return $badges[$this->status] ?? 'secondary';
    }

    // ─── Scopes ───
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    // ─── Helper Methods ───
    public static function getMonthlySummary($employeeId, $year, $month)
    {
        $attendance = self::where('employee_id', $employeeId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        return [
            'present' => $attendance->where('status', 'present')->count(),
            'absent' => $attendance->where('status', 'absent')->count(),
            'half_day' => $attendance->where('status', 'half_day')->count(),
            'total_hours' => $attendance->sum('working_hours'),
            'overtime_hours' => $attendance->sum('overtime_hours'),
            'total_days' => $attendance->count(),
        ];
    }
}
