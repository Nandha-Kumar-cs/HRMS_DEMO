<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = LeaveRequest::with(['employee', 'leaveType', 'approvedBy'])
                ->select('leave_requests.*');

            return DataTables::of($query)
                ->addColumn('employee_name', fn($r) => $r->employee->full_name . ' <small class="text-muted">(' . $r->employee->employee_code . ')</small>')
                ->addColumn('leave_type_name', fn($r) => $r->leaveType->name)
                ->addColumn('period', fn($r) => $r->start_date->format('d M Y') . ($r->start_date->ne($r->end_date) ? ' – ' . $r->end_date->format('d M Y') : ''))
                ->addColumn('status_badge', fn($r) => $r->status_badge)
                ->addColumn('action', function ($r) {
                    $view = '<a href="' . route('leave-requests.show', $r) . '" class="btn btn-sm btn-outline-primary"><i class="fa fa-eye"></i></a>';
                    return $view;
                })
                ->addColumn('created_at', fn($r) => $r->created_at->toDateTimeString())
                ->rawColumns(['employee_name', 'status_badge', 'action'])
                ->make(true);
        }

        $employees  = Employee::where('status', 'active')->orderBy('full_name')->get();
        $leaveTypes = LeaveType::where('status', 'active')->orderBy('name')->get();
        return view('leave-requests.index', compact('employees', 'leaveTypes'));
    }

    public function create()
    {
        $employees  = Employee::where('status', 'active')->orderBy('full_name')->get();
        $leaveTypes = LeaveType::where('status', 'active')->orderBy('name')->get();
        return view('leave-requests.create', compact('employees', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'reason'        => 'nullable|string|max:500',
        ]);

        // Calculate business days requested (Mon–Sat)
        $start = Carbon::parse($data['start_date']);
        $end   = Carbon::parse($data['end_date']);
        $days  = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (!$d->isSunday()) $days++;
        }
        $data['days_requested'] = $days;
        $data['status']         = 'pending';

        $leaveReq  = LeaveRequest::create($data);
        $leaveReq->load(['employee', 'leaveType']);
        ActivityLog::record('created', 'LeaveRequest',
            "Leave request submitted for {$leaveReq->employee->full_name} ({$leaveReq->employee->employee_code})" .
            " — {$leaveReq->leaveType->name}, {$leaveReq->days_requested} day(s)" .
            " (" . $leaveReq->start_date->format('d M Y') . " – " . $leaveReq->end_date->format('d M Y') . ")"
        );

        return redirect()->route('leave-requests.show', $leaveReq)
            ->with('success', 'Leave request submitted successfully.');
    }

    public function show(LeaveRequest $leaveRequest)
    {
        $leaveRequest->load(['employee.department', 'employee.designation', 'leaveType', 'approvedBy']);

        // Fetch employee's balance for this leave type this year
        $balance = LeaveBalance::where([
            'employee_id'   => $leaveRequest->employee_id,
            'leave_type_id' => $leaveRequest->leave_type_id,
            'year'          => now()->year,
        ])->first();

        return view('leave-requests.show', compact('leaveRequest', 'balance'));
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $leaveRequest->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks'     => $request->remarks,
        ]);

        // Deduct from leave balance
        $balance = LeaveBalance::firstOrCreate(
            [
                'employee_id'   => $leaveRequest->employee_id,
                'leave_type_id' => $leaveRequest->leave_type_id,
                'year'          => now()->year,
            ],
            ['total_days' => $leaveRequest->leaveType->days_allowed, 'used_days' => 0]
        );
        $balance->increment('used_days', $leaveRequest->days_requested);

        // Auto-mark attendance as 'on_leave' for each approved day
        $start = $leaveRequest->start_date->copy();
        $end   = $leaveRequest->end_date->copy();
        for ($d = $start; $d->lte($end); $d->addDay()) {
            if ($d->isSunday()) continue;
            Attendance::updateOrCreate(
                ['employee_id' => $leaveRequest->employee_id, 'date' => $d->toDateString()],
                ['status' => 'on_leave', 'remarks' => 'Approved leave: ' . $leaveRequest->leaveType->name]
            );
        }

        ActivityLog::record('approved', 'LeaveRequest',
            "Approved leave for {$leaveRequest->employee->full_name} ({$leaveRequest->days_requested} day(s) — {$leaveRequest->leaveType->name})"
        );

        return back()->with('success', 'Leave request approved.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $leaveRequest->update([
            'status'      => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks'     => $request->remarks,
        ]);

        ActivityLog::record('rejected', 'LeaveRequest',
            "Rejected leave for {$leaveRequest->employee->full_name} ({$leaveRequest->days_requested} day(s) — {$leaveRequest->leaveType->name})"
        );

        return back()->with('success', 'Leave request rejected.');
    }
}
