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
                @if($status === 'comp_off')
                    <span class="badge" style="background:#6f42c1">Comp Off</span>
                @else
                    <span class="badge bg-{{ $color }}">{{ \App\Models\Attendance::$statuses[$status] }}</span>
                @endif
            @endforeach
            <span class="badge bg-danger">A</span> <small class="text-muted">Absent / No record</small>
            <span class="ms-2" style="display:inline-block;width:16px;height:16px;background:#e9ecef;border:1px solid #dee2e6;vertical-align:middle;border-radius:2px"></span>
            <small class="text-muted">Weekend / Holiday</small>
            <span class="ms-2" style="display:inline-block;width:16px;height:16px;background:#fef9c3;border:1px solid #fde68a;vertical-align:middle;border-radius:2px;text-align:center;line-height:16px">
                <i class="fa fa-briefcase" style="font-size:.55rem;color:#b45309"></i>
            </span>
            <small class="text-muted">Working Holiday</small>
            <span class="ms-2" style="display:inline-block;width:16px;height:16px;background:#6f42c1;vertical-align:middle;border-radius:2px"></span>
            <small class="text-muted">Comp Off</small>
            <span class="ms-2 badge" style="background:#c8a96e;font-size:.7rem">OD</span>
            <small class="text-muted">On Duty (counted as Present)</small>
            <span class="ms-2" style="display:inline-block;width:16px;height:16px;background:#f3eeff;border:1px solid #d8b4fe;vertical-align:middle;border-radius:2px;text-align:center;line-height:16px">
                <i class="fa fa-calendar-plus" style="font-size:.55rem;color:#6f42c1"></i>
            </span>
            <small class="text-muted">Comp Off Day</small>
            <span class="ms-2 badge bg-info" style="font-size:.7rem">L</span>
            <small class="text-muted">Late (hover for minutes)</small>
            <span class="ms-2 badge" style="background:#e67e22;font-size:.7rem">A</span>
            <small class="text-muted">Absent — checked in but no checkout</small>
        </div>

        {{-- Non-working day note --}}
        @if(!empty($nonWorkingDays))
        <div class="alert alert-light border py-2 mb-3 small">
            <i class="fa fa-moon me-1 text-secondary"></i>
            <strong>Holidays & Weekly Offs this month:</strong>
            <span class="ms-1">
                @foreach($nonWorkingDays as $day => $label)
                    @php $isWknd = in_array($label, ['Sunday', '1st Saturday', '3rd Saturday']); @endphp
                    @if($isWknd)
                        <span class="badge bg-secondary me-1">{{ $day }} — {{ $label }}</span>
                    @else
                        <span class="badge me-1" style="background:#c0392b;">{{ $day }} — {{ $label }}</span>
                    @endif
                @endforeach
            </span>
        </div>
        @endif

        {{-- Late Permission Info Box --}}
        @php
            $officeStart   = \App\Helpers\AppSettings::getOfficeStartTime();
            $dailyGrace    = \App\Helpers\AppSettings::getDailyGraceMinutes();
            $monthlyGrace  = \App\Helpers\AppSettings::getMonthlyGraceMinutes();
            $lateThreshFmt = \Carbon\Carbon::createFromFormat('H:i', $officeStart)->addMinutes($dailyGrace)->format('h:i A');
            $halfDayFmt    = \Carbon\Carbon::createFromFormat('H:i', $officeStart)->addMinutes(120)->format('h:i A');
            $mGraceH       = intdiv($monthlyGrace, 60);
            $mGraceM       = $monthlyGrace % 60;
            $mGraceFmt     = ($mGraceH > 0 ? $mGraceH . 'h ' : '') . $mGraceM . 'm';
        @endphp
        <div class="alert alert-info border py-2 mb-3 small">
            <i class="fa fa-clock me-1"></i>
            <strong>Office start: {{ \Carbon\Carbon::createFromFormat('H:i', $officeStart)->format('h:i A') }}</strong>.
            &nbsp;Daily grace: <strong>{{ $dailyGrace }} min</strong> → late if check-in after <strong>{{ $lateThreshFmt }}</strong>.
            &nbsp;Monthly late permission: <strong>{{ $mGraceFmt }} ({{ $monthlyGrace }} min)</strong>.
            If total late exceeds <strong>{{ $mGraceFmt }}</strong>, the <em>entire</em> late amount is deducted at <strong>2× rate</strong>.
            <span class="text-muted ms-2">Half-day auto-triggers if check-in is after <strong>{{ $halfDayFmt }}</strong>.</span>
            <a href="{{ route('settings.grace.show') }}" class="ms-2 text-decoration-none small">
                <i class="fa fa-gear"></i> Change
            </a>
        </div>

        <style>
            .att-table thead th {
                position: sticky;
                top: 0;
                z-index: 10;
            }
        </style>
        <div class="table-responsive" style="max-height:calc(100vh - 220px);overflow-y:auto;">
            <table class="table table-bordered table-sm align-middle att-table" style="font-size:.8rem">
                <thead class="table-dark">
                    <tr>
                        <th style="min-width:160px">Employee</th>
                        @for($d = 1; $d <= $days; $d++)
                            @php
                                $dayDate    = \Carbon\Carbon::create($year, $month, $d);
                                $isNonWrk   = isset($nonWorkingDays[$d]);
                                $nwLabel    = $nonWorkingDays[$d] ?? null;
                                $isWorkHol  = isset($workingHolidayDays[$d]);
                                $whLabel    = $workingHolidayDays[$d] ?? null;
                                $isCompOff  = isset($compOffDays[$d]);
                                $coLabel    = $compOffDays[$d] ?? null;
                            @endphp
                            @php
                                $thStyle = 'min-width:38px;';
                                $thClass = 'text-center ';
                                if ($isNonWrk)      { $thClass .= 'table-secondary'; }
                                elseif ($isWorkHol) { $thClass .= 'table-warning'; }
                                elseif ($isCompOff) { $thStyle .= 'background:#ede9fe;'; }
                                $thTitle = $nwLabel ?? ($whLabel ? 'Working Holiday: '.$whLabel : ($coLabel ? 'Comp Off Day: '.$coLabel : ''));
                            @endphp
                            <th class="{{ $thClass }}" style="{{ $thStyle }}"
                                title="{{ $thTitle }}">
                                {{ $d }}<br>
                                <small style="font-size:.6rem">{{ $dayDate->format('D') }}</small>
                                @if($isWorkHol)
                                    <br><i class="fa fa-briefcase" style="font-size:.55rem;color:#b45309"></i>
                                @elseif($isCompOff && !$isNonWrk)
                                    <br><i class="fa fa-calendar-plus" style="font-size:.55rem;color:#6f42c1"></i>
                                @endif
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
                        <th class="text-center text-white" style="min-width:72px;background:rgba(13,110,253,0.45)" title="Total logged working hours for the month">Man Hrs</th>
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
                                $isNonWrk    = isset($nonWorkingDays[$d]);
                                $isCompOffDay = isset($compOffDays[$d]);
                                $rec         = $empRecords[$d] ?? null;
                                $status      = $rec?->status ?? null;

                                // Future day with no record → leave blank, not absent
                                $cellDate  = \Carbon\Carbon::create($year, $month, $d);
                                $isFuture  = $cellDate->isAfter(\Carbon\Carbon::today());

                                // No checkout → show orange A (checked in but never clocked out).
                                // Applies to both 'present' and 'late' — if there's no checkout the
                                // record is incomplete regardless of whether they arrived on time or late.
                                // 'late' WITH a checkout still shows L normally.
                                $noCheckout = in_array($status, ['present', 'late']) && empty($rec?->check_out);
                                if ($noCheckout) {
                                    $status = 'absent';
                                }

                                $dailyLateMins = $empLateMap[$d] ?? 0;

                                $isOnDuty = (!$isNonWrk && !$isCompOffDay && $status === 'on_duty');

                                if (!$isNonWrk && !$isCompOffDay && !$isOnDuty) {
                                    // No record on a future date → neutral dash, not absent
                                    if ($status === null && $isFuture) {
                                        $color = 'light';
                                        $abbr  = '—';
                                    } else {
                                        $color = $status ? (\App\Models\Attendance::$statusColors[$status] ?? 'danger') : 'danger';
                                        $abbr  = match($status) {
                                            'present'  => 'P',
                                            'absent'   => 'A',
                                            'half_day' => 'H',
                                            'late'     => 'L',
                                            'on_leave' => 'OL',
                                            'comp_off' => 'CO',
                                            default    => 'A',  // no record on past/today → treat as absent
                                        };
                                    }
                                    // Both red A (absent/no-record) and orange A (no-checkout) count as absent
                                    if ($noCheckout && !$isFuture) $absentCount++;
                                    elseif (($status === 'present' || $status === 'late') && !$noCheckout) $presentCount++;
                                    elseif (($status === 'absent' || $status === null) && !$isFuture) $absentCount++;
                                    elseif ($status === 'half_day') $halfCount++;
                                    elseif ($status === 'on_leave') $leaveCount++;
                                }
                                if ($isOnDuty) $presentCount++; // OD = present
                            @endphp
                            @php
                                $cellIsWeekend   = $isNonWrk && in_array($nonWorkingDays[$d] ?? '', ['Sunday', '1st Saturday', '3rd Saturday']);
                                $cellIsPublicHol = $isNonWrk && !$cellIsWeekend;
                                // Weekends → gray label; public holidays → red label
                                $nwLabelColor    = $cellIsWeekend ? '#6c757d' : '#c0392b';
                            @endphp
                            @if($isNonWrk)
                                {{-- All non-working days (weekends + public holidays): merged cell spans ALL rows --}}
                                @if($loop->first)
                                <td class="text-center table-secondary"
                                    rowspan="{{ count($employees) }}"
                                    style="vertical-align:middle;padding:4px 2px;">
                                    <span style="writing-mode:vertical-rl;transform:rotate(180deg);font-size:.6rem;color:{{ $nwLabelColor }};font-weight:700;letter-spacing:4px;display:inline-block;line-height:1;word-spacing:2em;"
                                          title="{{ $nonWorkingDays[$d] }}">{{ $nonWorkingDays[$d] }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $nonWorkingDays[$d] }}</span>
                                </td>
                                @endif
                                {{-- All other employee rows: skip — covered by rowspan above --}}
                            @else
                            <td class="text-center"
                                style="{{ $isCompOffDay && !$isNonWrk ? 'background:#ede9fe;' : ($isOnDuty ? 'background:#fdf3e3;' : '') }}">
                                @if($isCompOffDay)
                                    <span class="badge" style="background:#6f42c1;font-size:.68rem"
                                          title="Comp Off — {{ $compOffDays[$d] }}">CO</span>
                                @elseif($isOnDuty)
                                    <span class="badge" style="background:#c8a96e;font-size:.68rem"
                                          title="On Duty{{ $rec->remarks ? ': '.$rec->remarks : '' }}">OD</span>
                                @else
                                    @php
                                        $titleAttr = match(true) {
                                            $noCheckout                              => 'No Checkout — checked in but no checkout recorded',
                                            $status === 'late' && $dailyLateMins > 0 => 'Late by ' . $fmtMins($dailyLateMins),
                                            $status === 'on_leave'                   => 'On Leave' . (isset($leaveTypeByEmpDay[$emp->id][$d]) ? ' — ' . $leaveTypeByEmpDay[$emp->id][$d] : ''),
                                            $status === 'half_day'                   => 'Half Day',
                                            $status === 'comp_off'                   => 'Comp Off',
                                            $status === 'present'                    => 'Present',
                                            $status === 'absent'                     => isset($absentLeaveTypeByEmpDay[$emp->id][$d]) ? 'Absent — ' . $absentLeaveTypeByEmpDay[$emp->id][$d] : 'Absent',
                                            $status === null && !$isFuture           => 'Absent',
                                            default                                  => '',
                                        };
                                    @endphp
                                    @if($noCheckout)
                                        <span class="badge" style="background:#e67e22;font-size:.7rem;"
                                              title="{{ $titleAttr }}">A</span>
                                    @else
                                        <span class="badge bg-{{ $color }} text-{{ in_array($color, ['warning','light']) ? 'dark' : 'white' }}"
                                              style="font-size:.7rem{{ $color === 'light' ? ';border:1px solid #dee2e6' : '' }}"
                                              @if($titleAttr) title="{{ $titleAttr }}" @endif>{{ $abbr }}</span>
                                    @endif
                                @endif
                            </td>
                            @endif
                        @endfor

                        {{-- Attendance count columns --}}
                        <td class="text-center fw-bold text-success">{{ $presentCount }}</td>
                        <td class="text-center fw-bold text-danger">{{ $stat['absent_days'] ?? $absentCount }}</td>
                        <td class="text-center fw-bold text-warning">{{ $stat['half_days'] ?? $halfCount }}</td>
                        <td class="text-center fw-bold text-secondary" title="Approved paid leaves + comp-offs (from payroll)">{{ $stat['paid_leave_days'] ?? $leaveCount }}</td>

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

                        {{-- Man Hours (total logged working hours for the month) --}}
                        @php
                            $totalWH = (float)($stat['total_working_hours'] ?? 0);
                            if ($totalWH > 0) {
                                $whH = (int) floor($totalWH);
                                $whM = (int) round(($totalWH - $whH) * 60);
                                $whDisplay = $whH . 'h' . ($whM > 0 ? ' ' . $whM . 'm' : '');
                            } else {
                                $whDisplay = '—';
                            }
                        @endphp
                        <td class="text-center {{ $totalWH > 0 ? 'text-primary fw-semibold' : 'text-muted' }}"
                            style="font-size:.78rem"
                            title="Total working hours logged this month ({{ $totalWH }}h raw)">
                            {{ $whDisplay }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="small text-muted mt-2">
            <strong>Legend:</strong>
            P = Present &nbsp;|&nbsp; A = Absent &nbsp;|&nbsp; <span class="badge" style="background:#e67e22;font-size:.7rem">A</span> = No Checkout (marked absent, orange) &nbsp;|&nbsp; H = Half Day &nbsp;|&nbsp; L = Late &nbsp;|&nbsp; OL = On Leave
            &nbsp;|&nbsp; <span class="badge" style="background:#6f42c1;font-size:.7rem">CO</span> = Comp Off
            &nbsp;|&nbsp; <span class="badge" style="background:#0891b2;font-size:.7rem">OD</span> = On Duty (present, no deduction)
            &nbsp;|&nbsp; <span class="badge bg-secondary" style="font-size:.7rem">H</span> = Weekend (Sunday / Saturday)
            &nbsp;|&nbsp; <span style="writing-mode:vertical-rl;transform:rotate(180deg);font-size:.6rem;color:#c0392b;font-weight:700;letter-spacing:2px;display:inline-block;vertical-align:middle;">Diwali</span> = Public Holiday (name shown rotated)
            &nbsp;|&nbsp; <strong>Late Used</strong> = Total monthly late time
            &nbsp;|&nbsp; <strong>Remaining</strong> = Grace left (resets each month)
            &nbsp;|&nbsp; <strong>Exceeded</strong> = Minutes beyond 90-min grace
            &nbsp;|&nbsp; <strong>Deducted</strong> = Penalty charged (2× total late when grace exceeded)
            &nbsp;|&nbsp; <strong>Man Hrs</strong> = Total working hours logged (check-in → check-out) for the month
        </div>
    </div>
</div>
@endsection
