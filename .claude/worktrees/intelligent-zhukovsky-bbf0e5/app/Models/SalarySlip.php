<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySlip extends Model
{
    protected $fillable = [
        'employee_id', 'month', 'year', 'fixed_salary',
        'variable_salary', 'allowances', 'deductions', 'net_salary',
        'attendance_summary',
    ];

    protected $casts = [
        'allowances'         => 'array',
        'deductions'         => 'array',
        'attendance_summary' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function loanRepayments()
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }
}
