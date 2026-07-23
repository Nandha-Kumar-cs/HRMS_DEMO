<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\CompOffService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = LeaveRequest::with(['employee', 'leaveType', 'approvedBy'])
                ->select('leave_requests.*');

            return DataTables::of($query)
                ->addColumn('employee_name', fn($r) =>
                    e($r->employee?->full_name ?? '—') .
                    ' <small class="text-muted">(' . e($r->employee?->employee_code ?? '') . ')</small>'
                )
                ->addColumn('leave_type_name', fn($r) => e($r->leaveType?->name ?? '—'))
                ->addColumn('period', fn($r) =>
                    $r->start_date->format('d M Y') .
                    ($r->start_date->ne($r->end_date) ? ' – ' . $r->end_date->format('d M Y') : '')
                )
                ->addColumn('status_badge', fn($r) => $r->status_badge)
                ->addColumn('action', function ($r) {
                    $view = '<a href="' . route('leave-requests.show', $r->id) . '" class="btn btn-sm btn-outline-primary me-1" title="View"><i class="fa fa-eye"></i></a>';
                    $edit = '<a href="' . route('leave-requests.edit', $r->id) . '" class="btn btn-sm btn-outline-warning me-1" title="Edit"><i class="fa fa-pen-to-square"></i></a>';

                    if ($r->status === 'pending') {
                        $delete = '<form action="' . route('leave-requests.destroy', $r->id) . '" method="POST" class="d-inline leave-delete-form">'
                            . csrf_field() . method_field('DELETE')
                            . '<button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fa fa-trash"></i></button></form>';
                    } else {
                        $delete = '<button type="button" class="btn btn-sm btn-outline-danger disabled" title="Cannot delete — status is ' . ucfirst($r->status) . '" disabled><i class="fa fa-trash"></i></button>';
                    }

                    return $view . $edit . $delete;
                })
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
            'document'      => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        // ── Paid leave quota check (1 paid leave per month) ───────────────────
        $conflict = $this->paidLeaveConflict(
            $data['employee_id'],
            $data['leave_type_id'],
            $data['start_date'],
            $data['end_date']
        );
        if ($conflict) {
            return back()->withInput()
                ->withErrors(['leave_type_id' => $conflict]);
        }

        // Calculate business days requested (Mon–Sat)
        $start = Carbon::parse($data['start_date']);
        $end   = Carbon::parse($data['end_date']);
        $days  = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (!$d->isSunday()) $days++;
        }
        $data['days_requested'] = $days;

        // ── Comp Off balance check ────────────────────────────────────────────
        $leaveTypeModel = LeaveType::find($data['leave_type_id']);
        if ($leaveTypeModel?->is_comp_off) {
            $compOffError = app(CompOffService::class)->checkBalance(
                (int) $data['employee_id'],
                $days
            );
            if ($compOffError) {
                return back()->withInput()->withErrors(['leave_type_id' => $compOffError]);
            }
        }

        $data['status'] = 'pending';

        // ── Handle document upload ────────────────────────────────────────────
        if ($request->hasFile('document')) {
            $data['document'] = $request->file('document')
                ->store('leave-documents', 'public');
        }

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

    public function edit(LeaveRequest $leaveRequest)
    {
        $leaveRequest->load(['employee.department', 'employee.designation', 'leaveType', 'approvedBy']);
        $employees  = Employee::where('status', 'active')->orderBy('full_name')->get();
        $leaveTypes = LeaveType::where('status', 'active')->orderBy('name')->get();
        return view('leave-requests.edit', compact('leaveRequest', 'employees', 'leaveTypes'));
    }

    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $data = $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'reason'        => 'nullable|string|max:500',
            'document'      => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'status'        => 'required|in:pending,approved,rejected',
            'remarks'       => 'nullable|string|max:500',
        ]);

        // ── Handle document upload (replace old file if a new one is uploaded) ─
        if ($request->hasFile('document')) {
            if ($leaveRequest->document) {
                Storage::disk('public')->delete($leaveRequest->document);
            }
            $data['document'] = $request->file('document')->store('leave-documents', 'public');
        } elseif ($request->boolean('remove_document')) {
            // Explicit remove checkbox
            if ($leaveRequest->document) {
                Storage::disk('public')->delete($leaveRequest->document);
            }
            $data['document'] = null;
        } else {
            // No new file and no remove — keep existing
            unset($data['document']);
        }

        $oldStatus     = $leaveRequest->status;
        $oldDays       = $leaveRequest->days_requested;
        $oldEmployeeId = $leaveRequest->employee_id;
        $oldTypeId     = $leaveRequest->leave_type_id;
        $oldStart      = $leaveRequest->start_date->copy();
        $oldEnd        = $leaveRequest->end_date->copy();
        $oldTypeName   = $leaveRequest->leaveType->name;

        // ── Recalculate days_requested ────────────────────────────────────────
        $start = Carbon::parse($data['start_date']);
        $end   = Carbon::parse($data['end_date']);
        $days  = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (!$d->isSunday()) $days++;
        }
        $data['days_requested'] = $days;

        // ── Reverse old approval side-effects if it was approved ──────────────
        if ($oldStatus === 'approved') {
            // 1. Reverse leave balance deduction
            $oldBalance = LeaveBalance::where([
                'employee_id'   => $oldEmployeeId,
                'leave_type_id' => $oldTypeId,
                'year'          => now()->year,
            ])->first();
            if ($oldBalance) {
                $oldBalance->decrement('used_days', $oldDays);
            }

            // 2. Remove auto-marked attendance records (do not mark absent)
            for ($d = $oldStart->copy(); $d->lte($oldEnd); $d->addDay()) {
                if ($d->isSunday()) continue;
                Attendance::where('employee_id', $oldEmployeeId)
                    ->where('date', $d->toDateString())
                    ->where('remarks', 'Approved leave: ' . $oldTypeName)
                    ->delete();
            }
        }

        // ── Paid leave quota check when approving via edit ────────────────────
        if ($data['status'] === 'approved') {
            $conflict = $this->paidLeaveConflict(
                $data['employee_id'],
                $data['leave_type_id'],
                $data['start_date'],
                $data['end_date'],
                $leaveRequest->id   // exclude self
            );
            if ($conflict) {
                return back()->withInput()->withErrors(['leave_type_id' => $conflict]);
            }
        }

        // ── Handle approved_by / approved_at based on new status ─────────────
        if ($data['status'] === 'pending') {
            $data['approved_by'] = null;
            $data['approved_at'] = null;
            $data['remarks']     = null;
        } elseif (in_array($data['status'], ['approved', 'rejected'])) {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        }

        $leaveRequest->update($data);
        $leaveRequest->load(['employee', 'leaveType']);

        // ── Apply new approval side-effects if newly approved ─────────────────
        if ($data['status'] === 'approved') {
            // 1. Deduct new leave balance
            $newBalance = LeaveBalance::firstOrCreate(
                [
                    'employee_id'   => $leaveRequest->employee_id,
                    'leave_type_id' => $leaveRequest->leave_type_id,
                    'year'          => now()->year,
                ],
                ['total_days' => $leaveRequest->leaveType->days_allowed, 'used_days' => 0]
            );
            $newBalance->increment('used_days', $leaveRequest->days_requested);

            // 2. Auto-mark attendance as 'on_leave'
            $newStart = $leaveRequest->start_date->copy();
            $newEnd   = $leaveRequest->end_date->copy();
            for ($d = $newStart; $d->lte($newEnd); $d->addDay()) {
                if ($d->isSunday()) continue;
                Attendance::updateOrCreate(
                    ['employee_id' => $leaveRequest->employee_id, 'date' => $d->toDateString()],
                    ['status' => 'on_leave', 'remarks' => 'Approved leave: ' . $leaveRequest->leaveType->name]
                );
            }
        }

        ActivityLog::record('updated', 'LeaveRequest',
            "Edited leave request #{$leaveRequest->id} for {$leaveRequest->employee->full_name}" .
            " — status changed from {$oldStatus} to {$data['status']}"
        );

        return redirect()->route('leave-requests.show', $leaveRequest)
            ->with('success', 'Leave request updated successfully.');
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        // ── Paid leave quota check (1 paid leave per month) ───────────────────
        $conflict = $this->paidLeaveConflict(
            $leaveRequest->employee_id,
            $leaveRequest->leave_type_id,
            $leaveRequest->start_date->toDateString(),
            $leaveRequest->end_date->toDateString(),
            $leaveRequest->id   // exclude self
        );
        if ($conflict) {
            return back()->with('error', $conflict);
        }

        // ── Comp Off balance check ────────────────────────────────────────────
        $leaveRequest->loadMissing('leaveType');
        if ($leaveRequest->leaveType?->is_comp_off) {
            $compOffError = app(CompOffService::class)->checkBalance(
                $leaveRequest->employee_id,
                $leaveRequest->days_requested,
                $leaveRequest->id
            );
            if ($compOffError) {
                return back()->with('error', $compOffError);
            }
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

    public function destroy(LeaveRequest $leaveRequest)
    {
        $leaveRequest->load(['employee', 'leaveType']);

        // ── Reverse side-effects if the request was approved ─────────────────
        if ($leaveRequest->status === 'approved') {
            // 1. Reverse leave balance deduction
            $balance = LeaveBalance::where([
                'employee_id'   => $leaveRequest->employee_id,
                'leave_type_id' => $leaveRequest->leave_type_id,
                'year'          => now()->year,
            ])->first();
            if ($balance) {
                $balance->decrement('used_days', $leaveRequest->days_requested);
            }

            // 2. Remove auto-marked attendance records (do not mark absent)
            $start    = $leaveRequest->start_date->copy();
            $end      = $leaveRequest->end_date->copy();
            $typeName = $leaveRequest->leaveType->name;
            for ($d = $start; $d->lte($end); $d->addDay()) {
                if ($d->isSunday()) continue;
                Attendance::where('employee_id', $leaveRequest->employee_id)
                    ->where('date', $d->toDateString())
                    ->where('remarks', 'Approved leave: ' . $typeName)
                    ->delete();
            }
        }

        $empName  = $leaveRequest->employee->full_name ?? '—';
        $empCode  = $leaveRequest->employee->employee_code ?? '';
        $typeName = $leaveRequest->leaveType->name ?? '—';
        $days     = $leaveRequest->days_requested;

        // Delete attached document file if exists
        if ($leaveRequest->document) {
            Storage::disk('public')->delete($leaveRequest->document);
        }

        $leaveRequest->delete();

        ActivityLog::record('deleted', 'LeaveRequest',
            "Deleted leave request for {$empName} ({$empCode}) — {$typeName}, {$days} day(s)"
        );

        return redirect()->route('leave-requests.index')
            ->with('success', 'Leave request deleted successfully.');
    }

    /**
     * Leave History — complete leave records for all employees with status tabs & filters.
     */
    public function history(Request $request)
    {
        $employees  = Employee::where('status', 'active')->orderBy('full_name')->get();
        $leaveTypes = LeaveType::orderBy('name')->get();

        // ── Filters ───────────────────────────────────────────────────────────
        $filterEmp    = $request->filled('employee_id')   ? $request->employee_id   : null;
        $filterType   = $request->filled('leave_type_id') ? $request->leave_type_id : null;
        $filterStatus = $request->get('status', 'all');
        $filterYear   = (int) $request->get('year', now()->year);
        $filterMonth  = $request->filled('month') ? (int) $request->month : null;

        // ── Helper: build the base query with all active filters except status ─
        $buildBase = function () use ($filterEmp, $filterType, $filterYear, $filterMonth) {
            $q = LeaveRequest::with(['employee', 'leaveType', 'approvedBy'])
                ->whereYear('start_date', $filterYear);

            if ($filterEmp)   $q->where('employee_id',   $filterEmp);
            if ($filterType)  $q->where('leave_type_id', $filterType);
            if ($filterMonth) $q->whereMonth('start_date', $filterMonth);

            return $q;
        };

        // ── Tab counts (ignore status filter so all tabs always show their real totals) ──
        $allForYear = $buildBase()->get();
        $counts = [
            'all'      => $allForYear->count(),
            'approved' => $allForYear->where('status', 'approved')->count(),
            'pending'  => $allForYear->where('status', 'pending')->count(),
            'rejected' => $allForYear->where('status', 'rejected')->count(),
        ];
        $totalApprovedDays = $allForYear->where('status', 'approved')->sum('days_requested');

        // Top-5 employees by approved leave days
        $empSummary = $allForYear
            ->where('status', 'approved')
            ->groupBy('employee_id')
            ->map(fn ($rows) => [
                'name'  => $rows->first()->employee?->full_name ?? '—',
                'days'  => $rows->sum('days_requested'),
                'count' => $rows->count(),
            ])
            ->sortByDesc('days')
            ->take(5)
            ->values();

        // ── Paginated list (adds status filter on top of base) ────────────────
        $listQuery = $buildBase()->orderBy('start_date', 'desc');
        if ($filterStatus !== 'all') {
            $listQuery->where('status', $filterStatus);
        }
        $leaves = $listQuery->paginate(25)->withQueryString();

        return view('leave-requests.history', compact(
            'leaves', 'employees', 'leaveTypes',
            'filterEmp', 'filterType', 'filterStatus', 'filterYear', 'filterMonth',
            'counts', 'totalApprovedDays', 'empSummary'
        ));
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

    // ── Paid leave quota helper ───────────────────────────────────────────────

    /**
     * Check whether the employee already has an approved paid leave in any
     * month covered by the requested date range (max 1 paid leave per month).
     *
     * @param  int|string  $employeeId
     * @param  int|string  $leaveTypeId
     * @param  string      $startDate   Y-m-d
     * @param  string      $endDate     Y-m-d
     * @param  int|null    $excludeId   Leave request ID to exclude (for edit/approve)
     * @return string|null  Error message, or null if OK
     */
    private function paidLeaveConflict($employeeId, $leaveTypeId, $startDate, $endDate, $excludeId = null): ?string
    {
        // Only applies to regular paid leave types (not comp off — it has its own quota)
        $leaveType = LeaveType::find($leaveTypeId);
        if (!$leaveType || !$leaveType->is_paid || $leaveType->is_comp_off) {
            return null;
        }

        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        // Walk through each month covered by the request
        $cur = $start->copy()->startOfMonth();
        while ($cur->lte($end)) {
            $mStart = $cur->copy()->startOfMonth()->toDateString();
            $mEnd   = $cur->copy()->endOfMonth()->toDateString();

            $exists = LeaveRequest::where('employee_id', $employeeId)
                ->where('status', 'approved')
                ->whereHas('leaveType', fn ($q) => $q->where('is_paid', true))
                ->whereDate('end_date',   '>=', $mStart)
                ->whereDate('start_date', '<=', $mEnd)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists();

            if ($exists) {
                return 'This employee already has an approved paid leave in '
                    . $cur->format('F Y')
                    . '. Only 1 paid leave is allowed per month.';
            }

            $cur->addMonth();
        }

        return null;
    }
}
