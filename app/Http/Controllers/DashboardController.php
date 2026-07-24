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
            $joiningRaw = Employee::selectRaw('EXTRACT(MONTH FROM joining_date) AS m, EXTRACT(YEAR FROM joining_date) AS y, COUNT(*) as cnt')
                ->whereNotNull('joining_date')
                ->where('joining_date', '>=', now()->subYear())
                ->groupByRaw('EXTRACT(YEAR FROM joining_date), EXTRACT(MONTH FROM joining_date)')
                ->orderByRaw('EXTRACT(YEAR FROM joining_date), EXTRACT(MONTH FROM joining_date)')
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
