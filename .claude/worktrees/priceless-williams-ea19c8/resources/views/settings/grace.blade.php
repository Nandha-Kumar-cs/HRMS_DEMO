@extends('layouts.app')
@section('title', 'Grace & Late Permission Settings')
@section('breadcrumb')
<li class="breadcrumb-item active">Grace Settings</li>
@endsection

@section('content')
<div class="card page-card" style="max-width:680px">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-semibold">
            <i class="fa fa-hourglass-half me-2 text-warning"></i>Grace & Late Permission Settings
        </h5>
    </div>
    <div class="card-body">

        @if(session('success'))
        <div class="alert alert-success py-2">
            <i class="fa fa-circle-check me-1"></i>{{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        {{-- How it works info box --}}
        <div class="alert alert-info small mb-4 py-2">
            <i class="fa fa-info-circle me-1"></i>
            <strong>How grace time works:</strong><br>
            • <strong>Daily Grace:</strong> Employee has this many minutes after office start before being marked late.
              E.g. Office = <strong>{{ \Carbon\Carbon::createFromFormat('H:i', $officeStartTime)->format('h:i A') }}</strong>,
              Grace = <strong>{{ $dailyGraceMinutes }} min</strong> →
              late threshold is <strong id="lateThresholdInfo">{{ \Carbon\Carbon::createFromFormat('H:i', $officeStartTime)->addMinutes($dailyGraceMinutes)->format('h:i A') }}</strong>.<br>
            • <strong>Late minutes</strong> are always counted from office start (not from the grace cutoff).<br>
              E.g. Check-in at {{ \Carbon\Carbon::createFromFormat('H:i', $officeStartTime)->addMinutes($dailyGraceMinutes + 1)->format('h:i A') }}
              → <strong>{{ $dailyGraceMinutes + 1 }} min late</strong>.<br>
            • <strong>Monthly Permission:</strong> Total late minutes ≤ this limit → no salary deduction.
              Exceeding triggers a <strong>2× penalty</strong> on the full late amount.
        </div>

        <form action="{{ route('settings.grace.update') }}" method="POST">
            @csrf @method('PUT')

            {{-- ── Daily Grace ── --}}
            <h6 class="fw-semibold text-muted mb-3 mt-1">
                <i class="fa fa-clock me-1"></i>Daily Late Grace
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-5">
                    <label class="form-label">
                        Daily Grace (minutes) <span class="text-danger">*</span>
                        <i class="fa fa-circle-question ms-1 text-muted"
                           title="Minutes after office start that are still not counted as late"></i>
                    </label>
                    <div class="input-group">
                        <input type="number" name="daily_grace_minutes"
                               class="form-control @error('daily_grace_minutes') is-invalid @enderror"
                               value="{{ old('daily_grace_minutes', $dailyGraceMinutes) }}"
                               min="0" max="120" required>
                        <span class="input-group-text text-muted">min</span>
                    </div>
                    @error('daily_grace_minutes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    <div class="form-text">
                        Office starts at <strong>{{ \Carbon\Carbon::createFromFormat('H:i', $officeStartTime)->format('h:i A') }}</strong>.
                        Late threshold: <strong id="lateThreshold">
                            {{ \Carbon\Carbon::createFromFormat('H:i', $officeStartTime)->addMinutes($dailyGraceMinutes)->format('h:i A') }}
                        </strong>
                        <span class="text-muted">(updates live)</span>
                    </div>
                </div>
            </div>

            {{-- ── Monthly Grace ── --}}
            <h6 class="fw-semibold text-muted mb-3">
                <i class="fa fa-calendar-check me-1"></i>Monthly Late Permission
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-5">
                    <label class="form-label">
                        Monthly Permission (minutes) <span class="text-danger">*</span>
                        <i class="fa fa-circle-question ms-1 text-muted"
                           title="Total late minutes allowed per month before penalty kicks in"></i>
                    </label>
                    <div class="input-group">
                        <input type="number" name="monthly_grace_minutes"
                               class="form-control @error('monthly_grace_minutes') is-invalid @enderror"
                               value="{{ old('monthly_grace_minutes', $monthlyGraceMinutes) }}"
                               min="0" max="480" required>
                        <span class="input-group-text text-muted">min</span>
                    </div>
                    @error('monthly_grace_minutes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    <div class="form-text" id="monthlyHint">
                        Currently <strong>{{ $monthlyGraceMinutes }} min</strong>
                        = <strong>{{ intdiv($monthlyGraceMinutes, 60) }}h {{ $monthlyGraceMinutes % 60 }}m</strong>.
                        Exceeding triggers 2× deduction on total late.
                    </div>
                </div>
            </div>

            {{-- Live preview --}}
            <div class="bg-light rounded p-3 mt-2 mb-4 small" id="gracePreview">
                <strong><i class="fa fa-calculator me-1"></i>Live Preview</strong><br>
                <span id="previewText"></span>
            </div>

            <hr class="my-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save me-1"></i>Save Grace Settings
            </button>
            <a href="{{ route('settings.ot.show') }}" class="btn btn-outline-secondary ms-2">OT Settings</a>
        </form>

        <hr class="my-4">
        <div class="small text-muted">
            <strong>Defaults:</strong> Daily Grace = 15 min · Monthly Permission = 90 min (1h 30m)<br>
            <strong>Changes take effect immediately</strong> for new attendance records.
            Existing saved records are <em>not</em> retroactively updated.
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var officeStartMins = {{ \Carbon\Carbon::createFromFormat('H:i', $officeStartTime)->hour * 60 + \Carbon\Carbon::createFromFormat('H:i', $officeStartTime)->minute }};

    function minsToTime(m) {
        var h = Math.floor(m / 60), mn = m % 60;
        var suffix = h >= 12 ? 'PM' : 'AM';
        var h12 = h % 12 || 12;
        return h12 + ':' + String(mn).padStart(2, '0') + ' ' + suffix;
    }
    function fmtMins(m) {
        return (Math.floor(m/60) > 0 ? Math.floor(m/60) + 'h ' : '') + (m % 60) + 'm';
    }

    function updatePreview() {
        var daily   = parseInt($('input[name="daily_grace_minutes"]').val())   || 0;
        var monthly = parseInt($('input[name="monthly_grace_minutes"]').val()) || 0;

        var thresholdMins = officeStartMins + daily;
        $('#lateThreshold').text(minsToTime(thresholdMins));

        // Example: employee comes 1 min after threshold
        var exampleLate  = daily + 1;                       // mins late (from office start)
        var exampleIn    = minsToTime(thresholdMins + 1);   // check-in time

        var monthlyHint = monthly + ' min = ' + fmtMins(monthly) + '. Exceeding triggers 2× deduction.';
        $('#monthlyHint').html(monthlyHint);

        var lines = [];
        lines.push('Office start: <strong>' + minsToTime(officeStartMins) + '</strong> | Daily grace: <strong>' + daily + ' min</strong> | Late threshold: <strong>' + minsToTime(thresholdMins) + '</strong>');
        lines.push('Example: Check-in at <strong>' + exampleIn + '</strong> → <strong>' + exampleLate + ' min late</strong> (from office start)');
        if (monthly > 0) {
            lines.push('Monthly limit: <strong>' + fmtMins(monthly) + '</strong>. If total late = ' + fmtMins(monthly + 10) + ' → <strong>' + fmtMins((monthly + 10) * 2) + ' deducted (2×)</strong>');
        }
        $('#previewText').html(lines.join('<br>'));
    }

    $('input[name="daily_grace_minutes"], input[name="monthly_grace_minutes"]').on('input', updatePreview);
    updatePreview();
});
</script>
@endpush
