<?php
// app/Models/HR/Department.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function designations()
    {
        return $this->hasMany(Designation::class);
    }
}
