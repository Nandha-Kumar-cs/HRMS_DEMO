<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\LoanRepayment;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = EmployeeLoan::with('employee')
                ->when($request->employee_id, fn($q) => $q->where('employee_id', $request->employee_id))
                ->when($request->status,      fn($q) => $q->where('status',      $request->status))
                ->when($request->type,        fn($q) => $q->where('type',        $request->type));

            return DataTables::of($query)
                ->addColumn('employee_name', fn($r) =>
                    $r->employee->full_name . ' (' . $r->employee->employee_code . ')')
                ->addColumn('type_badge', fn($r) =>
                    '<span class="badge bg-' . ($r->type === 'loan' ? 'primary' : 'info') . '">'
                    . ucfirst($r->type) . '</span>')
                // Interest columns
                ->addColumn('total_due',   fn($r) => number_format($r->total_due, 2))
                ->addColumn('interest',    fn($r) => number_format($r->total_interest, 2))
                ->addColumn('returned',    fn($r) => number_format($r->returned_amount, 2))
                ->addColumn('pending',     fn($r) => number_format($r->pending_amount, 2))
                ->addColumn('status_badge', fn($r) =>
                    '<span class="badge bg-' . ($r->status === 'active' ? 'success' : 'secondary') . '">'
                    . ucfirst($r->status) . '</span>')
                ->addColumn('action', function ($r) {
                    return '
                        <a href="' . route('loans.show', $r) . '" class="btn btn-xs btn-outline-info" title="Repayment History"><i class="fa fa-history"></i></a>
                        <a href="' . route('loans.edit', $r) . '" class="btn btn-xs btn-outline-primary" title="Edit"><i class="fa fa-edit"></i></a>
                        <button class="btn btn-xs btn-outline-danger btn-delete" data-url="' . route('loans.destroy', $r) . '" title="Delete"><i class="fa fa-trash"></i></button>';
                })
                ->rawColumns(['type_badge', 'status_badge', 'action'])
                ->make(true);
        }

        $employees = Employee::orderBy('full_name')->get();
        return view('loans.index', compact('employees'));
    }

    public function create(Request $request)
    {
        $employees = Employee::where('status', 'active')->orderBy('full_name')->get();
        $selected  = $request->employee ? Employee::find($request->employee) : null;
        return view('loans.create', compact('employees', 'selected'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'       => 'required|exists:employees,id',
            'type'              => 'required|in:loan,advance',
            'amount'            => 'required|numeric|min:1',
            'interest_rate'     => 'nullable|numeric|min:0|max:100',
            'date_given'        => 'required|date',
            'monthly_deduction' => 'required|numeric|min:0.01',
            'total_months'      => 'required|integer|min:1',
            'remarks'           => 'nullable|string|max:1000',
        ]);

        $loan     = EmployeeLoan::create($data);
        $employee = Employee::find($data['employee_id']);
        ActivityLog::record('created', 'Loan',
            "Added " . ucfirst($data['type']) . " of ₹" . number_format($data['amount'], 2) .
            " for {$employee->full_name} ({$employee->employee_code})" .
            " — EMI: ₹" . number_format($data['monthly_deduction'], 2) . " × {$data['total_months']} months"
        );
        return redirect()->route('loans.index')
            ->with('success', ucfirst($data['type']) . ' recorded successfully.');
    }

    public function show(EmployeeLoan $loan)
    {
        $loan->load('employee', 'repayments');
        return view('loans.show', compact('loan'));
    }

    public function edit(EmployeeLoan $loan)
    {
        $employees = Employee::orderBy('full_name')->get();
        return view('loans.edit', compact('loan', 'employees'));
    }

    public function update(Request $request, EmployeeLoan $loan)
    {
        $data = $request->validate([
            'employee_id'       => 'required|exists:employees,id',
            'type'              => 'required|in:loan,advance',
            'amount'            => 'required|numeric|min:1',
            'interest_rate'     => 'nullable|numeric|min:0|max:100',
            'date_given'        => 'required|date',
            'monthly_deduction' => 'required|numeric|min:0.01',
            'total_months'      => 'required|integer|min:1',
            'status'            => 'required|in:active,closed',
            'remarks'           => 'nullable|string|max:1000',
        ]);

        $loan->update($data);
        $loan->load('employee');
        ActivityLog::record('updated', 'Loan',
            "Updated " . ucfirst($loan->type) . " for {$loan->employee->full_name} ({$loan->employee->employee_code})" .
            " — Amount: ₹" . number_format($loan->amount, 2) . ", Status: " . ucfirst($loan->status)
        );
        return redirect()->route('loans.index')->with('success', 'Record updated successfully.');
    }

    public function destroy(EmployeeLoan $loan)
    {
        $loan->load('employee');
        $desc = ucfirst($loan->type) . " of ₹" . number_format($loan->amount, 2) .
                " for {$loan->employee->full_name} ({$loan->employee->employee_code})";
        $loan->repayments()->delete();
        $loan->delete();
        ActivityLog::record('deleted', 'Loan', "Deleted {$desc}");
        return response()->json(['success' => true, 'message' => 'Record deleted.']);
    }

    // ── Manual Repayment ──────────────────────────────────────────────────────

    public function storeRepayment(Request $request, EmployeeLoan $loan)
    {
        // Refresh the loan so pending_amount (which hits the DB) is accurate
        $loan->refresh();
        $pending = $loan->pending_amount;

        $data = $request->validate([
            'amount_paid'  => [
                'required',
                'numeric',
                'min:0.01',
                function ($attr, $value, $fail) use ($pending) {
                    if ((float) $value > $pending + 0.01) {   // +0.01 for rounding tolerance
                        $fail('Amount paid (₹' . number_format($value, 2) . ') exceeds pending balance (₹' . number_format($pending, 2) . ').');
                    }
                },
            ],
            'payment_date' => 'required|date',
            'note'         => 'nullable|string|max:500',
        ]);

        $data['employee_loan_id'] = $loan->id;
        LoanRepayment::create($data);

        // Increment paid months counter
        $loan->increment('paid_months');

        // Refresh and check if fully paid
        $loan->refresh();
        $isFullyPaid = $loan->pending_amount <= 0.01   // ≤ 1 paise rounding tolerance
                    || $loan->paid_months >= $loan->total_months;

        if ($isFullyPaid && $loan->status !== 'closed') {
            $loan->update(['status' => 'closed']);
        }

        // Reload for accurate summary in flash message
        $loan->refresh();
        $msg = 'Repayment of ₹' . number_format((float) $data['amount_paid'], 2)
             . ' recorded successfully.';
        if ($loan->status === 'closed') {
            $msg .= ' Loan fully settled ✓';
        } else {
            $msg .= ' Pending balance: ₹' . number_format($loan->pending_amount, 2) . '.';
        }

        $loan->load('employee');
        ActivityLog::record('created', 'Loan',
            "Repayment of ₹" . number_format((float) $data['amount_paid'], 2) .
            " recorded for {$loan->employee->full_name} ({$loan->employee->employee_code})" .
            ($loan->status === 'closed' ? ' — Loan fully settled ✓' :
                ' — Pending: ₹' . number_format($loan->pending_amount, 2))
        );
        return redirect()->route('loans.show', $loan)->with('success', $msg);
    }
}
