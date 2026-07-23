<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePromotion extends Model
{
    protected $fillable = [
        'employee_id', 'previous_designation_id', 'new_designation_id',
        'department_id', 'effective_date', 'remarks',
    ];

    protected $casts = ['effective_date' => 'date'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function previousDesignation()
    {
        return $this->belongsTo(Designation::class, 'previous_designation_id');
    }

    public function newDesignation()
    {
        return $this->belongsTo(Designation::class, 'new_designation_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
