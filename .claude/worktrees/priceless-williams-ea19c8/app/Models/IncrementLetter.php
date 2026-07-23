<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncrementLetter extends Model
{
    protected $fillable = [
        'employee_id', 'old_salary', 'new_salary', 'increment_percentage', 'effective_date',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
