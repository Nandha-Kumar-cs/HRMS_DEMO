@extends('layouts.app')
@section('title','Attendance Monthly Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('attendance.index') }}" class="text-decoration-none">Attendance</a></li>
<li class="breadcrumb-item active">Monthly Report</li>
@endsection

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
            <h5 class="mb-0 fw-semibold">Monthly Attendance Report</h5>
        </div>
        <form method="GET" class="d-flex gap-2 align-items-end">
            <select name="month" class="form-select form-select-sm">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                @endfor
            </select>
            <select name="year" class="form-select form-select-sm">
                @for($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Go</button>
        </form>
    </div>

    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <h6 class="text-muted mb-0">{{ $startDate->format('F Y') }} — {{ $days }} days &nbsp;|&nbsp; <strong>{{ $totalWorkingDays }}</strong> working days</h6>
            <a href="{{ route('holidays.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-calendar-day me-1"></i>Manage Holidays
            </a>
        </div>

        {{-- Status legend --}}
        <div class="d-flex gap-3 mb-2 flex-wrap align-items-center">
            @foreach(\App\Models\Attendance::$statusColors as $status => $color)
                <span class="badge bg-{{ $color }}">{{ \App\Models\Attendance::$statuses[$status] }}</span>
            @endforeach
            <span class="badge bg-light text-dark border">—</span> <small class="text-muted">No record</small>
            <span class="ms-2" style="display:inline-block;width:16px;height:16px;background:#e9ecef;border:1px solid #dee2e6;vertical-align:middle;border-radius:2px"></span>
            <small class="text-muted">Weekend / Holiday</small>
            <span class="ms-2 badge bg-info" style="font-size:.7rem">L +15m</span>
            <small class="text-muted">Late with daily minutes</small>
        </div>

        {{-- Non-working day note --}}
        @if(!empty($nonWorkingDays))
        <div class="alert alert-light border py-2 mb-3 small">
            <i class="fa fa-moon me-1 text-secondary"></i>
            <strong>Holidays & Weekly Offs this month:</strong>
            <span class="ms-1">
                @foreach($nonWorkingDays as $day => $label)
                    <span class="badge bg-secondary me-1">{{ $day }} — {{ $label }}</span>
                @endforeach
            </span>
        </div>
        @endif

        {{-- Late Permission Info Box --}}
        <div class="alert alert-info border py-2 mb-3 small">
            <i class="fa fa-clock me-1"></i>
            <strong>Office hours: 09:00 – 18:15.</strong> &nbsp;Monthly Late Permission: Each employee has a grace of <strong>1h 30m (90 min)</strong> per month.
            If total late time exceeds 90 min, the <em>entire</em> late amount is deducted at <strong>2× rate</strong>.
            <span class="text-muted ms-2">Example: 1h 40m late → 1h 40m + 1h 40m = 3h 20m deducted. Half-day auto-triggers if check-in is after 11:00 AM.</span>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle" style="font-size:.8rem">
                <thead class="table-dark">
                    <tr>
                        <th style="min-width:160px">Employee</th>
                        @for($d = 1; $d <= $days; $d++)
                            @php
                                $dayDate  = \Carbon\Carbon::create($year, $month, $d);
                                $isNonWrk = isset($nonWorkingDays[$d]);
                                $nwLabel  = $nonWorkingDays[$d] ?? null;
                            @endphp
                            <th class="text-center {{ $isNonWrk ? 'table-secondary' : '' }}"
                                style="min-width:34px"
                                @if($nwLabel) title="{{ $nwLabel }}" @endif>
                                {{ $d }}<br>
                                <small style="font-size:.6rem">{{ $dayDate->format('D') }}</small>
                            </th>
                        @endfor
                        {{-- Summary columns --}}
                        <th class="text-center" style="min-width:32px" title="Present days">P</th>
                        <th class="text-center" style="min-width:32px" title="Absent days">A</th>
                        <th class="text-center" style="min-width:32px" title="Half days">H</th>
                        <th class="text-center" style="min-width:32px" title="Leave days">L</th>
                        <th class="text-center bg-warning bg-opacity-10" style="min-width:72px">Late Used</th>
                        <th class="text-center bg-info bg-opacity-10"   style="min-width:72px">Remaining</th>
                        <th class="text-center bg-danger bg-opacity-10" style="min-width:72px">Exceeded</th>
                        <th class="text-center bg-danger bg-opacity-25" style="min-width:80px">Deducted</th>
                        <th class="text-center bg-success bg-opacity-10" style="min-width:56px">OT Hrs</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $emp)
                    @php
                        $empRecords  = $records[$emp->id] ?? collect();
                        $presentCount = 0; $absentCount = 0; $halfCount = 0; $leaveCount = 0;
                        $stat        = $empStats[$emp->id] ?? [];
                        $empLateMap  = $lateByDay[$emp->id] ?? [];

                        $fmtMins = function(int $m): string {
                            if ($m <= 0) return '0m';
                            return ($m >= 60 ? intdiv($m, 60) . 'h ' : '') . ($m % 60) . 'm';
                        };
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $emp->full_name }}</strong><br>
                            <small class="text-muted">{{ $emp->employee_code }}</small>
                        </td>

                        {{-- Daily grid cells --}}
                        @for($d = 1; $d <= $days; $d++)
                            @php
                                $isNonWrk = isset($nonWorkingDays[$d]);
                                $rec      = $empRecords[$d] ?? null;
                                $status   = $rec?->status ?? null;

                                // No checkout → treat as absent in display
                                if ($status && in_array($status, ['present','late']) && empty($rec?->check_out)) {
                                    $status = 'absent';
                                }

                                $dailyLateMins = $empLateMap[$d] ?? 0;

                                if (!$isNonWrk) {
                                    $color = $status ? (\App\Models\Attendance::$statusColors[$status] ?? 'light') : 'light';
                                    $abbr  = match($status) {
                                        'present'  => 'P',
                                        'absent'   => 'A',
                                        'half_day' => 'H',
                                        'late'     => 'L',
                                        'on_leave' => 'OL',
                                        default    => '—',
                                    };
                                    if ($status === 'present' || $status === 'late') $presentCount++;
                                    elseif ($status === 'absent')   $absentCount++;
                                    elseif ($status === 'half_day') $halfCount++;
                                    elseif ($status === 'on_leave') $leaveCount++;
                                }
                            @endphp
                            <td class="text-center {{ $isNonWrk ? 'table-secondary text-muted' : '' }}">
                                @if($isNonWrk)
                                    <small class="text-muted" title="{{ $nonWorkingDays[$d] }}">H</small>
                                @else
                                    @php
                                        $titleAttr = $status === 'late' && $dailyLateMins > 0
                                            ? 'Late by ' . $fmtMins($dailyLateMins)
                                            : '';
                                    @endphp
                                    <span class="badge bg-{{ $color }} text-{{ $color === 'warning' ? 'dark' : 'white' }}"
                                          style="font-size:.7rem"
                                          @if($titleAttr) title="{{ $titleAttr }}" @endif>{{ $abbr }}</span>
                                    @if($status === 'late' && $dailyLateMins > 0)
                                        <br><small style="font-size:.58rem;color:#0dcaf0;line-height:1">+{{ $fmtMins($dailyLateMins) }}</small>
                                    @endif
                                @endif
                            </td>
                        @endfor

                        {{-- Attendance count columns --}}
                        <td class="text-center fw-bold text-success">{{ $presentCount }}</td>
                        <td class="text-center fw-bold text-danger">{{ $stat['absent_days'] ?? $absentCount }}</td>
                        <td class="text-center fw-bold text-warning">{{ $stat['half_days'] ?? $halfCount }}</td>
                        <td class="text-center fw-bold text-secondary">{{ $leaveCount }}</td>

                        {{-- Late Used --}}
                        @php $lateMins = (int)($stat['late_mins'] ?? 0); @endphp
                        <td class="text-center {{ $lateMins > 0 ? 'text-warning fw-semibold' : 'text-muted' }}"
                            style="font-size:.78rem"
                            title="Total late time this month">
                            {{ $lateMins > 0 ? $fmtMins($lateMins) : '—' }}
                        </td>

                        {{-- Remaining Permission --}}
                        @php $remainPerm = (int)($stat['remaining_perm'] ?? 90); @endphp
                        <td class="text-center {{ $remainPerm < 90 ? ($remainPerm === 0 ? 'text-danger fw-semibold' : 'text-info fw-semibold') : 'text-muted' }}"
                            style="font-size:.78rem"
                            title="Remaining late grace (90 min/month)">
                            {{ $fmtMins($remainPerm) }}
                        </td>

                        {{-- Exceeded --}}
                        @php $exceededMins = (int)($stat['exceeded_mins'] ?? 0); @endphp
                        <td class="text-center {{ $exceededMins > 0 ? 'text-danger fw-semibold' : 'text-muted' }}"
                            style="font-size:.78rem"
                            title="Late time beyond the 90-min grace">
                            {{ $exceededMins > 0 ? $fmtMins($exceededMins) : '—' }}
                        </td>

                        {{-- Deducted (2× penalty on full late amount) --}}
                        @php $deductMins = (int)($stat['deduct_mins'] ?? 0); @endphp
                        <td class="text-center {{ $deductMins > 0 ? 'text-danger fw-bold' : 'text-muted' }}"
                            style="font-size:.78rem"
                            title="Deducted at 2× of total late time">
                            @if($deductMins > 0)
                                {{ $fmtMins($deductMins) }}
                                <br><small style="font-size:.62rem">(2× penalty)</small>
                            @else
                                —
                            @endif
                        </td>

                        {{-- OT Hours --}}
                        @php $otHrs = (float)($stat['ot_hours'] ?? 0); @endphp
                        <td class="text-center {{ $otHrs > 0 ? 'text-success fw-semibold' : 'text-muted' }}"
                            style="font-size:.78rem">
                            {{ $otHrs > 0 ? $otHrs . 'h' : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="small text-muted mt-2">
            <strong>Legend:</strong>
            P = Present &nbsp;|&nbsp; A = Absent &nbsp;|&nbsp; H = Half Day &nbsp;|&nbsp; L = Late &nbsp;|&nbsp; OL = On Leave
            &nbsp;|&nbsp; <span class="badge bg-secondary" style="font-size:.7rem">H</span> = Holiday / Weekly Off
            &nbsp;|&nbsp; <strong>Late Used</strong> = Total monthly late time
            &nbsp;|&nbsp; <strong>Remaining</strong> = Grace left (resets each month)
            &nbsp;|&nbsp; <strong>Exceeded</strong> = Minutes beyond 90-min grace
            &nbsp;|&nbsp; <strong>Deducted</strong> = Penalty charged (2× total late when grace exceeded)
        </div>
    </div>
</div>
@endsection
