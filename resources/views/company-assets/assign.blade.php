@extends('layouts.app')
@section('title','Assign Asset')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('assets.index') }}" class="text-decoration-none">Assets</a></li>
<li class="breadcrumb-item active">Assign</li>
@endsection
@section('content')
<div class="card page-card" style="max-width:600px">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Assign Asset — {{ $asset->asset_name }}</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-4">
            <strong>{{ $asset->asset_name }}</strong> — {{ $asset->type_label }}
            @if($asset->serial_number) &bull; S/N: {{ $asset->serial_number }} @endif
        </div>
        <form action="{{ route('assets.assign.store', $asset) }}" method="POST">
            @csrf
            @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select">
                        <option value="">Select Employee</option>
                        @foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->employee_code }})</option>@endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                    <input type="date" name="issue_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Condition on Issue</label>
                    <input type="text" name="condition_on_issue" class="form-control" placeholder="e.g. Good, New">
                </div>
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="fa fa-user-plus me-1"></i>Assign Asset</button>
                <a href="{{ route('assets.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
