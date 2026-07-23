<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncrementLetterRequest;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\IncrementLetter;
use App\Traits\HasSalaryGuard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class IncrementLetterController extends Controller
{
    use HasSalaryGuard;

    public function index()
    {
        $letters = IncrementLetter::with('employee')->latest()->paginate(15);
        return view('increment-letters.index', compact('letters'));
    }

    public function create(Request $request)
    {
        $employees = Employee::orderBy('full_name')->get();
        $selected  = $request->employee ? Employee::find($request->employee) : null;
        return view('increment-letters.create', compact('employees', 'selected'));
    }

    public function store(StoreIncrementLetterRequest $request)
    {
        $employee = Employee::findOrFail($request->employee_id);

        if ($redirect = $this->guardSalary($employee)) {
            return $redirect;
        }

        $data = $request->validated();
        if (empty($data['increment_percentage']) && $data['old_salary'] > 0) {
            $data['increment_percentage'] = round((($data['new_salary'] - $data['old_salary']) / $data['old_salary']) * 100, 2);
        }
        $letter = IncrementLetter::create($data);
        ActivityLog::record('created', 'IncrementLetter',
            "Created increment letter for {$employee->full_name} ({$employee->employee_code})" .
            ": ₹" . number_format($letter->old_salary, 2) . " → ₹" . number_format($letter->new_salary, 2) .
            " ({$letter->increment_percentage}%)"
        );
        return redirect()->route('increment-letters.show', $letter)->with('success', 'Increment letter created.');
    }

    public function show(IncrementLetter $incrementLetter)
    {
        $incrementLetter->load('employee.department', 'employee.designation', 'employee.entity');
        return view('increment-letters.show', compact('incrementLetter'));
    }

    public function destroy(IncrementLetter $incrementLetter)
    {
        $incrementLetter->load('employee');
        $name = $incrementLetter->employee->full_name;
        $code = $incrementLetter->employee->employee_code;
        $incrementLetter->delete();
        ActivityLog::record('deleted', 'IncrementLetter', "Deleted increment letter for {$name} ({$code})");
        return redirect()->route('increment-letters.index')->with('success', 'Increment letter deleted.');
    }

    public function pdf(IncrementLetter $incrementLetter)
    {
        $incrementLetter->load('employee.department', 'employee.designation', 'employee.entity');
        $pdf = Pdf::loadView('pdf.increment-letter', compact('incrementLetter'));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('increment-' . $incrementLetter->employee->employee_code . '.pdf');
    }
}
