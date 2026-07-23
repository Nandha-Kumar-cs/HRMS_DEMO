<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeIncrement extends Model
{
    protected $fillable = [
        'employee_id', 'previous_salary', 'new_salary', 'increment_amount',
        'increment_percentage', 'effective_date', 'remarks',
    ];

    protected $casts = ['effective_date' => 'date'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
