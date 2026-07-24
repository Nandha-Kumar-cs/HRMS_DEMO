<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\OfferLetter;
use App\Models\SalarySlip;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        // Cache dashboard aggregates for 5 minutes — these counts don't need to
        // be live; caching avoids 6 DB queries on every dashboard page load.
        $stats = Cache::remember('dashboard_stats', now()->addMinutes(5), function () {
            // Department-wise employee count
            $deptData = Department::withCount('employees')->get();

            // Monthly joining — last 12 months
            $joiningRaw = Employee::selectRaw('MONTH(joining_date) as m, YEAR(joining_date) as y, COUNT(*) as cnt')
                ->whereNotNull('joining_date')
                ->where('joining_date', '>=', now()->subYear())
                ->groupByRaw('YEAR(joining_date), MONTH(joining_date)')
                ->orderByRaw('YEAR(joining_date), MONTH(joining_date)')
                ->get();

            return [
                'totalEmployees'   => Employee::count(),
                'totalDepartments' => Department::count(),
                'totalSalarySlips' => SalarySlip::count(),
                'totalOfferLetters'=> OfferLetter::count(),
                'deptLabels'       => $deptData->pluck('name'),
                'deptCounts'       => $deptData->pluck('employees_count'),
                'monthLabels'      => $joiningRaw->map(fn($r) => date('M Y', mktime(0, 0, 0, $r->m, 1, $r->y))),
                'joiningCounts'    => $joiningRaw->pluck('cnt'),
            ];
        });

        return view('dashboard.index', $stats);
    }
}
