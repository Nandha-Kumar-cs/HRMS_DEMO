<?php

namespace App\Traits;

use App\Models\Employee;
use Illuminate\Http\RedirectResponse;

trait HasSalaryGuard
{
    /**
     * Redirect back with an error if the employee has no salary configured.
     * Returns a RedirectResponse if salary is missing, null if OK.
     */
    protected function guardSalary(Employee $employee): ?RedirectResponse
    {
        if ($employee->fixed_salary == 0 && $employee->variable_salary == 0) {
            return redirect()->back()->withInput()->with(
                'salary_error',
                "⚠ {$employee->full_name} has no salary configured (Fixed Salary and Variable Salary are both ₹0). " .
                "Please update the employee's salary before creating documents."
            );
        }
        return null;
    }
}
