<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfferLetterRequest;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\OfferLetter;
use App\Models\SalaryComponent;
use App\Traits\HasSalaryGuard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OfferLetterController extends Controller
{
    use HasSalaryGuard;

    public function index(Request $request)
    {
        $offerLetters = OfferLetter::with('employee')->latest()->paginate(15);
        return view('offer-letters.index', compact('offerLetters'));
    }

    public function create(Request $request)
    {
        $employees = Employee::orderBy('full_name')->get();
        $selected  = $request->employee ? Employee::find($request->employee) : null;
        return view('offer-letters.create', compact('employees', 'selected'));
    }

    public function store(StoreOfferLetterRequest $request)
    {
        $employee = Employee::findOrFail($request->employee_id);

        if ($redirect = $this->guardSalary($employee)) {
            return $redirect;
        }

        $letter = OfferLetter::create($request->validated());
        ActivityLog::record('created', 'OfferLetter',
            "Created offer letter for {$employee->full_name} ({$employee->employee_code})" .
            " — Salary: ₹" . number_format($letter->salary, 2) .
            ", Joining: " . ($letter->joining_date ? $letter->joining_date->format('d M Y') : '—')
        );
        return redirect()->route('offer-letters.show', $letter)->with('success', 'Offer letter created successfully.');
    }

    public function show(OfferLetter $offerLetter)
    {
        $offerLetter->load('employee.department', 'employee.designation', 'employee.entity');
        $components = SalaryComponent::all();
        $salary     = $offerLetter->salary;
        [$allowances, $deductions] = $this->calcComponents($components, $salary);
        $grossPay = array_sum($allowances);
        $netPay   = $grossPay - array_sum($deductions);
        return view('offer-letters.show', compact('offerLetter', 'allowances', 'deductions', 'grossPay', 'netPay'));
    }

    public function destroy(OfferLetter $offerLetter)
    {
        $offerLetter->load('employee');
        $name = $offerLetter->employee->full_name;
        $code = $offerLetter->employee->employee_code;
        $offerLetter->delete();
        ActivityLog::record('deleted', 'OfferLetter', "Deleted offer letter for {$name} ({$code})");
        return redirect()->route('offer-letters.index')->with('success', 'Offer letter deleted successfully.');
    }

    public function pdf(OfferLetter $offerLetter)
    {
        $offerLetter->load('employee.department', 'employee.designation', 'employee.entity');
        $components = SalaryComponent::all();
        $salary     = $offerLetter->salary;
        [$allowances, $deductions] = $this->calcComponents($components, $salary);
        $grossPay = array_sum($allowances);
        $netPay   = $grossPay - array_sum($deductions);
        $pdf = Pdf::loadView('pdf.offer-letter', compact('offerLetter', 'allowances', 'deductions', 'grossPay', 'netPay'));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('offer-letter-' . $offerLetter->employee->employee_code . '.pdf');
    }

    private function calcComponents($components, float $salary): array
    {
        $allowances = [];
        $deductions = [];
        foreach ($components as $c) {
            $amount = $c->calculation_type === 'percentage' ? ($c->value / 100) * $salary : (float) $c->value;
            if ($c->type === 'allowance') $allowances[$c->name] = round($amount, 2);
            else $deductions[$c->name] = round($amount, 2);
        }
        return [$allowances, $deductions];
    }
}
