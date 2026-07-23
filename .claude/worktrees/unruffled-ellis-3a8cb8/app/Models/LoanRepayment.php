<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    protected $fillable = [
        'employee_loan_id', 'amount_paid', 'payment_date', 'salary_slip_id', 'note',
    ];

    protected $casts = ['payment_date' => 'date'];

    public function loan()
    {
        return $this->belongsTo(EmployeeLoan::class, 'employee_loan_id');
    }

    public function salarySlip()
    {
        return $this->belongsTo(SalarySlip::class);
    }
}
