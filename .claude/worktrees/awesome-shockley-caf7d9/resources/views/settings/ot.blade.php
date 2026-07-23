@extends('layouts.app')
@section('title', 'OT Settings')
@section('breadcrumb')
<li class="breadcrumb-item active">OT Settings</li>
@endsection

@section('content')
<div class="card page-card" style="max-width:680px">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-semibold"><i class="fas fa-clock me-2 text-primary"></i>Overtime (OT) Settings</h5>
    </div>
    <div class="card-body">

        
        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div class="alert alert-info small mb-4 py-2">
            <i class="fa fa-info-circle me-1"></i>
            <strong>How late &amp; OT are calculated:</strong><br>
            • <strong>Late:</strong> check-in after Office Start + Daily Grace → minutes counted from Office Start. Monthly limit triggers 2× deduction. See <a href="{{ route('settings.grace.show') }}">Grace Settings</a>.<br>
            • <strong>Half Day:</strong> check-in &gt; Office Start + 2 hours → auto half-day status.<br>
            • <strong>OT:</strong> checkout ≥ Trigger Time → <code>OT hrs = (checkout − baseline) / 60</code>. OT pay = Basic ÷ days ÷ 8 × 2 × hrs.
        </div>

        <form action="{{ route('settings.ot.update') }}" method="POST">
            @csrf @method('PUT')

            {{-- ── Attendance Timing ── --}}
            <h6 class="fw-semibold text-muted mb-3 mt-1"><i class="fa fa-clock me-1"></i>Attendance Timing</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">
                        Office Start Time <span class="text-danger">*</span>
                        <i class="fa fa-circle-question ms-1 text-muted" title="Check-in after this time is considered late"></i>
                    </label>
                    <input type="time" name="office_start_time"
                           class="form-control @error('office_start_time') is-invalid @enderror"
                           value="{{ old('office_start_time', $officeStartTime) }}" required>
                    @error('office_start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">
                        Current: <strong>{{ $officeStartTime }}</strong>
                        ({{ \Carbon\Carbon::createFromFormat('H:i', $officeStartTime)->format('h:i A') }})<br>
                        Late if check-in &gt; this. Half Day if &gt; this + 2 hrs.
                    </div>
                </div>
            </div>

            {{-- ── OT Timing ── --}}
            <h6 class="fw-semibold text-muted mb-3"><i class="fa fa-moon me-1"></i>Overtime (OT) Timing</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">
                        OT Trigger Time <span class="text-danger">*</span>
                        <i class="fa fa-circle-question ms-1 text-muted" title="Minimum checkout time for OT eligibility"></i>
                    </label>
                    <input type="time" name="ot_trigger_time"
                           class="form-control @error('ot_trigger_time') is-invalid @enderror"
                           value="{{ old('ot_trigger_time', $triggerTime) }}" required>
                    @error('ot_trigger_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">
                        Current: <strong>{{ $triggerTime }}</strong>
                        ({{ \Carbon\Carbon::createFromFormat('H:i', $triggerTime)->format('h:i A') }})<br>
                        Must clock out at or after this time to qualify for OT.
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">
                        OT Baseline Time <span class="text-danger">*</span>
                        <i class="fa fa-circle-question ms-1 text-muted" title="OT hours counted from this time onwards"></i>
                    </label>
                    <input type="time" name="ot_baseline_time"
                           class="form-control @error('ot_baseline_time') is-invalid @enderror"
                           value="{{ old('ot_baseline_time', $baselineTime) }}" required>
                    @error('ot_baseline_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">
                        Current: <strong>{{ $baselineTime }}</strong>
                        ({{ \Carbon\Carbon::createFromFormat('H:i', $baselineTime)->format('h:i A') }})<br>
                        OT hours accumulated from this time onwards.
                    </div>
                </div>
            </div>

            {{-- Live preview --}}
            <div class="bg-light rounded p-3 mt-4 small" id="otPreview">
                <strong><i class="fa fa-calculator me-1"></i>Live Preview</strong><br>
                <span id="previewText"></span>
            </div>

            <hr class="my-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save me-1"></i>Save OT Settings
            </button>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
        </form>

        <hr class="my-4">
        <div class="small text-muted">
            <strong>Defaults:</strong> Office Start = 09:00 · OT Trigger = 20:30 · OT Baseline = 18:15<br>
            <strong>Changes take effect immediately</strong> — new attendance records will use the updated times.
            Existing saved records are <em>not</em> retroactively updated.
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    function updatePreview() {
        var trigger  = $('input[name="ot_trigger_time"]').val();
        var baseline = $('input[name="ot_baseline_time"]').val();
        if (!trigger || !baseline) { $('#previewText').text('Enter both times to see preview.'); return; }

        var tParts = trigger.split(':'),  tMins = parseInt(tParts[0]) * 60 + parseInt(tParts[1]);
        var bParts = baseline.split(':'), bMins = parseInt(bParts[0]) * 60 + parseInt(bParts[1]);

        if (tMins <= bMins) {
            $('#previewText').html('<span class="text-danger">&#9888; Trigger time must be later than Baseline time.</span>');
            return;
        }

        function fmt(m) { return (Math.floor(m/60) > 0 ? Math.floor(m/60) + 'h ' : '') + (m % 60) + 'm'; }
        var exampleOut  = tMins;          // checkout exactly at trigger time
        var exampleOT   = (exampleOut - bMins) / 60;
        var exampleOut2 = tMins + 45;     // checkout 45 min after trigger
        var exampleOT2  = (exampleOut2 - bMins) / 60;

        $('#previewText').html(
            'If employee checks out at <strong>' + trigger + '</strong> &rarr; <strong>' + exampleOT.toFixed(2) + ' OT hrs</strong> | ' +
            'At <strong>' + Math.floor(exampleOut2/60) + ':' + String(exampleOut2%60).padStart(2,'0') + '</strong> &rarr; <strong>' + exampleOT2.toFixed(2) + ' OT hrs</strong>'
        );
    }

    $('input[name="ot_trigger_time"], input[name="ot_baseline_time"]').on('change input', updatePreview);
    updatePreview();
});
</script>
@endpush
