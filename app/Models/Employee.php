<?php
// app/Models/HR/Employee.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'date_of_birth',
        'hire_date',
        'termination_date',
        'supervisor_id',
        'department_id',
        'designation_id',
        'basic_salary',
        'profile_photo',
        'is_active',
        'user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'basic_salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ─── Relationships ───

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function advances()
    {
        return $this->hasMany(Advance::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    // ─── Accessors ───

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo) {
            return asset('storage/' . $this->profile_photo);
        }
        return asset('images/default-avatar.png');
    }

    public function getStatusBadgeAttribute()
    {
        return $this->is_active ? 'active' : 'inactive';
    }

    public function getStatusLabelAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    // ─── Scopes ───

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('first_name', 'like', "%{$search}%")
            ->orWhere('last_name', 'like', "%{$search}%")
            ->orWhere('employee_code', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%");
    }

    // ─── Helper Methods ───

    public static function generateEmployeeCode()
    {
        $prefix = 'EMP-' . date('Y') . '-';
        $lastEmployee = self::where('employee_code', 'like', $prefix . '%')
            ->orderBy('employee_code', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = intval(substr($lastEmployee->employee_code, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }
}
