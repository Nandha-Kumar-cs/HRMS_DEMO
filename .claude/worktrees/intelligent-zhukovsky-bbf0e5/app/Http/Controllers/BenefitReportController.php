<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeBenefit;
use App\Models\EmployeeBonus;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BenefitReportController extends Controller
{
    /** Index — links to all reports */
    public function index()
    {
        return view('reports.benefits-index');
    }

    /** Monthly Benefit Report */
    public function monthlyBenefits(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year',  now()->year);

        $rows = EmployeeBenefit::with(['employee.department', 'fundType'])
            ->activeInMonth($month, $year)
            ->get()
            ->groupBy('benefit_fund_type_id');

        $summary = [
            'total_amount'   => 0,
            'employee_count' => 0,
            'by_type'        => [],
        ];

        foreach ($rows as $typeId => $items) {
            $first = $items->first();
            $summary['by_type'][] = [
                'name'     => $first->fundType->name,
                'color'    => $first->fundType->color,
                'count'    => $items->count(),
                'total'    => $items->sum('amount'),
                'items'    => $items,
            ];
            $summary['total_amount']   += $items->sum('amount');
            $summary['employee_count'] += $items->count();
        }

        return view('reports.monthly-benefits', compact('summary', 'month', 'year'));
    }

    /** Bonus & Incentive Report */
    public function bonuses(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year',  now()->year);
        $type  = $request->get('type');

        $query = EmployeeBonus::with(['employee.department', 'addedBy'])
            ->forMonth($month, $year)
            ->approved();

        if ($type) $query->where('type', $type);

        $bonuses = $query->orderBy('type')->get();

        $byType = $bonuses->groupBy('type')->map(function ($items, $t) {
            return [
                'label' => EmployeeBonus::TYPES[$t] ?? $t,
                'count' => $items->count(),
                'total' => $items->sum('amount'),
            ];
        });

        $totalAmount = $bonuses->sum('amount');
        $totalCount  = $bonuses->count();

        return view('reports.bonuses', compact('bonuses', 'byType', 'totalAmount', 'totalCount', 'month', 'year', 'type'));
    }

    /** Employee-wise benefit history */
    public function employeeHistory(Request $request)
    {
        $employee = null;
        $benefits = collect();
        $bonuses  = collect();

        if ($request->filled('employee_id')) {
            $employee = Employee::with('department', 'designation')->findOrFail($request->employee_id);
            $benefits = EmployeeBenefit::with('fundType')
                ->where('employee_id', $employee->id)
                ->orderByDesc('effective_month')
                ->get();
            $bonuses = EmployeeBonus::where('employee_id', $employee->id)
                ->orderByDesc('payroll_year')
                ->orderByDesc('payroll_month')
                ->get();
        }

        $employees = Employee::orderBy('full_name')->get();

        return view('reports.employee-history', compact('employee', 'employees', 'benefits', 'bonuses'));
    }

    /** Payroll impact summary — combined view of benefits + bonuses for a month */
    public function payrollImpact(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year',  now()->year);

        $benefitsTotal = EmployeeBenefit::activeInMonth($month, $year)->sum('amount');
        $bonusesTotal  = EmployeeBonus::forMonth($month, $year)->approved()->sum('amount');

        // Per-employee combined view
        $employees = Employee::where('status', 'active')
            ->with([
                'activeBenefits' => fn($q) => $q->whereDate('effective_month', '<=', Carbon::create($year, $month, 1)->endOfMonth()->toDateString()),
                'activeBenefits.fundType',
                'bonuses' => fn($q) => $q->forMonth($month, $year)->approved(),
            ])
            ->orderBy('full_name')
            ->get();

        $rows = $employees->map(function (Employee $e) {
            $benefitTotal = $e->activeBenefits->sum('amount');
            $bonusTotal   = $e->bonuses->sum('amount');
            return [
                'employee'      => $e,
                'benefit_total' => $benefitTotal,
                'bonus_total'   => $bonusTotal,
                'total_extras'  => $benefitTotal + $bonusTotal,
                'benefits'      => $e->activeBenefits,
                'bonuses'       => $e->bonuses,
            ];
        })->filter(fn($r) => $r['total_extras'] > 0)->values();

        return view('reports.payroll-impact', compact('rows', 'month', 'year', 'benefitsTotal', 'bonusesTotal'));
    }
}
